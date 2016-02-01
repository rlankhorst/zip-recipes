var gulp = require("gulp");
var del = require("del");
var rename = require("gulp-rename");
var filter = require("gulp-filter");
var rename = require("gulp-rename");
var replace = require("gulp-replace");
var zip = require("gulp-zip");
var shell = require('gulp-shell');
var sequence = require('run-sequence');
var path = require("path");

const build_path = "build";
const dest_free = path.join(build_path, "free");
const dest_premium = path.join(build_path, "premium");

gulp.task("build-premium-js", function () {
  return gulp.src(["node_modules/vue/dist/vue.min.js"], {base: "."})
    .pipe(gulp.dest(dest_premium))
});

/**
 *  Task to build premium version of Zip Recipes.
 */
gulp.task("build-premium", function () {
  // used to rename premium readme file
  var premiumFileFilter = filter('PREMIUM_README.md', {restore: true});

  // used to change plugin name to Zip Recipes premium
  var mainPluginFileFilter = filter('zip-recipes.php', {restore: true});

  return gulp.src(["src/**",
    "!src/README.md",
    "!src/composer.*",
    "LICENSE"])
    // rename premium read me
    .pipe(premiumFileFilter)
    .pipe(rename("README.md"))
    .pipe(premiumFileFilter.restore)

    // replace plugin name to premium
    .pipe(mainPluginFileFilter)
    .pipe(replace(/(Plugin Name:) Zip Recipes/, "$1 Zip Recipes Premium"))
    .pipe(mainPluginFileFilter.restore)

    // move files to destination
    .pipe(gulp.dest(dest_premium))
});

gulp.task("zip-premium", function () {
  return gulp.src(path.join(dest_premium, "**"))
    .pipe(zip("zip-recipes-premium.zip"))
    .pipe(gulp.dest("build/"));
});

/**
 * Run composer command in src dir.
 */
gulp.task('composer', shell.task([
  'cd src && php ../composer.phar install'
]));

/**
 * Task to build free version of Zip Recipes.
 */
gulp.task("build-free", ["composer", "clean-free"], function () {
  return gulp.src(["src/**", "!src/PREMIUM_README.md", "!src/composer.*", "LICENSE", "src/plugins/index.php", "!src/plugins/**"])
    // move files to destination
    .pipe(gulp.dest(dest_free))

    // zip it all up
    .pipe(zip("zip-recipes.zip"))
    .pipe(gulp.dest("build/"));
});

gulp.task("premium-sequence", function (cb) {
  return sequence("clean-premium", ["composer", "build-premium-js", "build-premium"], "zip-premium", cb);
});

/**
 * Task to build free and premium versions.
 */
gulp.task("build", function(cb) {
  return sequence("clean", ["build-free", "premium-sequence"]);
});


gulp.task("clean", function () {
  return del(build_path);
});

/**
 * Task to clean free version build.
 */
gulp.task("clean-free", function () {
  return del(dest_free);
});

/**
* Task to clean premium version build.
*/
gulp.task("clean-premium", function () {
  return del(dest_premium);
});
