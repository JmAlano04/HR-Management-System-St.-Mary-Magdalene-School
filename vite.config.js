import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',

                // Add the new CSS and JS files for the homepage
                'resources/css/Home/HOMEPAGEStyle.css',
                'resources/js/Home/HOMEPAGEScript.js',


                
            ],
            refresh: true,
        }),
    ],
});
