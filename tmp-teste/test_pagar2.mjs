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
console.log('ANTES (mes 8):');
(await page.evaluate(() => [...document.querySelectorAll('table tbody tr')].map(tr => tr.innerText.replace(/\s+/g, ' ').trim()))).forEach(r => console.log('  ', r));

await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), page.evaluate(() => {
    [...document.querySelectorAll('form')].find(fm => fm.action.endsWith('/fatura')).submit();
})]);
await sleep(500);
console.log('DEPOIS (mes 8):');
(await page.evaluate(() => [...document.querySelectorAll('table tbody tr')].map(tr => tr.innerText.replace(/\s+/g, ' ').trim()))).forEach(r => console.log('  ', r));

await page.goto('http://127.0.0.1:8000/pendencias?mes=9&ano=2026', { waitUntil: 'networkidle0' });
await sleep(500);
console.log('MES 9:');
(await page.evaluate(() => [...document.querySelectorAll('table tbody tr')].map(tr => tr.innerText.replace(/\s+/g, ' ').trim()))).forEach(r => console.log('  ', r));

console.log('erros:', errors.length ? errors.join('\n') : 'nenhum');
await browser.close();
