<?php

namespace Treestoneit\ShoppingCart;

use Illuminate\Support\ServiceProvider;
use Treestoneit\ShoppingCart\Models\Cart;

class CartServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('shopping-cart.php'),
            ], 'config');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'migrations');
        }
    }

    /**
     * Register the application services.
     * TODO dedicated cart factory class
     * TODO replace Laravel framework facades with contracts.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'shopping-cart');

        $this->app->singleton('cart', function ($app) {
            if ($app['session']->has('cart')) {
                return CartManager::fromSessionIdentifier($app['session']->get('cart'));
            }

            if ($app['auth']->check()) {
                return CartManager::fromUserId($app['auth']->user());
            }

            $cart_id = intval(request()->header('cart-id'));
            $cart_hash = request()->header('cart-hash');
            if ($cart_id && $cart_hash && sha1($cart_id . substr(config('app.key'), 0, 10)) == $cart_hash) {
                return CartManager::fromSessionIdentifier($cart_id);
            }

            return new CartManager(new Cart());
        });

        $this->app->alias('cart', CartManager::class);
        $this->app->alias('cart', CartContract::class);
    }
}
