<?php

declare(strict_types=1);

namespace BezhanSalleh\FilamentShield;

use Filament\Panel;
use Filament\FilamentManager;
use Filament\Contracts\Plugin;
use BezhanSalleh\FilamentShield\Support\Utils;
use BezhanSalleh\FilamentShield\Concerns;

class FilamentShieldPlugin implements Plugin
{
    use Concerns\CanCustomizeColumns;

    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'filament-shield';
    }

    public function register(Panel $panel): void
    {
        if (! Utils::isResourcePublished()) {
            $panel->resources([
                Resources\RoleResource::class,
            ]);
        }
    }

    public function boot(Panel $panel): void
    {
        //
    }

    /**
     * Class MyClass overrides inline block form.
     *
     * @phpstan-ignore-next-line */
    public static function get(): Plugin | FilamentManager
    {
        return filament(app(static::class)->getId());
    }
}
