const path = require('path'),
    gulp = require('gulp'),
    del = require('del'),
    sourcemaps = require('gulp-sourcemaps'),
    plumber = require('gulp-plumber'),
    sass = require('gulp-sass'),
    less = require('gulp-less'),
    stylus = require('gulp-stylus'),
    autoprefixer = require('gulp-autoprefixer'),
    minifyCss = require('gulp-clean-css'),
    babel = require('gulp-babel'),
    webpack = require('webpack-stream'),
    uglify = require('gulp-uglify'),
    concat = require('gulp-concat'),
    imagemin = require('gulp-imagemin'),
    browserSync = require('browser-sync').create(),
    pug = require('gulp-pug'),
    dependents = require('gulp-dependents'),

    src_folder = 'assets',
    src_assets_folder = src_folder + '/src',
    node_modules_folder = 'node_modules/',

    node_dependencies = Object.keys(require('./package.json').dependencies || {});

gulp.task('clear', () => del(['assets/css/*.css', 'assets/css/fe/*.css', 'assets/js/*.js', 'assets/js/fe/*.js']));

gulp.task('sass', () => {
    return gulp.src([
        'assets/src/sass/*.scss',
        'assets/src/sass/includes/*.scss'
    ], { since: gulp.lastRun('sass') })
        .pipe(sourcemaps.init())
        .pipe(plumber())
        .pipe(dependents())
        .pipe(sass())
        .pipe(autoprefixer())
        .pipe(minifyCss())
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest('assets/css'))
        .pipe(browserSync.stream());
});

gulp.task('fe-sass', () => {
    return gulp.src([
        'assets/src/sass/fe/main.scss',
        'assets/src/sass/fe/includes/*.scss'
    ], {})
        .pipe(sourcemaps.init())
        .pipe(plumber())
        .pipe(dependents())
        .pipe(sass())
        .pipe(autoprefixer())
        .pipe(minifyCss())
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest('assets/css/fe'));
});


gulp.task('js', () => {
    return gulp.src(['assets/src/js/*.js'], { since: gulp.lastRun('js') })
        .pipe(plumber())
        .pipe(webpack({
            mode: 'production'
        }))
        .pipe(sourcemaps.init())
        .pipe(babel({
            presets: ['@babel/env']
        }))
        .pipe(concat('main.js'))
        .pipe(uglify())
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest('assets/js'))
        .pipe(browserSync.stream());
});

gulp.task('fe-js', () => {
    return gulp.src(['assets/src/js/fe/*.js'], { since: gulp.lastRun('fe-js') })
        .pipe(plumber())
        .pipe(webpack({
            mode: 'production'
        }))
        .pipe(sourcemaps.init())
        .pipe(babel({
            presets: ['@babel/env']
        }))
        .pipe(concat('main.js'))
        .pipe(uglify())
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest('assets/js/fe'))
        .pipe(browserSync.stream());
});

gulp.task('images', () => {
    return gulp.src([src_assets_folder + 'images/**/*.+(png|jpg|jpeg|gif|svg|ico)'], { since: gulp.lastRun('images') })
        .pipe(plumber())
        .pipe(imagemin())
        .pipe(gulp.dest('assets/images'))
        .pipe(browserSync.stream());
});

gulp.task('vendor', () => {
    if (node_dependencies.length === 0) {
        return new Promise((resolve) => {
            console.log("No dependencies specified");
            resolve();
        });
    }

    return gulp.src(node_dependencies.map(dependency => node_modules_folder + dependency + '/**/*.*'), {
        base: node_modules_folder,
        since: gulp.lastRun('vendor')
    })
        .pipe(gulp.dest(node_modules_folder))
        .pipe(browserSync.stream());
});

gulp.task('build', gulp.series('clear', 'sass', 'fe-sass', 'js', 'fe-js', 'images', 'vendor'));


gulp.task('watch', () => {
    const watchImages = [
        'assets/src/images/**/*.+(png|jpg|jpeg|gif|svg|ico)'
    ];

    const watchVendor = [];

    node_dependencies.forEach(dependency => {
        watchVendor.push(node_modules_folder + dependency + '/**/*.*');
    });

    const watch = [
        'assets/src/sass/fe/*.scss',
        'assets/src/sass/fe/includes/*.scss',
        'assets/src/sass/*.scss',
        'assets/src/sass/includes/*.scss',
        'assets/src/js/*.js',
        'assets/src/js/fe/*.js'
    ];

    gulp.watch(watch, gulp.series('dev')).on('change', browserSync.reload);
    gulp.watch(watchImages, gulp.series('images')).on('change', browserSync.reload);
    gulp.watch(watchVendor, gulp.series('vendor')).on('change', browserSync.reload);
});

gulp.task('dev', gulp.series('sass', 'fe-sass', 'js', 'fe-js', 'watch'));

gulp.task('default', gulp.series('build', gulp.parallel('watch')));