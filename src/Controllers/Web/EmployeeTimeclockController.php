<?php

declare(strict_types=1);

namespace kintai\Bundles\Installed\Timeclock\Controllers\Web;

use kintai\Core\Exceptions\ConflictException;
use kintai\Core\Exceptions\NotFoundException;
use kintai\Core\Repositories\StoreRepositoryInterface;
use kintai\Core\Repositories\StoreUserRepositoryInterface;
use kintai\Core\Repositories\TimeclockRepositoryInterface;
use kintai\Core\Request;
use kintai\Core\Response;
use kintai\Core\Services\AuditLogger;
use kintai\UI\Controller\Web\HasBaseUrl;
use kintai\UI\Controller\Web\HasStoreFeatureCheck;
use kintai\UI\ViewRenderer;

final class EmployeeTimeclockController
{
    use HasBaseUrl;
    use HasStoreFeatureCheck;

    public function __construct(
        private readonly ViewRenderer $view,
        private readonly TimeclockRepositoryInterface $timeclocks,
        private readonly StoreUserRepositoryInterface $storeUsers,
        private readonly StoreRepositoryInterface $stores,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function timeclock(Request $request): Response
    {
        if (($resp = $this->assertFeature($request, 'timeclock')) !== null) {
            return $resp;
        }
        $user   = $request->getAttribute('auth_user');
        $userId = (int) ($user['id'] ?? 0);

        $today        = date('Y-m-d');
        $activeClock  = $this->timeclocks->findActiveByUser($userId);
        $todayEntries = $this->timeclocks->findByUserAndDate($userId, $today);

        $weekStart   = date('Y-m-d', strtotime('monday this week'));
        $weekEnd     = date('Y-m-d', strtotime('sunday this week'));
        $allEntries  = $this->timeclocks->findByUser($userId);
        $weekEntries = array_filter(
            $allEntries,
            fn($e) => ($e['shift_date'] ?? '') >= $weekStart && ($e['shift_date'] ?? '') <= $weekEnd
        );
        usort($weekEntries, fn($a, $b) => strcmp($b['shift_date'] ?? '', $a['shift_date'] ?? ''));

        $memberships = $this->storeUsers->findByUser($userId);
        $storeId     = $memberships ? (int) $memberships[0]['store_id'] : 0;

        return Response::html($this->view->render('timeclock::employee-timeclock', [
            'title'         => __('timeclock'),
            'active_clock'  => $activeClock,
            'today'         => $today,
            'today_entries' => $todayEntries,
            'week_entries'  => array_values($weekEntries),
            'store_id'      => $storeId,
        ], 'layout.app'));
    }

    /**
     * POST /employee/timeclock/clock-in — pointage via la session web (pas l'API Bearer token,
     * inaccessible depuis le navigateur d'un employé).
     */
    public function clockIn(Request $request): Response
    {
        if (($resp = $this->assertFeature($request, 'timeclock')) !== null) {
            return $resp;
        }
        $user   = $request->getAttribute('auth_user');
        $userId = (int) ($user['id'] ?? 0);

        if ($this->timeclocks->findActiveByUser($userId) !== null) {
            throw new ConflictException(__('timeclock_already_active'));
        }

        $memberships = $this->storeUsers->findByUser($userId);
        $storeId     = $memberships ? (int) $memberships[0]['store_id'] : 0;

        $now       = date('Y-m-d H:i:s');
        $effective = $this->resolveClientTime($request->input('client_time'), $now);
        $record    = $this->timeclocks->save([
            'user_id'        => $userId,
            'store_id'       => $storeId,
            'shift_date'     => substr($effective, 0, 10),
            'clock_in_time'  => $effective,
            'clock_out_time' => null,
            'created_at'     => $now,
            'updated_at'     => $now,
        ]);

        $context = ['user_id' => $userId];
        if ($effective !== $now) {
            $context['offline_sync'] = true;
            $context['client_time']  = $effective;
            $context['synced_at']    = $now;
        }
        $this->auditLogger->log($request, 'timeclock.clock_in', 'timeclock', (int) ($record['id'] ?? 0) ?: null, $context, $storeId ?: null);
        return Response::json($record, 201);
    }

    /** POST /employee/timeclock/clock-out */
    public function clockOut(Request $request): Response
    {
        if (($resp = $this->assertFeature($request, 'timeclock')) !== null) {
            return $resp;
        }
        $user   = $request->getAttribute('auth_user');
        $userId = (int) ($user['id'] ?? 0);

        $active = $this->timeclocks->findActiveByUser($userId);
        if ($active === null) {
            throw new NotFoundException(__('timeclock_no_active'));
        }

        $now          = date('Y-m-d H:i:s');
        $effective    = $this->resolveClientTime($request->input('client_time'), $now);
        $clockInTime  = new \DateTimeImmutable($active['clock_in_time']);
        $clockOutTime = new \DateTimeImmutable($effective);
        // Un pointage de sortie différé peut se resynchroniser avant l'entrée si les deux actions
        // ont été mises en file hors-ligne et rejouées hors ordre : on retombe alors sur l'heure
        // serveur plutôt que de produire une durée négative.
        if ($clockOutTime < $clockInTime) {
            $effective    = $now;
            $clockOutTime = new \DateTimeImmutable($now);
        }
        $duration = (int) round(($clockOutTime->getTimestamp() - $clockInTime->getTimestamp()) / 60);

        $record = $this->timeclocks->save(array_merge($active, [
            'clock_out_time'   => $effective,
            'duration_minutes' => $duration,
            'updated_at'       => $now,
        ]));

        $context = ['user_id' => $userId];
        if ($effective !== $now) {
            $context['offline_sync'] = true;
            $context['client_time']  = $effective;
            $context['synced_at']    = $now;
        }
        $storeId = isset($active['store_id']) ? (int) $active['store_id'] : null;
        $this->auditLogger->logUpdate($request, 'timeclock.clock_out', 'timeclock', (int) ($record['id'] ?? 0) ?: null, $active, $record, $context, $storeId);
        return Response::json($record);
    }

    /**
     * Un pointage effectué hors-ligne transmet l'heure réelle du clic (client_time, ISO 8601)
     * plutôt que l'heure de resynchronisation — indispensable pour la justesse du calcul de paie.
     * Bornée à une fenêtre de confiance raisonnable (jusqu'à 24h dans le passé, 2 min dans le futur
     * pour la dérive d'horloge) : au-delà, l'heure serveur est utilisée à la place.
     */
    private function resolveClientTime(mixed $clientTime, string $serverNow): string
    {
        if (!is_string($clientTime) || $clientTime === '') {
            return $serverNow;
        }

        try {
            $parsed = new \DateTimeImmutable($clientTime);
        } catch (\Exception) {
            return $serverNow;
        }

        $diffSeconds = (new \DateTimeImmutable($serverNow))->getTimestamp() - $parsed->getTimestamp();
        if ($diffSeconds < -120 || $diffSeconds > 86400) {
            return $serverNow;
        }

        return $parsed->format('Y-m-d H:i:s');
    }
}
