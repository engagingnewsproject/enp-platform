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
}
mix
  .js("assets/js/app.js", "dist/js")
  .js("assets/js/homepage.js", "dist/js")
  .sass("assets/scss/app.scss", "dist/css")
  .sourceMaps() // Add this line to enable source maps for SCSS
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
    ],
  });
