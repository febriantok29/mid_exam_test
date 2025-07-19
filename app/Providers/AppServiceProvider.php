<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

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
        Paginator::useBootstrap();

        config(['app.locale' => 'id']);
        Carbon::setLocale('id');

        // Add custom password confirmation validator
        Validator::extend('matches_password', function ($attribute, $value, $parameters, $validator) {
            return $value === $validator->getData()[$parameters[0]];
        }, 'The :attribute must match with :other.');
    }
}
