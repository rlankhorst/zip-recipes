const puppeteer = require('puppeteer');
const argv = require('yargs').argv;

if (! (argv.url || argv.desc)) {
    console.log('Missing --desc and/or --url');
    process.exit(2);
}

let descSlug = argv.desc.split(' ').join('-');
let filename = `./screenshots/${descSlug}-screenshot.png`;

(async () => {
  const browser = await puppeteer.launch();
  const page = await browser.newPage();
  await page.goto(argv.url, {waitUntil: 'networkidle2'});
  await page.screenshot({path: filename, fullPage: true});
  console.log(`Saved to: ${filename}`);

  await browser.close();
})();