<?php

namespace InternetGuru\LaravelScripts;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\ScriptEvents;
use Composer\Util\ProcessExecutor;

class ComposerPlugin implements PluginInterface, EventSubscriberInterface
{
    // Playwright's Chromium paths follow its version, so they are linked again before each run (docker-ansible image)
    private const LINK_CHROMIUM = 'docker compose exec laravel sh -c "command -v playwright-chromium-link >/dev/null && playwright-chromium-link || true"';

    private const PREPARE_DATABASE = 'rm -f database/testing.sqlite* && echo > database/testing.sqlite'
        . ' && docker compose exec -e APP_ENV=testing -e DB_CONNECTION=sqlite -e DB_DATABASE=/app/database/testing.sqlite laravel php artisan migrate --force --quiet';

    // With Paratest, one PHP process per CPU, and one database copy each so the migration runs once
    private const PREPARE_PARALLEL = 'p="" && b="" && if [ -d vendor/brianium/paratest ]; then n=$(nproc) && p="--parallel --processes=$n"'
        // More than a few Chromiums at once stop answering and hang their workers
        . ' && b="--parallel --processes=$(( n < 4 ? n : 4 ))"'
        . ' && for i in $(seq 1 $n); do cp database/testing.sqlite database/testing.sqlite_test_$i; done; fi';

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
                'rm -f database/database.sqlite* && echo > database/database.sqlite && docker compose exec laravel php artisan migrate:fresh --seed'
            ],
            'install' => [
                'docker run --rm -v $(pwd):/app composer install --no-interaction --ignore-platform-reqs --working-dir=/app'
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
        ];

        $package = $composer->getPackage();
        $existingScripts = $package->getScripts();
        $package->setScripts(array_merge($existingScripts, $scripts));
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
        return [ScriptEvents::POST_UPDATE_CMD => 'refreshBoost'];
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
