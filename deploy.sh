#!/bin/bash
# WordPress Plugin Deployment Script
# This script creates a production-ready plugin package

echo "🚀 Building WP Authenticator Plugin for Production..."

# Create deployment directory
rm -rf dist/
mkdir -p dist/wp-authenticator

# Copy plugin files (excluding development files)
echo "📁 Copying plugin files..."
cp -r includes/ dist/wp-authenticator/includes/
cp -r routes/ dist/wp-authenticator/routes/
cp -r docs/ dist/wp-authenticator/docs/
cp wp-authenticator.php dist/wp-authenticator/
cp README.md dist/wp-authenticator/
cp CHANGELOG.md dist/wp-authenticator/
cp API_Docs.md dist/wp-authenticator/
cp composer.json dist/wp-authenticator/
cp composer.lock dist/wp-authenticator/
cp readme.txt dist/wp-authenticator/

# Copy additional files if they exist
if [ -f "SECURITY.md" ]; then
    cp SECURITY.md dist/wp-authenticator/
fi

if [ -f "JWT-ADMIN-GUIDE.md" ]; then
    cp JWT-ADMIN-GUIDE.md dist/wp-authenticator/
fi

if [ -d "examples/" ]; then
    cp -r examples/ dist/wp-authenticator/examples/
fi

# Copy test files for reference
if [ -f "test-3-step-registration.php" ]; then
    cp test-3-step-registration.php dist/wp-authenticator/
fi

# Copy Swagger test files for reference
if [ -f "test-swagger-integration.php" ]; then
    cp test-swagger-integration.php dist/wp-authenticator/
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
zip -r wp-authenticator-v1.1.0.zip wp-authenticator/ -x "*.DS_Store*" "*__MACOSX*"

echo "✅ Plugin package created: dist/wp-authenticator-v1.1.0.zip"
echo ""
echo "📋 Installation Instructions:"
echo "1. Upload wp-authenticator-v1.1.0.zip to WordPress admin"
echo "2. Activate the plugin"
echo "3. Configure JWT settings in WP Admin > WP Authenticator"
echo ""
echo "🔧 Manual Installation:"
echo "1. Extract to: wp-content/plugins/"
echo "2. Ensure vendor/ directory is included"
echo "3. Activate in WordPress admin"
echo ""
echo "🆕 New Features in v1.1.0:"
echo "• 3-Step Registration Process with Email Verification"
echo "• Organized Endpoint Structure in Subfolders"
echo "• Enhanced Session Management"
echo "• Refresh Token Support in Registration"
echo "• Comprehensive Documentation"
echo ""
echo "📚 Documentation:"
echo "• API Reference: API_Docs.md"
echo "• 3-Step Registration: docs/3-step-registration.md"
echo "• Token Management: docs/token-management.md"
echo "• Endpoint Organization: docs/endpoint-organization.md"
