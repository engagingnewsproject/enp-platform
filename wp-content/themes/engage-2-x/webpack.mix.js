const mix = require('laravel-mix');

mix
  .js("assets/js/app.js", "dist/js")
  .js("assets/js/homepage.js", "dist/js")
  .sass("assets/scss/app.scss", "dist/css")
  .sourceMaps() // Add this line to enable source maps for SCSS
  .browserSync({
    proxy: "http://localhost:10028", // Replace with your local URL
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
