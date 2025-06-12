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
                'rm database/database.sqlite* && echo > database/database.sqlite && docker compose exec laravel php artisan migrate:fresh --seed'
            ],
            'install' => [
                'docker run --rm -v $(pwd):/app composer install --no-interaction --ignore-platform-reqs --working-dir=/app'
            ],
            'artisan' => [
                'docker compose exec laravel php artisan $*'
            ],
            'dev' => [
                'Composer\\Config::disableProcessTimeout',
                'docker compose exec laravel npm install && npm run dev'
            ],
            'test:php' => [
                'rm database/testing.sqlite* && echo > database/testing.sqlite && docker compose exec laravel php artisan test'
            ],
            'test:e2e' => [
                'npx playwright test $*'
            ],
            'test:e2e:report' => [
                'npx playwright show-report'
            ],
        ];

        $composer->getPackage()->getScripts()->exchangeArray($scripts);
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
