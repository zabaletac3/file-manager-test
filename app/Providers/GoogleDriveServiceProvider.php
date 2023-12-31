<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;

class GoogleDriveServiceProvider extends ServiceProvider
{

    public function register(): void
    {
        // No es necesario realizar registros adicionales aquí.
    }

    public function boot(): void
    {
        try {
            Storage::extend('google', function($app, $config) {

                // Creamos una instancia del cliente de Google y configuramos las credenciales establecidas en .env
                $options = [];
                $client = new \Google\Client();
                $client->setClientId($config['clientId']);
                $client->setClientSecret($config['clientSecret']);
                $client->refreshToken($config['refreshToken']);
                #$client->setAccessToken($config['accessToken']);

                // Intenta obtener el token de acceso desde la base de datos
                $tokenRecord = DB::table('google_drive_tokens')->find(1); // Suponiendo que el token se almacena en la fila con ID 1

                // Almacenamos el access token
                $accessToken =  $tokenRecord->access_token;

                // Lo seteamos en la instancia del cliente
                $client->setAccessToken($accessToken);

                // Configura el token de actualización (refresh token)
                $client->refreshToken($config['refreshToken']);

                // Si el access token ha expirado
                if($client->isAccessTokenExpired()){

                    // Actualiza el token de acceso utilizando el token de actualización
                    $client->fetchAccessTokenWithRefreshToken();

                    $accessToken = $client->getAccessToken();

                    // Actualizamos el token de acceso en la base de datos
                    $tokenRecord->access_token = $accessToken;

                    $tokenRecord->save();

                } else {

                    // Si no existe en la base de datos, lo creamos
                    DB::table('google_drive_tokens')->updateOrInsert(['access_token'=>$accessToken]);

                }

                // Configuramos el servicio de Google Drive
                $service = new \Google\Service\Drive($client);
                $adapter = new \Masbug\Flysystem\GoogleDriveAdapter($service, $config['folder'] ?? '/', $options);
                $driver = new \League\Flysystem\Filesystem($adapter);

                return new \Illuminate\Filesystem\FilesystemAdapter($driver, $adapter);

            });

       } catch(\Exception $e) {

            Log::error('Error en el servicio de Google Drive: ' . $e->getMessage());
            //$e->getMessage();

       }
    }
}
