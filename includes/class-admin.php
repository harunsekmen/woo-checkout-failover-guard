<?php
declare(strict_types=1);

namespace WooCheckoutFailoverGuard;

defined('ABSPATH') || exit;

final class Admin {
    public function register(): void {
        add_action('admin_menu', [$this, 'add_menu_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_post_wcfg_clear_telemetry', [$this, 'handle_clear_telemetry']);
    }

    public function add_menu_page(): void {
        add_submenu_page(
            'woocommerce',
            __('Checkout Payment Guard', 'woo-checkout-failover-guard'),
            __('Checkout Guard', 'woo-checkout-failover-guard'),
            'manage_woocommerce',
            'wcfg-settings',
            [$this, 'render_dashboard']
        );
    }

    public function register_settings(): void {
        register_setting('wcfg_settings_group', Plugin::OPTION_KEY, [
            'type'              => 'array',
            'sanitize_callback' => [$this, 'sanitize_settings'],
            'default'           => [
                'timeout_ms'         => Guard::DEFAULT_TIMEOUT_MS,
                'fallback_gateways'  => ['bacs', 'cod'],
                'enable_telemetry'   => 1,
            ],
        ]);
    }

    public function sanitize_settings(mixed $input): array {
        $output = [];
        $output['timeout_ms'] = isset($input['timeout_ms']) ? max(3000, (int) $input['timeout_ms']) : Guard::DEFAULT_TIMEOUT_MS;
        $output['enable_telemetry'] = !empty($input['enable_telemetry']) ? 1 : 0;
        
        $output['fallback_gateways'] = [];
        if (!empty($input['fallback_gateways']) && is_array($input['fallback_gateways'])) {
            $output['fallback_gateways'] = array_map('sanitize_key', $input['fallback_gateways']);
        }

        return $output;
    }

    public function handle_clear_telemetry(): void {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Permission denied.', 'woo-checkout-failover-guard'));
        }
        check_admin_referer('wcfg_clear_telemetry_action', 'wcfg_clear_nonce');

        Telemetry::clear_all();
        wp_safe_redirect(add_query_arg(['page' => 'wcfg-settings', 'cleared' => '1'], admin_url('admin.php')));
        exit;
    }

    public function render_dashboard(): void {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }

        $settings  = get_option(Plugin::OPTION_KEY, ['timeout_ms' => Guard::DEFAULT_TIMEOUT_MS]);
        $stats     = Telemetry::get_stats();
        $incidents = Telemetry::get_incidents();
        $gateways  = WC()->payment_gateways ? WC()->payment_gateways->get_available_payment_gateways() : [];

        require_once Plugin::get_plugin_dir() . 'templates/admin-dashboard.php';
    }
}

