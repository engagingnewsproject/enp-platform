var gulp = require('gulp');
var runSequence = require('run-sequence');
var browserSync = require('browser-sync');
var sass = require('gulp-sass');
var autoprefixer = require('gulp-autoprefixer');
var minifyCss = require('gulp-minify-css');
var uglify = require('gulp-uglify');
var rename = require("gulp-rename");
var concat = require("gulp-concat");
var insert = require('gulp-insert');
var reload  = browserSync.reload;


// Static Server + watching scss/html files
gulp.task('serve', ['sassQuizTake', 'sassQuizCreate', 'quizCreateJS', 'quizDashboardJS', 'quizResultsJS', 'quizTakeJS', 'quizTakeIframeParentJS', 'quizTakeUtilityJS'], function() {

    browserSync({
        proxy: "local.quiz"
    });

    // quiz create
    gulp.watch('public/quiz-create/css/sass/*.{scss,sass}', ['sassQuizCreate']);
    // watch javascript files
    // compress on change
    gulp.watch('public/quiz-create/js/quiz-create/*.js', ['quizCreateJS']);
    gulp.watch('public/quiz-create/js/dashboard.js', ['quizDashboardJS']);
    // compress on change
    gulp.watch('public/quiz-create/js/quiz-results/*.js', ['quizResultsJS']);

    // quiz take
    gulp.watch('public/quiz-take/css/sass/*.{scss,sass}', ['sassQuizTake']);

    // compress on change
    gulp.watch('public/quiz-take/js/quiz-take/*.js', ['quizTakeJS']);

    // compress on change
    gulp.watch('public/quiz-take/js/iframe-parent/*.js', ['quizTakeIframeParentJS']);

    // compress on change Quiz Take utilities
    gulp.watch('public/quiz-take/js/utilities/*.js', ['quizTakeUtilityJS']);

    // Watch for file changes
    onChangeReload = ["public/quiz-create/css/enp_quiz-create.min.css",
                    "public/quiz-create/*.php",
                    "public/quiz-create/includes/*.php",
                    "public/quiz-create/js/dist/quiz-create.min.js",
                    "public/quiz-take/css/enp_quiz-take.min.css",
                    "public/quiz-take/*.php",
                    "public/quiz-take/includes/*.php",
                    "public/quiz-take/js/dist/quiz-take.min.js"
                    ];
    gulp.watch(onChangeReload).on('change', reload);
});

gulp.task('sassQuizCreate', function () {
    processSASS('public/quiz-create/css/');
});

gulp.task('sassQuizTake', function () {
    processSASS('public/quiz-take/css/');
    // the slider is in the quiz create css now too...
    processSASS('public/quiz-create/css/');
});

gulp.task('quizCreateJS', function(callback) {
    runSequence('concatQuizCreateJS',
             'compressQuizCreateJS',
             callback);
});

gulp.task('concatQuizCreateJS', function() {
    rootPath = "public/quiz-create/js/quiz-create/";
    src = [rootPath+"quiz-create--utilities.js",
           rootPath+"quiz-create--templates.js",
           rootPath+"quiz-create--onLoad.js",
           rootPath+"quiz-create--ux.js",
           "public/quiz-create/js/utilities/display-messages.js",
           rootPath+"quiz-create--save-question.js",
           rootPath+"quiz-create--save-question-image.js",
           rootPath+"quiz-create--save-mc-option.js",
           rootPath+"quiz-create--save-slider.js",
           rootPath+"quiz-create--save.js",
           "public/quiz-take/js/quiz-take/quiz-take--slider.js"

        ];
    filename = 'quiz-create';
    dist = 'public/quiz-create/js/dist/';
    return concatjQuery(src, filename, dist);
});

gulp.task('compressQuizCreateJS', function() {
    dist = 'public/quiz-create/js/dist/';
    return compressJS(dist);
});


gulp.task('quizDashboardJS', function(callback) {
    runSequence('concatQuizDashboardJS',
             'compressQuizCreateJS',
             callback);
});

gulp.task('concatQuizDashboardJS', function() {
    rootPath = "public/quiz-create/js/";
    src = [rootPath+"dashboard.js",
           rootPath+"utilities/display-messages.js",
        ];
    filename = 'dashboard';
    dist = 'public/quiz-create/js/dist/';
    return concatjQuery(src, filename, dist);
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
    rootPath = "public/quiz-create/js/quiz-results/";
    src = [rootPath+"quiz-results.js",
           rootPath+"quiz-results--init.js",
           rootPath+"quiz-results--slider.js"
        ];
    filename = 'quiz-results';
    dist = 'public/quiz-create/js/dist/';
    return concatjQuery(src, filename, dist);
});

gulp.task('concatQuizABResultsJS', function() {
    rootPath = "public/quiz-create/js/quiz-results/";
    src = [rootPath+"ab-results.js",
           rootPath+"quiz-results--init.js",
           rootPath+"quiz-results--slider.js"
        ];
    filename = 'ab-results';
    dist = 'public/quiz-create/js/dist/';
    return concatjQuery(src, filename, dist);
});


gulp.task('quizTakeJS', function(callback) {
    runSequence('concatQuizTakeJS',
             'compressQuizTakeJS',
             callback);
});

gulp.task('concatQuizTakeJS', function() {
    rootPath = "public/quiz-take/js/quiz-take/";
    src = [//"public/quiz-take/js/utilities/jquery.ui.touch-punch.min.js",
           rootPath+"quiz-take--utilities.js",
           rootPath+"quiz-take--templates.js",
           rootPath+"quiz-take--ux.js",
           rootPath+"quiz-take--postmessage.js",
           rootPath+"quiz-take--question.js",
           rootPath+"quiz-take--question-explanation.js",
           rootPath+"quiz-take--mc-option.js",
           rootPath+"quiz-take--slider.js",
           rootPath+"quiz-take--quiz-end.js",
           rootPath+"quiz-take--quiz-restart.js",
           rootPath+"quiz-take--init.js",
        ];
    filename = 'quiz-take';
    dist = 'public/quiz-take/js/dist/';
    return concatjQuery(src, filename, dist);
});

gulp.task('quizTakeIframeParentJS', function(callback) {
    runSequence('concatQuizTakeIframeParentJS',
             'compressQuizTakeJS',
             callback);
});

gulp.task('concatQuizTakeIframeParentJS', function() {
    rootPath = "public/quiz-take/js/iframe-parent/";
    src = [rootPath+"enpIframeQuiz.js",
           rootPath+"iframe-parent--listener.js",
          ];
    filename = 'iframe-parent';
    dist = 'public/quiz-take/js/dist/';
    return gulp.src(src)
      .pipe(concat(filename+'.js'))
      .pipe(gulp.dest(dist));
});

gulp.task('quizTakeUtilityJS', function(callback) {
    runSequence('concatQuizTakeUtilityJS',
             'compressQuizTakeJS',
             callback);
});

gulp.task('concatQuizTakeUtilityJS', function() {
    rootPath = "public/quiz-take/js/utilities/";
    src = [rootPath+"html5shiv.min.js",
           rootPath+"jquery-ui.min.js",
           rootPath+"underscore.min.js",
           rootPath+"jquery.ui.touch-punch.min.js"
        ];
    filename = 'utilities';
    dist = 'public/quiz-take/js/dist/';
    return gulp.src(src)
      .pipe(concat(filename+'.js'))
      .pipe(gulp.dest(dist));
});

gulp.task('compressQuizTakeJS', function() {
    dist = 'public/quiz-take/js/dist/';
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

      // Converts Sass into CSS with Gulp Sass
      .pipe(sass({
        errLogToConsole: true
      }))
      // adds prefixes to whatever needs to get done
      .pipe(autoprefixer())

      // minify the CSS
      .pipe(minifyCss())

      // rename to add .min
      .pipe(rename({
        suffix: '.min'
      }))
      // Outputs CSS files in the css folder
      .pipe(gulp.dest(path));
}

// Creating a default task
gulp.task('default', ['serve']);
