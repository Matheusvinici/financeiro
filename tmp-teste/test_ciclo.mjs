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

for (const [mes, ano] of [[8, 2026], [9, 2026], [10, 2026]]) {
    await page.goto(`http://127.0.0.1:8000/pendencias?mes=${mes}&ano=${ano}`, { waitUntil: 'networkidle0' });
    await sleep(500);
    const dados = await page.evaluate(() => {
        const rows = [...document.querySelectorAll('table tbody tr')].map(tr => tr.innerText.replace(/\s+/g, ' ').trim());
        const stats = [...document.querySelectorAll('.stat-card')].map(c => c.innerText.replace(/\s+/g, ' ').trim());
        return { stats, rows };
    });
    console.log(`--- mes ${mes}/${ano} ---`);
    console.log('stats:', JSON.stringify(dados.stats));
    dados.rows.forEach(r => console.log('  row:', r));
}

console.log('erros:', errors.length ? errors.join('\n') : 'nenhum');
await browser.close();
