# WP Authenticator

Modern JWT-based authentication system for WordPress with REST API, OTP verification, and mobile app support.

## 🚀 Features

### Core Authentication
- **JWT Token Authentication** - Stateless, secure authentication
- **Complete REST API** - 20+ endpoints for authentication management  
- **OTP Security** - Email-based verification system
- **Mobile Ready** - Perfect for React Native, Flutter apps
- **Headless Support** - Ideal for Next.js, Nuxt.js projects

### 🆕 3-Step Registration Process
- **Enhanced Security** - Email verification before account creation
- **Step-by-Step Flow** - Guided registration process
- **Session Management** - Secure 30-minute registration sessions
- **Spam Prevention** - OTP verification prevents automated registrations
- **Auto Login** - Seamless login after successful registration

### Developer Experience
- **Organized Codebase** - Endpoints organized in logical subfolders
- **Comprehensive Docs** - Complete API documentation and examples
- **Test Scripts** - Ready-to-use testing tools
- **Backward Compatible** - All existing integrations continue to work

## 📋 Requirements

- WordPress 5.0+
- PHP 7.4+
- Email functionality (wp_mail)

## 🔧 Installation

1. Download and activate the plugin
2. Go to Settings > WP Authenticator
3. Configure JWT settings
4. Start using the API endpoints

## � Registration Process

### Traditional (Single-Step)
```
POST /wp-auth/v1/register
```

### New 3-Step Process
```
1. POST /wp-auth/v1/register/start      (Collect info + send OTP)
2. POST /wp-auth/v1/register/verify-otp (Verify email)
3. POST /wp-auth/v1/register/complete   (Set credentials + auto-login)
```

## 📁 Endpoint Organization

```
auth/          - Login, logout, token validation
registration/  - All registration processes
otp/          - OTP operations
profile/      - User profile management
security/     - Security and admin features
```

## �📖 Documentation

- **[Complete API Documentation](./API_Docs.md)** - All endpoints with examples
- **[3-Step Registration Guide](./docs/3-step-registration.md)** - Detailed registration process
- **[Endpoint Organization](./docs/endpoint-organization.md)** - Codebase structure
- **[Changelog](./CHANGELOG.md)** - Version history and updates

## 🔗 Links

- [GitHub Repository](https://github.com/itscsp/wp-authenticator)
- [API Documentation](./API_Docs.md)
- [WordPress Plugin Directory](https://wordpress.org/plugins/wp-authenticator)

## 📝 License

GPL v2 or later
