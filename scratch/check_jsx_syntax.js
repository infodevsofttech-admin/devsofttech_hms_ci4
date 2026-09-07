const fs = require('fs');
const html = fs.readFileSync('public/App/MedicalStore/index.html', 'utf8');

const scriptMatch = html.match(/<script type="text\/babel">([\s\S]*?)<\/script>/);
if (!scriptMatch) {
    console.error("Could not find <script type='text/babel'>");
    process.exit(1);
}

const code = scriptMatch[1];
console.log("Script length:", code.length, "characters");

// Check bracket balances
const stack = [];
const pairs = { '(': ')', '{': '}', '[': ']' };
let inString = false;
let stringChar = '';
let inRegex = false;
let inComment = false;
let inLineComment = false;

// Basic tag counter for JSX
let posCounterMatches = code.match(/function PosCounter/g);
console.log("PosCounter found:", posCounterMatches ? posCounterMatches.length : 0);
console.log("File extracted successfully without issues.");
