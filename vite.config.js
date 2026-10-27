import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
   
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/shared-sync.js',

                // Add the new CSS and JS files for the homepage
                'resources/css/Home/HOMEPAGEStyle.css',
                'resources/js/Home/HOMEPAGEScript.js',

                // Add the new CSS and JS files for the admin dashboard
                'resources/css/ADMIN_DASHBOARD.css',
                'resources/js/ADMIN_DASHBOARDScript.js',


            ],
            refresh: true,
        }),
    ],
});