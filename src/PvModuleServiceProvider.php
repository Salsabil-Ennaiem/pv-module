<?php

namespace SalsabilEnnaiem\PvModule;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use SalsabilEnnaiem\PvModule\Contracts\ApprovalRules;
use SalsabilEnnaiem\PvModule\Contracts\CanManagePv;
use SalsabilEnnaiem\PvModule\Contracts\ParticipantResolver;
use SalsabilEnnaiem\PvModule\Defaults\DefaultApprovalRules;
use SalsabilEnnaiem\PvModule\Defaults\DefaultParticipantResolver;
use SalsabilEnnaiem\PvModule\Defaults\DefaultPvRules;

class PvModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/pv-module.php', 'pv-module');

        $this->app->singleton(CanManagePv::class, function ($app) {
            return $app->make(config('pv-module.can_manage_pv', DefaultPvRules::class));
        });

        $this->app->singleton(ApprovalRules::class, function ($app) {
            return $app->make(config('pv-module.approval_rules', DefaultApprovalRules::class));
        });

        $this->app->singleton(ParticipantResolver::class, function ($app) {
            return $app->make(config('pv-module.participant_resolver', DefaultParticipantResolver::class));
        });
    }

    public function boot(): void
    {
        $this->registerRoutes();
        $this->registerResources();
        $this->registerPublishing();
    }

    protected function registerRoutes(): void
    {
        Route::middleware(config('pv-module.routes.middleware', ['web', 'auth']))
            ->prefix(config('pv-module.routes.prefix', 'pv-module'))
            ->name(config('pv-module.routes.name_prefix', 'pv-module.'))
            ->group(__DIR__.'/../routes/web.php');
    }

    protected function registerResources(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'pv-module');
        $this->loadJsonTranslationsFrom(__DIR__.'/../resources/lang');
    }

    protected function registerPublishing(): void
    {
        $this->publishes([
            __DIR__.'/../config/pv-module.php' => config_path('pv-module.php'),
        ], 'pv-config');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'pv-migrations');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/pv-module'),
        ], 'pv-views');

        $this->publishes([
            __DIR__.'/../resources/lang' => lang_path('vendor/pv-module'),
        ], 'pv-lang');
    }
}