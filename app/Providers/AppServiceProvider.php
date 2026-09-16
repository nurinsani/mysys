<?php

namespace App\Providers;

use App\Repositories\Contracts\AnggotaRepositoryInterface;
use App\Repositories\Contracts\KelompokRepositoryInterface;
use App\Repositories\Eloquent\AnggotaRepository;
use App\Repositories\Eloquent\KelompokRepository;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{

    public function register(): void
    {
        $this->app->bind(KelompokRepositoryInterface::class, KelompokRepository::class);
        $this->app->bind(AnggotaRepositoryInterface::class, AnggotaRepository::class);

        if ($this->app->environment('local') && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(\App\Providers\TelescopeServiceProvider::class);
        }
    }

    public function boot(): void
    {
        Event::listen(Login::class, function (Login $event) {
            activity('akses')
                ->causedBy($event->user)
                ->withProperties([
                    'ip' => request()->ip(),
                    'user_agent' => (string) request()->userAgent(),
                    'session_id' => session()->getId(),
                ])
                ->event('login')
                ->log('Login');
        });

        Event::listen(Logout::class, function (Logout $event) {
            if (!$event->user) {
                return;
            }

            activity('akses')
                ->causedBy($event->user)
                ->withProperties([
                    'ip' => request()->ip(),
                    'session_id' => session()->getId(),
                ])
                ->event('logout')
                ->log('Logout');
        });
    }


}
