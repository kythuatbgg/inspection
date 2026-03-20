const fs = require('fs');
const path = require('path');

function walk(dir) {
  let results = [];
  const list = fs.readdirSync(dir);
  list.forEach(file => {
    f = dir + '/' + file;
    const stat = fs.statSync(f);
    if (stat && stat.isDirectory()) { 
      results = results.concat(walk(f));
    } else if (f.endsWith('.vue')) {
      results.push(f);
    }
  });
  return results;
}

const files = walk('./src');
const svgRegex = /<svg[^>]*>.*?<\/svg>/gs;
const paths = new Set();
const pathCount = {};

files.forEach(f => {
  const content = fs.readFileSync(f, 'utf8');
  let match;
  while ((match = svgRegex.exec(content)) !== null) {
    const svgContent = match[0];
    // extract all 'd' attributes from paths
    const dRegex = /d="([^"]+)"/g;
    let dMatch;
    let dCombo = [];
    while ((dMatch = dRegex.exec(svgContent)) !== null) {
      dCombo.push(dMatch[1]);
    }
    const key = dCombo.join('||');
    if (key) {
      paths.add(key);
      pathCount[key] = (pathCount[key] || 0) + 1;
    }
  }
});

const output = Array.from(paths).sort((a,b) => pathCount[b] - pathCount[a]).map(p => `${pathCount[p]} times: ${p}`).join('\n');
fs.writeFileSync('svgs.txt', output);
console.log('Saved to svgs.txt');
