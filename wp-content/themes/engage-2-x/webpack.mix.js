const mix = require('laravel-mix');
// Each developer may have a different local development URL, 
// to fix this, a configuration file (config.json, added to .gitignore) contains 
// each developers Local App URL without affecting the shared codebase.
const config = require('./config.json');

const proxyUrl = config.proxy || 'https://default-url.local';
if (!mix.inProduction()) {
  mix.webpackConfig({
    devtool: "inline-source-map",
  });
  mix.sourceMaps();
}

mix
  .setPublicPath('dist')
  .js("assets/js/app.js", "js")
  .js("assets/js/homepage.js", "js")
  .sass("assets/scss/app.scss", "css")
  .sass("assets/scss/editor-style.scss", "css") // Editor styles (includes admin styles)
  .version()
  .browserSync({
    proxy: proxyUrl,
    files: [
      "**/*.php",
      "dist/css/**/*.css",
      "templates/**/*",
      "templates/**/*.twig",
      "templates/**/*.php",
      "assets/js/**/*.js",
      "./../../plugins/enp-quiz/**/*.php",
      "./../../plugins/enp-quiz/**/*.scss",
      "./../../plugins/enp-quiz/**/*.js",
      "!**/debug.log" // Exclude debug.log
    ],
  });

  mix.options({
    processCssUrls: false, // Disable processing of URLs in CSS
  })