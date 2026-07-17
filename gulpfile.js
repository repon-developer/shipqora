
const { watch, src, dest, series } = require('gulp');
const uglify = require('gulp-uglify');
const rename = require('gulp-rename');
const cleanCSS = require('gulp-clean-css');
const package_file = require('./package.json'); // or wherever you store version
const replace = require('gulp-replace');

function minifyjs() {
	return src(['assets/**/*.js', '!assets/ace/*.js', '!assets/**/*.min.js']) // base ensures directory structure is preserved
		.pipe(replace('@@VERSION', package_file.version)) // inject version
		.pipe(uglify())
		.pipe(rename({ suffix: '.min' })) // optional: adds .min suffix
		.pipe(dest('assets/'));
}

function minifycss() {
	return src(['assets/**/*.css', '!assets/**/*.min.css'])
		.pipe(cleanCSS({ compatibility: 'ie8' }))
		.pipe(rename({ extname: '.min.css' }))
		.pipe(dest('assets/'));
}


exports.watch = function () {
	watch(['assets/**/*.css', '!assets/**/*.min.css'], minifycss);
	watch(['assets/**/*.js', '!assets/**/*.min.js', '!assets/ace/*.js'], minifyjs);
}

exports.default = series(minifyjs, minifycss)