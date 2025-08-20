# WP Authenticator Plugin

A comprehensive WordPress plugin that provides REST API endpoints for user authentication, registration, and profile management with enhanced security features.

## Features

- Complete REST API for user authentication
- User registration and profile management
- Password reset functionality
- Token-based authentication
- Security features (rate limiting, IP blocking)
- Failed login attempt tracking
- Admin dashboard with security statistics

## Installation

1. Copy the `wp-authenticator` folder to your WordPress `wp-content/plugins/` directory
2. Activate the plugin through the WordPress admin panel
3. Configure settings in **Settings > WP Authenticator**

## API Endpoints

All endpoints are prefixed with `/wp-json/wp-auth/v1/`

### Authentication Endpoints

#### Login
```
POST /wp-json/wp-auth/v1/login
```

**Parameters:**
- `username` (required): Username or email
- `password` (required): User password
- `remember` (optional): Boolean, remember user session

**Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user_id": 1,
    "username": "john_doe",
    "email": "john@example.com",
    "display_name": "John Doe",
    "token": "abc123...",
    "expires": 1692345600
  }
}
```

#### Register
```
POST /wp-json/wp-auth/v1/register
```

**Parameters:**
- `username` (required): Desired username
- `email` (required): User email
- `password` (required): User password
- `first_name` (optional): First name
- `last_name` (optional): Last name

**Response:**
```json
{
  "success": true,
  "message": "Registration successful",
  "data": {
    "user_id": 2,
    "username": "jane_doe",
    "email": "jane@example.com"
  }
}
```

#### Logout
```
POST /wp-json/wp-auth/v1/logout
```

**Headers:** `Authorization: Bearer {token}`

**Response:**
```json
{
  "success": true,
  "message": "Logout successful"
}
```

### Profile Management

#### Get Profile
```
GET /wp-json/wp-auth/v1/profile
```

**Headers:** `Authorization: Bearer {token}`

**Response:**
```json
{
  "success": true,
  "data": {
    "user_id": 1,
    "username": "john_doe",
    "email": "john@example.com",
    "first_name": "John",
    "last_name": "Doe",
    "display_name": "John Doe",
    "description": "User bio",
    "registered": "2023-01-01 00:00:00",
    "roles": ["subscriber"]
  }
}
```

#### Update Profile
```
PUT /wp-json/wp-auth/v1/profile
```

**Headers:** `Authorization: Bearer {token}`

**Parameters:**
- `first_name` (optional): First name
- `last_name` (optional): Last name
- `email` (optional): Email address
- `description` (optional): User bio

**Response:**
```json
{
  "success": true,
  "message": "Profile updated successfully",
  "data": {
    "user_id": 1,
    "username": "john_doe",
    "email": "john@example.com",
    "first_name": "John",
    "last_name": "Doe",
    "display_name": "John Doe",
    "description": "Updated bio"
  }
}
```

### Password Management

#### Request Password Reset
```
POST /wp-json/wp-auth/v1/password-reset-request
```

**Parameters:**
- `email` (required): User email

**Response:**
```json
{
  "success": true,
  "message": "Password reset email sent successfully."
}
```

#### Reset Password
```
POST /wp-json/wp-auth/v1/password-reset
```

**Parameters:**
- `email` (required): User email
- `reset_key` (required): Reset key from email
- `new_password` (required): New password

**Response:**
```json
{
  "success": true,
  "message": "Password reset successfully."
}
```

#### Change Password
```
POST /wp-json/wp-auth/v1/change-password
```

**Headers:** `Authorization: Bearer {token}`

**Parameters:**
- `current_password` (required): Current password
- `new_password` (required): New password

**Response:**
```json
{
  "success": true,
  "message": "Password changed successfully."
}
```

### Token Management

#### Validate Token
```
GET /wp-json/wp-auth/v1/validate-token
```

**Headers:** `Authorization: Bearer {token}`

**Response:**
```json
{
  "success": true,
  "message": "Token is valid",
  "data": {
    "user_id": 1,
    "expires": 1692345600
  }
}
```

#### Refresh Token
```
POST /wp-json/wp-auth/v1/refresh-token
```

**Headers:** `Authorization: Bearer {token}`

**Response:**
```json
{
  "success": true,
  "message": "Token refreshed successfully.",
  "data": {
    "token": "new_token_here",
    "expires": 1692432000
  }
}
```

### Utility Endpoints

#### Check Username Availability
```
GET /wp-json/wp-auth/v1/check-username?username=desired_username
```

**Response:**
```json
{
  "available": true,
  "message": "Username is available."
}
```

#### Check Email Availability
```
GET /wp-json/wp-auth/v1/check-email?email=user@example.com
```

**Response:**
```json
{
  "available": false,
  "message": "Email is already registered."
}
```

#### Get User Roles
```
GET /wp-json/wp-auth/v1/user/roles
```

**Headers:** `Authorization: Bearer {token}`

**Response:**
```json
{
  "success": true,
  "data": {
    "roles": ["subscriber"],
    "capabilities": ["read", "level_0"]
  }
}
```

### Admin Endpoints

#### Security Statistics
```
GET /wp-json/wp-auth/v1/security/stats
```

**Headers:** `Authorization: Bearer {admin_token}`

**Response:**
```json
{
  "success": true,
  "data": {
    "failed_logins": 25,
    "blocked_ips": 3,
    "successful_logins": 150,
    "registrations": 10
  }
}
```

## Authentication

The plugin supports two authentication methods:

1. **WordPress Cookie Authentication** - Standard WordPress session
2. **Token Authentication** - Bearer token in Authorization header

### Using Token Authentication

After login, you'll receive a token. Include it in subsequent requests:

```
Authorization: Bearer your_token_here
```

Tokens expire after 24 hours but can be refreshed using the refresh endpoint.

## Security Features

- **Rate Limiting**: Prevents brute force attacks
- **IP Blocking**: Temporarily blocks IPs after failed attempts
- **Activity Logging**: Tracks all authentication events
- **Token Expiration**: Automatic token expiry for security
- **Password Strength**: Enforces minimum password requirements

## Configuration

Configure the plugin through **Settings > WP Authenticator**:

- Login/logout redirect URLs
- Security settings (max attempts, lockout duration)
- Email notifications
- Auto-login after registration

## Error Handling

All endpoints return standardized error responses:

```json
{
  "code": "error_code",
  "message": "Human readable error message",
  "data": {
    "status": 400
  }
}
```

Common error codes:
- `invalid_credentials`: Wrong username/password
- `user_not_found`: User doesn't exist
- `username_exists`: Username already taken
- `email_exists`: Email already registered
- `ip_blocked`: IP temporarily blocked
- `invalid_token`: Token is invalid or expired

## Examples

### JavaScript/Fetch

```javascript
// Login
const loginResponse = await fetch('/wp-json/wp-auth/v1/login', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    username: 'john_doe',
    password: 'password123',
    remember: true
  })
});

const loginData = await loginResponse.json();
const token = loginData.data.token;

// Get Profile
const profileResponse = await fetch('/wp-json/wp-auth/v1/profile', {
  headers: {
    'Authorization': `Bearer ${token}`
  }
});
```

### cURL

```bash
# Login
curl -X POST https://yoursite.com/wp-json/wp-auth/v1/login \
  -H "Content-Type: application/json" \
  -d '{"username":"john_doe","password":"password123"}'

# Get Profile with token
curl -X GET https://yoursite.com/wp-json/wp-auth/v1/profile \
  -H "Authorization: Bearer your_token_here"
```

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher

## Support

For support and bug reports, please create an issue in the plugin repository.
