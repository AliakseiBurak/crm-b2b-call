const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({ args: ['--ignore-certificate-errors'] });
  const page = await browser.newPage();
  await page.goto('https://b2b-crm.local/dashboard');
  const res = await page.evaluate(() => {
    const out = [];
    for (const sheet of document.styleSheets) {
      try {
        const rules = [...sheet.cssRules].filter(r => /box-sizing/.test(r.cssText));
        out.push({ href: sheet.href, ruleCount: sheet.cssRules.length, boxRules: rules.map(r => r.cssText.slice(0, 120)) });
      } catch (e) { out.push({ href: sheet.href, error: String(e) }); }
    }
    return out;
  });
  console.log(JSON.stringify(res, null, 1));
  await browser.close();
})();