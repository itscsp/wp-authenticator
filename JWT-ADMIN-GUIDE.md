# JWT Security Key Management - Admin Interface

## 🔧 **Where to Find JWT Security Settings**

### **Location:**
1. **WordPress Admin Dashboard**
2. **Settings → WP Authenticator**
3. **JWT Security Settings Section**

### **Available Options:**

#### **1. JWT Secret Key Management**
- **Current Secret**: Shows masked current secret key
- **Regenerate Secret**: Checkbox to generate new secret (invalidates all tokens)
- **Set Custom Secret**: Advanced option to set your own secret key (minimum 32 characters)

#### **2. Token Expiration Settings**
- **Access Token Expiry**: 300-86400 seconds (5 minutes to 24 hours)
- **Refresh Token Expiry**: 86400-2592000 seconds (1 day to 30 days)

#### **3. JWT Algorithm**
- **HS256** (Recommended - HMAC SHA-256)
- **HS384** (HMAC SHA-384)
- **HS512** (HMAC SHA-512)

### **Security Features:**

#### **🔒 Auto-Generated Secret**
```
Status: JWT Secret Key is configured (64 characters)
```
- Automatically generates 64-character cryptographically secure secret
- Stored in WordPress options table
- Only visible in masked format for security

#### **⚠️ Security Warnings**
- Custom secret validation (minimum 32 characters)
- Token invalidation warnings when changing settings
- HTTPS enforcement notices

#### **🔄 Token Management**
- Regenerate secret invalidates ALL existing tokens
- Users must re-authenticate after secret changes
- Validation ensures secure key lengths

### **Example Settings:**

```php
// Current Configuration
JWT Secret: ••••••••••••••••••••abcd1234
Access Token Expiry: 3600 seconds (1 hour)
Refresh Token Expiry: 604800 seconds (7 days)
Algorithm: HS256
```

### **Best Practices:**

1. **Keep Default Settings**: Auto-generated secret is cryptographically secure
2. **Short Access Tokens**: 1 hour or less for security
3. **Longer Refresh Tokens**: 7 days for user convenience
4. **Use HS256**: Most compatible and secure for HMAC
5. **Monitor Changes**: All changes log security events

### **Access Path:**
```
WordPress Admin → Settings → WP Authenticator → JWT Security Settings
```

The JWT secret key is now manageable through the WordPress admin interface with full security controls and validation!
