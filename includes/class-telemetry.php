<?php
declare(strict_types=1);

namespace WooCheckoutFailoverGuard;

defined('ABSPATH') || exit;

final class Telemetry {
    public const INCIDENTS_OPTION = 'wcfg_incidents_log';
    public const STATS_OPTION = 'wcfg_aggregate_stats';
    public const MAX_INCIDENTS = 100;

    public function register(): void {
        add_action('wcfg_daily_cleanup_hook', [$this, 'prune_stale_telemetry']);

        if (!wp_next_scheduled('wcfg_daily_cleanup_hook')) {
            wp_schedule_event(time() + DAY_IN_SECONDS, 'daily', 'wcfg_daily_cleanup_hook');
        }
    }

    public static function log_incident(array $incident): void {
        $log = get_option(self::INCIDENTS_OPTION, []);
        if (!is_array($log)) {
            $log = [];
        }

        array_unshift($log, $incident);
        if (count($log) > self::MAX_INCIDENTS) {
            $log = array_slice($log, 0, self::MAX_INCIDENTS);
        }

        update_option(self::INCIDENTS_OPTION, $log, false);

        // Update aggregate counters
        $stats = get_option(self::STATS_OPTION, [
            'total_intercepted' => 0,
            'gateways'          => [],
        ]);

        $stats['total_intercepted'] = (int) ($stats['total_intercepted'] ?? 0) + 1;
        $gw = $incident['gateway'] ?? 'unknown';
        $stats['gateways'][$gw] = (int) ($stats['gateways'][$gw] ?? 0) + 1;

        update_option(self::STATS_OPTION, $stats, false);
    }

    public static function get_incidents(): array {
        $log = get_option(self::INCIDENTS_OPTION, []);
        return is_array($log) ? $log : [];
    }

    public static function get_stats(): array {
        $stats = get_option(self::STATS_OPTION, [
            'total_intercepted' => 0,
            'gateways'          => [],
        ]);
        return is_array($stats) ? $stats : [];
    }

    public static function clear_all(): void {
        update_option(self::INCIDENTS_OPTION, [], false);
        update_option(self::STATS_OPTION, [
            'total_intercepted' => 0,
            'gateways'          => [],
        ], false);
    }

    public function prune_stale_telemetry(): void {
        $log = self::get_incidents();
        $cutoff = time() - (30 * DAY_IN_SECONDS);
        $filtered = array_filter($log, static function (array $item) use ($cutoff): bool {
            $time = isset($item['created_at']) ? strtotime((string) $item['created_at']) : 0;
            return $time >= $cutoff;
        });
        update_option(self::INCIDENTS_OPTION, array_values($filtered), false);
    }
}

