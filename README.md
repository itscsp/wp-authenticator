# WP Authenticator

A modern, JWT-based authentication system for WordPress that provides enhanced security, REST API endpoints, OTP verification, and mobile app support. This plugin transforms WordPress from a traditional session-based authentication system into a modern, stateless, API-first authentication platform.

## 🎯 What This Plugin Does

WP Authenticator extends WordPress's default authentication capabilities by providing:

- **JWT Token-Based Authentication** - Replace WordPress cookies with secure, stateless JWT tokens
- **REST API First Approach** - 20+ comprehensive API endpoints for complete authentication management
- **Multi-Step Registration** - Enhanced 3-step registration process with email verification
- **OTP Security System** - Email-based One-Time Password verification for enhanced security
- **Mobile & Headless Support** - Perfect for React Native, Flutter, Next.js, and other modern applications
- **Advanced Security Features** - Rate limiting, failed login attempt blocking, and enhanced security monitoring

## 🔄 How It Differs from Default WordPress Authentication

### Default WordPress Authentication:
- **Session-based** - Uses PHP sessions and cookies tied to the server
- **Limited API** - Basic REST API with limited authentication endpoints
- **Simple Registration** - Single-step user registration without verification
- **No Mobile Support** - Not designed for mobile app authentication
- **Basic Security** - Limited rate limiting and security features

### WP Authenticator Enhancement:
- **Stateless JWT Tokens** - No server-side sessions, perfect for distributed systems
- **Comprehensive REST API** - 20+ specialized endpoints for all authentication needs
- **3-Step Registration** - Email verification, OTP confirmation, and secure account creation
- **Mobile-First Design** - Built specifically for mobile apps and SPA applications
- **Advanced Security** - IP-based rate limiting, failed login blocking, token refresh, and security monitoring
- **Headless CMS Ready** - Perfect for decoupled WordPress architectures

### Key Differences Table:

| Feature | Default WordPress | WP Authenticator |
|---------|------------------|------------------|
| Authentication Method | Session cookies | JWT tokens |
| Mobile App Support | Limited | Native support |
| Registration Process | Single step | 3-step with verification |
| API Endpoints | Basic | 20+ specialized endpoints |
| Security Features | Basic | Advanced (rate limiting, OTP) |
| Headless Support | Limited | Full support |
| Token Management | N/A | Refresh tokens, expiration |
| OTP Verification | No | Email-based OTP |

## 🚀 Core Features

### Authentication System
- **JWT Token Authentication** - Secure, stateless authentication using Firebase JWT library
- **Token Refresh Mechanism** - Automatic token renewal for seamless user experience
- **Multi-device Support** - Users can authenticate across multiple devices simultaneously
- **Logout Management** - Secure token invalidation and logout functionality

### Registration & Verification
- **3-Step Registration Process** - Enhanced security with email verification before account creation
- **OTP Email Verification** - Prevent fake accounts with one-time password verification
- **Session Management** - Secure 30-minute registration sessions with automatic cleanup
- **Auto-Login** - Seamless authentication after successful registration completion

### Security Features
- **Rate Limiting** - IP-based failed login attempt blocking (5 attempts = 15 minute block)
- **Spam Prevention** - OTP verification prevents automated account creation
- **Security Monitoring** - Admin dashboard with authentication statistics
- **Secure Headers** - Proper CORS and security headers for API endpoints

### Developer Experience
- **Organized Codebase** - Endpoints logically organized in subfolders (`auth/`, `registration/`, `otp/`, etc.)
- **Interactive Swagger UI** - 🆕 Live API documentation and testing interface
- **OpenAPI 3.0 Specification** - 🆕 Machine-readable API documentation
- **Comprehensive Documentation** - Complete API documentation with examples
- **Test Scripts** - Ready-to-use testing tools for development
- **Backward Compatibility** - All existing integrations continue to work seamlessly

## 📋 Requirements

- WordPress 5.0+
- PHP 7.4+
- Email functionality (wp_mail configured)
- HTTPS recommended for production (JWT tokens)
- Composer (for dependency management)

## 🔧 Installation & Setup

### Quick Installation
1. Download the plugin from [GitHub](https://github.com/itscsp/wp-authenticator)
2. Upload and activate the plugin in WordPress admin
3. Go to **Settings > WP Authenticator** in your WordPress admin
4. Configure JWT settings (secret key will be auto-generated)
5. Test the API endpoints using the provided examples

### Manual Installation
1. Upload the plugin folder to `/wp-content/plugins/`
2. Ensure the `vendor/` directory is included (contains Firebase JWT library)
3. Activate the plugin through the WordPress admin
4. Configure settings in **WP Admin > WP Authenticator**

### Configuration
- **JWT Secret Key**: Auto-generated secure key for token signing
- **Token Expiration**: Configure how long tokens remain valid (default: 24 hours)
- **Email Settings**: Ensure `wp_mail()` is configured for OTP delivery
- **Security Settings**: Configure rate limiting and failed login thresholds

## 🏗️ Building the Project with Deploy Script

The plugin includes a comprehensive deployment script that creates production-ready packages.

### Using the Deploy Script

```bash
# Make the script executable
chmod +x deploy.sh

# Run the deployment
./deploy.sh
```

### What the Deploy Script Does:

1. **Creates Clean Build** 
   - Creates `dist/wp-authenticator/` directory
   - Copies all necessary plugin files

2. **Installs Production Dependencies**
   - Runs `composer install --no-dev --optimize-autoloader`
   - Includes only Firebase JWT library (no dev dependencies)

3. **Cleans Development Files**
   - Removes test files, documentation markdown files from vendor
   - Removes Git files and development artifacts
   - Optimizes for production deployment

4. **Creates Distribution Package**
   - Generates `wp-authenticator-v1.1.0.zip` 
   - Ready for WordPress plugin installation

5. **Provides Installation Instructions**
   - Shows manual and automated installation steps
   - Lists new features and documentation references

### Build Output Structure:
```
dist/
└── wp-authenticator/
    ├── includes/           # Core plugin classes
    ├── routes/            # REST API route definitions
    ├── docs/              # Documentation files
    ├── vendor/            # Production dependencies (Firebase JWT)
    ├── wp-authenticator.php
    ├── README.md
    ├── API_Docs.md
    └── composer.json
```

### Deploy Script Features:
- ✅ **Production Optimization** - Only includes necessary files
- ✅ **Dependency Management** - Composer production dependencies
- ✅ **Clean Package** - Removes development files
- ✅ **Version Tracking** - Updates version numbers automatically
- ✅ **Documentation** - Includes all necessary documentation

## 🔐 Authentication Process Comparison

### Traditional WordPress Authentication Flow:
```
1. User submits login form
2. WordPress validates credentials
3. Creates PHP session and sets cookies
4. User authenticated for current session
```

### WP Authenticator JWT Flow:
```
1. User submits credentials to /wp-auth/v1/auth/login
2. Plugin validates credentials
3. Generates JWT token with expiration
4. Returns token + refresh token
5. Client stores token for API requests
6. Token used for all subsequent API calls
```

## 🔄 Registration Process Comparison

### Default WordPress Registration:
```
POST /wp-json/wp/v2/users (limited functionality)
→ Creates user immediately (if enabled)
→ No email verification
→ Basic user data only
```

### WP Authenticator 3-Step Registration:

#### Step 1: Start Registration
```bash
POST /wp-auth/v1/register/start
{
  "email": "user@example.com",
  "first_name": "John",
  "last_name": "Doe"
}
```
- Validates email uniqueness
- Generates OTP and sends verification email
- Creates temporary registration session (30 minutes)
- Returns session token for next steps

#### Step 2: Verify OTP
```bash
POST /wp-auth/v1/register/verify-otp
{
  "session_token": "session_token_from_step1",
  "otp": "123456"
}
```
- Validates OTP from email
- Confirms email ownership
- Advances registration to final step

#### Step 3: Complete Registration
```bash
POST /wp-auth/v1/register/complete
{
  "session_token": "session_token_from_step1",
  "username": "johndoe",
  "password": "secure_password"
}
```
- Creates actual WordPress user account
- Automatically logs in user
- Returns JWT tokens for immediate use
- Cleans up registration session

### Legacy Single-Step Registration (Still Available):
```bash
POST /wp-auth/v1/register
{
  "username": "johndoe", 
  "email": "user@example.com",
  "password": "secure_password"
}
```

## 📁 API Endpoint Organization

The plugin organizes endpoints into logical categories for better code organization and easier maintenance:

```
/wp-json/wp-auth/v1/
├── auth/
│   ├── login              # POST - User authentication
│   ├── logout             # POST - Token invalidation  
│   └── validate-token     # POST - Token validation
├── registration/
│   ├── register           # POST - Legacy single-step registration
│   ├── start              # POST - Begin 3-step registration
│   ├── verify-otp         # POST - Verify email OTP
│   ├── complete           # POST - Finalize registration
│   └── status             # GET  - Check registration progress
├── otp/
│   ├── verify             # POST - Verify OTP codes
│   ├── resend             # POST - Resend OTP via email
│   └── status             # GET  - Check OTP status
├── profile/
│   └── profile            # GET/POST - User profile management
└── security/
    └── stats              # GET - Security statistics (admin)
```

### Key Benefits:
- **Organized Structure** - Related endpoints grouped together
- **Easier Maintenance** - Each category in separate files
- **Better Documentation** - Clear endpoint hierarchy
- **Scalable Architecture** - Easy to add new endpoint categories

## 🛠️ Usage Examples

### Basic Authentication Flow
```javascript
// 1. Login and get JWT token
const loginResponse = await fetch('/wp-json/wp-auth/v1/auth/login', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    username: 'johndoe',
    password: 'password123'
  })
});

const { token, refresh_token } = await loginResponse.json();

// 2. Use token for authenticated requests
const profileResponse = await fetch('/wp-json/wp-auth/v1/profile/profile', {
  headers: { 
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  }
});
```

### 3-Step Registration Flow
```javascript
// Step 1: Start registration
const step1 = await fetch('/wp-json/wp-auth/v1/register/start', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    email: 'user@example.com',
    first_name: 'John',
    last_name: 'Doe'
  })
});

const { session_token } = await step1.json();

// Step 2: Verify OTP (after user checks email)
const step2 = await fetch('/wp-json/wp-auth/v1/register/verify-otp', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    session_token: session_token,
    otp: '123456' // From email
  })
});

// Step 3: Complete registration
const step3 = await fetch('/wp-json/wp-auth/v1/register/complete', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    session_token: session_token,
    username: 'johndoe',
    password: 'securepassword123'
  })
});

const { token, user } = await step3.json();
// User is now registered and logged in!
```

## 🔒 Security Features

### Rate Limiting & Protection
- **Failed Login Protection** - Blocks IP after 5 failed attempts for 15 minutes
- **Registration Session Timeout** - 30-minute limit for multi-step registration
- **OTP Expiration** - Time-limited OTP codes for email verification
- **JWT Token Expiration** - Configurable token lifetimes

### Security Monitoring
- **Login Attempt Tracking** - Monitor failed authentication attempts
- **IP-based Blocking** - Automatic temporary IP blocks for suspicious activity
- **Admin Security Dashboard** - View authentication statistics and security events

## 📖 Documentation & Resources

### Complete Documentation
- **[Complete API Documentation](./API_Docs.md)** - All 20+ endpoints with request/response examples
- **[Interactive Swagger UI](./docs/swagger-integration.md)** - 🆕 Live API testing and documentation
- **[3-Step Registration Guide](./docs/3-step-registration.md)** - Detailed implementation guide
- **[JWT Implementation Guide](./docs/jwt_implemention.md)** - JWT token management details
- **[Token Management](./docs/token-management.md)** - Advanced token handling
- **[Endpoint Organization](./docs/endpoint-organization.md)** - Codebase structure explanation
- **[React Native Integration](./docs/jwt_plugin_react_nativev.md)** - Mobile app integration guide

### 🚀 Interactive API Testing
- **Swagger UI**: Access at `/wp-json/wp-auth/v1/docs` on your site
- **Admin Dashboard**: WordPress Admin → Settings → API Docs
- **OpenAPI Spec**: Available at `/wp-json/wp-auth/v1/swagger.json`

### Additional Resources
- **[Changelog](./CHANGELOG.md)** - Version history and updates
- **[Test Scripts](./test-3-step-registration.php)** - Ready-to-use testing examples
- **[Swagger Integration Test](./test-swagger-integration.php)** - 🆕 Validate Swagger setup
- **[Reorganization Summary](./docs/reorganization-summary.md)** - Plugin architecture overview

## � Use Cases

### Perfect For:
- **Mobile Applications** - React Native, Flutter, Ionic apps
- **Single Page Applications (SPA)** - React, Vue, Angular frontends  
- **Headless WordPress** - Next.js, Nuxt.js, Gatsby sites
- **API-First Projects** - Microservices and distributed architectures
- **Multi-Platform Authentication** - Consistent auth across web, mobile, desktop

### Integration Examples:
- **E-commerce Apps** - Secure customer authentication with email verification
- **Membership Sites** - Multi-step registration with enhanced security
- **Learning Management Systems** - JWT-based authentication for course access
- **Social Applications** - Secure user registration and profile management

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/new-feature`
3. Make your changes and test thoroughly
4. Commit your changes: `git commit -am 'Add new feature'`
5. Push to the branch: `git push origin feature/new-feature`
6. Submit a pull request

### Development Setup
```bash
# Clone the repository
git clone https://github.com/itscsp/wp-authenticator.git

# Install dependencies
composer install

# Run tests
php test-3-step-registration.php
```

## 📞 Support & Community

- **GitHub Issues** - [Report bugs and request features](https://github.com/itscsp/wp-authenticator/issues)
- **Discussions** - [Community discussions and questions](https://github.com/itscsp/wp-authenticator/discussions)
- **WordPress Support** - [WordPress.org Plugin Forum](https://wordpress.org/support/plugin/wp-authenticator)

## 🔗 Links & Resources

- **[GitHub Repository](https://github.com/itscsp/wp-authenticator)** - Source code and development
- **[WordPress Plugin Directory](https://wordpress.org/plugins/wp-authenticator)** - Official plugin page
- **[API Documentation](./API_Docs.md)** - Complete endpoint reference
- **[Live Demo](https://demo.example.com)** - Try the plugin features

## 👨‍💻 Author

**Chethan S Poojary**
- GitHub: [@itscsp](https://github.com/itscsp)
- WordPress Profile: [Chethan S Poojary](https://profiles.wordpress.org/itscsp)

## 📝 License

This project is licensed under the GPL v2 or later - see the [LICENSE](LICENSE) file for details.

---

**🌟 If this plugin helps your project, please consider giving it a star on GitHub!**
