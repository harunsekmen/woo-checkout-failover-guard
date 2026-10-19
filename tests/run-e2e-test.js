
const { chromium } = require('playwright');
const path = require('path');
const fs = require('fs');

(async () => {
  const browser = await chromium.launch({
    headless: true,
    executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome'
  });
  const context = await browser.newContext({
    viewport: { width: 1200, height: 820 },
    deviceScaleFactor: 2
  });
  const page = await context.newPage();

  const docsDir = path.join(__dirname, '..', 'docs', 'assets');
  if (!fs.existsSync(docsDir)) {
    fs.mkdirSync(docsDir, { recursive: true });
  }

  // 1. Test & Capture Checkout Simulation
  const checkoutUrl = 'file://' + path.join(__dirname, 'checkout-simulation.html');
  console.log('Navigating to:', checkoutUrl);
  await page.goto(checkoutUrl);

  // Prevent file:// page reload on form submit
  await page.evaluate(() => {
    jQuery('form.checkout').on('submit', function (e) {
      e.preventDefault();
    });
  });

  // Trigger checkout submit to invoke the interceptor circuit breaker
  await page.click('#place_order');
  console.log('Clicked place order. Waiting for circuit breaker timeout trigger...');

  // Wait for the failover card to appear in the DOM
  await page.waitForSelector('.wcfg-failover-card', { timeout: 4000 });
  console.log('Circuit breaker activated! Card is rendered in DOM.');

  // Small delay for CSS slide-down animation to settle
  await new Promise(r => setTimeout(r, 400));

  // Take screenshot of the checkout failover card in action
  const checkoutScreenshot = path.join(docsDir, 'checkout-failover-preview.png');
  await page.screenshot({ path: checkoutScreenshot, fullPage: false });
  console.log('Saved checkout screenshot to:', checkoutScreenshot);

  // 2. Test & Capture Admin Dashboard Simulation
  const adminUrl = 'file://' + path.join(__dirname, 'admin-simulation.html');
  await page.goto(adminUrl);
  await page.waitForSelector('.postbox');

  const adminScreenshot = path.join(docsDir, 'admin-telemetry-preview.png');
  await page.screenshot({ path: adminScreenshot, fullPage: false });
  console.log('Saved admin telemetry screenshot to:', adminScreenshot);

  await browser.close();
  console.log('ALL TESTS PASSED: E2E Simulation verified and screenshots generated successfully.');
})();

