<?php
if (!defined('ABSPATH')) define('ABSPATH', 1);
if (!function_exists('add_action')) { function add_action($tag, $callback, $priority = 10, $accepted_args = 1) {} }
if (!function_exists('add_filter')) { function add_filter($tag, $callback, $priority = 10, $accepted_args = 1) {} }
if (!function_exists('is_checkout')) { function is_checkout() { return false; } }
if (!function_exists('is_product')) { function is_product() { return true; } }
if (!function_exists('is_order_received_page')) { function is_order_received_page() { return false; } }
if (!function_exists('esc_html__')) { function esc_html__($t, $d = 'default') { return $t; } }
if (!function_exists('esc_attr')) { function esc_attr($t) { return htmlspecialchars((string)$t, ENT_QUOTES); } }
if (!function_exists('esc_html')) { function esc_html($t) { return htmlspecialchars((string)$t, ENT_QUOTES); } }
if (!function_exists('sanitize_text_field')) { function sanitize_text_field($t) { return trim(strip_tags((string)$t)); } }
if (!function_exists('sanitize_email')) { function sanitize_email($e) { return filter_var($e, FILTER_VALIDATE_EMAIL) ? $e : ''; } }
if (!function_exists('is_email')) { function is_email($e) { return (bool)filter_var($e, FILTER_VALIDATE_EMAIL); } }
if (!function_exists('wp_unslash')) { function wp_unslash($d) { return $d; } }
if (!function_exists('wc_price')) { function wc_price($p) { return '$' . number_format((float)$p, 2); } }
if (!function_exists('get_transient')) { function get_transient($k) { return false; } }
if (!function_exists('set_transient')) { function set_transient($k, $v, $e = 0) { return true; } }
if (!function_exists('wp_generate_password')) { function wp_generate_password($len = 12) { return bin2hex(random_bytes((int)($len / 2))); } }

if (!function_exists('esc_url_raw')) { function esc_url_raw($u) { return filter_var($u, FILTER_SANITIZE_URL); } }
