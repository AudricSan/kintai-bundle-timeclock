<?php

declare(strict_types=1);

namespace kintai\Bundles\Installed\Timeclock;

use kintai\Core\BundleContract\Bundle;

/**
 * Contrairement aux bundles à extraction complète (StorePhoto, ShiftClaim, ...),
 * TimeclockRepositoryInterface reste un service Core (RepositoryServiceProvider) :
 * HomeController (widget "pointages actifs" du dashboard admin),
 * EmployeeController::dashboard() (widget "pointage en cours" de l'employé) et
 * DashboardAlertService en dépendent tous pour des calculs qui doivent
 * continuer de fonctionner même si ce bundle est désactivé. Désactiver
 * "timeclock" retire uniquement l'UI dédiée (pointer/dépointer, historique
 * employé, gestion admin des entrées, /api/v1/timeclocks), pas les données
 * existantes ni ces widgets.
 */
final class TimeclockBundle extends Bundle
{
    public function getName(): string
    {
        return 'timeclock';
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function getLabel(): string
    {
        return __('bundle_timeclock');
    }

    public function getDescription(): string
    {
        return __('bundle_timeclock_desc');
    }

    public function register(): void
    {
        $this->loadViewsFrom($this->getPath() . '/Views', 'timeclock');
        $this->loadRoutesFrom($this->getPath() . '/routes.php');
    }
}
