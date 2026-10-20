<?php
/**
 * Plugin Name:       WooCommerce Checkout Failover & Payment Guard
 * Plugin URI:        https://github.com/harunsekmen/woo-checkout-failover-guard
 * Description:       Enterprise-grade, zero-downtime checkout failover and timeout circuit breaker for WooCommerce and payment gateways (Stripe, PayPal, Mollie).
 * Version:           1.0.0
 * Author:            Harun Sekmen
 * Author URI:        https://github.com/harunsekmen
 * License:           GPL-3.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       woo-checkout-failover-guard
 * Domain Path:       /languages
 * Requires at least: 6.2
 * Requires PHP:      8.1
 * WC requires at least: 8.0
 * WC tested up to:   9.4
 */

declare(strict_types=1);

namespace WooCheckoutFailoverGuard;

defined('ABSPATH') || exit;

// Autoloader for internal classes
foreach (glob(__DIR__ . '/includes/*.php') as $inc) {
    require_once $inc;
}

// Declare WooCommerce High-Performance Order Storage (HPOS) & Cart/Checkout Blocks compatibility
add_action('before_woocommerce_init', static function (): void {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'custom_order_tables',
            __FILE__,
            true
        );
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'cart_checkout_blocks',
            __FILE__,
            true
        );
    }
});

// Bootstrap main plugin lifecycle
add_action('plugins_loaded', static function (): void {
    Plugin::instance()->init();
});

