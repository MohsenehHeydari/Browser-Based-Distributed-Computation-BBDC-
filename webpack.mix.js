const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel applications. By default, we are compiling the CSS
 | file for the application as well as bundling up all the JS files.
 |
 */
// mix.js('resources/assets/js/app.js', 'public/js').vue();
// mix.js('resources/assets/js/panel.js', 'public/js').vue();
mix.js('resources/js/owner.js', 'public/js').vue();
mix.js('resources/js/worker.js', 'public/js').vue();
mix.js('resources/js/auth.js', 'public/js').vue();

// mix.browserSync({
//     proxy:'bbmr.test',
//     files: [
//         [
//             'resources/**/*'
//         ]
//     ]});
