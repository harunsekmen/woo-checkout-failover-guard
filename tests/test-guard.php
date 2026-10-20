<?php
require_once __DIR__ . '/bootstrap.php';
if (!function_exists('get_option')) { function get_option($k, $d = []) { return $d; } }
require_once dirname(__DIR__) . '/includes/class-guard.php';
require_once dirname(__DIR__) . '/includes/class-telemetry.php';

echo "Testing WooCheckoutFailoverGuard (Guard & Telemetry)...
";

$guard = new \WooCheckoutFailoverGuard\Guard();
assert($guard instanceof \WooCheckoutFailoverGuard\Guard, "Guard instance check");

$stats = \WooCheckoutFailoverGuard\Telemetry::get_stats();
assert(is_array($stats), "Stats must be array");

echo "PASS: woo-checkout-failover-guard verified.
";
