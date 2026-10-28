const fs = require('fs');
const path = require('path');
const vm = require('vm');

const rootDir = path.resolve(__dirname, '..');
const babelPath = path.join(rootDir, 'public', 'assets', 'vendor', 'react', 'babel.min.js');

console.log('Loading Babel standalone from:', babelPath);
const babelCode = fs.readFileSync(babelPath, 'utf8');

const context = {
    window: {},
    self: {},
    console: console,
    Array,
    Object,
    Math,
    Date,
    RegExp,
    String,
    Number,
    Boolean,
    Error,
    parseFloat,
    parseInt,
    isNaN,
    isFinite
};
context.globalThis = context;
vm.createContext(context);
vm.runInContext(babelCode, context);
const Babel = context.Babel || context.window.Babel;

if (!Babel) {
    console.error('Failed to initialize Babel');
    process.exit(1);
}
console.log('Babel standalone initialized successfully. Version:', Babel.version);

const apps = [
    { name: 'Doctor', dir: path.join(rootDir, 'public', 'App', 'Doctor'), bundleName: 'doctor-app.bundle.js' },
    { name: 'Nursing', dir: path.join(rootDir, 'public', 'App', 'Nursing'), bundleName: 'nursing-app.bundle.js' },
    { name: 'MedicalStore', dir: path.join(rootDir, 'public', 'App', 'MedicalStore'), bundleName: 'medicalstore-app.bundle.js' }
];

for (const app of apps) {
    const htmlPath = path.join(app.dir, 'index.html');
    if (!fs.existsSync(htmlPath)) {
        console.warn(`HTML file not found: ${htmlPath}`);
        continue;
    }
    console.log(`\n--- Processing ${app.name} ---`);
    const htmlContent = fs.readFileSync(htmlPath, 'utf8');
    const match = htmlContent.match(/<script type="text\/babel">([\s\S]*?)<\/script>/);
    let jsxSource = '';
    if (match) {
        jsxSource = match[1];
    } else {
        console.log(`No <script type="text/babel"> in current HTML, fetching from git history (c548c0d~1)...`);
        try {
            const { execSync } = require('child_process');
            const gitCmd = `git show c548c0d~1:public/App/${app.name}/index.html`;
            const gitHtml = execSync(gitCmd, { maxBuffer: 50 * 1024 * 1024 }).toString('utf8');
            const gitMatch = gitHtml.match(/<script type="text\/babel">([\s\S]*?)<\/script>/);
            if (gitMatch) {
                jsxSource = gitMatch[1];
            }
        } catch (e) {
            console.warn(`Could not read git history for ${app.name}: ${e.message}`);
        }
    }

    if (!jsxSource) {
        console.warn(`Could not find JSX source for ${app.name}`);
        continue;
    }

    console.log(`Found JSX code in ${app.name}, size: ${(jsxSource.length / 1024).toFixed(2)} KB`);

    console.log(`Compiling JSX with Babel presets: [['react', { runtime: 'classic' }]]...`);
    const startTime = Date.now();
    const result = Babel.transform(jsxSource, {
        presets: [['react', { runtime: 'classic' }]],
        compact: false
    });
    console.log(`Compilation finished in ${Date.now() - startTime}ms. Output JS size: ${(result.code.length / 1024).toFixed(2)} KB`);

    const bundlePath = path.join(app.dir, app.bundleName);
    fs.writeFileSync(bundlePath, result.code, 'utf8');
    console.log(`Saved bundle to: ${bundlePath}`);
}

console.log('\nAll PWA apps precompiled successfully!');
