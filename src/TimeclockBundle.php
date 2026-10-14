<?php

declare(strict_types=1);

namespace kintai\Bundles\Installed\Timeclock;

use kintai\Bundles\Installed\Timeclock\Providers\TimeclockAttendanceProvider;
use kintai\Contract\Bundle;
use kintai\Contract\Container;
use kintai\Contract\Extension\NavItem;
use kintai\Contract\Extension\StoreFeature;

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
        return '1.2.0';
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
        $this->loadAssetsFrom('public');
    }

    /**
     * Navigation (contrat 1.1) : entrée(s) de la barre du haut et de la barre mobile.
     */
    public function navigation(): array
    {
        return [
            new NavItem('timeclocks', NavItem::PLANNING, 'timeclocks', 'admin.timeclocks', 'employee.timeclock',
                icon: 'clock', employeeLabel: 'timeclock', activePaths: ['/admin/timeclocks', '/employee/timeclock'],
                storeFeature: 'timeclock', order: 40, mobile: true),
        ];
    }

    /**
     * Catégories de permissions (contrat 1.2), proposées sur la page des rôles.
     */
    public function permissions(): array
    {
        return ['timeclock' => ['view', 'update', 'delete']];
    }

    /**
     * Fonctionnalité activable par magasin (contrat 1.3), sur la fiche magasin.
     */
    public function storeFeatures(): array
    {
        return [new StoreFeature('timeclock', 'feature_timeclock', 'feature_timeclock_desc', '🕐', 20)];
    }

    /**
     * Pointages (contrat 1.11) : tableaux de bord du Core.
     */
    public function attendanceProviders(): array
    {
        return [new TimeclockAttendanceProvider(Container::getInstance())];
    }
}
