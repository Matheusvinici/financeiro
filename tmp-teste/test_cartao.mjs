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

await page.goto('http://127.0.0.1:8000/cartoes', { waitUntil: 'networkidle0' });
await sleep(800);
const primeiroGasto = await page.evaluate(() => {
    const a = document.querySelector('a[href*="forma=cartao"]');
    return a ? a.href : null;
});
console.log('link gasto:', primeiroGasto);
await page.goto(primeiroGasto, { waitUntil: 'networkidle0' });
await sleep(1200);
const pre = await page.evaluate(() => ({
    forma: document.querySelector('select[wire\\:model\\.live="forma_pagamento"]')?.value,
    cartao: document.querySelector('#cartao_div select')?.value,
    categoriaOculta: document.getElementById('categoria_div')?.classList.contains('d-none'),
    pagoOculto: document.getElementById('pago_div')?.classList.contains('d-none'),
    campos: !!document.getElementById('campos-lancamento')
}));
console.log('form pre-configurado:', JSON.stringify(pre));

await page.type('input[wire\\:model="descricao"]', 'TESTE GASTO CARTAO');
await page.type('input[placeholder="R$ 0,00"]', '25000');
await page.select('#cartao_div select', pre.cartao);
await sleep(700);
await page.click('input[wire\\:model="descricao"]');
await sleep(800);
await page.click('#btn-salvar');
await sleep(3500);
console.log('url apos salvar:', page.url());

await page.goto('http://127.0.0.1:8000/pendencias', { waitUntil: 'networkidle0' });
await sleep(800);
const nasPendencias = await page.evaluate(() => document.body.innerText.includes('TESTE GASTO CARTAO'));
console.log('aparece nas pendencias (espera false):', nasPendencias);

await page.goto('http://127.0.0.1:8000/cartoes', { waitUntil: 'networkidle0' });
await sleep(800);
const cartaoTxt = await page.evaluate(() => {
    const h = [...document.querySelectorAll('h5')].find(e => e.textContent.trim() === 'Nubank');
    const card = h?.closest('.card');
    return card ? card.innerText : '';
});
console.log('card Nubank mostra gasto:', cartaoTxt.includes('TESTE GASTO CARTAO') || /R\$ 25[0-9]/.test(cartaoTxt) ? 'sim' : 'nao verificado');
console.log('erros:', errors.length ? errors.join('\n') : 'nenhum');
await browser.close();
