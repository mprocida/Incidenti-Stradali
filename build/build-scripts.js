// build-scripts.js - Utility scripts for advanced build operations

const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');
const archiver = require('archiver');
const crypto = require('crypto');

class IncidentiPluginBuilder {
  constructor() {
    this.rootDir = path.resolve(__dirname, '..');
    this.buildDir = __dirname;
    this.assetsDir = path.join(this.rootDir, 'assets');
    this.srcDir = path.join(this.buildDir, '../src');
    
    this.config = {
      pluginName: 'incidenti-stradali',
      version: this.getPluginVersion(),
      textDomain: 'incidenti-stradali',
      minWpVersion: '5.0',
      minPhpVersion: '7.4'
    };
  }

  // Get current plugin version from main file
  getPluginVersion() {
    try {
      const pluginFile = path.join(this.rootDir, 'incidenti-stradali.php');
      const content = fs.readFileSync(pluginFile, 'utf8');
      const match = content.match(/Version:\s*([0-9.]+)/);
      return match ? match[1] : '1.0.0';
    } catch (error) {
      console.warn('Could not determine plugin version, using default');
      return '1.0.0';
    }
  }

  // Bump version (patch, minor, or major)
  bumpVersion(type = 'patch') {
    const versionParts = this.config.version.split('.').map(Number);
    
    switch (type) {
      case 'major':
        versionParts[0]++;
        versionParts[1] = 0;
        versionParts[2] = 0;
        break;
      case 'minor':
        versionParts[1]++;
        versionParts[2] = 0;
        break;
      case 'patch':
      default:
        versionParts[2]++;
        break;
    }
    
    const newVersion = versionParts.join('.');
    
    // Update main plugin file
    this.updatePluginVersion(newVersion);
    
    // Update package.json
    this.updatePackageVersion(newVersion);
    
    // Update readme.txt if exists
    this.updateReadmeVersion(newVersion);
    
    this.config.version = newVersion;
    console.log(`✅ Version bumped to ${newVersion}`);
    
    return newVersion;
  }

  // Update version in main plugin file
  updatePluginVersion(version) {
    const pluginFile = path.join(this.rootDir, 'incidenti-stradali.php');
    let content = fs.readFileSync(pluginFile, 'utf8');
    
    content = content.replace(
      /Version:\s*[0-9.]+/,
      `Version: ${version}`
    );
    
    content = content.replace(
      /define\(\s*['"]\s*INCIDENTI_VERSION\s*['"]\s*,\s*['"]\s*[0-9.]+\s*['"]\s*\)/,
      `define('INCIDENTI_VERSION', '${version}')`
    );
    
    fs.writeFileSync(pluginFile, content);
  }

  // Update package.json version
  updatePackageVersion(version) {
    const packageFile = path.join(this.buildDir, 'package.json');
    if (fs.existsSync(packageFile)) {
      const packageData = JSON.parse(fs.readFileSync(packageFile, 'utf8'));
      packageData.version = version;
      fs.writeFileSync(packageFile, JSON.stringify(packageData, null, 2));
    }
  }

  // Update readme.txt version (WordPress plugin directory format)
  updateReadmeVersion(version) {
    const readmeFile = path.join(this.rootDir, 'readme.txt');
    if (fs.existsSync(readmeFile)) {
      let content = fs.readFileSync(readmeFile, 'utf8');
      content = content.replace(
        /Stable tag:\s*[0-9.]+/,
        `Stable tag: ${version}`
      );
      fs.writeFileSync(readmeFile, content);
    }
  }

  // Generate checksums for security verification
  generateChecksums() {
    const checksums = {};
    const criticalFiles = [
      'incidenti-stradali.php',
      'uninstall.php'
    ];
    
    // Add all PHP files from includes directory
    const includesDir = path.join(this.rootDir, 'includes');
    if (fs.existsSync(includesDir)) {
      const phpFiles = fs.readdirSync(includesDir)
        .filter(file => file.endsWith('.php'))
        .map(file => `includes/${file}`);
      criticalFiles.push(...phpFiles);
    }
    
    criticalFiles.forEach(file => {
      const filePath = path.join(this.rootDir, file);
      if (fs.existsSync(filePath)) {
        const content = fs.readFileSync(filePath);
        checksums[file] = {
          md5: crypto.createHash('md5').update(content).digest('hex'),
          sha256: crypto.createHash('sha256').update(content).digest('hex'),
          size: content.length
        };
      }
    });
    
    const checksumFile = path.join(this.rootDir, 'checksums.json');
    fs.writeFileSync(checksumFile, JSON.stringify(checksums, null, 2));
    
    console.log(`✅ Generated checksums for ${Object.keys(checksums).length} files`);
    return checksums;
  }

  // Validate file integrity
  validateIntegrity() {
    const checksumFile = path.join(this.rootDir, 'checksums.json');
    
    if (!fs.existsSync(checksumFile)) {
      console.warn('⚠️  No checksums file found');
      return false;
    }
    
    const expectedChecksums = JSON.parse(fs.readFileSync(checksumFile, 'utf8'));
    let isValid = true;
    
    Object.keys(expectedChecksums).forEach(file => {
      const filePath = path.join(this.rootDir, file);
      
      if (!fs.existsSync(filePath)) {
        console.error(`❌ Missing file: ${file}`);
        isValid = false;
        return;
      }
      
      const content = fs.readFileSync(filePath);
      const actualMd5 = crypto.createHash('md5').update(content).digest('hex');
      
      if (actualMd5 !== expectedChecksums[file].md5) {
        console.error(`❌ Checksum mismatch: ${file}`);
        isValid = false;
      }
    });
    
    if (isValid) {
      console.log('✅ All file integrity checks passed');
    }
    
    return isValid;
  }

  // Clean build artifacts
  clean() {
    const pathsToClean = [
      path.join(this.assetsDir, 'css', '*.min.css'),
      path.join(this.assetsDir, 'css', '*.map'),
      path.join(this.assetsDir, 'js', '*.min.js'),
      path.join(this.assetsDir, 'js', '*.map'),
      path.join(this.buildDir, 'dist'),
      path.join(this.buildDir, 'temp')
    ];
    
    pathsToClean.forEach(pattern => {
      try {
        execSync(`rm -rf ${pattern}`, { stdio: 'inherit' });
      } catch (error) {
        // Path might not exist, continue
      }
    });
    
    console.log('✅ Build artifacts cleaned');
  }

  // Create distribution package
  async createDistribution() {
    const distDir = path.join(this.buildDir, 'dist');
    const zipPath = path.join(distDir, `${this.config.pluginName}-${this.config.version}.zip`);
    
    // Ensure dist directory exists
    if (!fs.existsSync(distDir)) {
      fs.mkdirSync(distDir, { recursive: true });
    }
    
    return new Promise((resolve, reject) => {
      const output = fs.createWriteStream(zipPath);
      const archive = archiver('zip', { zlib: { level: 9 } });
      
      output.on('close', () => {
        const sizeInMB = (archive.pointer() / 1024 / 1024).toFixed(2);
        console.log(`✅ Plugin package created: ${zipPath} (${sizeInMB} MB)`);
        resolve(zipPath);
      });
      
      archive.on('error', reject);
      archive.pipe(output);
      
      // Add files to archive with exclusions
      archive.glob('**/*', {
        cwd: this.rootDir,
        ignore: [
          'build/**',
          'src/**',
          'node_modules/**',
          '.git/**',
          '.gitignore',
          '.DS_Store',
          'Thumbs.db',
          '*.log',
          'tests/**',
          'phpunit.xml',
          'composer.json',
          'composer.lock',
          '.phpcs.xml'
        ]
      });
      
      archive.finalize();
    });
  }

  // WordPress.org SVN preparation
  prepareSvnRelease() {
    const svnDir = path.join(this.buildDir, 'svn-release');
    const trunkDir = path.join(svnDir, 'trunk');
    const tagsDir = path.join(svnDir, 'tags', this.config.version);
    
    // Clean previous SVN directory
    if (fs.existsSync(svnDir)) {
      execSync(`rm -rf ${svnDir}`);
    }
    
    // Create SVN structure
    fs.mkdirSync(trunkDir, { recursive: true });
    fs.mkdirSync(tagsDir, { recursive: true });
    
    // Copy files to trunk
    execSync(`rsync -av --exclude-from=${path.join(this.buildDir, 'svn-exclude.txt')} ${this.rootDir}/ ${trunkDir}/`);
    
    // Copy trunk to version tag
    execSync(`cp -r ${trunkDir}/* ${tagsDir}/`);
    
    console.log(`✅ SVN release prepared in ${svnDir}`);
    return svnDir;
  }

  // Run WordPress coding standards check
  runCodingStandards() {
    try {
      console.log('🔍 Running WordPress Coding Standards check...');
      
      const phpcsCommand = `phpcs --standard=WordPress --extensions=php ${this.rootDir}/includes/ ${this.rootDir}/incidenti-stradali.php`;
      
      execSync(phpcsCommand, { 
        stdio: 'inherit',
        cwd: this.rootDir 
      });
      
      console.log('✅ Coding standards check passed');
      return true;
    } catch (error) {
      console.error('❌ Coding standards check failed');
      return false;
    }
  }

  // Generate translation files
  generateTranslations() {
    try {
      console.log('🌐 Generating translation files...');
      
      const languagesDir = path.join(this.rootDir, 'languages');
      
      // Ensure languages directory exists
      if (!fs.existsSync(languagesDir)) {
        fs.mkdirSync(languagesDir, { recursive: true });
      }
      
      // Generate POT file
      const potCommand = `wp i18n make-pot ${this.rootDir} ${languagesDir}/${this.config.textDomain}.pot --domain=${this.config.textDomain}`;
      
      execSync(potCommand, { stdio: 'inherit' });
      
      console.log('✅ Translation template generated');
      return true;
    } catch (error) {
      console.warn('⚠️  Translation generation failed - WP-CLI might not be available');
      return false;
    }
  }

  // Validate plugin structure
  validateStructure() {
    const requiredFiles = [
      'incidenti-stradali.php',
      'includes/class-custom-post-type.php',
      'includes/class-meta-boxes.php',
      'includes/class-export-functions.php',
      'assets/css/frontend.min.css',
      'assets/css/admin.min.css',
      'assets/js/frontend.min.js',
      'assets/js/admin.min.js'
    ];
    
    let isValid = true;
    
    requiredFiles.forEach(file => {
      const filePath = path.join(this.rootDir, file);
      if (!fs.existsSync(filePath)) {
        console.error(`❌ Missing required file: ${file}`);
        isValid = false;
      }
    });
    
    if (isValid) {
      console.log('✅ Plugin structure validation passed');
    }
    
    return isValid;
  }

  // Complete build process
  async build(options = {}) {
    console.log(`🏗️  Building ${this.config.pluginName} v${this.config.version}...`);
    
    try {
      // Clean previous build
      if (options.clean !== false) {
        this.clean();
      }
      
      // Validate structure
      if (!this.validateStructure()) {
        throw new Error('Plugin structure validation failed');
      }
      
      // Run coding standards (optional)
      if (options.codingStandards) {
        this.runCodingStandards();
      }
      
      // Generate checksums
      this.generateChecksums();
      
      // Generate translations
      if (options.translations !== false) {
        this.generateTranslations();
      }
      
      // Create distribution package
      if (options.package !== false) {
        await this.createDistribution();
      }
      
      // Prepare SVN release if requested
      if (options.svn) {
        this.prepareSvnRelease();
      }
      
      console.log('🎉 Build completed successfully!');
      
    } catch (error) {
      console.error('❌ Build failed:', error.message);
      process.exit(1);
    }
  }
}

// CLI interface
if (require.main === module) {
  const builder = new IncidentiPluginBuilder();
  const args = process.argv.slice(2);
  const command = args[0];
  
  switch (command) {
    case 'build':
      builder.build({
        clean: true,
        codingStandards: args.includes('--phpcs'),
        translations: !args.includes('--no-translations'),
        package: !args.includes('--no-package'),
        svn: args.includes('--svn')
      });
      break;
      
    case 'version':
      const type = args[1] || 'patch';
      builder.bumpVersion(type);
      break;
      
    case 'clean':
      builder.clean();
      break;
      
    case 'validate':
      const structureValid = builder.validateStructure();
      const integrityValid = builder.validateIntegrity();
      process.exit(structureValid && integrityValid ? 0 : 1);
      break;
      
    case 'checksums':
      builder.generateChecksums();
      break;
      
    case 'package':
      builder.createDistribution();
      break;
      
    case 'translations':
      builder.generateTranslations();
      break;
      
    default:
      console.log(`
📦 Incidenti Stradali Plugin Builder

Usage: node build-scripts.js <command> [options]

Commands:
  build [options]    - Complete build process
  version [type]     - Bump version (patch|minor|major)
  clean             - Clean build artifacts
  validate          - Validate plugin structure and integrity
  checksums         - Generate file checksums
  package           - Create distribution package
  translations      - Generate translation files

Build Options:
  --phpcs           - Run PHP CodeSniffer
  --no-translations - Skip translation generation
  --no-package      - Skip package creation
  --svn             - Prepare SVN release

Examples:
  node build-scripts.js build --phpcs
  node build-scripts.js version minor
  node build-scripts.js build --svn
      `);
      break;
  }
}

module.exports = IncidentiPluginBuilder;