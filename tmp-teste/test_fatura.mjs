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

const pre = await page.evaluate(() => ({
    cartaoVisivel: !document.getElementById('cartao_div')?.classList.contains('d-none'),
    tagVisivel: document.getElementById('categoria_div')?.innerText.includes('Gasto com cartão'),
    cartaoSelect: document.getElementById('cartao_div')?.querySelector('select')?.value,
}));
console.log('pre:', JSON.stringify(pre));

await page.type('input[wire\\:model="descricao"]', 'TESTE FATURA CARTAO');
await page.type('input[placeholder="R$ 0,00"]', '25000');
await page.select('#cartao_div select', '9');
await sleep(700);
await page.click('input[wire\\:model="descricao"]');
await sleep(800);
await page.click('#btn-salvar');
await sleep(3500);
console.log('url apos salvar:', page.url());

await page.goto('http://127.0.0.1:8000/pendencias', { waitUntil: 'networkidle0' });
await sleep(1000);
const pos = await page.evaluate(() => {
    const linhas = [...document.querySelectorAll('tbody tr')].map(r => r.innerText);
    return {
        temSecao: document.body.innerText.includes('Faturas de cartão'),
        linhaFatura: linhas.find(l => l.includes('Bola')),
        todasLinhas: linhas.slice(0, 8),
    };
});
console.log('pendencias:', JSON.stringify(pos, null, 1));

const btnPagar = await page.evaluate(() => {
    const tr = [...document.querySelectorAll('tbody tr')].find(r => r.innerText.includes('Bola'));
    if (!tr) return null;
    const form = tr.querySelector('form[action*="fatura"]');
    form?.querySelector('button')?.click();
    return !!form;
});
console.log('clicou pagar fatura:', btnPagar);
if (btnPagar) {
    await sleep(3000);
    const apos = await page.evaluate(() => {
        const txt = document.body.innerText;
        return {
            faturaPaga: txt.includes('Fatura paga'),
            semBotao: ![...document.querySelectorAll('button')].some(b => b.textContent.includes('Pagar fatura')),
        };
    });
    console.log('apos pagar fatura:', JSON.stringify(apos));
}

console.log('erros:', errors.length ? errors.join('\n') : 'nenhum');
await browser.close();
