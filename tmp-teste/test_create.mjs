import puppeteer from 'puppeteer-core';
const sleep = ms => new Promise(r => setTimeout(r, ms));
const browser = await puppeteer.launch({ executablePath: '/usr/bin/google-chrome', headless: 'new', args: ['--no-sandbox'] });
const page = await browser.newPage();
const errors = [];
page.on('pageerror', e => errors.push('PAGEERROR: ' + e.message));
page.on('console', m => { if (m.type() === 'error') errors.push('CONSOLE: ' + m.text()); });
await page.goto('http://127.0.0.1:8000/login', { waitUntil: 'networkidle0' });
await page.type('input[name="email"]', 'matheus2vandrade@gmail.com');
await page.type('input[name="password"]', 'Carpediem1996#');
await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), page.click('button[type="submit"]')]);
await page.goto('http://127.0.0.1:8000/lancamentos/create', { waitUntil: 'networkidle0' });
await sleep(700);

const estado = () => page.evaluate(() => ({
    campos: !!document.getElementById('campos-lancamento'),
    tipoDespChecked: document.getElementById('tipo-despesa')?.checked,
    tipoRecChecked: document.getElementById('tipo-receita')?.checked,
    itemSelect: !!document.querySelector('select[wire\\:model="subcategoria_id"]'),
    forma: !!document.querySelector('select[wire\\:model\\.live="forma_pagamento"]'),
    categorias: [...document.querySelectorAll('#categoria_id option')].filter(o => o.value).map(o => o.textContent)
}));

console.log('inicial:', JSON.stringify(await estado()));

await page.click('#tipo-receita');
await sleep(1200);
console.log('receita:', JSON.stringify(await estado()));

await page.click('#tipo-despesa');
await sleep(1200);
await page.select('#categoria_id', '27');
await sleep(1200);
console.log('despesa CARTÕES (sem itens):', JSON.stringify(await estado()));

await page.select('#categoria_id', '26');
await sleep(1200);
const comItens = await page.evaluate(() => ({
    itemSelect: !!document.querySelector('select[wire\\:model="subcategoria_id"]'),
    itens: [...document.querySelectorAll('select[wire\\:model="subcategoria_id"] option')].filter(o => o.value).map(o => o.textContent)
}));
console.log('despesa CARRO (com itens):', JSON.stringify(comItens));

const valorInput = await page.$('input[wire\\:model="valor"]');
await valorInput.click({ clickCount: 3 });
await valorInput.type('98765');
await sleep(900);
const v = await page.evaluate(() => document.querySelector('input[wire\\:model="valor"]').value);
console.log('valor digitado 98765 ->', v);
console.log('erros:', errors.length ? errors.join('\n') : 'nenhum');
await browser.close();
