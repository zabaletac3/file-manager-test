<?php

namespace App\Providers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;

class GoogleDriveServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        try {
            Storage::extend('google', function($app, $config) {
                $options = [];
                $client = new \Google\Client();
                $client->setClientId($config['clientId']);
                $client->setClientSecret($config['clientSecret']);
                $client->refreshToken($config['refreshToken']);
                $client->setAccessToken($config['accessToken']);

                // Verifica si el token de acceso ha expirado y, si es así, renueva automáticamente
//                if ($client->isAccessTokenExpired()) {
//                    // Actualiza el token de acceso utilizando el token de actualización
//                    $client->fetchAccessTokenWithRefreshToken();
//                    // Actualiza el token de acceso en la configuración
//                    $config['accessToken'] = $client->getAccessToken();
//                } else {
//                    $client->setAccessToken($config['accessToken']);
//                }

                $service = new \Google\Service\Drive($client);
                $adapter = new \Masbug\Flysystem\GoogleDriveAdapter($service, $config['folder'] ?? '/', $options);
                $driver = new \League\Flysystem\Filesystem($adapter);

                return new \Illuminate\Filesystem\FilesystemAdapter($driver, $adapter);
            });
       } catch(\Exception $e) {
            $e->getMessage();
       }
    }
}
