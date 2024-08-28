const mix = require('laravel-mix');
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
    proxy: "https://mediaengagementorg.local", // Replace with your local URL
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
