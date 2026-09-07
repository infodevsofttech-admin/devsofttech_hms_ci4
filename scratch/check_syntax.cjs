const fs = require('fs');

const html = fs.readFileSync('public/App/MedicalStore/index.html', 'utf8');
const match = html.match(/<script type="text\/babel">([\s\S]*?)<\/script>/);

if (!match) {
    console.log('No <script type="text/babel"> found!');
    process.exit(1);
}

const code = match[1];
fs.writeFileSync('scratch/extracted_app.jsx', code);
console.log(`Extracted script: ${code.length} characters, ${code.split('\n').length} lines.`);
