import puppeteer from 'puppeteer-core';
const sleep = ms => new Promise(r => setTimeout(r, ms));
const browser = await puppeteer.launch({ executablePath: '/usr/bin/google-chrome', headless: 'new', args: ['--no-sandbox'] });
const page = await browser.newPage();
const errors = [];
page.on('dialog', d => { console.log('DIALOG:', d.message()); d.accept(); });
page.on('response', async r => {
    if (r.url().includes('contas-pagar')) {
        console.log('HTTP', r.status(), r.request().method(), r.request().url());
    }
});
await page.goto('http://127.0.0.1:8000/login', { waitUntil: 'networkidle0' });
await page.type('input[name="email"]', 'matheus2vandrade@gmail.com');
await page.type('input[name="password"]', 'Carpediem1996#');
await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), page.click('button[type="submit"]')]);

await page.goto('http://127.0.0.1:8000/contas-pagar', { waitUntil: 'networkidle0' });
await sleep(1000);
console.log('tem TESTE EXCLUSAO na lista:', await page.evaluate(() => document.body.innerText.includes('TESTE EXCLUSAO')));

const del = await page.evaluate(() => {
    const row = [...document.querySelectorAll('tr')].find(r => r.innerText.includes('TESTE EXCLUSAO'));
    if (!row) return 'sem linha';
    const forms = [...row.querySelectorAll('form')];
    const form = forms.find(f => f.action.includes('/contas-pagar/') && !f.action.endsWith('/pagar'));
    if (!form) return 'sem form delete';
    form.querySelector('button')?.click();
    return 'clicado';
});
console.log('delete:', del);
await sleep(3000);
console.log('ainda na lista:', await page.evaluate(() => document.body.innerText.includes('TESTE EXCLUSAO')));
console.log('erros:', errors.length ? errors.join('\n') : 'nenhum');
await browser.close();
