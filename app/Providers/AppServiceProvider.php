<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Validation\ValidationException;

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
    // ... lo que ya tengas

    RateLimiter::for('login', function (Request $request) {
        $email = (string) $request->input('email');

        return [
            // 5 intentos en una ventana de 5 minutos
            Limit::perMinutes(5, 5)
                ->by($email.'|'.$request->ip())
                ->response(function () {
                    // Mensaje y status 429 cuando se supera el límite
                    throw ValidationException::withMessages([
                        'email' => __('Has superado el número de intentos. Vuelve a intentarlo en 5 minutos.'),
                    ])->status(429);
                }),
        ];
    });
}

        public const HOME = '/dashboard';

}
