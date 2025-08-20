# 🚀 Production Build & Deployment Guide

## Overview
This guide explains how to create production-ready builds of the WP Authenticator plugin every time you make code changes. Follow these workflows to ensure consistent, reliable deployments.

## 📋 Quick Start

### One-Command Production Build
```bash
# From plugin root directory
./deploy.sh
```
This creates a production-ready `wp-authenticator-v1.0.0.zip` package.

---

## 🔄 Development Workflow

### 1. **Development Phase**
```bash
# Make your code changes
git add .
git commit -m "Add new feature: user profile updates"

# Test locally (optional but recommended)
composer install --dev
php test-production-ready.php
```

### 2. **Pre-Production Checks**
```bash
# Ensure dependencies are clean
composer install --no-dev --optimize-autoloader

# Run syntax checks
find . -name "*.php" -not -path "./vendor/*" -exec php -l {} \;

# Test plugin structure
php test-production-ready.php
```

### 3. **Create Production Build**
```bash
# Run deployment script
./deploy.sh

# Verify the package
ls -la dist/wp-authenticator-v1.0.0.zip
```

---

## 🛠️ Detailed Build Process

### The `deploy.sh` Script Breakdown

```bash
#!/bin/bash
# 1. Clean previous builds
rm -rf dist/
mkdir -p dist/wp-authenticator

# 2. Copy plugin files (excludes dev files)
cp -r includes/ dist/wp-authenticator/
cp wp-authenticator.php dist/wp-authenticator/
cp README.md SECURITY.md JWT-ADMIN-GUIDE.md dist/wp-authenticator/
cp composer.json dist/wp-authenticator/

# 3. Install ONLY production dependencies
cd dist/wp-authenticator
composer install --no-dev --optimize-autoloader --no-interaction

# 4. Clean up development artifacts
find vendor/ -name "*.md" -delete
find vendor/ -name "phpunit.xml*" -delete
find vendor/ -name ".git*" -delete
find vendor/ -name "tests/" -type d -exec rm -rf {} + 2>/dev/null || true

# 5. Create distribution package
cd ..
zip -r wp-authenticator-v1.0.0.zip wp-authenticator/
```

### What Gets Included in Production
✅ **Core Plugin Files:**
- `wp-authenticator.php` (main plugin file)
- `includes/class-*.php` (all plugin classes)
- `composer.json` (dependency definition)

✅ **Production Dependencies:**
- `vendor/firebase/php-jwt/` (JWT library)
- `vendor/autoload.php` (Composer autoloader)
- `vendor/composer/` (Composer internals)

✅ **Documentation:**
- `README.md`
- `SECURITY.md`
- `JWT-ADMIN-GUIDE.md`

### What Gets Excluded
❌ **Development Files:**
- `.git/` directory
- `.gitignore`
- `test-*.php` files
- `node_modules/`
- Development dependencies

❌ **Build Artifacts:**
- Previous `dist/` folders
- Temporary files
- IDE configurations

---

## 🔧 Advanced Build Configurations

### Custom Build Script with Version Management

<function_calls>
<invoke name="create_file">
<parameter name="content">#!/bin/bash
# Advanced deployment script with version management
# Usage: ./deploy-advanced.sh [version] [environment]

set -e  # Exit on any error

# Configuration
PLUGIN_NAME="wp-authenticator"
DEFAULT_VERSION="1.0.0"
DEFAULT_ENV="production"

# Parse arguments
VERSION=${1:-$DEFAULT_VERSION}
ENVIRONMENT=${2:-$DEFAULT_ENV}
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BUILD_DIR="dist"
PACKAGE_NAME="${PLUGIN_NAME}-v${VERSION}"

echo "🚀 Building ${PLUGIN_NAME} v${VERSION} for ${ENVIRONMENT}"
echo "📅 Build timestamp: ${TIMESTAMP}"
echo "=========================================="

# Clean previous builds
echo "🧹 Cleaning previous builds..."
rm -rf ${BUILD_DIR}/
mkdir -p ${BUILD_DIR}/${PACKAGE_NAME}

# Copy core files
echo "📁 Copying core plugin files..."
cp wp-authenticator.php ${BUILD_DIR}/${PACKAGE_NAME}/
cp -r includes/ ${BUILD_DIR}/${PACKAGE_NAME}/
cp composer.json ${BUILD_DIR}/${PACKAGE_NAME}/

# Copy documentation
echo "📚 Copying documentation..."
cp README.md ${BUILD_DIR}/${PACKAGE_NAME}/
cp SECURITY.md ${BUILD_DIR}/${PACKAGE_NAME}/
cp JWT-ADMIN-GUIDE.md ${BUILD_DIR}/${PACKAGE_NAME}/
cp INSTALLATION.md ${BUILD_DIR}/${PACKAGE_NAME}/

# Copy examples (if they exist)
if [ -d "examples/" ]; then
    echo "📋 Copying examples..."
    cp -r examples/ ${BUILD_DIR}/${PACKAGE_NAME}/
fi

# Update version in main plugin file
echo "🔄 Updating version to ${VERSION}..."
sed -i.bak "s/Version: [0-9.]*/Version: ${VERSION}/" ${BUILD_DIR}/${PACKAGE_NAME}/wp-authenticator.php
sed -i.bak "s/define('WP_AUTHENTICATOR_VERSION', '[^']*')/define('WP_AUTHENTICATOR_VERSION', '${VERSION}')/" ${BUILD_DIR}/${PACKAGE_NAME}/wp-authenticator.php
rm ${BUILD_DIR}/${PACKAGE_NAME}/wp-authenticator.php.bak

# Install dependencies based on environment
echo "📦 Installing dependencies for ${ENVIRONMENT}..."
cd ${BUILD_DIR}/${PACKAGE_NAME}

if [ "$ENVIRONMENT" = "development" ]; then
    composer install --optimize-autoloader
    echo "   ✅ Development dependencies included"
elif [ "$ENVIRONMENT" = "testing" ]; then
    composer install --no-dev --optimize-autoloader
    echo "   ✅ Testing configuration applied"
else
    composer install --no-dev --optimize-autoloader --no-interaction
    echo "   ✅ Production dependencies only"
    
    # Clean up development files from vendor
    echo "🧹 Cleaning development artifacts..."
    find vendor/ -name "*.md" -delete 2>/dev/null || true
    find vendor/ -name "phpunit.xml*" -delete 2>/dev/null || true
    find vendor/ -name ".git*" -delete 2>/dev/null || true
    find vendor/ -name "tests/" -type d -exec rm -rf {} + 2>/dev/null || true
    find vendor/ -name "test/" -type d -exec rm -rf {} + 2>/dev/null || true
    echo "   ✅ Development artifacts removed"
fi

cd ../..

# Create build manifest
echo "📋 Creating build manifest..."
cat > ${BUILD_DIR}/${PACKAGE_NAME}/BUILD_INFO.txt << EOF
Build Information
================
Plugin: ${PLUGIN_NAME}
Version: ${VERSION}
Environment: ${ENVIRONMENT}
Build Date: $(date)
Build Timestamp: ${TIMESTAMP}
Git Commit: $(git rev-parse HEAD 2>/dev/null || echo "N/A")
Git Branch: $(git branch --show-current 2>/dev/null || echo "N/A")

Files Included:
$(find ${BUILD_DIR}/${PACKAGE_NAME} -type f | sort)

Dependencies:
$(cd ${BUILD_DIR}/${PACKAGE_NAME} && composer show --no-dev 2>/dev/null || echo "N/A")
EOF

# Run production tests
echo "🧪 Running production tests..."
cd ${BUILD_DIR}/${PACKAGE_NAME}
if php -r "
require_once 'vendor/autoload.php';
if (class_exists('Firebase\\\\JWT\\\\JWT')) {
    echo 'JWT: OK\\n';
} else {
    echo 'JWT: FAILED\\n';
    exit(1);
}
" 2>/dev/null; then
    echo "   ✅ Production tests passed"
else
    echo "   ❌ Production tests failed"
    exit 1
fi
cd ../..

# Create packages
echo "📦 Creating distribution packages..."

# Standard zip package
cd ${BUILD_DIR}
zip -r ${PACKAGE_NAME}.zip ${PACKAGE_NAME}/ -x "*.DS_Store*" "*__MACOSX*"
echo "   ✅ ZIP package: ${BUILD_DIR}/${PACKAGE_NAME}.zip"

# Create tar.gz for servers
tar -czf ${PACKAGE_NAME}.tar.gz ${PACKAGE_NAME}/
echo "   ✅ TAR package: ${BUILD_DIR}/${PACKAGE_NAME}.tar.gz"

# Create timestamped backup
cp ${PACKAGE_NAME}.zip ${PACKAGE_NAME}_${TIMESTAMP}.zip
echo "   ✅ Timestamped backup: ${BUILD_DIR}/${PACKAGE_NAME}_${TIMESTAMP}.zip"

cd ..

# Generate checksums
echo "🔐 Generating checksums..."
cd ${BUILD_DIR}
sha256sum ${PACKAGE_NAME}.zip > ${PACKAGE_NAME}.sha256
sha256sum ${PACKAGE_NAME}.tar.gz >> ${PACKAGE_NAME}.sha256
echo "   ✅ Checksums: ${BUILD_DIR}/${PACKAGE_NAME}.sha256"
cd ..

# Summary
echo ""
echo "🎉 Build Complete!"
echo "=================="
echo "Version: ${VERSION}"
echo "Environment: ${ENVIRONMENT}"
echo "Build Directory: ${BUILD_DIR}/"
echo ""
echo "📦 Packages Created:"
echo "   - ${PACKAGE_NAME}.zip (WordPress upload)"
echo "   - ${PACKAGE_NAME}.tar.gz (Server deployment)"
echo "   - ${PACKAGE_NAME}_${TIMESTAMP}.zip (Backup)"
echo ""
echo "📋 Next Steps:"
if [ "$ENVIRONMENT" = "production" ]; then
    echo "   1. Test the package: unzip ${BUILD_DIR}/${PACKAGE_NAME}.zip"
    echo "   2. Upload to WordPress: Admin > Plugins > Add New > Upload"
    echo "   3. Or deploy to server: extract to wp-content/plugins/"
elif [ "$ENVIRONMENT" = "testing" ]; then
    echo "   1. Deploy to testing environment"
    echo "   2. Run integration tests"
    echo "   3. Validate API endpoints"
else
    echo "   1. Continue development"
    echo "   2. Run local tests"
fi
echo ""
echo "🔗 Package Info:"
echo "   Size: $(du -h ${BUILD_DIR}/${PACKAGE_NAME}.zip | cut -f1)"
echo "   Files: $(find ${BUILD_DIR}/${PACKAGE_NAME} -type f | wc -l | tr -d ' ')"
echo "   SHA256: $(cat ${BUILD_DIR}/${PACKAGE_NAME}.sha256 | head -1 | cut -d' ' -f1)"
