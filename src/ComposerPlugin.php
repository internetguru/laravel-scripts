<?php

namespace InternetGuru\LaravelScripts;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

class ComposerPlugin implements PluginInterface, EventSubscriberInterface
{
    // Playwright's Chromium paths follow its version, so they are linked again before each run (docker-ansible image)
    private const LINK_CHROMIUM = 'docker compose exec laravel sh -c "command -v playwright-chromium-link >/dev/null && playwright-chromium-link || true"';

    public function activate(Composer $composer, IOInterface $io): void
    {
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
                'rm -f database/testing.sqlite* && echo > database/testing.sqlite'
                    . ' && docker compose exec -e APP_ENV=testing -e DB_CONNECTION=sqlite -e DB_DATABASE=/app/database/testing.sqlite laravel php artisan migrate --force --quiet'
                    . ' && p="" && b="" && if [ -d vendor/brianium/paratest ]; then n=$(nproc) && p="--parallel --processes=$n"'
                    // More than a few Chromiums at once stop answering and hang their workers
                    . ' && b="--parallel --processes=$(( n < 4 ? n : 4 ))"'
                    . ' && for i in $(seq 1 $n); do cp database/testing.sqlite database/testing.sqlite_test_$i; done; fi'
                    . ' && ' . self::LINK_CHROMIUM
                    // Browser tests next to busy CPU-bound tests starve their Chromium and time out, so they run after
                    . ' && docker compose exec laravel php artisan test $p --exclude-testsuite=Browser @additional_args'
                    . ' && if [ -d tests/Browser ]; then docker compose exec laravel php artisan test $b --testsuite=Browser @additional_args; fi'
            ],
            'test:browser' => [
                'Composer\\Config::disableProcessTimeout',
                'rm -f database/testing.sqlite* && echo > database/testing.sqlite'
                    . ' && docker compose exec -e APP_ENV=testing -e DB_CONNECTION=sqlite -e DB_DATABASE=/app/database/testing.sqlite laravel php artisan migrate --force --quiet'
                    . ' && ' . self::LINK_CHROMIUM
                    . ' && docker compose exec laravel php artisan test --testsuite=Browser'
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
        return [];
    }
}
