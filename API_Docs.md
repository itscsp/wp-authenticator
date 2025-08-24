# WP Authenticator API Documentation

## Overview
This document provides comprehensive information about the WP Authenticator REST API endpoints.

**New Features:**
- **3-Step Registration Process**: Enhanced security with email verification before account creation
- **Session Management**: Secure session handling for multi-step processes
- **Organized Endpoint Structure**: Endpoints are now organized in logical subfolders
- **Backward Compatibility**: All existing endpoints continue to work as before

## Base URL
```
https://your-site.com/wp-json/wp-auth/v1
```

## Authentication
Most endpoints require JWT token authentication via Authorization header:
```
Authorization: Bearer YOUR_JWT_TOKEN
```

## Quick Reference

### Registration Endpoints
- `POST /register` - Legacy single-step registration
- `POST /register/start` - Start 3-step registration (Step 1)
- `POST /register/verify-otp` - Verify OTP (Step 2)
- `POST /register/complete` - Complete registration (Step 3)
- `GET /register/status` - Check registration status

---

## Core Authentication Endpoints

### 1. Login
**Endpoint:** `POST /login`  
**Description:** Authenticate user and receive JWT token  
**Authentication Required:** No

#### Request Body
```json
{
  "username": "string",   // required
  "password": "string",   // required
  "remember": false       // optional, boolean
}
```

#### Success Response (200)
```json
{
  "success": true,
  "message": "Login successful",
  "token": "JWT_TOKEN_HERE",
  "user": {
    "id": 1,
    "username": "john_doe",
    "email": "john@example.com"
  }
}
```

#### Error Responses
- **400 Bad Request**
```json
{
  "success": false,
  "message": "Username and password are required"
}
```
- **401 Unauthorized**
```json
{
  "success": false,
  "message": "Invalid credentials"
}
```

### 2. Register (Legacy Single-Step)
**Endpoint:** `POST /register`  
**Description:** Register a new user (legacy single-step process with OTP verification)  
**Authentication Required:** No

#### Request Body
```json
{
  "username": "string",      // required
  "email": "string",         // required
  "password": "string",      // required
  "first_name": "string",    // optional
  "last_name": "string"      // optional
}
```

#### Success Response (200)
```json
{
  "success": true,
  "message": "Registration initiated. Please check your email for the OTP verification code.",
  "data": {
    "email": "user@example.com",
    "otp_expires": 1640908800,
    "requires_verification": true,
    "next_step": "Please call /wp-json/wp-auth/v1/verify-otp with your email and OTP code"
  }
}
```

#### Error Responses
- **400 Bad Request**
```json
{
  "success": false,
  "message": "Username, email and password are required"
}
```
- **409 Conflict**
```json
{
  "success": false,
  "message": "Username or email already exists"
}
```

---

## 3-Step Registration Process

The new registration system provides a secure, step-by-step process for user registration with email verification.

### Registration Workflow
```
1. Start Registration → 2. Verify OTP → 3. Complete Registration
   (Collect Info)       (Email Verify)   (Set Credentials)
```

**Benefits:**
- ✅ Email verification before account creation
- ✅ Enhanced security against spam registrations
- ✅ Better user experience with step-by-step guidance
- ✅ Session recovery - users can continue where they left off
- ✅ Automatic login after successful registration

### 2a. Start Registration (Step 1)
**Endpoint:** `POST /register/start`  
**Description:** Start the registration process by collecting name and email, then send OTP  
**Authentication Required:** No

#### Request Body
```json
{
  "email": "string",         // required
  "first_name": "string",    // required
  "last_name": "string"      // optional
}
```

#### Success Response (200)
```json
{
  "success": true,
  "message": "Registration started successfully. Please check your email for the verification code.",
  "data": {
    "session_token": "abc123def456...",
    "email": "user@example.com",
    "step": 1,
    "next_step": "verify_otp",
    "otp_expires_in": 300,
    "session_expires_in": 1800
  }
}
```

#### Error Responses
- **400 Bad Request**
```json
{
  "success": false,
  "message": "First name is required"
}
```
- **409 Conflict**
```json
{
  "success": false,
  "message": "An account with this email address already exists"
}
```

### 2b. Verify OTP (Step 2)
**Endpoint:** `POST /register/verify-otp`  
**Description:** Verify the OTP code sent to email  
**Authentication Required:** No

#### Request Body
```json
{
  "session_token": "string", // required - from step 1
  "otp": "string"            // required - 6-digit code from email
}
```

#### Success Response (200)
```json
{
  "success": true,
  "message": "Email verified successfully. Please complete your registration by setting a username and password.",
  "data": {
    "session_token": "abc123def456...",
    "email": "user@example.com",
    "step": 2,
    "next_step": "complete_registration",
    "email_verified": true,
    "session_expires_in": 1800
  }
}
```

#### Error Responses
- **400 Bad Request**
```json
{
  "success": false,
  "message": "Invalid or expired registration session"
}
```
- **401 Unauthorized**
```json
{
  "success": false,
  "message": "Invalid OTP code"
}
```

### 2c. Complete Registration (Step 3)
**Endpoint:** `POST /register/complete`  
**Description:** Complete registration by setting username and password, create user account  
**Authentication Required:** No

#### Request Body
```json
{
  "session_token": "string", // required - from previous steps
  "username": "string",      // required
  "password": "string"       // required (minimum 6 characters)
}
```

#### Success Response (200)
```json
{
  "success": true,
  "message": "Registration completed successfully! You are now logged in.",
  "data": {
    "user_id": 123,
    "username": "johndoe",
    "email": "user@example.com",
    "token": "jwt_token_here...",
    "refresh_token": "refresh_jwt_token_here...",
    "token_expires": 1640995200,
    "user": {
      "ID": 123,
      "username": "johndoe",
      "email": "user@example.com",
      "first_name": "John",
      "last_name": "Doe",
      "display_name": "John Doe"
    },
    "registration_completed_at": 1640908800
  }
}
```

#### Error Responses
- **400 Bad Request**
```json
{
  "success": false,
  "message": "You must verify your email first before completing registration"
}
```
- **409 Conflict**
```json
{
  "success": false,
  "message": "Username already exists. Please choose a different username"
}
```

### 2d. Registration Status
**Endpoint:** `GET /register/status`  
**Description:** Check the current status of a registration session  
**Authentication Required:** No

#### Query Parameters
- `session_token` (string, required): Session token from registration start

#### Success Response (200)
```json
{
  "success": true,
  "data": {
    "session_token": "abc123def456...",
    "email": "user@example.com",
    "first_name": "John",
    "last_name": "Doe",
    "current_step": 2,
    "email_verified": true,
    "next_action": "complete_registration",
    "session_expires_in": 1200,
    "session_expires_at": 1640910600,
    "started_at": 1640908800,
    "otp_sent_at": 1640908800,
    "otp_verified_at": 1640909100
  }
}
```

### 3. Logout
**Endpoint:** `POST /logout`  
**Description:** Logout user and blacklist JWT token  
**Authentication Required:** Yes

#### Request Headers
```
Authorization: Bearer YOUR_JWT_TOKEN
```

#### Success Response (200)
```json
{
  "success": true,
  "message": "Logout successful"
}
```

#### Error Responses
- **401 Unauthorized**
```json
{
  "success": false,
  "message": "Invalid or expired token"
}
```

### 4. User Profile (GET)
**Endpoint:** `GET /profile`  
**Description:** Get current user profile information  
**Authentication Required:** Yes

#### Request Headers
```
Authorization: Bearer YOUR_JWT_TOKEN
```

#### Success Response (200)
```json
{
  "success": true,
  "data": {
    "id": 1,
    "username": "john_doe",
    "email": "john@example.com",
    "first_name": "John",
    "last_name": "Doe",
    "description": "User bio"
  }
}
```

### 5. Update Profile
**Endpoint:** `PUT /profile`  
**Description:** Update current user profile  
**Authentication Required:** Yes

#### Request Headers
```
Authorization: Bearer YOUR_JWT_TOKEN
```

#### Request Body
```json
{
  "first_name": "string",    // optional
  "last_name": "string",     // optional
  "email": "string",         // optional
  "description": "string"    // optional
}
```

#### Success Response (200)
```json
{
  "success": true,
  "message": "Profile updated successfully"
}
```

### 6. Validate Token
**Endpoint:** `GET /validate-token`  
**Description:** Validate JWT token  
**Authentication Required:** No (token passed in header)

#### Request Headers
```
Authorization: Bearer YOUR_JWT_TOKEN
```

#### Success Response (200)
```json
{
  "success": true,
  "message": "Token is valid",
  "user_id": 1
}
```

#### Error Responses
- **401 Unauthorized**
```json
{
  "success": false,
  "message": "Invalid or expired token"
}
```

---

## OTP Endpoints

### 7. Verify OTP
**Endpoint:** `POST /verify-otp`  
**Description:** Verify OTP code for two-factor authentication  
**Authentication Required:** No

#### Request Body
```json
{
  "email": "string",     // required
  "otp": "string"        // required
}
```

#### Success Response (200)
```json
{
  "success": true,
  "message": "OTP verified successfully"
}
```

#### Error Responses
- **400 Bad Request**
```json
{
  "success": false,
  "message": "Email and OTP are required"
}
```
- **401 Unauthorized**
```json
{
  "success": false,
  "message": "Invalid OTP code"
}
```

### 8. Resend OTP
**Endpoint:** `POST /resend-otp`  
**Description:** Resend OTP code to user  
**Authentication Required:** No

#### Request Body
```json
{
  "email": "string"      // required
}
```

#### Success Response (200)
```json
{
  "success": true,
  "message": "OTP sent successfully"
}
```

#### Error Responses
- **400 Bad Request**
```json
{
  "success": false,
  "message": "Email is required"
}
```
- **429 Too Many Requests**
```json
{
  "success": false,
  "message": "Please wait before requesting another OTP"
}
```

### 9. OTP Status
**Endpoint:** `GET /otp-status`  
**Description:** Get OTP verification status for a user  
**Authentication Required:** No

#### Query Parameters
- `email` (string, required): User's email address

#### Success Response (200)
```json
{
  "success": true,
  "data": {
    "otp_verified": true,
    "otp_expires_at": "2025-08-23T15:30:00Z"
  }
}
```

---

## Security & Admin Endpoints

### 10. Security Stats
**Endpoint:** `GET /security/stats`  
**Description:** Get security statistics  
**Authentication Required:** Yes (Admin only)

#### Request Headers
```
Authorization: Bearer YOUR_JWT_TOKEN
```

#### Success Response (200)
```json
{
  "success": true,
  "data": {
    "total_users": 150,
    "active_sessions": 45,
    "failed_logins_24h": 12,
    "blocked_ips": 3
  }
}
```

#### Error Responses
- **403 Forbidden**
```json
{
  "success": false,
  "message": "Admin access required"
}
```

---

## Additional Endpoints (WP_Auth_API_Endpoints)

### 11. Password Reset Request
**Endpoint:** `POST /password-reset-request`  
**Description:** Request password reset via OTP  
**Authentication Required:** No

#### Request Body
```json
{
  "email": "string"      // required
}
```

#### Success Response (200)
```json
{
  "success": true,
  "message": "OTP sent to your email for password reset verification.",
  "data": {
    "email": "user@example.com",
    "expires_in": 300
  }
}
```

### 12. Password Reset
**Endpoint:** `POST /password-reset`  
**Description:** Reset password using OTP verification  
**Authentication Required:** No

#### Request Body
```json
{
  "email": "string",         // required
  "otp": "string",           // required - 6-digit code
  "new_password": "string"   // required
}
```

#### Success Response (200)
```json
{
  "success": true,
  "message": "Password reset successfully"
}
```

### 13. Change Password Request
**Endpoint:** `POST /change-password-request`  
**Description:** Request OTP for password change verification  
**Authentication Required:** Yes

#### Request Headers
```
Authorization: Bearer YOUR_JWT_TOKEN
```

#### Success Response (200)
```json
{
  "success": true,
  "message": "OTP sent to your email for password change verification.",
  "data": {
    "email": "user@example.com",
    "expires_in": 300
  }
}
```

### 14. Change Password
**Endpoint:** `POST /change-password`  
**Description:** Change current password with OTP verification  
**Authentication Required:** Yes

#### Request Headers
```
Authorization: Bearer YOUR_JWT_TOKEN
```

#### Request Body
```json
{
  "otp": "string",               // required - 6-digit code
  "current_password": "string",  // required
  "new_password": "string"       // required
}
```

#### Success Response (200)
```json
{
  "success": true,
  "message": "Password changed successfully. Please login again."
}
```

### 15. Refresh Token
**Endpoint:** `POST /refresh-token`  
**Description:** Refresh JWT token  
**Authentication Required:** No

#### Request Headers
```
Authorization: Bearer YOUR_JWT_TOKEN
```

#### Success Response (200)
```json
{
  "success": true,
  "token": "NEW_JWT_TOKEN",
  "expires_in": 3600
}
```

### 16. User Roles
**Endpoint:** `GET /user/roles`  
**Description:** Get current user's roles  
**Authentication Required:** Yes

#### Request Headers
```
Authorization: Bearer YOUR_JWT_TOKEN
```

#### Success Response (200)
```json
{
  "success": true,
  "roles": ["subscriber", "editor"]
}
```

### 17. Check Username Availability
**Endpoint:** `GET /check-username`  
**Description:** Check if username is available  
**Authentication Required:** No

#### Query Parameters
- `username` (string, required): Username to check

#### Success Response (200)
```json
{
  "success": true,
  "available": true
}
```

### 18. Check Email Availability
**Endpoint:** `GET /check-email`  
**Description:** Check if email is available  
**Authentication Required:** No

#### Query Parameters
- `email` (string, required): Email to check

#### Success Response (200)
```json
{
  "success": true,
  "available": false
}
```

---

## Error Handling

All API endpoints return consistent error responses with appropriate HTTP status codes:

- **400 Bad Request**: Invalid or missing required parameters
- **401 Unauthorized**: Authentication failed or token expired
- **403 Forbidden**: Insufficient permissions
- **404 Not Found**: Resource not found
- **409 Conflict**: Resource already exists
- **410 Gone**: Resource expired
- **429 Too Many Requests**: Rate limit exceeded
- **500 Internal Server Error**: Server error

## Rate Limiting

The API implements rate limiting to prevent abuse:
- OTP requests: Maximum 3 requests per 5 minutes per user
- Login attempts: Maximum 5 attempts per 15 minutes per IP
- General API calls: Maximum 100 requests per minute per user

## Security Features

- JWT-based authentication with token blacklisting
- Password validation and strength requirements
- OTP-based two-factor authentication and email verification
- Rate limiting on sensitive endpoints
- Admin-only access controls for security endpoints
- Session-based registration with automatic cleanup
- Protection against automated spam registrations
- Race condition protection for email/username conflicts

---

## Postman Collection Examples

### Environment Variables
Create a Postman environment with these variables:
- `base_url`: https://your-site.com/wp-json/wp-auth/v1
- `jwt_token`: (will be set after login)
- `registration_session_token`: (will be set after starting registration)

### Login Example
```
POST {{base_url}}/login
Content-Type: application/json

{
  "username": "john_doe",
  "password": "password123",
  "remember": true
}
```

### Register Example (Legacy)
```
POST {{base_url}}/register
Content-Type: application/json

{
  "username": "new_user",
  "email": "user@example.com",
  "password": "password123",
  "first_name": "John",
  "last_name": "Doe"
}
```

### 3-Step Registration Examples

#### Step 1: Start Registration
```
POST {{base_url}}/register/start
Content-Type: application/json

{
  "email": "user@example.com",
  "first_name": "John",
  "last_name": "Doe"
}
```

#### Step 2: Verify OTP
```
POST {{base_url}}/register/verify-otp
Content-Type: application/json

{
  "session_token": "{{registration_session_token}}",
  "otp": "123456"
}
```

#### Step 3: Complete Registration
```
POST {{base_url}}/register/complete
Content-Type: application/json

{
  "session_token": "{{registration_session_token}}",
  "username": "johndoe",
  "password": "securepassword123"
}
```

#### Check Registration Status
```
GET {{base_url}}/register/status?session_token={{registration_session_token}}
```

### Get Profile Example
```
GET {{base_url}}/profile
Authorization: Bearer {{jwt_token}}
```

### Update Profile Example
```
PUT {{base_url}}/profile
Authorization: Bearer {{jwt_token}}
Content-Type: application/json

{
  "first_name": "Updated",
  "last_name": "Name",
  "description": "New bio"
}
```

### Verify OTP Example
```
POST {{base_url}}/verify-otp
Content-Type: application/json

{
  "email": "user@example.com",
  "otp": "123456"
}
```

### Password Reset Request Example
```
POST {{base_url}}/password-reset-request
Content-Type: application/json

{
  "email": "user@example.com"
}
```

### Password Reset Example
```
POST {{base_url}}/password-reset
Content-Type: application/json

{
  "email": "user@example.com",
  "otp": "123456",
  "new_password": "newpassword123"
}
```

### Change Password Request Example
```
POST {{base_url}}/change-password-request
Authorization: Bearer {{jwt_token}}
Content-Type: application/json
```

### Change Password Example
```
POST {{base_url}}/change-password
Authorization: Bearer {{jwt_token}}
Content-Type: application/json

{
  "otp": "123456",
  "current_password": "oldpassword",
  "new_password": "newpassword123"
}
```

### Check Username Availability Example
```
GET {{base_url}}/check-username?username=new_username
```

### Security Stats Example (Admin only)
```
GET {{base_url}}/security/stats
Authorization: Bearer {{admin_jwt_token}}
```

---

## Implementation Notes

1. **Endpoint Organization**: Endpoints are now organized in logical subfolders:
   - `auth/`: Authentication endpoints (login, logout, validate-token)
   - `registration/`: Registration endpoints (all registration-related functionality)
   - `otp/`: OTP operations (verify, resend, status)
   - `profile/`: User profile management
   - `security/`: Security and admin endpoints

2. **Route Registration**: Routes are registered in two classes:
   - `WP_Auth_Rest_Routes`: Core authentication endpoints
   - `WP_Auth_API_Endpoints`: Additional utility endpoints

3. **3-Step Registration**: The new registration process provides enhanced security:
   - **Step 1**: Collect user information and send OTP
   - **Step 2**: Verify email with OTP code
   - **Step 3**: Set username/password and create account
   - **Session Management**: 30-minute sessions with automatic cleanup
   - **Backward Compatibility**: Legacy single-step endpoint still available

4. **JWT Token Format**: Tokens are issued using Firebase JWT library and include:
   - User ID
   - Expiration time
   - Issue time
   - Custom claims (roles, etc.)

5. **OTP Implementation**: 
   - 6-digit numeric codes
   - 5-minute expiration
   - Email delivery
   - Rate limited to prevent abuse
   - Maximum 3 attempts before requiring resend

6. **Permission Checks**:
   - `__return_true`: Public access
   - `WP_Auth_JWT_Permission::permission_check`: Requires valid JWT
   - `current_user_can('manage_options')`: Admin only

7. **Session Management** (Registration):
   - Sessions stored using WordPress transients
   - 32-character random session tokens
   - Automatic cleanup after expiration
   - Progress tracking and recovery support

8. **Data Validation**: All endpoints use WordPress sanitization and validation functions for security.
