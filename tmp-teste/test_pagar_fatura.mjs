import puppeteer from 'puppeteer-core';
const sleep = ms => new Promise(r => setTimeout(r, ms));
const browser = await puppeteer.launch({ executablePath: '/usr/bin/google-chrome', headless: 'new', args: ['--no-sandbox'] });
const page = await browser.newPage();
const errors = [];
page.on('pageerror', e => errors.push('PAGEERROR: ' + e.message));
await page.goto('http://127.0.0.1:8000/login', { waitUntil: 'networkidle0' });
await page.type('input[name="email"]', 'matheus2vandrade@gmail.com');
await page.type('input[name="password"]', 'Carpediem1996#');
await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), page.click('button[type="submit"]')]);

await page.goto('http://127.0.0.1:8000/pendencias?mes=8&ano=2026', { waitUntil: 'networkidle0' });
await sleep(500);
console.log('antes:');
const antes = await page.evaluate(() => [...document.querySelectorAll('table tbody tr')].map(tr => tr.innerText.replace(/\s+/g, ' ').trim()));
antes.forEach(r => console.log('  row:', r));

const form = await page.evaluate(() => {
    const f = [...document.querySelectorAll('form')].find(fm => fm.action.includes('/fatura/'));
    if (!f) return null;
    const mes = new FormData(f).get('mes');
    const ano = new FormData(f).get('ano');
    return { mes, ano };
});
console.log('form posts mes/ano:', JSON.stringify(form));

await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), page.evaluate(() => {
    [...document.querySelectorAll('form')].find(fm => fm.action.includes('/fatura/')).submit();
})]);
await sleep(500);
console.log('depois de pagar (mes 8):');
const depois = await page.evaluate(() => [...document.querySelectorAll('table tbody tr')].map(tr => tr.innerText.replace(/\s+/g, ' ').trim()));
depois.forEach(r => console.log('  row:', r));
const stats = await page.evaluate(() => [...document.querySelectorAll('.stat-card')].map(c => c.innerText.replace(/\s+/g, ' ').trim()));
console.log('stats:', JSON.stringify(stats));

console.log('erros:', errors.length ? errors.join('\n') : 'nenhum');
await browser.close();
