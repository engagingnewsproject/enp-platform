const mix = require('laravel-mix');
const tailwindcss = require('tailwindcss');

mix.js('assets/js/app.js', 'js')
   .js('assets/js/homepage.js', 'js')
   .js('assets/js/flickity.js', 'js')
   .sass('assets/scss/app.scss', 'css')
   .sourceMaps() // Add this line to enable source maps for SCSS
   // Tailwind config below:
   // .postCss("assets/css/app-t.css", "css", [
   //  	require("tailwindcss"),
   //  ])
   // .options({
   //    processCssUrls: false, // Prevent Mix from processing CSS URLs
   //    postCss: [tailwindcss('./tailwind.config.js')],
   // })
   .setPublicPath('dist')
   .browserSync({
      proxy: 'http://localhost:10028', // Replace with your local URL
      files: [
         '**/*.php', 
         'dist/css/**/*.css', 
         'templates/**/*',
         "templates/**/*.twig",
			"templates/**/*.php",
			"./../../plugins/enp-quiz/**/*.php",
			"./../../plugins/enp-quiz/**/*.scss",
			"./../../plugins/enp-quiz/**/*.js"
      ]
   })
