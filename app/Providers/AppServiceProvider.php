<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;
use Masbug\Flysystem\GoogleDriveAdapter;
use Illuminate\Filesystem\FilesystemAdapter;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Storage::extend('google', function ($app, $config) {
            $client = new \Google\Client();
            $client->setClientId($config['clientId'] ?? '');
            $client->setClientSecret($config['clientSecret'] ?? '');
            $client->refreshToken($config['refreshToken'] ?? '');

            $service = new \Google\Service\Drive($client);
            $adapter = new GoogleDriveAdapter($service, $config['folderId'] ?? '/');

            $driver = new Filesystem($adapter, $config);

            return new FilesystemAdapter(
                $driver,
                $adapter,
                $config
            );
        });
    }
}
