<?php

namespace  MFrouh\CheckMissingTranslations;

use MFrouh\CheckMissingTranslations\Console\Commands\CheckMissingTranslate;
use Illuminate\Support\ServiceProvider;

class CheckMissingTranslationServiceProvider extends ServiceProvider
{
    public function register()
    {
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([CheckMissingTranslate::class]);
        }
    }
}
