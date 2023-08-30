import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
//import alpineFiles from '../resources/js/alpine-files.js'
//console.log(alpineFiles)

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/welcome/index.js'],
            refresh: true,
        }),
    ],
    server: { 
      hmr: {
          host: 'localhost',
      },
    }, 
});
