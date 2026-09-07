const fs = require('fs');

// Load babel
const babelCode = fs.readFileSync('scratch/babel.min.js', 'utf8');
const vm = require('vm');
const sandbox = { window: {}, console: console };
vm.createContext(sandbox);
vm.runInContext(babelCode, sandbox);

const Babel = sandbox.Babel || sandbox.window.Babel;
console.log('Babel version:', Babel.version);

const code = fs.readFileSync('scratch/extracted_app.jsx', 'utf8');

try {
    const output = Babel.transform(code, {
        presets: ['react', 'env']
    });
    console.log('Babel successfully compiled!', output.code.length, 'bytes');
} catch (err) {
    console.error('Babel compilation ERROR:');
    console.error(err.message);
    if (err.loc) {
        console.error('Location:', err.loc);
        const lines = code.split('\n');
        const start = Math.max(0, err.loc.line - 5);
        const end = Math.min(lines.length, err.loc.line + 5);
        for (let i = start; i < end; i++) {
            console.error(`${i + 1}: ${lines[i]}`);
        }
    }
}
