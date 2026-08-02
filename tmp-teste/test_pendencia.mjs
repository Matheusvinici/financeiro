import puppeteer from 'puppeteer-core';
const sleep = ms => new Promise(r => setTimeout(r, ms));
const browser = await puppeteer.launch({ executablePath: '/usr/bin/google-chrome', headless: 'new', args: ['--no-sandbox'] });
const page = await browser.newPage();
const errors = [];
page.on('pageerror', e => errors.push('PAGEERROR: ' + e.message));
page.on('console', m => { if (m.type() === 'error' && !m.text().includes('setAttribute')) errors.push('CONSOLE: ' + m.text()); });

await page.goto('http://127.0.0.1:8000/login', { waitUntil: 'networkidle0' });
await page.type('input[name="email"]', 'matheus2vandrade@gmail.com');
await page.type('input[name="password"]', 'Carpediem1996#');
await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), page.click('button[type="submit"]')]);

// Criar despesa NÃO paga (futura) — fatura do cartão
await page.goto('http://127.0.0.1:8000/lancamentos/create', { waitUntil: 'networkidle0' });
await sleep(700);
await page.click('#tipo-despesa');
await sleep(1000);
await page.type('input[wire\\:model="descricao"]', 'TESTE FATURA NUBANK');
await page.type('input[placeholder="R$ 0,00"]', '50000');
await sleep(500);
const checks = await page.$$('input[type="checkbox"]');
console.log('checkboxes encontrados:', checks.length);
if (checks.length >= 2) {
    await checks[0].click({ clickCount: 1 }).catch(() => {}); // pago -> false
    await page.click('#campo_pago');
}
await page.click('#campo_pago'); // desmarca "já pago"
await sleep(500);
const pagoChecked = await page.evaluate(() => document.getElementById('campo_pago').checked);
const abateChecked = await page.evaluate(() => document.getElementById('campo_abate').checked);
console.log('pago checked (espera false):', pagoChecked, '| abate checked (espera true):', abateChecked);
await page.click('#campo_abate'); // desmarca "abate" -> nao abate
await sleep(500);
await page.select('#categoria_id', '27');
await page.select('select[wire\\:model\\.live="forma_pagamento"]', 'cartao');
await sleep(700);
await page.click('input[wire\\:model="descricao"]');
await sleep(800);
await page.click('#btn-salvar');
await page.waitForNavigation({ waitUntil: 'networkidle0' }).catch(() => {});
await sleep(1500);
console.log('url apos salvar:', page.url());

// Ver pendências
await page.goto('http://127.0.0.1:8000/pendencias', { waitUntil: 'networkidle0' });
await sleep(1000);
const pend = await page.evaluate(() => document.body.innerText.includes('TESTE FATURA NUBANK'));
console.log('aparece nas pendencias:', pend);
const stats = await page.evaluate(() => [...document.querySelectorAll('.stat-value')].map(e => e.textContent.trim()));
console.log('cards:', JSON.stringify(stats));

console.log('erros:', errors.length ? errors.join('\n') : 'nenhum');
await browser.close();
