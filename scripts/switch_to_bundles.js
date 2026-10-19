const fs = require('fs');
const path = require('path');

const rootDir = path.resolve(__dirname, '..');

const apps = [
    { name: 'Doctor', dir: path.join(rootDir, 'public', 'App', 'Doctor'), bundleName: 'doctor-app.bundle.js' },
    { name: 'Nursing', dir: path.join(rootDir, 'public', 'App', 'Nursing'), bundleName: 'nursing-app.bundle.js' },
    { name: 'MedicalStore', dir: path.join(rootDir, 'public', 'App', 'MedicalStore'), bundleName: 'medicalstore-app.bundle.js' }
];

for (const app of apps) {
    const htmlPath = path.join(app.dir, 'index.html');
    let content = fs.readFileSync(htmlPath, 'utf8');

    // 1. Remove unsafe-eval from meta CSP
    content = content.replace(/'unsafe-eval'\s*/g, '');

    // 2. Remove babel.min.js script tag
    content = content.replace(/\s*<script src="\/assets\/vendor\/react\/babel\.min\.js"><\/script>/g, '');

    // 3. Replace <script type="text/babel">...</script> with the bundle script
    const babelStart = content.indexOf('<script type="text/babel">');
    if (babelStart !== -1) {
        const babelEnd = content.indexOf('</script>', babelStart);
        if (babelEnd !== -1) {
            const before = content.substring(0, babelStart);
            const after = content.substring(babelEnd + '</script>'.length);
            content = before + `<script src="/App/${app.name}/${app.bundleName}"></script>` + after;
            console.log(`Updated ${app.name}/index.html to use ${app.bundleName}`);
        } else {
            console.warn(`Could not find closing </script> for ${app.name}`);
        }
    } else {
        console.log(`${app.name}/index.html already updated`);
    }

    fs.writeFileSync(htmlPath, content, 'utf8');
}

console.log('All PWA index.html files switched to precompiled bundles without Babel or unsafe-eval.');
