const fs = require('fs');

const iconMap = {
  'M15 19l-7-7 7-7': 'ChevronLeft',
  'M6 18L18 6M6 6l12 12': 'X',
  'M9 5l7 7-7 7': 'ChevronRight',
  'M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z': 'Loader2',
  'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16': 'Trash2',
  'M5 13l4 4L19 7': 'Check',
  'M12 4v16m8-8H4': 'Plus',
  'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z||M15 11a3 3 0 11-6 0 3 3 0 016 0z': 'MapPin',
  'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z': 'ShieldCheck',
  'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z': 'Search',
  'M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6': 'Undo2',
  'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z': 'FileEdit',
  'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10': 'FileStack',
  'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z': 'Calendar',
  'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z': 'AlertTriangle',
  'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4': 'ListTodo',
  'M15 12a3 3 0 11-6 0 3 3 0 016 0z||M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z': 'Eye',
  'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z': 'Clock',
  'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z': 'UserPlus',
  'M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z': 'Loader2',
  'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15': 'RefreshCw',
  'M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z||M15 13a3 3 0 11-6 0 3 3 0 016 0z': 'Camera',
  'M4 6h16M4 12h16M4 18h16': 'Menu',
  'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12': 'ArrowUpToLine',
  'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4': 'ArrowDownToLine',
  'M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4': 'Server',
  'M11 19l-7-7 7-7m8 14l-7-7 7-7': 'ChevronsLeft',
  'M13 5l7 7-7 7M5 5l7 7-7 7': 'ChevronsRight',
  'M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z': 'FileDown',
  'M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4': 'Save',
  'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m-3-3h6': 'ZoomIn',
  'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m-3-3h6M10 10V4m-3 3h6': 'ZoomOut',
  'M19 9l-7 7-7-7': 'ChevronDown',
  'M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z': 'AlertCircle',
  'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z': 'Image',
  'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z': 'AlertCircle',
  'M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z': 'Settings',
  'M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z': 'Settings',
  'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z': 'Lock',
  'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z': 'Info',
  'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4': 'Building2',
  'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z': 'XCircle',
  'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z': 'Users',
  'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2': 'ClipboardList',
  'M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21': 'EyeOff'
};

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
const svgRegex = /<svg([^>]*)>.*?<\/svg>/gs;

files.forEach(f => {
  let content = fs.readFileSync(f, 'utf8');
  let originalContent = content;
  let componentsNeeded = new Set();
  
  let match;
  let newContent = content;
  const tempContent = content;
  
  let svgMatches = [];
  while ((match = svgRegex.exec(tempContent)) !== null) {
      svgMatches.push(match);
  }
  
  // Replace from bottom to top to preserve indices
  for (let i = svgMatches.length - 1; i >= 0; i--) {
      const match = svgMatches[i];
      const svgContent = match[0];
      const svgAttrsRaw = match[1]; // Attributes string
      
      const dRegex = /d="([^"]+)"/g;
      let dMatch;
      let dCombo = [];
      while ((dMatch = dRegex.exec(svgContent)) !== null) {
          dCombo.push(dMatch[1]);
      }
      
      const key = dCombo.join('||');
      if (iconMap[key]) {
          const compName = iconMap[key];
          componentsNeeded.add(compName);
          
          let cleanAttrs = svgAttrsRaw.replace(/\s*(fill|stroke|stroke-linecap|stroke-linejoin|stroke-width|viewBox|xmlns|version|x|y|xmlspace|xml:space)="[^"]*"/g, '');
          
          if(compName === 'Loader2' && !cleanAttrs.includes('animate-spin')) {
              if (cleanAttrs.includes('class="')) {
                  cleanAttrs = cleanAttrs.replace('class="', 'class="animate-spin ');
              } else {
                  cleanAttrs += ' class="animate-spin"';
              }
          }
          
          const replacement = `<${compName}${cleanAttrs} />`;
          newContent = newContent.substring(0, match.index) + replacement + newContent.substring(match.index + svgContent.length);
      }
  }
  
  // Important logic to merge imports if needed
  if (componentsNeeded.size > 0 && newContent !== originalContent) {
      const importString = `import { ${Array.from(componentsNeeded).join(', ')} } from 'lucide-vue-next'`;
      
      const scriptSetupRegex = /<script[^>]*setup[^>]*>/;
      const scriptMatch = newContent.match(scriptSetupRegex);
      
      if (scriptMatch) {
          if (newContent.includes("'lucide-vue-next'")) {
              newContent = Object.assign(
                  newContent.substring(0, scriptMatch.index + scriptMatch[0].length),
                  "\n" + importString + "\n" + newContent.substring(scriptMatch.index + scriptMatch[0].length)
              );
          } else {
              newContent = newContent.replace(scriptMatch[0], scriptMatch[0] + '\n' + importString + '\n');
          }
      }
      fs.writeFileSync(f, newContent);
      console.log(`Updated ${f} with ${Array.from(componentsNeeded).join(', ')}`);
  }
});
