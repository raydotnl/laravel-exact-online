<?php

namespace PendoNL\LaravelExactOnline\Providers;

use File;
use Illuminate\Support\ServiceProvider;
use PendoNL\LaravelExactOnline\LaravelExactOnline;

class LaravelExactOnlineServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/../Http/routes.php');

        $this->loadViewsFrom(__DIR__.'/../views', 'laravelexactonline');

        $this->publishes([
            __DIR__.'/../views' => base_path('resources/views/vendor/laravelexactonline'),
        ]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->alias(LaravelExactOnline::class, 'laravel-exact-online');

        $this->app->singleton('Exact\Connection', function() {
            $config = json_decode(
                File::get(
                    $file = storage_path('exact.api.json')
                ),
                true
            );

            $connection = new \Picqer\Financials\Exact\Connection();
            $connection->setRedirectUrl(route('exact.callback'));
            $connection->setExactClientId(env('EXACT_CLIENT_ID'));
            $connection->setExactClientSecret(env('EXACT_CLIENT_SECRET'));

            if(isset($config['authorisationCode'])) {
                $connection->setAuthorizationCode($config['authorisationCode']);
            }
            if(isset($config['accessToken'])) {
                $connection->setAccessToken(unserialize($config['accessToken']));
            }
            if(isset($config['refreshToken'])) {
                $connection->setRefreshToken($config['refreshToken']);
            }
            if(isset($config['tokenExpires'])) {
                $connection->setTokenExpires($config['tokenExpires']);
            }

            try {

                if(isset($config['authorisationCode'])) {
                    $connection->connect();
                }

            } catch (\Exception $e)
            {
                throw new \Exception('Could not connect to Exact: ' . $e->getMessage());
            }

            $config['accessToken'] = serialize($connection->getAccessToken());
            $config['refreshToken'] = $connection->getRefreshToken();
            $config['tokenExpires'] = $connection->getTokenExpires();

            File::put($file, json_encode($config));

            return $connection;
        });
    }
}