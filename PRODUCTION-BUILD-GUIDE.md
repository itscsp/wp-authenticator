# 🚀 Production Build & Deployment Guide

## Overview
This guide explains how to create production-ready builds of the WP Authenticator plugin every time you make code changes. Follow these workflows to ensure consistent, reliable deployments.

**🔥 IMPORTANT:** The deployment script has been fixed to resolve fatal errors during WordPress installation.

---

## 📋 Quick Start

### One-Command Production Build
```bash
# From plugin root directory
./deploy.sh
```
This creates a production-ready `wp-authenticator-v1.0.0.zip` package with correct file structure.

### Test Before Deployment
```bash
# Always test your package
cd dist/wp-authenticator/
php test-package.php
```

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

# Run syntax checks on all PHP files
find . -name "*.php" -not -path "./vendor/*" -exec php -l {} \;

# Test plugin structure
php test-production-ready.php
```

### 3. **Create Production Build**
```bash
# Run deployment script (FIXED VERSION)
./deploy.sh

# ALWAYS verify the package
cd dist/wp-authenticator/
php test-package.php

# Check file structure
ls -la includes/
ls -la vendor/firebase/php-jwt/
```

---

## 🛠️ Fixed Deployment Process

### The Updated `deploy.sh` Script

**🔧 Key Fix:** Proper directory structure preservation

```bash
#!/bin/bash
# FIXED: Ensures includes/ directory is properly structured

# Clean previous builds
rm -rf dist/
mkdir -p dist/wp-authenticator

# Copy files with CORRECT structure
cp -r includes/ dist/wp-authenticator/includes/  # ✅ FIXED
cp wp-authenticator.php dist/wp-authenticator/
cp README.md SECURITY.md JWT-ADMIN-GUIDE.md dist/wp-authenticator/
cp composer.json dist/wp-authenticator/

# Copy examples with correct structure  
if [ -d "examples/" ]; then
    cp -r examples/ dist/wp-authenticator/examples/  # ✅ FIXED
fi

# Install production dependencies
cd dist/wp-authenticator
composer install --no-dev --optimize-autoloader --no-interaction

# Clean development artifacts
find vendor/ -name "*.md" -delete
find vendor/ -name "phpunit.xml*" -delete
find vendor/ -name ".git*" -delete
find vendor/ -name "tests/" -type d -exec rm -rf {} + 2>/dev/null || true

# Create distribution package
cd ..
zip -r wp-authenticator-v1.0.0.zip wp-authenticator/
```

### What's Fixed ✅

**Before (Broken Structure):**
```
wp-authenticator/
├── class-jwt-handler.php        ❌ Files in wrong location
├── class-admin-settings.php     ❌ Causes fatal errors
├── wp-authenticator.php
└── vendor/
```

**After (Correct Structure):**
```
wp-authenticator/
├── includes/                    ✅ Proper directory
│   ├── class-jwt-handler.php    ✅ Correct location
│   ├── class-admin-settings.php ✅ Plugin finds files
│   ├── class-api-endpoints.php  ✅ No fatal errors
│   └── class-security-handler.php
├── examples/
│   └── jwt-usage-examples.php
├── vendor/
│   └── firebase/php-jwt/
├── wp-authenticator.php
├── README.md
├── SECURITY.md
└── composer.json
```

---

## 🧪 Quality Assurance Checklist

### Pre-Build Checks
- [ ] All PHP files pass syntax check
- [ ] Composer dependencies are clean
- [ ] No development files in includes/
- [ ] Git repository is clean

### Post-Build Verification
```bash
# 1. Test package structure
cd dist/wp-authenticator/
php test-package.php

# Expected output:
# ✅ Autoloader loaded
# ✅ Firebase JWT available  
# ✅ includes/class-jwt-handler.php
# ✅ includes/class-security-handler.php
# ✅ includes/class-admin-settings.php
# ✅ includes/class-api-endpoints.php
# ✅ All classes loaded successfully
```

### Manual Structure Verification
```bash
# 2. Check ZIP contents
unzip -l dist/wp-authenticator-v1.0.0.zip | grep includes/

# Should show:
# includes/class-jwt-handler.php
# includes/class-admin-settings.php
# includes/class-api-endpoints.php  
# includes/class-security-handler.php
```

### WordPress Installation Test
```bash
# 3. Test in clean WordPress install
# Upload zip file via WordPress admin
# Activate plugin
# Check for errors in debug.log
```

---

## 🔧 Advanced Build Configurations

### Version-Managed Builds
```bash
# Use the advanced deployment script
./deploy-advanced.sh 1.1.0 production

# Features:
# - Automatic version updates
# - Environment-specific builds
# - Build manifests with Git info
# - Multiple package formats
# - Integrity checksums
# - Automated testing
```

### Continuous Integration Ready
```bash
# Create CI/CD friendly script
#!/bin/bash
set -e  # Exit on any error

# Build
./deploy.sh

# Test
cd dist/wp-authenticator/
php test-package.php || exit 1

# Deploy
if [ "$CI" = "true" ]; then
    # Upload to releases
    echo "Deploying to production..."
fi
```

---

## 📦 Production Deployment Scenarios

### Scenario 1: WordPress Admin Upload ✅
```bash
# 1. Build package
./deploy.sh

# 2. Test package  
cd dist/wp-authenticator && php test-package.php

# 3. Upload wp-authenticator-v1.0.0.zip via WordPress admin
# 4. Activate plugin
# 5. Configure in WP Admin > WP Authenticator
```

### Scenario 2: Server Deployment ✅
```bash
# 1. Build and test package
./deploy.sh && cd dist/wp-authenticator && php test-package.php

# 2. Transfer to server
scp dist/wp-authenticator-v1.0.0.zip user@server:/tmp/

# 3. Extract on server
ssh user@server
cd /var/www/html/wp-content/plugins/
unzip /tmp/wp-authenticator-v1.0.0.zip

# 4. Set permissions
chmod -R 755 wp-authenticator/

# 5. Activate via WordPress admin
```

### Scenario 3: Development to Production ✅
```bash
# Development workflow
git checkout develop
# Make changes...
git add . && git commit -m "New feature"

# Build and test
./deploy.sh
cd dist/wp-authenticator && php test-package.php && cd ../..

# Merge to main
git checkout main
git merge develop

# Tag release
git tag v1.0.0
git push origin main --tags

# Deploy
./deploy-advanced.sh 1.0.0 production
```

---

## 🚨 Troubleshooting

### If Build Fails
```bash
# Check disk space
df -h

# Check permissions
ls -la includes/

# Verify Composer
composer --version
composer validate

# Clean and retry
rm -rf vendor/ composer.lock dist/
composer install --no-dev
./deploy.sh
```

### If Package Test Fails
```bash
# Check PHP version
php -v  # Requires 7.4+

# Test Firebase JWT separately
php -r "
require_once 'vendor/autoload.php';
var_dump(class_exists('Firebase\\JWT\\JWT'));
"

# Check file permissions
find . -name "*.php" -exec php -l {} \;
```

---

## 📈 Best Practices

### Always Follow This Sequence
1. ✅ Make code changes
2. ✅ Test locally 
3. ✅ Run `./deploy.sh`
4. ✅ Test package with `php test-package.php`
5. ✅ Verify ZIP structure
6. ✅ Deploy to WordPress

### Never Skip Testing
```bash
# This should ALWAYS pass before deployment
cd dist/wp-authenticator/
php test-package.php
```

### Keep Build Artifacts
```bash
# Archive successful builds
cp dist/wp-authenticator-v1.0.0.zip releases/
cp dist/wp-authenticator-v1.0.0.zip releases/wp-authenticator-v1.0.0_$(date +%Y%m%d).zip
```

---

## ✅ Confirmed: Fatal Error Issue Resolved

The fatal error when installing the WordPress plugin has been **COMPLETELY FIXED**:

- ✅ **Root Cause Identified:** Incorrect file structure in deployment package
- ✅ **Deploy Script Fixed:** Now preserves proper `includes/` directory structure  
- ✅ **Automated Testing Added:** `test-package.php` prevents future issues
- ✅ **Documentation Updated:** Clear troubleshooting guide provided
- ✅ **Verified Working:** Package tested and confirmed functional

**The new `wp-authenticator-v1.0.0.zip` package is ready for production deployment!**
