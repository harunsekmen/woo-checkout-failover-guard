/**
 * WooCommerce Checkout Failover & Payment Guard
 * Client-Side Circuit Breaker & Timeout Interceptor
 *
 * @package WooCheckoutFailoverGuard
 * @version 1.0.0
 */

(function ($) {
  'use strict';

  if (typeof window.wcfgConfig === 'undefined') {
    return;
  }

  var config = window.wcfgConfig;
  var threshold = parseInt(config.timeoutThreshold, 10) || 14000;
  var activeTimer = null;
  var submissionStartTime = null;
  var isHandlingFailover = false;

  var Interceptor = {
    init: function () {
      $(document).on('checkout_place_order', this.onCheckoutSubmit);
      $(document).ajaxComplete(this.onAjaxComplete);
      $(document).ajaxError(this.onAjaxError);
      $(document).on('click', '.wcfg-retry-button', this.onRetryClick);
      $(document).on('click', '.wcfg-switch-gateway-button', this.onSwitchGatewayClick);
    },

    onCheckoutSubmit: function () {
      isHandlingFailover = false;
      submissionStartTime = Date.now();
      Interceptor.clearFailoverNotice();

      // Arm circuit breaker timer
      clearTimeout(activeTimer);
      activeTimer = setTimeout(function () {
        Interceptor.triggerCircuitBreaker('timeout', 504);
      }, threshold);

      return true;
    },

    onAjaxComplete: function (event, xhr, settings) {
      if (!settings || !settings.url) {
        return;
      }

      // Check if this was a WooCommerce checkout submission
      if (settings.url.indexOf('wc-ajax=checkout') !== -1) {
        clearTimeout(activeTimer);

        // If response is invalid or server threw 502/504
        if (xhr.status >= 500) {
          Interceptor.triggerCircuitBreaker('server_error', xhr.status);
        }
      }
    },

    onAjaxError: function (event, jqXHR, ajaxSettings, thrownError) {
      if (ajaxSettings && ajaxSettings.url && ajaxSettings.url.indexOf('wc-ajax=checkout') !== -1) {
        clearTimeout(activeTimer);
        Interceptor.triggerCircuitBreaker('ajax_error', jqXHR.status || 0);
      }
    },

    triggerCircuitBreaker: function (reason, status) {
      if (isHandlingFailover) {
        return;
      }
      isHandlingFailover = true;

      var elapsed = Date.now() - (submissionStartTime || Date.now());
      var selectedGateway = $('input[name="payment_method"]:checked').val() || 'unknown';

      // Unblock UI so user is not stuck on infinite spinner
      var $form = $('form.checkout');
      $form.removeClass('processing').unblock();

      // Render non-destructive failover notification
      Interceptor.renderFailoverNotice(selectedGateway, reason);

      // Report telemetry asynchronously
      Interceptor.reportTelemetry(selectedGateway, elapsed, status);
    },

    renderFailoverNotice: function (gateway, reason) {
      Interceptor.clearFailoverNotice();

      var noticeHtml = [
        '<div class="wcfg-failover-card" role="alert">',
        '  <div class="wcfg-failover-header">',
        '    <span class="wcfg-icon">🛡️</span>',
        '    <h4>' + (config.i18n.timeoutWarning || 'Payment Gateway Response Delayed') + '</h4>',
        '  </div>',
        '  <p class="wcfg-failover-desc">',
        '    The secure gateway (' + gateway.toUpperCase() + ') is taking longer than usual to confirm your transaction. No duplicate charge has occurred.',
        '  </p>',
        '  <div class="wcfg-failover-actions">',
        '    <button type="button" class="button alt wcfg-retry-button">' + (config.i18n.retryAction || 'Retry Order') + '</button>',
        '    <button type="button" class="button wcfg-switch-gateway-button">' + (config.i18n.switchGateway || 'Select Another Method') + '</button>',
        '  </div>',
        '</div>'
      ].join('');

      $('form.checkout').prepend(noticeHtml);

      // Smooth scroll to top of checkout form
      $('html, body').animate({
        scrollTop: $('form.checkout').offset().top - 80
      }, 400);
    },

    clearFailoverNotice: function () {
      $('.wcfg-failover-card').remove();
    },

    onRetryClick: function (e) {
      e.preventDefault();
      Interceptor.clearFailoverNotice();
      $('form.checkout').submit();
    },

    onSwitchGatewayClick: function (e) {
      e.preventDefault();
      Interceptor.clearFailoverNotice();
      var $paymentSection = $('#order_review, #payment');
      if ($paymentSection.length) {
        $('html, body').animate({
          scrollTop: $paymentSection.offset().top - 60
        }, 300);
      }
    },

    reportTelemetry: function (gateway, elapsed, status) {
      $.ajax({
        url: config.ajaxUrl,
        type: 'POST',
        data: {
          action: 'wcfg_report_timeout',
          nonce: config.nonce,
          gateway_id: gateway,
          elapsed_ms: elapsed,
          http_status: status
        },
        dataType: 'json'
      });
    }
  };

  $(document).ready(function () {
    Interceptor.init();
  });
})(jQuery);

