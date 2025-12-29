<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Spatie\Dropbox\Client as DropboxClient;
use Spatie\FlysystemDropbox\DropboxAdapter;
use League\Flysystem\Filesystem;
use Illuminate\Filesystem\FilesystemAdapter;

class DropboxProvider extends ServiceProvider
{
    public function boot(): void
    {
        Storage::extend('dropbox', function ($app, $config) {
            $accessToken = Cache::get('dropbox_access_token');

            if (!$accessToken) {
                $response = Http::asForm()->post('https://api.dropbox.com/oauth2/token', [
                    'grant_type'    => 'refresh_token',
                    'refresh_token' => $config['refreshToken'],
                    'client_id'     => $config['key'],
                    'client_secret' => $config['secret'],
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $accessToken = $data['access_token'];
                    
                    Cache::put('dropbox_access_token', $accessToken, now()->addMinutes(230));
                } else {
                    \Log::error('Error crítico al refrescar token de Dropbox: ' . $response->body());
                    $accessToken = $config['accessToken'];
                }
            }

            $client = new DropboxClient($accessToken);
            $adapter = new DropboxAdapter($client);
            
            return new FilesystemAdapter(
                new Filesystem($adapter, $config),
                $adapter,
                $config
            );
        });
    }

    public function register(): void
    {
        //
    }
}