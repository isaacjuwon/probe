import { svelte } from '@sveltejs/vite-plugin-svelte';
import tailwindcss from '@tailwindcss/vite';
import path from 'node:path';
import { defineConfig } from 'vite';

export default defineConfig({
  plugins: [svelte(), tailwindcss()],
  resolve: {
    alias: {
      $lib: path.resolve(import.meta.dirname, './src/lib'),
    },
  },
  base: './',
  build: {
    outDir: path.resolve(import.meta.dirname, '../../public'),
    emptyOutDir: false,
    rollupOptions: {
      input: path.resolve(import.meta.dirname, './src/main.ts'),
      output: {
        entryFileNames: 'probe.js',
        chunkFileNames: 'chunks/[name]-[hash].js',
        assetFileNames: (assetInfo) => {
          if (assetInfo.name && assetInfo.name.endsWith('.css')) {
            return 'probe.css';
          }
          return 'assets/[name]-[hash][extname]';
        },
      },
    },
  },
});
