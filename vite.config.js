import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import { resolve } from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.tsx',
            refresh: true,
        }),
        react(),
    ],
    server: {
      hmr: {
          host: 'localhost',
      },
      watch: {
          usePolling: true
      }
    },
    resolve: {
        alias: {
            '@googlemaps/extended-component-library': resolve(
                __dirname,
                'node_modules/@googlemaps/extended-component-library/dist/index.min.js'
            ),
        },
    },
});
