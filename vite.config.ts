import { defineConfig } from 'vite';
import { resolve } from 'path';
import fs from 'fs';

// Dynamically capture all HTML files in the Laravel public directory
function getPublicHtmlInputs() {
  const publicDir = resolve(__dirname, 'public');
  try {
    const files = fs.readdirSync(publicDir);
    const htmlFiles = files.filter(f => f.endsWith('.html'));
    const input: Record<string, string> = {};
    for (const file of htmlFiles) {
      const name = file.replace(/\.html$/, '');
      input[name] = resolve(publicDir, file);
    }
    return input;
  } catch (e) {
    return { index: resolve(publicDir, 'index.html') };
  }
}

export default defineConfig({
  root: resolve(__dirname, 'public'),
  plugins: [{
    name: 'copy-static-assets',
    closeBundle() {
      fs.cpSync(resolve(__dirname, 'public/assets'), resolve(__dirname, 'dist/assets'), { recursive: true });
    }
  }],
  server: {
    port: 3000,
    host: '0.0.0.0',
    // Send API calls to Laravel (`php artisan serve`) so this dev server shows the same live data.
    proxy: {
      '/api': 'http://127.0.0.1:8000',
    },
  },
  build: {
    outDir: resolve(__dirname, 'dist'),
    emptyOutDir: true,
    rollupOptions: {
      input: getPublicHtmlInputs(),
    },
  },
});
