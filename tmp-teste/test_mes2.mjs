import puppeteer from 'puppeteer-core';
const sleep = ms => new Promise(r => setTimeout(r, ms));
const browser = await puppeteer.launch({ executablePath: '/usr/bin/google-chrome', headless: 'new', args: ['--no-sandbox'] });
const page = await browser.newPage();
let respBody = null;
page.on('response', async r => {
    if (r.url().includes('/livewire/update')) {
        respBody = await r.text().catch(() => null);
    }
});
await page.goto('http://127.0.0.1:8000/login', { waitUntil: 'networkidle0' });
await page.type('input[name="email"]', 'matheus2vandrade@gmail.com');
await page.type('input[name="password"]', 'Carpediem1996#');
await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), page.click('button[type="submit"]')]);
await page.goto('http://127.0.0.1:8000/dashboard', { waitUntil: 'networkidle0' });
await sleep(1000);
await page.select('select[wire\\:model\\.live="mes"]', '8');
await sleep(2500);
if (respBody) {
    const idx = respBody.indexOf('setUnit');
    console.log(respBody.slice(Math.max(0, idx - 1500), idx + 400));
} else {
    console.log('sem resposta livewire capturada');
}
await browser.close();
