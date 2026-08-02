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
await page.evaluate(() => document.getElementById('tipo-despesa').click());
await sleep(800);
await page.evaluate(() => document.getElementById('forma-cartao').click());
await sleep(1000);

const radio = await page.evaluate(() => ({
    credito: !!document.getElementById('cartao-credito'),
    debito: !!document.getElementById('cartao-debito'),
    tagCredito: document.getElementById('categoria_div')?.innerText.includes('Gasto com cartão'),
}));
console.log('radio credito/debito:', JSON.stringify(radio));

await page.evaluate(() => document.getElementById('cartao-debito').click());
await sleep(1000);
const depoisDebito = await page.evaluate(() => ({
    tagSumiu: !document.getElementById('categoria_div')?.innerText.includes('Gasto com cartão'),
    selectCategoria: !!document.getElementById('categoria_id'),
    pagoOculto: document.getElementById('pago_div')?.classList.contains('d-none'),
}));
console.log('apos debito:', JSON.stringify(depoisDebito));

await page.select('#categoria_id', '27');
await sleep(700);
await page.type('input[wire\\:model="descricao"]', 'TESTE DEBITO CARTAO');
await page.type('input[placeholder="R$ 0,00"]', '12000');
await page.select('#cartao_div select', '9');
await sleep(700);
await page.click('input[wire\\:model="descricao"]');
await sleep(800);
await page.click('#btn-salvar');
await sleep(3500);
console.log('url debito apos salvar:', page.url());

await page.goto('http://127.0.0.1:8000/pendencias', { waitUntil: 'networkidle0' });
await sleep(1000);
const pos = await page.evaluate(() => {
    const linhas = [...document.querySelectorAll('tbody tr')].map(r => r.innerText);
    return {
        debitoEmPendencia: linhas.some(l => l.includes('TESTE DEBITO CARTAO')),
        debitoEmPagas: linhas.some(l => l.includes('TESTE DEBITO CARTAO')),
        linhasFatura: linhas.filter(l => l.includes('fatura')),
    };
});
console.log('pendencias pos debito:', JSON.stringify(pos, null, 1));

await page.goto('http://127.0.0.1:8000/cartoes', { waitUntil: 'networkidle0' });
await sleep(1000);
const cartaoPage = await page.evaluate(() => {
    const h = [...document.querySelectorAll('h5')].find(e => e.textContent.includes('Bola'));
    return h?.closest('.card')?.innerText ?? '';
});
console.log('cartao Bola (debito deve NAO somar):', cartaoPage.split('\n').slice(0, 14).join(' | '));

console.log('erros:', errors.length ? errors.join('\n') : 'nenhum');
await browser.close();
