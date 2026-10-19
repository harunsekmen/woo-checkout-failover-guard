# WooCommerce Checkout Failover & Payment Guard

[![WooCommerce HPOS Compatible](https://img.shields.io/badge/WooCommerce-HPOS%20Ready-7f54b3.svg)](https://github.com/harunsekmen/woo-checkout-failover-guard)
[![License: GPL-3.0](https://img.shields.io/badge/License-GPL%203.0-blue.svg)](https://github.com/harunsekmen/woo-checkout-failover-guard/blob/main/LICENSE)
[![PHP: 8.1+](https://img.shields.io/badge/PHP-8.1%20to%208.3-8892be.svg)](https://github.com/harunsekmen/woo-checkout-failover-guard)
[![Build Status](https://img.shields.io/badge/Build-Passing-brightgreen.svg)](https://github.com/harunsekmen/woo-checkout-failover-guard/actions)

> Zero-downtime client-side circuit breaker. Intercepts payment gateway 504 timeouts, stops cart abandonment, and preserves transaction integrity without page reloads.

Developed and maintained by **[Harun Sekmen](https://github.com/harunsekmen)**.

---

## ⚡ The Problem It Solves

Under sudden traffic spikes, flash sales, or third-party payment gateway latency (Stripe, PayPal, Mollie, Adyen), WooCommerce checkout endpoints frequently encounter `504 Gateway Timeouts` or unhandled Ajax drops. 

When this happens:
1. The checkout page gets permanently stuck on the gray loading block overlay.
2. Frustrated buyers either abandon the cart or aggressively click the submit button multiple times, causing duplicate authorization attempts.
3. Orders remain stranded in the database with orphaned `Pending Payment` statuses.

**WooCommerce Checkout Failover & Payment Guard** resolves this natively by introducing an asynchronous circuit breaker into the checkout pipeline. When a gateway exceeds configurable response thresholds (e.g. 14,000ms) or returns an HTTP 5xx error, the client gracefully unblocks the UI, alerts the customer with non-destructive failover options, and records diagnostic telemetry.

---

## 🛠️ Key Architectural Features

- **Asynchronous Client Circuit Breaker**: Disarms infinite checkout spinners and gracefully restores form interactivity on timeout.
- **Full HPOS Compatibility**: Built with native support for WooCommerce High-Performance Order Storage (`custom_order_tables`) and Cart/Checkout Blocks.
- **Idempotent Order State Protection**: Automatically tags affected orders with latency and failover metadata to prevent duplicate charges.
- **Diagnostic Admin Telemetry**: Track gateway latencies and timeout events directly inside the WP Admin WooCommerce menu.
- **Zero Front-End Bloat**: Clean, dependency-light vanilla JS/jQuery event interceptor under 3 KB gzipped.

---

## 🏗️ Repository Structure

```
woo-checkout-failover-guard/
├── .github/
│   └── workflows/
│       └── ci.yml               <-- GitHub Actions syntax and linting checks
├── assets/
│   ├── css/
│   │   └── checkout-guard.css   <-- Failover alert styling & animations
│   └── js/
│       └── checkout-interceptor.js <-- Client-side circuit breaker listener
├── includes/
│   ├── class-admin.php          <-- WP Admin dashboard & settings controller
│   ├── class-guard.php          <-- Core timeout handler & HPOS metadata
│   ├── class-plugin.php         <-- Singleton bootstrap & dependency checks
│   └── class-telemetry.php      <-- Latency metrics & log pruner
├── templates/
│   └── admin-dashboard.php      <-- Telemetry UI table & settings form
├── composer.json                <-- PSR-4 autoload mapping
├── LICENSE                      <-- GPL-3.0 License
├── README.md                    <-- Technical documentation
└── woo-checkout-failover-guard.php <-- Main plugin entry point & metadata
```

---

## 🚀 Installation & Quick Start

### Option 1: Git Clone (Development)
Clone directly into your WordPress plugins directory:
```bash
cd wp-content/plugins/
git clone https://github.com/harunsekmen/woo-checkout-failover-guard.git
```

### Option 2: WP-CLI Activation
```bash
wp plugin activate woo-checkout-failover-guard
```

---

## ⚙️ Configuration

Navigate to **WooCommerce > Checkout Guard** in your WordPress dashboard:
1. **Timeout Threshold**: Define maximum acceptable gateway response latency in milliseconds (Default: `14000ms`).
2. **Telemetry Logging**: Toggle incident hash logging for latency auditing.

---

## 📄 License

This project is licensed under the terms of the **GNU General Public License v3.0 or later (GPL-3.0-or-later)**.

