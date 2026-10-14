<?php

namespace App\Providers;

use App\Contracts\PenyediaNarasi;
use App\Exceptions\NarasiTidakTersedia;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Resolve the narration provider declared in config/ai.php. Registering it
        // here keeps the choice in one place and lets anything type-hinting the
        // contract receive the configured driver.
        $this->app->bind(PenyediaNarasi::class, function (): PenyediaNarasi {
            $nama = (string) config('ai.default');
            $konfigurasi = config("ai.providers.{$nama}");

            if (! is_array($konfigurasi)) {
                throw NarasiTidakTersedia::tidakDikenal($nama);
            }

            $driver = $konfigurasi['driver'] ?? null;

            if (! is_string($driver) || ! is_subclass_of($driver, PenyediaNarasi::class)) {
                throw NarasiTidakTersedia::tidakDikenal($nama);
            }

            return new $driver($konfigurasi);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
