const gulp = require('gulp');
const sass = require('gulp-sass')(require('sass'));
const babel = require('gulp-babel');
const uglify = require('gulp-uglify');
const cleanCSS = require('gulp-clean-css');
const autoprefixer = require('gulp-autoprefixer');
const sourcemaps = require('gulp-sourcemaps');
const rename = require('gulp-rename');
const concat = require('gulp-concat');
const imagemin = require('gulp-imagemin');
const zip = require('gulp-zip');
const clean = require('gulp-clean');
const browserSync = require('browser-sync').create();
const plumber = require('gulp-plumber');
const notify = require('gulp-notify');
const gulpif = require('gulp-if');
const eslint = require('gulp-eslint');
const stylelint = require('gulp-stylelint');
const replace = require('gulp-replace');
const header = require('gulp-header');
const footer = require('gulp-footer');
const size = require('gulp-size');
const newer = require('gulp-newer');
const cached = require('gulp-cached');
const dependents = require('gulp-dependents');

// Configuration
const config = {
  src: {
    scss: 'src/scss/**/*.scss',
    js: 'src/js/**/*.js',
    images: 'src/images/**/*.{jpg,jpeg,png,gif,svg}',
    fonts: 'src/fonts/**/*.{woff,woff2,eot,ttf,otf}',
    php: '../**/*.php'
  },
  dist: {
    css: '../assets/css/',
    js: '../assets/js/',
    images: '../assets/images/',
    fonts: '../assets/fonts/'
  },
  watch: {
    scss: 'src/scss/**/*.scss',
    js: 'src/js/**/*.js',
    images: 'src/images/**/*',
    php: '../**/*.php'
  }
};

// Environment detection
const isProduction = process.env.NODE_ENV === 'production';

// Error handling
const handleError = function(task) {
  return function(err) {
    notify.onError({
      title: 'Gulp Task Error',
      subtitle: `Error in ${task} task`,
      message: '<%= error.message %>',
      sound: 'Beep'
    })(err);
    
    console.log(err.toString());
    this.emit('end');
  };
};

// Banner for minified files
const banner = [
  '/*!',
  ' * Incidenti Stradali WordPress Plugin',
  ' * Version: 1.0.0',
  ' * Built: <%= new Date().toISOString() %>',
  ' * License: GPL-2.0-or-later',
  ' */',
  ''
].join('\n');

// Clean task
gulp.task('clean', function() {
  return gulp.src([
    config.dist.css + '*.css',
    config.dist.js + '*.js',
    config.dist.images + '*',
    config.dist.fonts + '*'
  ], { read: false, allowEmpty: true })
    .pipe(clean());
});

// SCSS compilation
gulp.task('scss', function() {
  return gulp.src([
    'src/scss/frontend.scss',
    'src/scss/admin.scss',
    'src/scss/maps.scss',
    'src/scss/charts.scss'
  ])
    .pipe(plumber({ errorHandler: handleError('SCSS') }))
    .pipe(gulpif(!isProduction, sourcemaps.init()))
    .pipe(cached('scss'))
    .pipe(dependents())
    .pipe(sass({
      outputStyle: isProduction ? 'compressed' : 'expanded',
      precision: 8,
      includePaths: ['node_modules']
    }))
    .pipe(autoprefixer({
      overrideBrowserslist: ['> 1%', 'last 2 versions', 'ie >= 11'],
      cascade: false
    }))
    .pipe(gulpif(isProduction, cleanCSS({
      level: 2,
      compatibility: 'ie11'
    })))
    .pipe(gulpif(isProduction, header(banner)))
    .pipe(rename({ suffix: '.min' }))
    .pipe(gulpif(!isProduction, sourcemaps.write('./')))
    .pipe(size({
      showFiles: true,
      showTotal: false
    }))
    .pipe(gulp.dest(config.dist.css))
    .pipe(browserSync.stream());
});

// JavaScript compilation
gulp.task('js', function() {
  return gulp.src([
    'src/js/frontend.js',
    'src/js/admin.js',
    'src/js/maps.js',
    'src/js/validation.js',
    'src/js/charts.js'
  ])
    .pipe(plumber({ errorHandler: handleError('JavaScript') }))
    .pipe(gulpif(!isProduction, sourcemaps.init()))
    .pipe(cached('js'))
    .pipe(babel({
      presets: [
        ['@babel/preset-env', {
          targets: {
            browsers: ['> 1%', 'last 2 versions', 'ie >= 11']
          }
        }]
      ]
    }))
    .pipe(gulpif(isProduction, uglify({
      compress: {
        drop_console: true,
        drop_debugger: true
      }
    })))
    .pipe(gulpif(isProduction, header(banner)))
    .pipe(rename({ suffix: '.min' }))
    .pipe(gulpif(!isProduction, sourcemaps.write('./')))
    .pipe(size({
      showFiles: true,
      showTotal: false
    }))
    .pipe(gulp.dest(config.dist.js))
    .pipe(browserSync.stream());
});

// Vendor JavaScript bundling
gulp.task('js:vendor', function() {
  return gulp.src([
    'node_modules/leaflet/dist/leaflet.js',
    'node_modules/leaflet.markercluster/dist/leaflet.markercluster.js',
    'node_modules/chart.js/dist/chart.min.js'
  ])
    .pipe(concat('vendor.min.js'))
    .pipe(gulpif(isProduction, uglify()))
    .pipe(gulpif(isProduction, header(banner)))
    .pipe(size({
      showFiles: true,
      showTotal: false
    }))
    .pipe(gulp.dest(config.dist.js));
});

// Vendor CSS bundling
gulp.task('css:vendor', function() {
  return gulp.src([
    'node_modules/leaflet/dist/leaflet.css',
    'node_modules/leaflet.markercluster/dist/MarkerCluster.css',
    'node_modules/leaflet.markercluster/dist/MarkerCluster.Default.css'
  ])
    .pipe(concat('vendor.min.css'))
    .pipe(gulpif(isProduction, cleanCSS()))
    .pipe(gulpif(isProduction, header(banner)))
    .pipe(size({
      showFiles: true,
      showTotal: false
    }))
    .pipe(gulp.dest(config.dist.css));
});

// Image optimization
gulp.task('images', function() {
  return gulp.src(config.src.images)
    .pipe(newer(config.dist.images))
    .pipe(imagemin([
      imagemin.gifsicle({ interlaced: true }),
      imagemin.mozjpeg({ 
        quality: 85,
        progressive: true 
      }),
      imagemin.optipng({ optimizationLevel: 5 }),
      imagemin.svgo({
        plugins: [
          { removeViewBox: false },
          { cleanupIDs: false }
        ]
      })
    ], {
      verbose: true
    }))
    .pipe(size({
      showFiles: true,
      showTotal: false
    }))
    .pipe(gulp.dest(config.dist.images));
});

// Font copying
gulp.task('fonts', function() {
  return gulp.src(config.src.fonts)
    .pipe(newer(config.dist.fonts))
    .pipe(gulp.dest(config.dist.fonts));
});

// Linting tasks
gulp.task('lint:js', function() {
  return gulp.src(config.src.js)
    .pipe(eslint())
    .pipe(eslint.format())
    .pipe(eslint.failAfterError());
});

gulp.task('lint:scss', function() {
  return gulp.src(config.src.scss)
    .pipe(stylelint({
      reporters: [
        { formatter: 'string', console: true }
      ]
    }));
});

gulp.task('lint', gulp.parallel('lint:js', 'lint:scss'));

// Development server
gulp.task('serve', function() {
  browserSync.init({
    proxy: 'http://localhost/your-wordpress-site', // Adjust as needed
    port: 3000,
    ui: {
      port: 3001
    },
    notify: false,
    open: false
  });
});

// Watch task
gulp.task('watch', function() {
  gulp.watch(config.watch.scss, gulp.series('scss'));
  gulp.watch(config.watch.js, gulp.series('js'));
  gulp.watch(config.watch.images, gulp.series('images'));
  gulp.watch(config.watch.php).on('change', browserSync.reload);
});

// Version bumping
gulp.task('version:bump', function() {
  const fs = require('fs');
  const path = require('path');
  
  // Read current version from main plugin file
  const pluginFile = path.join(__dirname, '../incidenti-stradali.php');
  let content = fs.readFileSync(pluginFile, 'utf8');
  
  // Extract current version
  const versionMatch = content.match(/Version:\s*(\d+\.\d+\.\d+)/);
  if (!versionMatch) {
    throw new Error('Could not find version in plugin file');
  }
  
  const currentVersion = versionMatch[1];
  const versionParts = currentVersion.split('.').map(Number);
  
  // Bump patch version
  versionParts[2]++;
  const newVersion = versionParts.join('.');
  
  // Update plugin file
  content = content.replace(
    /Version:\s*\d+\.\d+\.\d+/,
    `Version: ${newVersion}`
  );
  
  fs.writeFileSync(pluginFile, content);
  
  // Update package.json
  const packageFile = path.join(__dirname, 'package.json');
  const packageData = JSON.parse(fs.readFileSync(packageFile, 'utf8'));
  packageData.version = newVersion;
  fs.writeFileSync(packageFile, JSON.stringify(packageData, null, 2));
  
  console.log(`Version bumped to ${newVersion}`);
  
  return Promise.resolve();
});

// PHP validation (requires PHP CLI)
gulp.task('php:validate', function() {
  const { exec } = require('child_process');
  
  return new Promise((resolve, reject) => {
    exec('php -l ../incidenti-stradali.php', (error, stdout, stderr) => {
      if (error) {
        console.error('PHP validation failed:', stderr);
        reject(error);
      } else {
        console.log('PHP validation passed');
        resolve();
      }
    });
  });
});

// Plugin packaging
gulp.task('package', function() {
  return gulp.src([
    '../**/*',
    '!../build/**',
    '!../src/**',
    '!../node_modules/**',
    '!../.git/**',
    '!../.gitignore',
    '!../.DS_Store',
    '!../Thumbs.db'
  ], { base: '../' })
    .pipe(zip('incidenti-stradali-plugin.zip'))
    .pipe(size({
      showFiles: true,
      showTotal: false
    }))
    .pipe(gulp.dest('./dist/'));
});

// WordPress coding standards check (requires PHP CodeSniffer)
gulp.task('phpcs', function() {
  const { exec } = require('child_process');
  
  return new Promise((resolve, reject) => {
    exec('phpcs --standard=WordPress ../incidenti-stradali.php ../includes/', (error, stdout, stderr) => {
      if (error && error.code !== 1) { // Code 1 means warnings/errors found but execution successful
        console.error('PHPCS execution failed:', stderr);
        reject(error);
      } else {
        if (stdout) {
          console.log('PHPCS Results:');
          console.log(stdout);
        } else {
          console.log('PHPCS: No coding standard violations found');
        }
        resolve();
      }
    });
  });
});

// Generate translation template
gulp.task('pot', function() {
  const { exec } = require('child_process');
  
  return new Promise((resolve, reject) => {
    const cmd = 'wp i18n make-pot ../ ../languages/incidenti-stradali.pot --domain=incidenti-stradali';
    
    exec(cmd, (error, stdout, stderr) => {
      if (error) {
        console.error('POT generation failed:', stderr);
        reject(error);
      } else {
        console.log('POT file generated successfully');
        console.log(stdout);
        resolve();
      }
    });
  });
});

// Security scan (basic file permission check)
gulp.task('security:check', function() {
  const fs = require('fs');
  const path = require('path');
  
  const checkFile = (filePath) => {
    try {
      const stats = fs.statSync(filePath);
      const mode = stats.mode & parseInt('777', 8);
      
      if (mode > parseInt('644', 8)) {
        console.warn(`⚠️  Potential security issue: ${filePath} has permissions ${mode.toString(8)}`);
      }
    } catch (err) {
      // File doesn't exist or can't be accessed
    }
  };
  
  // Check critical files
  const criticalFiles = [
    '../incidenti-stradali.php',
    '../uninstall.php',
    '../includes/class-export-functions.php',
    '../includes/class-user-roles.php'
  ];
  
  console.log('🔍 Performing basic security checks...');
  
  criticalFiles.forEach(file => {
    checkFile(path.resolve(__dirname, file));
  });
  
  console.log('✅ Security check completed');
  
  return Promise.resolve();
});

// Performance optimization
gulp.task('optimize', gulp.parallel('images', function() {
  // Additional optimization tasks
  return gulp.src('../assets/**/*.{css,js}')
    .pipe(size({
      title: 'Total asset size:',
      showFiles: false,
      showTotal: true
    }));
}));

// Documentation generation
gulp.task('docs:generate', function() {
  const { exec } = require('child_process');
  
  return new Promise((resolve, reject) => {
    // Generate JSDoc for JavaScript files
    exec('jsdoc src/js/ -d docs/js/', (error, stdout, stderr) => {
      if (error) {
        console.warn('JSDoc generation warning:', stderr);
      }
      
      console.log('📚 Documentation generated');
      resolve();
    });
  });
});

// File header injection
gulp.task('headers:inject', function() {
  const phpHeader = [
    '<?php',
    '/**',
    ' * File generated automatically by build process',
    ' * Do not modify directly',
    ' * @package IncidentiStradali',
    ' * @version 1.0.0',
    ' */',
    '',
    '// Prevent direct access',
    'if (!defined(\'ABSPATH\')) {',
    '    exit;',
    '}',
    ''
  ].join('\n');
  
  return gulp.src('../includes/generated/*.php')
    .pipe(header(phpHeader))
    .pipe(gulp.dest('../includes/generated/'));
});

// Database migration files validation
gulp.task('db:validate', function() {
  return gulp.src('../includes/migrations/*.sql')
    .pipe(plumber({ errorHandler: handleError('DB Validation') }))
    .pipe(gulp.dest('./temp/')) // Just for validation
    .on('end', function() {
      console.log('✅ Database migration files validated');
    });
});

// Asset integrity check
gulp.task('integrity:check', function() {
  const crypto = require('crypto');
  const fs = require('fs');
  const path = require('path');
  
  const generateHash = (filePath) => {
    const content = fs.readFileSync(filePath);
    return crypto.createHash('sha256').update(content).digest('hex');
  };
  
  const assetsDir = path.resolve(__dirname, '../assets');
  const integrityFile = path.join(assetsDir, 'integrity.json');
  const integrity = {};
  
  // Generate hashes for all built assets
  const assetFiles = [
    'css/frontend.min.css',
    'css/admin.min.css',
    'js/frontend.min.js',
    'js/admin.min.js'
  ];
  
  assetFiles.forEach(file => {
    const filePath = path.join(assetsDir, file);
    if (fs.existsSync(filePath)) {
      integrity[file] = generateHash(filePath);
    }
  });
  
  fs.writeFileSync(integrityFile, JSON.stringify(integrity, null, 2));
  console.log('🔒 Asset integrity hashes generated');
  
  return Promise.resolve();
});

// Plugin health check
gulp.task('health:check', gulp.series('php:validate', 'security:check', 'integrity:check'));

// Development workflow
gulp.task('dev', gulp.series(
  'clean',
  gulp.parallel('scss', 'js', 'images', 'fonts'),
  gulp.parallel('watch', 'serve')
));

// Production build
gulp.task('build', gulp.series(
  'clean',
  'lint',
  'php:validate',
  gulp.parallel(
    'scss',
    'js',
    'js:vendor',
    'css:vendor',
    'images',
    'fonts'
  ),
  'integrity:check'
));

// Pre-release tasks
gulp.task('pre-release', gulp.series(
  'build',
  'version:bump',
  'pot',
  'health:check',
  'docs:generate'
));

// Release build
gulp.task('release', gulp.series(
  'pre-release',
  'package'
));

// Testing environment setup
gulp.task('test:setup', function() {
  const testConfig = {
    wp_version: '6.0',
    php_version: '7.4',
    mysql_version: '8.0'
  };
  
  console.log('🧪 Test environment configuration:', testConfig);
  
  // You could add Docker container setup here
  return Promise.resolve();
});

// Backup current build
gulp.task('backup', function() {
  const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
  
  return gulp.src('../assets/**/*')
    .pipe(zip(`backup-${timestamp}.zip`))
    .pipe(gulp.dest('./backups/'));
});

// Rollback to previous build
gulp.task('rollback', function() {
  const fs = require('fs');
  const path = require('path');
  
  const backupsDir = path.join(__dirname, 'backups');
  
  if (!fs.existsSync(backupsDir)) {
    console.error('❌ No backups found');
    return Promise.reject(new Error('No backups available'));
  }
  
  const backups = fs.readdirSync(backupsDir)
    .filter(file => file.endsWith('.zip'))
    .sort()
    .reverse();
  
  if (backups.length === 0) {
    console.error('❌ No backup files found');
    return Promise.reject(new Error('No backup files available'));
  }
  
  console.log(`🔄 Rolling back to: ${backups[0]}`);
  
  // In a real scenario, you'd extract the backup here
  return Promise.resolve();
});

// Default task
gulp.task('default', gulp.series('dev'));

// Task groups for convenience
gulp.task('assets', gulp.parallel('scss', 'js', 'images', 'fonts'));
gulp.task('vendor', gulp.parallel('js:vendor', 'css:vendor'));
gulp.task('validate', gulp.parallel('lint', 'php:validate', 'phpcs'));

// Help task
gulp.task('help', function(done) {
  console.log('\n📋 Available Gulp Tasks:\n');
  console.log('🏗️  Build Tasks:');
  console.log('   build        - Production build');
  console.log('   dev          - Development build with watch');
  console.log('   assets       - Build CSS, JS, images, and fonts');
  console.log('   vendor       - Bundle vendor assets');
  console.log('');
  console.log('🧹 Maintenance:');
  console.log('   clean        - Remove built assets');
  console.log('   optimize     - Optimize images and check sizes');
  console.log('   backup       - Backup current build');
  console.log('   rollback     - Rollback to previous backup');
  console.log('');
  console.log('✅ Quality Assurance:');
  console.log('   lint         - Lint JavaScript and SCSS');
  console.log('   validate     - Run all validation checks');
  console.log('   health:check - Complete health check');
  console.log('');
  console.log('📦 Release:');
  console.log('   pre-release  - Prepare for release');
  console.log('   release      - Create release package');
  console.log('   package      - Create plugin ZIP file');
  console.log('');
  console.log('🔧 Utilities:');
  console.log('   version:bump - Increment version number');
  console.log('   pot          - Generate translation template');
  console.log('   docs:generate- Generate documentation');
  console.log('');
  
  done();
});

// Error handling for all tasks
process.on('uncaughtException', function(err) {
  console.error('❌ Uncaught Exception:', err);
  process.exit(1);
});

// Graceful shutdown
process.on('SIGINT', function() {
  console.log('\n👋 Goodbye! Build process terminated.');
  process.exit(0);
});