import sharp from 'sharp';
import { writeFileSync } from 'fs';

const svgContent = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 192 192" fill="none">
  <rect width="192" height="192" fill="#2563eb" rx="24"/>
  <path d="M96 40L140 80V140L96 180L52 140V80L96 40Z" fill="white" fill-opacity="0.9"/>
  <path d="M96 60L124 88V136L96 164L68 136V88L96 60Z" fill="#2563eb"/>
  <circle cx="96" cy="112" r="16" fill="white"/>
</svg>`;

async function generateIcons() {
  // Generate 192x192
  await sharp(Buffer.from(svgContent))
    .resize(192, 192)
    .png()
    .toFile('public/icon-192.png');

  // Generate 512x512
  await sharp(Buffer.from(svgContent))
    .resize(512, 512)
    .png()
    .toFile('public/icon-512.png');

  console.log('Icons generated successfully!');
}

generateIcons();
