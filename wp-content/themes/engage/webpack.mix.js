let mix = require("laravel-mix");

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for your application, as well as bundling up your JS files.
 |
 */

mix.js("assets/js/app.js", "dist/js/")
	.sass("assets/scss/app.scss", "dist/css/")
	.browserSync({
		proxy: "http://localhost:10028/",
		port: "3000",
		// We need to exclude the vendor directory from hot reloading,
		// as we won't be changing those here and it has to watch so many
		// files that it ends up crashing.
		// I tried adding about every permutation of "!vendor" to exclude the
		// vendor files, but the subsequent "*.php" or "**/*.php" would always end up matching
		// to vendor still and crashing
		files: [
			"dist/**/*.+(css|js)",
			"src/**/*.php",
			"404.php",
			"about.php",
			"archive.php",
			"author.php",
			"enp-quiz-page.php",
			"footer.php",
			"functions.php",
			"header.php",
			"index.php",
			"page.php",
			"search.php",
			"sidebar.php",
			"single.php",
			"tools-template.php",
			"templates/**/*.twig",
		],
	})
	.webpackConfig({
		plugins: [],
		output: {
			publicPath: "/wp-content/themes/engage/",
			chunkFilename: "dist/js/chunk/[name].[chunkhash].js",
		},
	});
