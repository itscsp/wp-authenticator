# Endpoint Organization Structure

This document explains the new organized folder structure for the WP Authenticator endpoints.

## Folder Structure

```
includes/endpoints/
├── auth/                           # Authentication related endpoints
│   ├── class-login-endpoint.php
│   ├── class-logout-endpoint.php
│   └── class-validate-token-endpoint.php
├── registration/                   # Registration related endpoints
│   ├── class-register-endpoint.php              # Legacy single-step registration
│   ├── class-register-start-endpoint.php        # Step 1: Start registration
│   ├── class-register-verify-otp-endpoint.php   # Step 2: Verify OTP
│   ├── class-register-complete-endpoint.php     # Step 3: Complete registration
│   └── class-register-status-endpoint.php       # Check registration status
├── otp/                           # OTP related endpoints
│   ├── class-verify-otp-endpoint.php
│   ├── class-resend-otp-endpoint.php
│   └── class-otp-status-endpoint.php
├── profile/                       # User profile endpoints
│   └── class-profile-endpoint.php
└── security/                      # Security related endpoints
    └── class-security-stats-endpoint.php
```

## Endpoint Categories

### 🔐 Authentication (`auth/`)
Handles user authentication, token management, and session control.

- **Login**: `POST /wp-auth/v1/login`
- **Logout**: `POST /wp-auth/v1/logout`
- **Validate Token**: `GET /wp-auth/v1/validate-token`

### 📝 Registration (`registration/`)
Manages user registration process, both legacy single-step and new 3-step process.

- **Legacy Registration**: `POST /wp-auth/v1/register`
- **Start Registration**: `POST /wp-auth/v1/register/start`
- **Verify OTP**: `POST /wp-auth/v1/register/verify-otp`
- **Complete Registration**: `POST /wp-auth/v1/register/complete`
- **Registration Status**: `GET /wp-auth/v1/register/status`

### 🔢 OTP (`otp/`)
Handles OTP generation, verification, and management for various purposes.

- **Verify OTP**: `POST /wp-auth/v1/verify-otp`
- **Resend OTP**: `POST /wp-auth/v1/resend-otp`
- **OTP Status**: `GET /wp-auth/v1/otp-status`

### 👤 Profile (`profile/`)
Manages user profile information and updates.

- **Get Profile**: `GET /wp-auth/v1/profile`
- **Update Profile**: `PUT /wp-auth/v1/profile`

### 🛡️ Security (`security/`)
Provides security-related functionality and statistics.

- **Security Stats**: `GET /wp-auth/v1/security/stats`

## Benefits of This Organization

### 🗂️ **Better Organization**
- Clear separation of concerns
- Easier to navigate and maintain
- Logical grouping of related functionality

### 🔍 **Improved Maintainability**
- Easy to locate specific endpoints
- Reduced cognitive load when working on features
- Clear boundaries between different functionalities

### 🚀 **Scalability**
- Easy to add new endpoints to appropriate categories
- Room for future expansion within each category
- Consistent naming conventions

### 👥 **Developer Experience**
- Intuitive folder structure
- Self-documenting organization
- Easier onboarding for new developers

## File Naming Convention

All endpoint files follow the pattern:
```
class-{feature}-endpoint.php
```

For multi-step processes:
```
class-{feature}-{step}-endpoint.php
```

Examples:
- `class-login-endpoint.php`
- `class-register-start-endpoint.php`
- `class-register-verify-otp-endpoint.php`

## Implementation Notes

### Route Registration
All routes are still registered in `/routes/rest-routes.php` with updated require_once paths:

```php
// Old path
require_once WP_AUTHENTICATOR_PLUGIN_PATH . 'includes/endpoints/class-login-endpoint.php';

// New path
require_once WP_AUTHENTICATOR_PLUGIN_PATH . 'includes/endpoints/auth/class-login-endpoint.php';
```

### Backward Compatibility
- All existing API endpoints remain functional
- No changes to endpoint URLs or functionality
- Only internal file organization has changed

### Future Additions
When adding new endpoints:

1. Determine the appropriate category
2. Place the file in the corresponding subfolder
3. Update the route registration in `rest-routes.php`
4. Follow the established naming conventions

## Quick Reference

| Functionality | Folder | Purpose |
|--------------|--------|---------|
| Login/Logout | `auth/` | User authentication |
| Registration | `registration/` | Account creation |
| OTP | `otp/` | One-time passwords |
| Profile | `profile/` | User data management |
| Security | `security/` | Security features |

This organization makes the codebase more maintainable and provides a clear structure for future development.
