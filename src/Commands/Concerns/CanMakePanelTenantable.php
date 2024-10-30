<?php

declare(strict_types=1);

namespace BezhanSalleh\FilamentShield\Commands\Concerns;

use BezhanSalleh\FilamentShield\Stringer;
use Filament\Panel;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

trait CanMakePanelTenantable
{
    protected function makePanelTenantable(Panel $panel, string $panelPath, ?string $tenantModel): int
    {
        $tenantModelClass = str($tenantModel)->contains('\\')
            ? $tenantModel
            : str($tenantModel)->prepend('App\\Models\\')
                ->toString();

        if (filled($tenantModel) && ! class_exists($tenantModelClass) && ! $tenantModelClass instanceof Model) {
            $this->components->error("Tenant model not found: {$tenantModel}");

            return Command::FAILURE;
        }

        if (filled($tenantModel) && ! $panel->hasTenancy()) {

            dump('filled no tenancy');
            Stringer::for($panelPath)
                ->prepend('->discoverResources', '->tenant(\\' . $tenantModel . '::class)')
                ->save();

            $this->activateTenancy($panelPath);

            $this->components->info("Panel `{$panel->getId()}` is now tenantable.");
        }

        if ($panel->hasTenancy()) {
            dump('tenancy check');

            $this->activateTenancy($panelPath);
            $this->components->info("Panel `{$panel->getId()}` is now tenantable.");
        }

        return Command::SUCCESS;
    }

    private function activateTenancy(string $panelPath): void
    {
        $pluginsArrayMarker = '->plugins([';
        $shieldMiddlewareImportStatement = 'use BezhanSalleh\FilamentShield\Middleware\SyncShieldTenant;';
        $shieldMiddleware = 'SyncShieldTenant::class,';
        $tenantMiddlewareMarker = '->tenantMiddleware([';

        $stringer = Stringer::for($panelPath);
        if (! $stringer->contains($shieldMiddlewareImportStatement)) {
            $stringer->append('use', $shieldMiddlewareImportStatement);
        }

        $stringer->when(
            value: (! $stringer->contains($shieldMiddleware) && $stringer->contains($tenantMiddlewareMarker)),
            callback: fn (Stringer $stringer): Stringer => $stringer
                ->indent(4)
                ->append('->tenantMiddleware([', $shieldMiddleware),
        )
            ->when(
                value: (! $stringer->contains($shieldMiddleware) && ! $stringer->contains($tenantMiddlewareMarker)),
                callback: fn (Stringer $stringer): Stringer => $stringer
                    ->prependBeforeLast($stringer->contains($pluginsArrayMarker) ? $pluginsArrayMarker : '->', $tenantMiddlewareMarker)
                    ->append($tenantMiddlewareMarker, '], isPersistent: true)')
                    ->indent(4)
                    ->prepend('], isPersistent: true)', $shieldMiddleware)
            )
            ->save();
    }
}
