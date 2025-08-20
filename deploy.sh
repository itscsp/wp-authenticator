#!/bin/bash
# WordPress Plugin Deployment Script
# This script creates a production-ready plugin package

echo "🚀 Building WP Authenticator Plugin for Production..."

# Create deployment directory
rm -rf dist/
mkdir -p dist/wp-authenticator

# Copy plugin files (excluding development files)
echo "📁 Copying plugin files..."
cp -r includes/ dist/wp-authenticator/
cp wp-authenticator.php dist/wp-authenticator/
cp README.md dist/wp-authenticator/
cp SECURITY.md dist/wp-authenticator/
cp JWT-ADMIN-GUIDE.md dist/wp-authenticator/
cp composer.json dist/wp-authenticator/

# Copy examples if they exist
if [ -d "examples/" ]; then
    cp -r examples/ dist/wp-authenticator/
fi

# Install production dependencies
echo "📦 Installing production dependencies..."
cd dist/wp-authenticator
composer install --no-dev --optimize-autoloader --no-interaction

# Remove development files from vendor
echo "🧹 Cleaning up development files..."
find vendor/ -name "*.md" -delete
find vendor/ -name "phpunit.xml*" -delete
find vendor/ -name ".git*" -delete
find vendor/ -name "tests/" -type d -exec rm -rf {} + 2>/dev/null || true
find vendor/ -name "test/" -type d -exec rm -rf {} + 2>/dev/null || true

# Create zip package
echo "📦 Creating plugin package..."
cd ..
zip -r wp-authenticator-v1.0.0.zip wp-authenticator/ -x "*.DS_Store*" "*__MACOSX*"

echo "✅ Plugin package created: dist/wp-authenticator-v1.0.0.zip"
echo ""
echo "📋 Installation Instructions:"
echo "1. Upload wp-authenticator-v1.0.0.zip to WordPress admin"
echo "2. Activate the plugin"
echo "3. Configure JWT settings in WP Admin > WP Authenticator"
echo ""
echo "🔧 Manual Installation:"
echo "1. Extract to: wp-content/plugins/"
echo "2. Ensure vendor/ directory is included"
echo "3. Activate in WordPress admin"
