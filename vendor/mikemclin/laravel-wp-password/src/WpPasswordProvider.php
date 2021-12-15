<?php namespace MikeMcLin\WpPassword;

use Hautelook\Phpass\PasswordHash;
use Illuminate\Support\ServiceProvider;

class WpPasswordProvider extends ServiceProvider {

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('MikeMcLin\WpPassword\Contracts\WpPassword', function () {
            return new WpPassword(new PasswordHash(8, true));
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['MikeMcLin\WpPassword\Contracts\WpPassword'];
    }

}