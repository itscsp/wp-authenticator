=== WP Authenticator ===
Contributors: itscsp
Tags: authentication, jwt, rest-api, otp, security, mobile-app, headless
Requires at least: 5.0
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.0.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Modern JWT-based authentication system for WordPress with REST API, OTP verification, and mobile app support.

== Description ==

WP Authenticator transforms WordPress into a modern, API-first authentication platform perfect for mobile apps, headless websites, and progressive web applications.

= 🚀 Key Features =

* **JWT Token Authentication** - Stateless, secure token-based authentication
* **Complete REST API** - 18+ endpoints for full authentication management
* **OTP Security Layer** - Email-based OTP verification for enhanced security
* **Mobile App Ready** - Perfect for React Native, Flutter, and mobile applications
* **Headless WordPress** - Ideal for Next.js, Nuxt.js, and decoupled architectures
* **Password Management** - OTP-based password reset and change functionality
* **User Management** - Registration, profile management, and user verification
* **Cross-Domain Support** - Works across different domains and subdomains

= 🔐 Security Features =

* OTP-based password reset (no vulnerable email links)
* Two-factor authentication ready
* Rate limiting and security monitoring
* JWT token refresh mechanism
* Secure user registration with email verification

= 📱 Perfect For =

* Mobile Applications (React Native, Flutter, Ionic)
* Progressive Web Apps (PWAs)
* Headless/Decoupled WordPress sites
* Single Page Applications (React, Vue, Angular)
* Third-party integrations and APIs
* Modern WordPress development

= 🛠 Developer Friendly =

* Comprehensive API documentation
* Postman collection included
* React implementation examples
* Easy integration guides
* Extensive hooks and filters

= 📋 API Endpoints =

**Authentication:**
* Login with JWT token generation
* User registration with OTP verification
* Logout and token invalidation
* Token validation and refresh

**Password Management:**
* OTP-based password reset request
* Secure password reset with OTP
* Password change with OTP verification

**User Management:**
* Get and update user profiles
* Check username/email availability
* User roles and capabilities

**Security:**
* OTP verification and resend
* Security statistics (admin)
* Failed login monitoring

= 🔧 Technical Requirements =

* WordPress 5.0+
* PHP 7.4+
* Firebase JWT PHP library (included)
* Email functionality (wp_mail)

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/wp-authenticator/` directory
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to Settings > WP Authenticator to configure the plugin
4. Set your JWT secret key (auto-generated if not set)
5. Configure email settings for OTP delivery
6. Start using the REST API endpoints

= Manual Installation =

1. Download the plugin zip file
2. Go to WordPress Admin > Plugins > Add New
3. Click "Upload Plugin" and select the zip file
4. Install and activate the plugin
5. Configure settings as described above

== Frequently Asked Questions ==

= Is this plugin secure? =

Yes! WP Authenticator uses industry-standard JWT tokens, OTP verification, and follows WordPress security best practices. All passwords are hashed using WordPress core functions.

= Can I use this with mobile apps? =

Absolutely! This plugin is specifically designed for mobile app authentication. It provides JWT tokens that work perfectly with React Native, Flutter, and other mobile frameworks.

= Does it work with headless WordPress? =

Yes! This is ideal for headless/decoupled WordPress setups. You can authenticate users through the REST API while using any frontend framework.

= Will it conflict with other authentication plugins? =

WP Authenticator is designed to work alongside WordPress core authentication. It adds new capabilities without breaking existing functionality.

= Can I customize the OTP email templates? =

Yes! The plugin provides hooks and filters to customize OTP email content, subject lines, and styling.

= Is there documentation for developers? =

Yes! Comprehensive API documentation, Postman collections, and implementation examples are included with the plugin.

== Screenshots ==

1. Admin settings page with JWT configuration
2. API endpoints documentation
3. OTP email verification example
4. Mobile app login integration
5. REST API response examples
6. Security monitoring dashboard

== Changelog ==

= 1.0.2 =
* Added OTP-based password reset functionality
* Implemented password change with OTP verification
* Enhanced security monitoring
* Added comprehensive API documentation
* Improved error handling and validation
* Added rate limiting for OTP requests

= 1.0.0 =
* Initial release
* JWT token authentication system
* User registration and login endpoints
* Profile management API
* OTP verification system
* Security features and monitoring
* Admin settings interface

== Upgrade Notice ==

= 1.0.2 =
Major security enhancement with OTP-based password management. Recommended update for all users.

== Developer Information ==

= API Base URL =
`https://yoursite.com/wp-json/wp-auth/v1`

= Authentication Header =
`Authorization: Bearer YOUR_JWT_TOKEN`

= Example Usage =
```javascript
// Login
const response = await fetch('/wp-json/wp-auth/v1/login', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ username: 'user', password: 'pass' })
});

// Use token for authenticated requests
const profile = await fetch('/wp-json/wp-auth/v1/profile', {
  headers: { 'Authorization': `Bearer ${token}` }
});
```

= Support =
* GitHub Repository: https://github.com/itscsp/wp-authenticator
* Documentation: Included in plugin files
* Issues: GitHub Issues page

= Contributing =
Contributions are welcome! Please see the GitHub repository for contribution guidelines.
