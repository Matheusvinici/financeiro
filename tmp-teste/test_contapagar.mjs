import puppeteer from 'puppeteer-core';
const sleep = ms => new Promise(r => setTimeout(r, ms));
const browser = await puppeteer.launch({ executablePath: '/usr/bin/google-chrome', headless: 'new', args: ['--no-sandbox'] });
const page = await browser.newPage();
page.on('pageerror', e => console.log('PAGEERROR:', e.message));
await page.goto('http://127.0.0.1:8000/login', { waitUntil: 'networkidle0' });
await page.type('input[name="email"]', 'matheus2vandrade@gmail.com');
await page.type('input[name="password"]', 'Carpediem1996#');
await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), page.click('button[type="submit"]')]);

await page.goto('http://127.0.0.1:8000/contas-pagar', { waitUntil: 'networkidle0' });
await sleep(1000);

const formExist = await page.evaluate(() => !!document.querySelector('form[action*="contas-pagar"][onsubmit*="Excluir"]') || [...document.forms].some(f => f.action.includes('contas-pagar')));
console.log('tem formulario de criar/excluir:', formExist);

const antes = await page.evaluate(() => document.body.innerText.includes('TESTE EXCLUSAO'));
console.log('conta teste ja existe:', antes);

const descInput = await page.evaluate(() => {
    const inp = document.querySelector('input[name="descricao"]');
    const val = document.querySelector('input[name="valor_total"]');
    if (inp && val) {
        inp.value = 'TESTE EXCLUSAO'; inp.dispatchEvent(new Event('input', { bubbles: true }));
        val.value = '10'; val.dispatchEvent(new Event('input', { bubbles: true }));
        return true;
    }
    return false;
});
console.log('preencheu descricao:', descInput);
if (descInput) {
    await sleep(300);
    const result = await page.evaluate(() => {
        const form = document.querySelector('form[action*="contas-pagar"]');
        if (!form) return 'sem form';
        const subm = form.querySelector('button[type="submit"]');
        subm?.click();
        return 'clicado';
    });
    console.log('submit:', result);
    await sleep(2500);
}
const criou = await page.evaluate(() => document.body.innerText.includes('TESTE EXCLUSAO'));
console.log('apareceu na lista:', criou);

if (criou) {
    const del = await page.evaluate(() => {
        const row = [...document.querySelectorAll('tr')].find(r => r.innerText.includes('TESTE EXCLUSAO'));
        const form = row?.querySelector('form[onsubmit*="Excluir"]') || [...(row?.querySelectorAll('form') || [])].find(f => (f.method || '').toLowerCase() === 'post' || (f.innerHTML.includes('_method') && f.innerHTML.includes('delete')));
        if (form) { form.querySelector('button')?.click(); return 'clicado'; }
        return 'sem form delete';
    });
    console.log('delete:', del);
    await sleep(2500);
    const sumiu = await page.evaluate(() => !document.body.innerText.includes('TESTE EXCLUSAO'));
    console.log('excluida com sucesso:', sumiu);
}
await browser.close();
