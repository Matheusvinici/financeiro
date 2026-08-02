import puppeteer from 'puppeteer-core';
const sleep = ms => new Promise(r => setTimeout(r, ms));
const browser = await puppeteer.launch({ executablePath: '/usr/bin/google-chrome', headless: 'new', args: ['--no-sandbox'] });
const page = await browser.newPage();
const errors = [];
page.on('pageerror', e => errors.push('PAGEERROR: ' + e.message));
page.on('console', m => { if (m.type() === 'error') errors.push('CONSOLE: ' + m.text()); });
page.on('requestfailed', r => errors.push('REQFAIL: ' + r.url() + ' ' + (r.failure()?.errorText || '')));
await page.goto('http://127.0.0.1:8000/login', { waitUntil: 'networkidle0' });
await page.type('input[name="email"]', 'matheus2vandrade@gmail.com');
await page.type('input[name="password"]', 'Carpediem1996#');
await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), page.click('button[type="submit"]')]);
await page.goto('http://127.0.0.1:8000/dashboard', { waitUntil: 'networkidle0' });
await sleep(1000);
await page.select('select[wire\\:model\\.live="mes"]', '8');
await sleep(2500);
const state = await page.evaluate(() => ({
    url: location.href,
    corpo: document.body.innerText.slice(0, 300)
}));
console.log('apos selecionar mes 8:', JSON.stringify(state));
console.log('erros:', errors.length ? errors.join('\n---\n') : 'nenhum');
await browser.close();
