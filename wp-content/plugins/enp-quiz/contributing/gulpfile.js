var gulp = require('gulp');
var runSequence = require('run-sequence');
var browserSync = require('browser-sync');
var sass = require('gulp-sass');
var sourcemaps = require('gulp-sourcemaps');
var autoprefixer = require('gulp-autoprefixer');
var minifyCss = require('gulp-minify-css');
var uglify = require('gulp-uglify');
var rename = require("gulp-rename");
var concat = require("gulp-concat");
var insert = require('gulp-insert');
var reload  = browserSync.reload;


// Static Server + watching scss/html files
gulp.task('serve', ['sassQuizTake', 'sassQuizCreate', 'quizCreateJS', 'quizResultsJS', 'quizTakeJS', 'quizTakeUtilityJS'], function() {

    browserSync({
        proxy: "dev/quiz"
    });

    // quiz create
    gulp.watch('../enp-quiz/public/quiz-create/css/sass/*.{scss,sass}', ['sassQuizCreate']);
    // watch javascript files
    // compress on change
    gulp.watch('../enp-quiz/public/quiz-create/js/quiz-create/*.js', ['quizCreateJS']);
    // compress on change
    gulp.watch('../enp-quiz/public/quiz-create/js/quiz-results/*.js', ['quizResultsJS']);

    // quiz take
    gulp.watch('../enp-quiz/public/quiz-take/css/sass/*.{scss,sass}', ['sassQuizTake']);

    // compress on change
    gulp.watch('../enp-quiz/public/quiz-take/js/quiz-take/*.js', ['quizTakeJS']);

    // compress on change Quiz Take utilities
    gulp.watch('../enp-quiz/public/quiz-take/js/utilities/*.js', ['quizTakeUtilityJS']);

    // Watch for file changes
    onChangeReload = ["../enp-quiz/public/quiz-create/css/enp_quiz-create.min.css",
                    "../enp-quiz/public/quiz-create/*.php",
                    "../enp-quiz/public/quiz-create/includes/*.php",
                    "../enp-quiz/public/quiz-create/js/dist/quiz-create.min.js",
                    "../enp-quiz/public/quiz-take/css/enp_quiz-take.min.css",
                    "../enp-quiz/public/quiz-take/*.php",
                    "../enp-quiz/public/quiz-take/includes/*.php",
                    "../enp-quiz/public/quiz-take/js/dist/quiz-take.min.js"
                    ];
    gulp.watch(onChangeReload).on('change', reload);
});

gulp.task('sassQuizCreate', function () {
    processSASS('../enp-quiz/public/quiz-create/css/');
});

gulp.task('sassQuizTake', function () {
    processSASS('../enp-quiz/public/quiz-take/css/');
    // the slider is in the quiz create css now too...
    processSASS('../enp-quiz/public/quiz-create/css/');
});

gulp.task('quizCreateJS', function(callback) {
    runSequence('concatQuizCreateJS',
             'compressQuizCreateJS',
             callback);
});

gulp.task('concatQuizCreateJS', function() {
    rootPath = "../enp-quiz/public/quiz-create/js/quiz-create/";
    src = [rootPath+"quiz-create--utilities.js",
           rootPath+"quiz-create--templates.js",
           rootPath+"quiz-create--onLoad.js",
           rootPath+"quiz-create--ux.js",
           rootPath+"quiz-create--messages.js",
           rootPath+"quiz-create--save-question.js",
           rootPath+"quiz-create--save-question-image.js",
           rootPath+"quiz-create--save-mc-option.js",
           rootPath+"quiz-create--save-slider.js",
           rootPath+"quiz-create--save.js",
           "../enp-quiz/public/quiz-take/js/quiz-take/quiz-take--slider.js"

        ];
    filename = 'quiz-create';
    dist = '../enp-quiz/public/quiz-create/js/dist/';
    return concatjQuery(src, filename, dist);
});

gulp.task('compressQuizCreateJS', function() {
    dist = '../enp-quiz/public/quiz-create/js/dist/';
    return compressJS(dist);
});

gulp.task('quizResultsJS', function(callback) {
    runSequence('concatQuizResultsJS',
                'concatQuizABResultsJS',
                'compressQuizCreateJS',
             callback);
});

gulp.task('quizABResultsJS', function(callback) {
    runSequence('concatQuizABResultsJS',
            'compressQuizCreateJS',
             callback);
});

gulp.task('concatQuizResultsJS', function() {
    rootPath = "../enp-quiz/public/quiz-create/js/quiz-results/";
    src = [rootPath+"quiz-results.js",
           rootPath+"quiz-results--init.js",
           rootPath+"quiz-results--slider.js"
        ];
    filename = 'quiz-results';
    dist = '../enp-quiz/public/quiz-create/js/dist/';
    return concatjQuery(src, filename, dist);
});

gulp.task('concatQuizABResultsJS', function() {
    rootPath = "../enp-quiz/public/quiz-create/js/quiz-results/";
    src = [rootPath+"ab-results.js",
           rootPath+"quiz-results--init.js",
           rootPath+"quiz-results--slider.js"
        ];
    filename = 'ab-results';
    dist = '../enp-quiz/public/quiz-create/js/dist/';
    return concatjQuery(src, filename, dist);
});


gulp.task('quizTakeJS', function(callback) {
    runSequence('concatQuizTakeJS',
             'compressQuizTakeJS',
             callback);
});

gulp.task('concatQuizTakeJS', function() {
    rootPath = "../enp-quiz/public/quiz-take/js/quiz-take/";
    src = [rootPath+"quiz-take--utilities.js",
           rootPath+"quiz-take--templates.js",
           rootPath+"quiz-take--ux.js",
           rootPath+"quiz-take--postmessage.js",
           rootPath+"quiz-take--question.js",
           rootPath+"quiz-take--question-explanation.js",
           rootPath+"quiz-take--mc-option.js",
           rootPath+"quiz-take--slider.js",
           rootPath+"quiz-take--quiz-end.js",
           rootPath+"quiz-take--init.js",
        ];
    filename = 'quiz-take';
    dist = '../enp-quiz/public/quiz-take/js/dist/';
    return concatjQuery(src, filename, dist);
});

gulp.task('quizTakeUtilityJS', function(callback) {
    runSequence('concatQuizTakeUtilityJS',
             'compressQuizTakeJS',
             callback);
});

gulp.task('concatQuizTakeUtilityJS', function() {
    rootPath = "../enp-quiz/public/quiz-take/js/utilities/";
    src = [rootPath+"html5shiv.min.js",
           rootPath+"jquery-ui.min.js",
           rootPath+"jquery.uitouch-punch.min.js",
           rootPath+"underscore.min.js",
        ];
    filename = 'utilities';
    dist = '../enp-quiz/public/quiz-take/js/dist/';
    return gulp.src(src)
      .pipe(concat(filename+'.js'))
      .pipe(gulp.dest(dist));
});

gulp.task('compressQuizTakeJS', function() {
    dist = '../enp-quiz/public/quiz-take/js/dist/';
    return compressJS(dist);
});

function concatjQuery(src, filename, dist) {
    return gulp.src(src)
      .pipe(concat(filename+'.js'))
      .pipe(insert.wrap('jQuery( document ).ready( function( $ ) {', '});'))
      .pipe(gulp.dest(dist));
}

function compressJS(path) {
    return gulp.src([path+"*.js","!"+path+"*.min.js"])
      .pipe(uglify())
      .pipe(rename({
        suffix: '.min'
      }))
      .pipe(gulp.dest(path));
}

function processSASS(path) {
    return gulp.src(path+'sass/*.{scss,sass}')
      // Initializes sourcemaps
      // Uncomment this and the other sourcemaps line to add sourcemaps back in
      //////////// .pipe(sourcemaps.init())

      // Converts Sass into CSS with Gulp Sass
      .pipe(sass({
        errLogToConsole: true
      }))
      // adds prefixes to whatever needs to get done
      .pipe(autoprefixer())

      // minify the CSS
      .pipe(minifyCss())

      // Writes sourcemaps into the CSS file
      // Uncomment this and the other sourcemaps line to add sourcemaps back in
      ///////////// .pipe(sourcemaps.write())

      // rename to add .min
      .pipe(rename({
        suffix: '.min'
      }))
      // Outputs CSS files in the css folder
      .pipe(gulp.dest(path));
}

// Creating a default task
gulp.task('default', ['serve']);
