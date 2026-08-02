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

await page.goto('http://127.0.0.1:8000/lancamentos/create', { waitUntil: 'networkidle0' });
await sleep(1000);

const antes = await page.evaluate(() => ({
    botaoCartao: !!document.getElementById('forma-cartao'),
    botaoOutra: !!document.querySelector('button[wire\\:click*="forma_pagamento"]'),
    cartaoVisivel: !document.getElementById('cartao_div')?.classList.contains('d-none'),
}));
console.log('fluxo direto (antes):', JSON.stringify(antes));

await page.evaluate(() => document.getElementById('tipo-despesa').click());
await sleep(800);
const depoisDespesa = await page.evaluate(() => ({
    botaoCartao: !!document.getElementById('forma-cartao'),
    cartaoVisivel: !document.getElementById('cartao_div')?.classList.contains('d-none'),
    formaSelect: !!document.getElementById('forma_div'),
}));
console.log('apos Despesa:', JSON.stringify(depoisDespesa));

await page.evaluate(() => document.getElementById('forma-cartao').click());
await sleep(1000);
const depoisCartao = await page.evaluate(() => ({
    cartaoVisivel: !document.getElementById('cartao_div')?.classList.contains('d-none'),
    tagVisivel: document.getElementById('categoria_div')?.innerText.includes('Gasto com cartão'),
    pagoOculto: document.getElementById('pago_div')?.classList.contains('d-none'),
    itemVisivel: !!document.getElementById('campo-item'),
    formaOculto: document.getElementById('forma_div')?.classList.contains('d-none'),
    cartaoSelect: document.getElementById('cartao_div')?.querySelector('select')?.value,
}));
console.log('apos Gasto com cartao:', JSON.stringify(depoisCartao));

await page.evaluate(() => [...document.querySelectorAll('button')].find(b => b.textContent.includes('Outra forma')).click());
await sleep(1000);
const depoisOutra = await page.evaluate(() => ({
    cartaoOculto: document.getElementById('cartao_div')?.classList.contains('d-none'),
    formaVisivel: !document.getElementById('forma_div')?.classList.contains('d-none'),
    pagoVisivel: !document.getElementById('pago_div')?.classList.contains('d-none'),
    itemVisivel: !!document.getElementById('campo-item'),
}));
console.log('apos Outra forma:', JSON.stringify(depoisOutra));

console.log('erros:', errors.length ? errors.join('\n') : 'nenhum');
await browser.close();
