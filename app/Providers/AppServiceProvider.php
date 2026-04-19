<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // تعيين public_path للـ webroot مباشرة (cPanel: public_html = project root)
        $this->app->bind('path.public', fn () => base_path());
    }

    public function boot(): void
    {
        Model::unguard();

        // Super Admin يمتلك كل الصلاحيات
        Gate::before(function ($user, $ability) {
            return $user->hasRole('super_admin') ? true : null;
        });
    }
}
