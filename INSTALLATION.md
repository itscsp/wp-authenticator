# 🚀 WP Authenticator Plugin Installation Guide

## How Package Imports Work

### 📦 **Understanding Dependencies**

This plugin uses **Firebase JWT library** for secure token handling. Here's how it works:

```php
// In wp-authenticator.php
if (file_exists(WP_AUTHENTICATOR_PLUGIN_PATH . 'vendor/autoload.php')) {
    require_once WP_AUTHENTICATOR_PLUGIN_PATH . 'vendor/autoload.php';
}
```

### 🔧 **Installation Methods**

#### **Method 1: WordPress Admin Upload (Recommended)**
```bash
# 1. Create production package
./deploy.sh

# 2. Upload dist/wp-authenticator-v1.0.0.zip via:
# WordPress Admin > Plugins > Add New > Upload Plugin
```

#### **Method 2: Manual Installation**
```bash
# 1. Extract plugin to WordPress
unzip wp-authenticator-v1.0.0.zip
mv wp-authenticator/ /path/to/wordpress/wp-content/plugins/

# 2. Ensure dependencies are included
ls wp-content/plugins/wp-authenticator/vendor/firebase/
```

#### **Method 3: Server-Side Installation (Advanced)**
```bash
# 1. Upload plugin files (without vendor/)
scp -r wp-authenticator/ user@server:/var/www/html/wp-content/plugins/

# 2. Install dependencies on server
ssh user@server
cd /var/www/html/wp-content/plugins/wp-authenticator/
composer install --no-dev --optimize-autoloader
```

### 🔍 **How WordPress Loads the Plugin**

1. **Plugin Activation**:
   ```php
   // WordPress scans wp-content/plugins/
   // Finds wp-authenticator.php with plugin header
   ```

2. **Autoloader Check**:
   ```php
   // Plugin checks for vendor/autoload.php
   if (file_exists(WP_AUTHENTICATOR_PLUGIN_PATH . 'vendor/autoload.php')) {
       require_once WP_AUTHENTICATOR_PLUGIN_PATH . 'vendor/autoload.php';
   }
   ```

3. **Firebase JWT Available**:
   ```php
   use Firebase\JWT\JWT;
   use Firebase\JWT\Key;
   // Classes are now available throughout the plugin
   ```

### ⚠️ **Common Issues & Solutions**

#### **Issue: "Class 'Firebase\JWT\JWT' not found"**
```bash
# Solution: Ensure vendor directory exists
ls wp-content/plugins/wp-authenticator/vendor/firebase/php-jwt/

# If missing, install dependencies:
cd wp-content/plugins/wp-authenticator/
composer install --no-dev
```

#### **Issue: Plugin won't activate**
```bash
# Check PHP version (requires 7.4+)
php -v

# Check file permissions
chmod -R 755 wp-content/plugins/wp-authenticator/
```

#### **Issue: JWT features not working**
```php
// Check if Firebase JWT is loaded
if (class_exists('Firebase\JWT\JWT')) {
    echo "✅ Firebase JWT loaded successfully";
} else {
    echo "❌ Firebase JWT not loaded - check vendor directory";
}
```

### 📋 **Pre-Installation Checklist**

- [ ] PHP 7.4 or higher
- [ ] WordPress 5.0 or higher
- [ ] `vendor/` directory included in plugin
- [ ] Firebase JWT library present
- [ ] File permissions set correctly

### 🔐 **Post-Installation Setup**

1. **Activate Plugin**:
   - Go to WordPress Admin > Plugins
   - Find "WP Authenticator"
   - Click "Activate"

2. **Configure JWT Settings**:
   - Go to WordPress Admin > WP Authenticator
   - Set JWT Secret Key
   - Configure token expiration
   - Select JWT algorithm

3. **Test API Endpoints**:
   ```bash
   # Test registration endpoint
   curl -X POST /wp-json/wp-auth/v1/register \
     -H "Content-Type: application/json" \
     -d '{"username":"test","email":"test@example.com","password":"password123"}'
   ```

### 🚀 **Distribution Best Practices**

#### **For End Users (WordPress Sites)**:
- Include `vendor/` directory in plugin zip
- Use `./deploy.sh` script to create clean package
- Include installation instructions

#### **For Developers**:
- Keep `composer.json` for dependency management
- Use `.gitignore` to exclude `vendor/` from repository
- Provide installation instructions for Composer

#### **For WordPress Plugin Directory**:
- Bundle all dependencies in plugin package
- No external dependency installation required
- Follow WordPress plugin guidelines

### 📚 **API Usage After Installation**

```javascript
// Login API
fetch('/wp-json/wp-auth/v1/login', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        username: 'user@example.com',
        password: 'password123'
    })
})
.then(response => response.json())
.then(data => {
    // JWT token available in data.token
    localStorage.setItem('jwt_token', data.token);
});
```

### 🔧 **Troubleshooting**

Run this test to verify installation:

```php
// Add to functions.php temporarily
add_action('wp_footer', function() {
    if (class_exists('Firebase\JWT\JWT')) {
        echo '<!-- ✅ WP Authenticator: Firebase JWT loaded -->';
    } else {
        echo '<!-- ❌ WP Authenticator: Firebase JWT missing -->';
    }
});
```
