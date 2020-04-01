/*
* Settings
*/
// Set this for browserSync to know where your
// url normally is.
var localhost = "https://decision-tree.dev";

/*
* NPM Packages
*/
const gulp = require('gulp');
const browserSync = require('browser-sync');
const sass = require('gulp-sass');
const autoprefixer = require('gulp-autoprefixer');
const reload  = browserSync.reload;
const crass = require('gulp-crass');
const cssVip = require('gulp-css-vip'); // adds !important tags to all CSS lines
const uglify = require('gulp-uglify');
const rename = require("gulp-rename");
const concat = require("gulp-concat");
const babel = require("gulp-babel");
const plumber = require('gulp-plumber');
const insert = require('gulp-insert');
// Handlebars and dependencies
const handlebars = require('gulp-handlebars');
const wrap = require('gulp-wrap');
const declare = require('gulp-declare');

const cssFiles = [
                  'base',
                  'structure-color',
                  'structure-typography',
                  'structure'
                ];

// Static Server + watching scss/html files
gulp.task('serve', ['sass', 'iframeJS', 'TreeJS', 'TreeLoaderJS', 'handlebars', 'editSass', 'editJs'], function() {

    browserSync({
        proxy: localhost
    });
    // Watch SCSS file for change to pass on to sass compiler,
    gulp.watch(['assets/sass/*.{scss,sass}','assets/sass/*/*.{scss,sass}'], ['sass']);
    gulp.watch(['dist/css/'+cssFiles[cssFiles.length - 1].toString()+'.min.css'], ['sassImportantify']);
    gulp.watch(['dist/css/'+cssFiles[cssFiles.length - 1].toString()+'-important.min.css'], ['sassCleanSlate']);
    // Watch JS file for change to pass on to sass compiler,
    gulp.watch('assets/js/iframe-parent/*.js', ['iframeJS']);
    gulp.watch('assets/js/Tree/*.js', ['TreeJS']);
    gulp.watch('assets/js/TreeLoader/*.js', ['TreeLoaderJS']);
    // compile templates
    gulp.watch('templates/*.hbs', ['handlebars']);
    // compile js
    gulp.watch(["dist/js/scripts.js", "dist/js/handlebars.runtime.js", "dist/js/templates.js"], ['concatTreeJS']);
    // Watch our CSS file and reload when it's done compiling
    gulp.watch("dist/css/structure-typography-important.*").on('change', reload);
    // Watch php file
    gulp.watch("../*/*.php").on('change', reload);
    // watch javascript files
    gulp.watch("dist/js/cme-tree.min.js").on('change', reload);

    compressJS("dist/js/handlebars.runtime.js");


    // edit files
    gulp.watch("views/edit/assets/sass/*.scss", ['editSass'])
    gulp.watch("views/edit/assets/js/*.js", ['editJs'])
    gulp.watch("views/edit/dist/*/*").on('change', reload);
    gulp.watch("views/edit/*.php").on('change', reload);
});


gulp.task('sass', function () {
  for(let i = 0; i < cssFiles.length; i++) {
    processSASS(cssFiles[i]);
  }
});

gulp.task('sassImportantify', function () {
  for(let i = 0; i < cssFiles.length; i++) {
    cssImportantify(cssFiles[i]);
  }
});

gulp.task('sassCleanSlate', function () {
  for(let i = 0; i < cssFiles.length; i++) {
    cssCleanSlate(cssFiles[i]);
  }
});

gulp.task('TreeJS', function() {
    var jsFiles = 'assets/js/Tree/*.js',
    jsDest = 'dist/js';

    return gulp.src(jsFiles)
        .pipe(plumber())
        .pipe(babel())
        .pipe(concat('scripts.js'))
        .pipe(gulp.dest(jsDest))
        .pipe(rename('scripts.min.js'))
        .pipe(uglify())
        .pipe(gulp.dest(jsDest));
});

gulp.task('TreeLoaderJS', function() {
    var jsFiles = 'assets/js/TreeLoader/*.js',
    jsDest = 'dist/js';

    return gulp.src(jsFiles)
        .pipe(plumber())
        .pipe(babel({
            presets: ['env']
        }))
        .pipe(concat('TreeLoader.js'))
        .pipe(gulp.dest(jsDest))
        .pipe(rename('TreeLoader.min.js'))
        .pipe(uglify())
        .pipe(gulp.dest(jsDest));
});

gulp.task('iframeJS', function() {
    var jsFiles = 'assets/js/iframe-parent/*.js',
    jsDest = 'dist/js';

    return gulp.src(jsFiles)
        .pipe(plumber())
        .pipe(babel({
            presets: ['env']
        }))
        .pipe(concat('iframe-parent.js'))
        .pipe(gulp.dest(jsDest))
        .pipe(rename('iframe-parent.min.js'))
        .pipe(uglify())
        .pipe(gulp.dest(jsDest));
});

function compressJS(src) {

    dist = 'dist/js/';

    return gulp.src(src)
        .pipe(plumber())
        .pipe(uglify())
        .pipe(rename({
          suffix: '.min'
        }))
        .pipe(gulp.dest(dist));
}

gulp.task('concatTreeJS', function() {
    dist = 'dist/js/';
    src = [dist+'handlebars.runtime.js',
           dist+'templates.js',
           dist+'scripts.js'
       ];
    filename = 'cme-tree';

    return gulp.src(src)
    .pipe(concat('cme-tree.js'))
    .pipe(gulp.dest(dist))
    .pipe(rename({
      suffix: '.min'
    }))
    .pipe(uglify())
    .pipe(gulp.dest(dist));
});


function processSASS(filename) {
    return gulp.src('assets/sass/output/'+filename+'.{scss,sass}')
      // Converts Sass into CSS with Gulp Sass
      .pipe(plumber())
      .pipe(sass({
        errLogToConsole: true
      }))
      // adds prefixes to whatever needs to get done
      .pipe(autoprefixer())

      // minify the CSS
      .pipe(crass({pretty:false}))

      // rename to add .min
      .pipe(rename({
        suffix: '.min'
      }))
      // Outputs CSS files in the css folder
      .pipe(gulp.dest('dist/css/'));
}

function cssImportantify(filename) {

  return gulp.src('dist/css/'+filename+'.min.css')
    // catch errors
    .pipe(plumber())
    // wrap our file in an .cme-tree__loader-container {}
    // before processing it with sass to increase specificty
    .pipe(insert.wrap('.cme-tree__loader-container {', '}'))
    // process it with sass
    .pipe(sass({
        errLogToConsole: true
      }))
    // adds prefixes to whatever needs to get done
    .pipe(autoprefixer())
    // add !important tags
    .pipe(cssVip())
    // minify the CSS
    .pipe(crass({pretty:false}))
    // rename to add .min
    .pipe(rename({
      basename: filename+'-important',
      suffix: '.min'
    }))
    // Outputs CSS files in the css folder
    .pipe(gulp.dest('dist/css/'));
}

function cssCleanSlate(filename) {

  return gulp.src(['assets/css/clean-slate.css','dist/css/'+filename+'-important.min.css'])
    // catch errors
    .pipe(plumber())
    // concatenate clean slate with the '-important' files
    .pipe(concat(filename+'-clean-slate.css'))
    // minify the CSS
    // @TODO This makes it so you have to save twice, apparently...
    .pipe(crass({pretty:false}))
    // rename to add .min
    .pipe(rename({
      suffix: '.min'
    }))
    // Outputs CSS files in the css folder
    .pipe(gulp.dest('dist/css/'));
}

gulp.task('handlebars', function(){
  gulp.src('templates/*.hbs')
      .pipe(handlebars({
        handlebars: require('handlebars')
      }))
      .pipe(wrap('Handlebars.template(<%= contents %>)'))
      .pipe(declare({
        namespace: 'TreeTemplates',
        noRedeclare: true, // Avoid duplicate declarations
      }))
      .pipe(concat('templates.js'))
      .pipe(gulp.dest('dist/js/'))
      .pipe(rename('templates.min.js'))
      .pipe(uglify())
      .pipe(gulp.dest('dist/js/'));
});


gulp.task('editSass', function () {
  return gulp.src('views/edit/assets/sass/edit.scss')
      // Converts Sass into CSS with Gulp Sass
      .pipe(plumber())
      .pipe(sass({
        errLogToConsole: true
      }))
      // adds prefixes to whatever needs to get done
      .pipe(autoprefixer())

      // minify the CSS
      .pipe(crass({pretty:false}))

      // rename to add .min
      .pipe(rename({
        suffix: '.min'
      }))
      // Outputs CSS files in the css folder
      .pipe(gulp.dest('views/edit/dist/css/'));
});

gulp.task('editJs', function () {
  var jsFiles = 'views/edit/assets/js/*.js',
    jsDest = 'views/edit/dist/js';

    return gulp.src(jsFiles)
        .pipe(plumber())
        .pipe(babel())
        .pipe(concat('edit.js'))
        .pipe(gulp.dest(jsDest))
        .pipe(rename('edit.min.js'))
        .pipe(uglify())
        .pipe(gulp.dest(jsDest));
});

// Creating a default task
gulp.task('default', ['serve']);
