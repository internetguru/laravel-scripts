<?php

namespace InternetGuru\LaravelScripts;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\Capability\CommandProvider;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Composer\Util\ProcessExecutor;

class ComposerPlugin implements Capable, EventSubscriberInterface, PluginInterface
{
    /** What `composer list` and `composer run list:scripts` show next to each script. */
    public const DESCRIPTIONS = [
        'bash' => 'Open a shell in the laravel container',
        'migrate:fresh' => 'Recreate the development database and seed it',
        'install:docker' => 'Run composer install in a temporary Docker container, without local PHP',
        'artisan' => 'Run an Artisan command in the laravel container',
        'dev' => 'Install npm packages and start the Vite dev server',
        'test:php' => 'Run the PHP tests (Unit and Feature) in parallel',
        'test:browser' => 'Run the browser tests one at a time',
        'test:all' => 'Run the PHP tests, then the browser tests',
        'list:scripts' => 'List these scripts with what they do',
        'analyse' => 'Run PHPStan (Larastan) on the project, in the laravel container when there is one',
        'analyse:init' => 'Write a phpstan.neon with the shared Internet Guru rules and an empty baseline',
    ];

    public const SHARED_NEON = 'vendor/internetguru/laravel-scripts/phpstan/ig.neon';

    public const SHARED_WORKFLOW = 'internetguru/laravel-scripts/.github/workflows/phpstan.yml';

    // Playwright's Chromium paths follow its version, so they are linked again before each run (docker-ansible image)
    private const LINK_CHROMIUM = 'docker compose exec laravel sh -c "command -v playwright-chromium-link >/dev/null && playwright-chromium-link || true"';

    private const PREPARE_DATABASE = 'rm -f database/testing.sqlite* && echo > database/testing.sqlite'
        . ' && docker compose exec -e APP_ENV=testing -e DB_CONNECTION=sqlite -e DB_DATABASE=/app/database/testing.sqlite laravel php artisan migrate --force --quiet';

    // With Paratest, one PHP process per CPU, and one database copy each so the migration runs once
    private const PREPARE_PARALLEL = 'p="" && b="" && if [ -d vendor/brianium/paratest ]; then n=$(nproc) && p="--parallel --processes=$n"'
        // More than a few Chromiums at once stop answering and hang their workers
        . ' && b="--parallel --processes=$(( n < 4 ? n : 4 ))"'
        . ' && for i in $(seq 1 $n); do cp database/testing.sqlite database/testing.sqlite_test_$i; done; fi';

    /**
     * The queue and the scheduler hold the SQLite file open, and writing to it while it is recreated corrupts it, so
     * they stop for the while; Octane's workers held the deleted file and reload onto the new one (the container keeps
     * running, with the Vite dev server in it). Without a TTY (`-T`), so an agent or a CI job can run it too.
     */
    private const MIGRATE_FRESH = '(docker compose stop queue scheduler 2>/dev/null || true)'
        . ' && rm -f database/database.sqlite* && echo > database/database.sqlite'
        . ' && docker compose exec -T laravel php artisan migrate:fresh --seed --no-interaction'
        . '; status=$?; (docker compose exec -T laravel php artisan octane:reload >/dev/null 2>&1 || true)'
        . '; (docker compose start queue scheduler 2>/dev/null || true); exit $status';

    private const RUN_PHP = 'docker compose exec laravel php artisan test $p --exclude-testsuite=Browser @additional_args';

    private IOInterface $io;

    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->io = $io;

        $scripts = [
            'bash' => [
                'Composer\\Config::disableProcessTimeout',
                'docker compose exec laravel /bin/sh'
            ],
            'migrate:fresh' => [
                'Composer\\Config::disableProcessTimeout',
                self::MIGRATE_FRESH,
            ],
            'install:docker' => [
                'docker run --rm -u "$(id -u):$(id -g)" -v $(pwd):/app composer:2 install --no-interaction --ignore-platform-reqs --working-dir=/app'
            ],
            'artisan' => [
                'Composer\\Config::disableProcessTimeout',
                'docker compose exec laravel php artisan $*'
            ],
            'dev' => [
                'Composer\\Config::disableProcessTimeout',
                'docker compose exec laravel npm install && docker compose exec laravel npm run dev -- --host=0.0.0.0'
            ],
            'test:php' => [
                'Composer\\Config::disableProcessTimeout',
                self::PREPARE_DATABASE . ' && ' . self::PREPARE_PARALLEL . ' && ' . self::RUN_PHP,
            ],
            'test:browser' => [
                'Composer\\Config::disableProcessTimeout',
                self::PREPARE_DATABASE
                    . ' && ' . self::LINK_CHROMIUM
                    . ' && docker compose exec laravel php artisan test --testsuite=Browser'
            ],
            'test:all' => [
                'Composer\\Config::disableProcessTimeout',
                self::PREPARE_DATABASE . ' && ' . self::PREPARE_PARALLEL
                    . ' && ' . self::LINK_CHROMIUM
                    // Browser tests next to busy CPU-bound tests starve their Chromium and time out, so they run after
                    . ' && ' . self::RUN_PHP
                    . ' && if [ -d tests/Browser ]; then docker compose exec laravel php artisan test $b --testsuite=Browser @additional_args; fi'
            ],
            'list:scripts' => [self::class . '::listScripts'],
            'analyse' => [
                'Composer\\Config::disableProcessTimeout',
                // Packages have no Docker stack, so they run PHPStan with the local PHP
                (file_exists('docker-compose.yml') ? 'docker compose exec laravel ' : '')
                    . 'vendor/bin/phpstan analyse --memory-limit=1G @additional_args',
            ],
            'analyse:init' => [self::class . '::initPhpstan'],
        ];
        ksort($scripts);

        $package = $composer->getPackage();
        $existingScripts = $package->getScripts();
        $package->setScripts(array_merge($existingScripts, $scripts));
    }

    /** `composer run` shows no descriptions for plugin scripts, so this lists them. */
    public static function listScripts(Event $event): void
    {
        $descriptions = self::DESCRIPTIONS;
        ksort($descriptions);
        $width = max(array_map('strlen', array_keys($descriptions)));
        foreach ($descriptions as $script => $description) {
            $event->getIO()->write(sprintf('  <info>%s</info>  %s', str_pad($script, $width), $description));
        }
    }

    /** Writes only files that do not exist yet and prints the remaining steps. */
    public static function initPhpstan(Event $event): void
    {
        $io = $event->getIO();
        $path = is_dir('app') ? 'app/' : 'src/';

        if (self::phpstanConfig() === null) {
            file_put_contents('phpstan.neon', "includes:\n"
                . "    - vendor/larastan/larastan/extension.neon\n"
                . '    - ' . self::SHARED_NEON . "\n"
                . "    - phpstan-baseline.neon\n\n"
                . "parameters:\n"
                . "    paths:\n"
                . "        - {$path}\n");
            $io->write('<info>Wrote phpstan.neon</info>');
        }
        if (! file_exists('phpstan-baseline.neon')) {
            file_put_contents('phpstan-baseline.neon', "parameters:\n    ignoreErrors: []\n");
            $io->write('<info>Wrote an empty phpstan-baseline.neon</info>');
        }

        $steps = [];
        if (! isset($event->getComposer()->getPackage()->getDevRequires()['larastan/larastan'])) {
            $steps[] = 'composer require --dev larastan/larastan';
        }
        $steps[] = 'composer analyse -- --generate-baseline';
        if (! self::callsSharedWorkflow()) {
            $steps[] = 'add .github/workflows/phpstan.yml calling ' . self::SHARED_WORKFLOW . '@main (see the laravel-scripts README)';
        }
        $io->write('Next: ' . implode(', then ', $steps));
    }

    /** Warns about a missing PHPStan setup after `composer update`; never fails and never writes. */
    public function checkPhpstan(Event $event): void
    {
        try {
            $this->warnAboutPhpstan($event);
        } catch (\Throwable) {
            // A warning must never fail the update
        }
    }

    private function warnAboutPhpstan(Event $event): void
    {
        if (getenv('CI') || getenv('GITHUB_ACTIONS') || ! $event->isDevMode()) {
            return;
        }

        $config = self::phpstanConfig();
        $package = $event->getComposer()->getPackage();
        $hasLarastan = isset($package->getDevRequires()['larastan/larastan']) || isset($package->getRequires()['larastan/larastan']);
        if ($config === null || ! $hasLarastan) {
            $this->io->writeError('<warning>PHPStan is not set up; run `composer analyse:init`.</warning>');
        } elseif (! str_contains((string) file_get_contents($config), self::SHARED_NEON)) {
            $this->io->writeError("<warning>{$config} does not use the shared rules; add the include line `- " . self::SHARED_NEON . "` and remove your own `level` and `missingType.generics` entries.</warning>");
        }
        if (! self::callsSharedWorkflow()) {
            $this->io->writeError('<warning>No CI workflow calls the shared PHPStan workflow (' . self::SHARED_WORKFLOW . ').</warning>');
        }
    }

    private static function phpstanConfig(): ?string
    {
        foreach (['phpstan.neon', 'phpstan.neon.dist', 'phpstan.dist.neon'] as $file) {
            if (file_exists($file)) {
                return $file;
            }
        }

        return null;
    }

    private static function callsSharedWorkflow(): bool
    {
        $workflows = array_merge(glob('.github/workflows/*.yml') ?: [], glob('.github/workflows/*.yaml') ?: []);
        foreach ($workflows as $workflow) {
            if (str_contains((string) file_get_contents($workflow), self::SHARED_WORKFLOW)) {
                return true;
            }
        }

        return false;
    }

    public function getCapabilities(): array
    {
        return [CommandProvider::class => ScriptCommands::class];
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
        // Optional: cleanup or reverse actions
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
        // Optional: final cleanup
    }

    public static function getSubscribedEvents(): array
    {
        return [ScriptEvents::POST_UPDATE_CMD => [['refreshBoost', 0], ['checkPhpstan', 0]]];
    }

    public function refreshBoost(): void
    {
        if (! file_exists('boost.json') || ! file_exists('artisan')) {
            return;
        }

        $command = ProcessExecutor::escape(PHP_BINARY) . ' artisan boost:update --no-interaction';
        if ((new ProcessExecutor($this->io))->execute($command) !== 0) {
            $this->io->writeError('<warning>Boost guidelines were not refreshed; run `php artisan boost:update` by hand.</warning>');
        }
    }
}
