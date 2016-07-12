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
var sort = require('gulp-sort');
var path = require('path');

const build_path = "build";
const dest_free = path.join(build_path, "free");
const dest_premium = path.join(build_path, "premium");
const translationTemplateFilePath = "./src/languages/zip-recipes.pot";

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

  return gulp.src([
    "src/**",
    "!src/README.md",
    "!src/composer.*",
    "!node_modules/**",
    "!src/vendor-dev/**",
    "!src/vendor-dev",
    "LICENSE",
    "!src/plugins/**"
  ])
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
  // Ship UsageStats plugin with free version
  return gulp.src(["src/plugins/index.php", "src/plugins/UsageStats/**"], {base: "src"})
    .pipe(gulp.dest(dest_free));
});

gulp.task("plugins-premium", function () {
  // Don't ship UsageStats plugin with premium version
  return gulp.src(["src/plugins/**", "!src/plugins/{UsageStats,UsageStats/**}"], {base: "src"})
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
  fs.rename('src/vendor', 'src/vendor-dev', function(err) {
    if (err) {
      console.log("Can't rename `vendor` to `vendor-dev`.");
    }
    done();
  });
});

/**
 * Renames vendor-dev to vendor if if it exists. Removes `vendor` contents
 * This restores vendor name for vendor packages that include dev packages
 */
gulp.task('vendor-rename-post', function(done) {
  del('src/vendor/**')
    .then(function () {
      return fs.rename('src/vendor-dev', 'src/vendor', function(err) {
        if (err) {
          console.log("Couldn't rename `vendor-dev` to `vendor`");
        }
      });
    })
    .catch(function() {
      console.log("Could not delete `src/vendor/`");
    })
    .then(function () {
      done();
    });
});


/**
 * Run composer install --no-dev in src dir.
 */
gulp.task('composer-install', shell.task([
    'cd src && php ../composer.phar install --no-dev'
]));

/**
 * Run composer install (includes dev packages)
 */
gulp.task('composer-dev-install', shell.task([
  'cd src && php ../composer.phar install'
]));

/**
 * Task to build free version of Zip Recipes.
 */
gulp.task("free-sequence", function (cb) {
  return sequence(
    "clean-free",
    ["plugins-free", "build-free-js"],
    "build-free",
    "compress-free",
    cb);
});

gulp.task("premium-sequence", function (cb) {
  return sequence(
    "clean-premium",
    ["plugins-premium", "build-premium-js"],
    "build-premium",
    "compress-premium",
    cb);
});

/**
 * Task to build free and premium versions.
 */
gulp.task("build", function(cb) {
  // we need to rename vendor to vendor-dev becaus we don't want to ship vendor dev
  return sequence(
    "clean",
    "composer-dev-install",
    "i18n", // needs vendor to include dev packages
    "vendor-rename-pre",
    "composer-install",
    ["free-sequence", "premium-sequence"],
    "vendor-rename-post",
    cb);
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

/**
 * Generate translation template (pot) file.
 */
gulp.task("generate-pot", function (done) {
  var twigFileFilter = filter(['**/*.twig'], {restore: true});
  var phpFileFilter = filter(['**/*.php'], {restore: true});

  // remove all contents of template file because it will not update strings
  require('fs').writeFileSync(translationTemplateFilePath, '');

  // Extract twig strings
  return gulp.src(["src/**/*.php", "src/**/*.twig", "!src/vendor/**", "!src/vendor-dev/**"], {read: false})
    .pipe(twigFileFilter)
    .pipe(shell([
      `./src/vendor/bin/twig-gettext-extractor --join-existing --output='${translationTemplateFilePath}' -L PHP --from-code='UTF-8' --default-domain='zip-recipes' --package-name="zip-recipes" --msgid-bugs-address="hello@ziprecipes.net" --copyright-holder="Zip Recipes Ltd" --keyword='__' --no-location --files <%= relativePath(file.path) %>`
    ], {templateData: {
      // Return relative path, given absolute one. Needed by twig-gettext-extractor
      relativePath: function (absolutePath) {
        var relPath = path.relative('.', absolutePath);
        return relPath;
      }
    }}, done))
    .pipe(twigFileFilter.restore)
    .pipe(phpFileFilter)
    .pipe(shell([
      `xgettext --join-existing --output='${translationTemplateFilePath}' --from-code='UTF-8' --default-domain='zip-recipes' --keyword='__' --no-location --package-name="zip-recipes" --msgid-bugs-address="hello@ziprecipes.net" --copyright-holder="Zip Recipes Ltd" <%= file.path %>`
    ]));
});

/**
 * Copy translation template into all languages.
 */
gulp.task('update-languages', function(done) {
  return gulp.src('./src/languages/*.po')
    .pipe(shell([`msgmerge -U <%= file.path %> ${translationTemplateFilePath}`])); // {verbose: true} to see details
});

/**
 * Generate binary translation files (mo)
 */
gulp.task('generate-mos', function(done) {
  return gulp.src('./src/languages/*.po')
    .pipe(shell([`msgfmt --output-file='./src/languages/<%= mo(file.path) %>' <%= file.path %>`
    ], { templateData:
    {
      mo: function(path) {
        return path.replace(/.+\/([a-zA-Z0-9-_]+)\.po/, '$1.mo');
      }
    }
    }));
});

gulp.task('i18n', function(done) {
  return sequence(
    "generate-pot",
    "update-languages",
    "generate-mos",
    done);
});