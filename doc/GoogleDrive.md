# Conectar Google Drive a Laravel

Este es un tutorial de como implementar Google Drive como disco en Laravel.

### Instalar Flysystem adapter for Google Drive

[Repositorio original](https://github.com/masbug/flysystem-google-drive-ext)

### Obtener credenciales de Google


[Articulo tutsmake.com](https://www.tutsmake.com/laravel-10-backup-store-on-google-drive/)


### Establecer el disco para google y configurar el archivo .env

Ir a `config/filesystem.php` y registrar el disco

```php
'google' => [
            'driver' => 'google',
            'clientId' => env('GOOGLE_DRIVE_CLIENT_ID'),
            'clientSecret' => env('GOOGLE_DRIVE_CLIENT_SECRET'),
            'refreshToken' => env('GOOGLE_DRIVE_REFRESH_TOKEN'),
            'folder' => env('GOOGLE_DRIVE_FOLDER'),
            #'accessToken' => env('GOOGLE_DRIVE_ACCESS_TOKEN'),
        ],
```

Ir a `.env` en la raiz del proyecto

```php
FILESYSTEM_CLOUD=google
GOOGLE_DRIVE_CLIENT_ID=xxx
GOOGLE_DRIVE_CLIENT_SECRET=xxx
GOOGLE_DRIVE_REFRESH_TOKEN=xxx
GOOGLE_DRIVE_FOLDER=
#GOOGLE_DRIVE_ACCESS_TOKEN=xxx
```

<span style="color:#FF5733;">Nota.</span>

> El access_token está comentado debido a que el planteo del ejemplo es para producción.
Para desarrollo, se puede dejar y quemar el access_token en el archivo .env cada vez que este venza.


### Guardar access_token en base de datos
`php artisan make:migration "create table google drive token"`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('google_drive_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('access_token');
            $table->string('refresh_token');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_drive_tokens');
    }
};
```
`php artisan migrate"`


### Crear un Provider

`php artisan make:provider GoogleDriveServiceProvider`

```php
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
```

### Registramos en provides en `config/app.php`
```php
App\Providers\GoogleDriveServiceProvider::class,
```

### Utilización
```php
Storage::disk('google')->makeDirectory('Nueva carpeta');
```
