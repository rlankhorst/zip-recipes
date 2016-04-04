var gulp = require("gulp");
var del = require("del");
var rename = require("gulp-rename");
var filter = require("gulp-filter");
var replace = require("gulp-replace");
var zip = require("gulp-zip");
var shell = require('gulp-shell');
var sequence = require('run-sequence');
var path = require("path");
var fs = require("fs");
var debug = require("gulp-debug");

const build_path = "build";
const dest_free = path.join(build_path, "free");
const dest_premium = path.join(build_path, "premium");

gulp.task("build-premium-js", function () {
  return gulp.src(["node_modules/vue/dist/vue.min.js"], {base: "."})
    .pipe(gulp.dest(dest_premium));
});

gulp.task("build-free-js", function () {
  return gulp.src(["node_modules/vue/dist/vue.min.js"], {base: "."})
    .pipe(gulp.dest(dest_free));
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
    "!node_modules/**",
    "!src/vendor-dev/**",
    "!src/vendor-dev",
    "LICENSE",
    "!src/plugins/**"])
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

gulp.task("compress-premium", function () {
  return gulp.src(path.join(dest_premium, "**"))
    .pipe(zip("zip-recipes-premium.zip"))
    .pipe(gulp.dest("build/"));
});

gulp.task("plugins-free", function () {
  return gulp.src(["src/plugins/index.php"], {base: "src"})
    .pipe(gulp.dest(dest_free));
});

gulp.task("plugins-premium", function () {
  return gulp.src(["src/plugins/**"], {base: "src"})
    .pipe(gulp.dest(dest_premium));
});

gulp.task("build-free", function () {
  return gulp.src([
    "src/**",
    "!src/PREMIUM_README.md",
    "!src/composer.*",
    "!node_modules/**",
    "!src/vendor-dev/**",
    "!src/vendor-dev",
    "LICENSE",
    "!src/plugins/**"
  ])
    .pipe(debug())
    // move files to destination
    .pipe(gulp.dest(dest_free));
});

gulp.task("compress-free", function() {
  return gulp.src(path.join(dest_free, "**"))
    .pipe(zip("zip-recipes.zip"))
    .pipe(gulp.dest("build/"));
});

/**
 * Renames vendor to vendor-dev if if it exists because vendor contains dev packages as well
 */
gulp.task('vendor-rename-pre', function(done) {
  try {
    return fs.renameSync('src/vendor', 'src/vendor-dev');
  }
  catch (e) {
    console.log("vendor dir not found");
  }
  done();
});

/**
 * Renames vendor-dev to vendor if if it exists
 * This restores vendor name for vendor packages that include dev packages
 */
gulp.task('vendor-rename-post', function(done) {
  try {
    return fs.renameSync('src/vendor-dev', 'src/vendor');
  }
  catch (e) {
    console.log("vendor dir not found");
  }
  done();
});


/**
 * Run composer install --no-dev in src dir.
 */
gulp.task('composer-install', shell.task([
    'cd src && php ../composer.phar install --no-dev'
]));

/**
 * Ensure composer is installed with no dev packages (we don't want to ship dev packages).
 * To ensure no dev packages are present we rename vendor to vendor-dev then rename it back after the install.
 */
gulp.task('composer', function (done) {
  return sequence('vendor-rename-pre', 'composer-install', 'vendor-rename-post', done);
});

/**
 * Task to build free version of Zip Recipes.
 */
gulp.task("free-sequence", function (cb) {
  return sequence("clean-free", ["composer", "plugins-free", "build-free-js"], "build-free", "compress-free", cb);
});

gulp.task("premium-sequence", function (cb) {
  return sequence("clean-premium", ["composer", "plugins-premium", "build-premium-js"], "build-premium", "compress-premium", cb);
});

/**
 * Task to build free and premium versions.
 */
gulp.task("build", function(cb) {
  return sequence("clean", ["free-sequence", "premium-sequence"]);
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
