# 🚨 WordPress Plugin Installation Troubleshooting Guide

## Common Fatal Error: "Class not found" or "File not found"

### ❌ **Problem**
When uploading `wp-authenticator-v1.0.0.zip` to WordPress, you get errors like:
```
Fatal error: require_once(includes/class-jwt-handler.php): 
failed to open stream: No such file or directory
```

### ✅ **Solution**
The issue was caused by incorrect file structure in the deployment package. **This has been FIXED.**

---

## 🔧 **Fixed Issues**

### Issue #1: Missing `includes/` Directory
**Problem:** The deployment script was copying files incorrectly
**Fix:** Updated `deploy.sh` to properly preserve directory structure

**Before (broken):**
```
wp-authenticator/
├── class-jwt-handler.php        ❌ Wrong location
├── class-admin-settings.php     ❌ Wrong location  
├── wp-authenticator.php
└── vendor/
```

**After (fixed):**
```
wp-authenticator/
├── includes/                    ✅ Correct
│   ├── class-jwt-handler.php    ✅ Correct
│   ├── class-admin-settings.php ✅ Correct
│   └── ...
├── wp-authenticator.php
└── vendor/
```

---

## 🧪 **How to Verify Your Package**

### Quick Test (before uploading to WordPress):
```bash
# Extract and test the package
unzip wp-authenticator-v1.0.0.zip
cd wp-authenticator/
php test-package.php

# Should show:
# ✅ Autoloader loaded
# ✅ Firebase JWT available
# ✅ includes/class-jwt-handler.php
# ✅ All classes loaded successfully
```

### Manual Verification:
```bash
unzip -l wp-authenticator-v1.0.0.zip | grep includes/
# Should show:
# includes/class-jwt-handler.php
# includes/class-admin-settings.php
# includes/class-api-endpoints.php
# includes/class-security-handler.php
```

---

## 🚀 **Installation Instructions (Updated)**

### Method 1: WordPress Admin (Recommended)
1. Download the **NEW** `wp-authenticator-v1.0.0.zip` (rebuilt with fix)
2. Go to WordPress Admin → Plugins → Add New → Upload Plugin
3. Choose the zip file
4. Click "Install Now"
5. Click "Activate Plugin"
6. Go to WP Admin → WP Authenticator to configure

### Method 2: Manual Installation
```bash
# Extract to WordPress plugins directory
unzip wp-authenticator-v1.0.0.zip
mv wp-authenticator/ /path/to/wordpress/wp-content/plugins/

# Verify structure
ls /path/to/wordpress/wp-content/plugins/wp-authenticator/includes/
# Should show all class files

# Activate in WordPress admin
```

---

## ⚠️ **If You Still Get Errors**

### Check PHP Version
```bash
php -v
# Requires PHP 7.4 or higher
```

### Check File Permissions
```bash
chmod -R 755 wp-content/plugins/wp-authenticator/
```

### Check WordPress Version
- Requires WordPress 5.0 or higher
- REST API must be enabled (default)

### Debug Mode
Add to `wp-config.php` temporarily:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```
Check `/wp-content/debug.log` for detailed error messages.

---

## 🔍 **Error Messages & Solutions**

### "Class 'Firebase\JWT\JWT' not found"
**Cause:** Composer dependencies not loaded
**Solution:** Ensure `vendor/` directory is present and `vendor/autoload.php` exists

### "Class 'WP_Auth_JWT_Handler' not found"  
**Cause:** Include files not found
**Solution:** Ensure `includes/` directory structure is correct

### "Plugin could not be activated"
**Cause:** PHP syntax error or missing dependency
**Solution:** Check error logs, verify PHP version, redownload package

### "Headers already sent"
**Cause:** Output before WordPress headers
**Solution:** Ensure no whitespace before `<?php` tags in plugin files

---

## 📞 **Getting Help**

If you continue to experience issues:

1. **Verify Package Integrity:**
   ```bash
   unzip -t wp-authenticator-v1.0.0.zip
   ```

2. **Check Package Contents:**
   ```bash
   unzip -l wp-authenticator-v1.0.0.zip
   ```

3. **Test Package Locally:**
   ```bash
   cd wp-authenticator/
   php test-package.php
   ```

4. **Check WordPress Requirements:**
   - PHP 7.4+
   - WordPress 5.0+
   - REST API enabled

---

## 🎯 **Prevention for Future Builds**

Always test packages before distribution:
```bash
# After running ./deploy.sh
cd dist/wp-authenticator/
php test-package.php
```

The updated deployment script now correctly preserves the directory structure and includes automated testing.

---

## ✅ **Confirmation: Issue Resolved**

The fatal error issue has been **FIXED** in the latest build. The new `wp-authenticator-v1.0.0.zip` package:

- ✅ Includes proper `includes/` directory structure
- ✅ Contains all required class files in correct locations  
- ✅ Has Firebase JWT library properly bundled
- ✅ Passes all automated tests
- ✅ Ready for WordPress installation

**Download the NEW package and try again!**
