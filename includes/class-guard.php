<?php
declare(strict_types=1);

namespace WooCheckoutFailoverGuard;

use WC_Order;

defined('ABSPATH') || exit;

final class Guard {
    public const DEFAULT_TIMEOUT_MS = 14000;
    public const NONCE_ACTION = 'wcfg_checkout_guard_nonce';

    public function register(): void {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_wcfg_report_timeout', [$this, 'handle_client_timeout_report']);
        add_action('wp_ajax_nopriv_wcfg_report_timeout', [$this, 'handle_client_timeout_report']);
        add_action('woocommerce_checkout_order_processed', [$this, 'attach_circuit_metadata'], 10, 3);
        add_action('woocommerce_order_status_changed', [$this, 'inspect_order_transition'], 10, 4);
    }

    public function enqueue_assets(): void {
        if (!is_checkout() || is_order_received_page()) {
            return;
        }

        $min = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '';
        $plugin_url = Plugin::get_plugin_url();

        wp_enqueue_style(
            'wcfg-styles',
            $plugin_url . 'assets/css/checkout-guard.css',
            [],
            Plugin::VERSION
        );

        wp_enqueue_script(
            'wcfg-interceptor',
            $plugin_url . 'assets/js/checkout-interceptor.js',
            ['jquery'],
            Plugin::VERSION,
            true
        );

        $settings = get_option(Plugin::OPTION_KEY, []);
        $timeout_ms = isset($settings['timeout_ms']) ? (int) $settings['timeout_ms'] : self::DEFAULT_TIMEOUT_MS;
        $fallback_gateways = isset($settings['fallback_gateways']) && is_array($settings['fallback_gateways'])
            ? $settings['fallback_gateways']
            : ['bacs', 'cod'];

        wp_localize_script('wcfg-interceptor', 'wcfgConfig', [
            'ajaxUrl'           => admin_url('admin-ajax.php'),
            'nonce'             => wp_create_nonce(self::NONCE_ACTION),
            'timeoutThreshold'  => max(5000, $timeout_ms),
            'fallbackGateways'  => $fallback_gateways,
            'i18n'              => [
                'timeoutWarning'   => __('Payment gateway is taking longer than expected. We kept your order safe.', 'woo-checkout-failover-guard'),
                'retryAction'      => __('Try Submitting Again', 'woo-checkout-failover-guard'),
                'switchGateway'    => __('Choose Another Payment Method', 'woo-checkout-failover-guard'),
                'verifyingStatus'  => __('Verifying order status with payment provider...', 'woo-checkout-failover-guard'),
                'orderRecovered'   => __('Payment confirmed! Redirecting to receipt...', 'woo-checkout-failover-guard'),
            ],
        ]);
    }

    public function handle_client_timeout_report(): void {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');

        $gateway_id   = isset($_POST['gateway_id']) ? sanitize_key((string) $_POST['gateway_id']) : 'unknown';
        $elapsed_ms   = isset($_POST['elapsed_ms']) ? (int) $_POST['elapsed_ms'] : 0;
        $order_id     = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
        $error_status = isset($_POST['http_status']) ? sanitize_text_field((string) $_POST['http_status']) : 'timeout';

        $incident_id = sanitize_key(uniqid('inc_', true));
        $incident = [
            'id'          => $incident_id,
            'gateway'     => $gateway_id,
            'elapsed_ms'  => $elapsed_ms,
            'order_id'    => $order_id,
            'http_status' => $error_status,
            'created_at'  => current_time('mysql'),
            'ip_hash'     => hash('sha256', (string) ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1')),
        ];

        Telemetry::log_incident($incident);

        // If an order ID was already generated, flag it in HPOS metadata
        if ($order_id > 0) {
            $order = wc_get_order($order_id);
            if ($order instanceof WC_Order) {
                $order->update_meta_data('_wcfg_timeout_intercepted', 1);
                $order->update_meta_data('_wcfg_intercepted_at', current_time('mysql'));
                $order->add_order_note(
                    sprintf(
                        /* translators: 1: gateway, 2: elapsed milliseconds */
                        __('[Payment Guard] Checkout timeout intercepted after %2$dms on gateway "%1$s". Customer offered failover retry.', 'woo-checkout-failover-guard'),
                        $gateway_id,
                        $elapsed_ms
                    )
                );
                $order->save();
            }
        }

        wp_send_json_success([
            'incident_id' => $incident_id,
            'status'      => 'recorded',
        ]);
    }

    public function attach_circuit_metadata(int $order_id, array $posted_data, WC_Order $order): void {
        $payment_method = sanitize_text_field((string) ($posted_data['payment_method'] ?? ''));
        $order->update_meta_data('_wcfg_circuit_active', 1);
        $order->update_meta_data('_wcfg_primary_gateway', $payment_method);
        $order->update_meta_data('_wcfg_checkout_init_time', microtime(true));
        $order->save();
    }

    public function inspect_order_transition(int $order_id, string $from, string $to, WC_Order $order): void {
        if ($to === 'processing' || $to === 'completed') {
            if ($order->get_meta('_wcfg_timeout_intercepted')) {
                $order->update_meta_data('_wcfg_recovered_successfully', 1);
                $order->add_order_note(__(' [Payment Guard] Order successfully recovered after initial gateway timeout.', 'woo-checkout-failover-guard'));
                $order->save();
            }
        }
    }
}

