<?php

namespace InternetGuru\LaravelScripts;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

class ComposerPlugin implements PluginInterface, EventSubscriberInterface
{
    public function activate(Composer $composer, IOInterface $io): void
    {
        $scripts = [
            'bash' => [
                'docker compose exec laravel /bin/sh'
            ],
            'migrate:fresh' => [
                'rm -f database/database.sqlite* && echo > database/database.sqlite && docker compose exec laravel php artisan migrate:fresh --seed'
            ],
            'install' => [
                'docker run --rm -v $(pwd):/app composer install --no-interaction --ignore-platform-reqs --working-dir=/app'
            ],
            'artisan' => [
                'docker compose exec laravel php artisan $*'
            ],
            'dev' => [
                'Composer\\Config::disableProcessTimeout',
                'docker compose exec laravel npm install && npm run dev --host "$(hostname -I | awk \'{print $1}\')"'
            ],
            'test:php' => [
                'rm -f database/testing.sqlite* && echo > database/testing.sqlite && docker compose exec laravel php artisan test'
            ],
            'test:e2e' => [
                'npx playwright test'
            ],
            'test:e2e:ui' => [
                'npx playwright test --ui'
            ],
            'test:e2e:codegen' => [
                'npx playwright codegen'
            ],
            'test:e2e:report' => [
                'npx playwright show-report'
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
