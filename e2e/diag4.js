const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({ args: ['--ignore-certificate-errors'] });
  const page = await browser.newPage();
  await page.goto('https://b2b-crm.local/dashboard');
  const res = await page.evaluate(async () => {
    const css = await (await fetch('/build/app.302e3bf8.css')).text();
    const hasBB = css.includes('box-sizing:border-box');
    const hasContentBox = /box-sizing:content-box/.test(css);
    const firstIdx = css.indexOf('box-sizing');
    const sample = css.slice(Math.max(0, firstIdx - 60), firstIdx + 60);
    const body = getComputedStyle(document.body).boxSizing;
    return { cssLen: css.length, hasBB, hasContentBox, sample, bodyBoxSizing: body };
  });
  console.log(JSON.stringify(res, null, 1));
  await browser.close();
})();