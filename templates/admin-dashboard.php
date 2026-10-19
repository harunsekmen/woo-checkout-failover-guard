<?php
declare(strict_types=1);

namespace WooCheckoutFailoverGuard;

defined('ABSPATH') || exit;

/**
 * @var array $settings
 * @var array $stats
 * @var array $incidents
 * @var array $gateways
 */
?>
<div class="wrap wcfg-admin-wrap">
    <h1><?php esc_html_e('WooCommerce Checkout Failover & Payment Guard', 'woo-checkout-failover-guard'); ?></h1>
    <p class="description">
        <?php esc_html_e('Zero-downtime client-side circuit breaker. Catches gateway 504 timeouts, preserves order state, and rescues abandoned payments.', 'woo-checkout-failover-guard'); ?>
    </p>

    <?php if (isset($_GET['cleared'])): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e('Incident telemetry has been cleared.', 'woo-checkout-failover-guard'); ?></p>
        </div>
    <?php endif; ?>

    <div class="wcfg-dashboard-grid" style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-top: 20px;">
        <div class="wcfg-main-column">
            <div class="postbox" style="padding: 20px;">
                <h2><?php esc_html_e('Active Gateway Health & Intercepted Incidents', 'woo-checkout-failover-guard'); ?></h2>
                <div style="display: flex; gap: 15px; margin-bottom: 20px;">
                    <div style="background: #f0f6fc; border-left: 4px solid #0969da; padding: 12px 20px; border-radius: 4px;">
                        <span style="font-size: 24px; font-weight: bold; color: #0969da; display: block;">
                            <?php echo esc_html((string) ($stats['total_intercepted'] ?? 0)); ?>
                        </span>
                        <span style="font-size: 13px; color: #57606a;"><?php esc_html_e('Total Intercepted Timeouts', 'woo-checkout-failover-guard'); ?></span>
                    </div>
                </div>

                <h3><?php esc_html_e('Recent Interceptions (Circuit Breaker Activations)', 'woo-checkout-failover-guard'); ?></h3>
                <?php if (empty($incidents)): ?>
                    <p style="color: #57606a; font-style: italic;"><?php esc_html_e('No timeouts detected yet. All checkout pipelines are healthy.', 'woo-checkout-failover-guard'); ?></p>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Timestamp', 'woo-checkout-failover-guard'); ?></th>
                                <th><?php esc_html_e('Gateway', 'woo-checkout-failover-guard'); ?></th>
                                <th><?php esc_html_e('Latency (ms)', 'woo-checkout-failover-guard'); ?></th>
                                <th><?php esc_html_e('Order ID', 'woo-checkout-failover-guard'); ?></th>
                                <th><?php esc_html_e('HTTP Status', 'woo-checkout-failover-guard'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($incidents, 0, 15) as $incident): ?>
                                <tr>
                                    <td><?php echo esc_html($incident['created_at'] ?? '-'); ?></td>
                                    <td><strong><?php echo esc_html(strtoupper($incident['gateway'] ?? 'unknown')); ?></strong></td>
                                    <td><?php echo esc_html((string) ($incident['elapsed_ms'] ?? 0)); ?> ms</td>
                                    <td>
                                        <?php if (!empty($incident['order_id'])): ?>
                                            <a href="<?php echo esc_url(admin_url('post.php?post=' . (int) $incident['order_id'] . '&action=edit')); ?>">
                                                #<?php echo esc_html((string) $incident['order_id']); ?>
                                            </a>
                                        <?php else: ?>
                                            <span style="color: #8c959f;"><?php esc_html_e('Pre-submission', 'woo-checkout-failover-guard'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><code><?php echo esc_html($incident['http_status'] ?? 'timeout'); ?></code></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <div class="wcfg-side-column">
            <div class="postbox" style="padding: 20px;">
                <h2><?php esc_html_e('Circuit Configuration', 'woo-checkout-failover-guard'); ?></h2>
                <form method="post" action="options.php">
                    <?php settings_fields('wcfg_settings_group'); ?>
                    
                    <p>
                        <label for="wcfg_timeout_ms"><strong><?php esc_html_e('Timeout Threshold (Milliseconds):', 'woo-checkout-failover-guard'); ?></strong></label><br>
                        <input type="number" id="wcfg_timeout_ms" name="wcfg_settings[timeout_ms]" value="<?php echo esc_attr((string) ($settings['timeout_ms'] ?? 14000)); ?>" min="3000" step="500" class="regular-text" style="width: 100%; margin-top: 5px;">
                        <span class="description" style="display: block; margin-top: 4px;">
                            <?php esc_html_e('Recommended: 12000 - 16000ms. If a payment gateway does not respond within this window, the failover card activates.', 'woo-checkout-failover-guard'); ?>
                        </span>
                    </p>

                    <p style="margin-top: 15px;">
                        <label><strong><?php esc_html_e('Telemetry Logging:', 'woo-checkout-failover-guard'); ?></strong></label><br>
                        <label>
                            <input type="checkbox" name="wcfg_settings[enable_telemetry]" value="1" <?php checked(!empty($settings['enable_telemetry'])); ?>>
                            <?php esc_html_e('Log checkout latency anomalies and incident hashes', 'woo-checkout-failover-guard'); ?>
                        </label>
                    </p>

                    <?php submit_button(__('Save Guard Settings', 'woo-checkout-failover-guard')); ?>
                </form>

                <hr style="margin: 20px 0;">

                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="wcfg_clear_telemetry">
                    <?php wp_nonce_field('wcfg_clear_telemetry_action', 'wcfg_clear_nonce'); ?>
                    <button type="submit" class="button button-secondary" onclick="return confirm('<?php esc_attr_e('Are you sure you want to clear incident logs?', 'woo-checkout-failover-guard'); ?>');">
                        <?php esc_html_e('Clear Telemetry Log', 'woo-checkout-failover-guard'); ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

