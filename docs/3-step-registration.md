# 3-Step Registration Process

This document explains how to use the new 3-step registration process in the WP Authenticator plugin.

## Overview

The registration process is now divided into 3 steps to enhance security and improve user experience:

1. **Step 1**: Collect name and email, send OTP
2. **Step 2**: Verify OTP 
3. **Step 3**: Set username and password, create account

## API Endpoints

### Step 1: Start Registration

**Endpoint**: `POST /wp-json/wp-auth/v1/register/start`

**Required Parameters**:
- `email` (string) - User's email address
- `first_name` (string) - User's first name

**Optional Parameters**:
- `last_name` (string) - User's last name

**Response**:
```json
{
  "success": true,
  "message": "Registration started successfully. Please check your email for the verification code.",
  "data": {
    "session_token": "abc123...",
    "email": "user@example.com",
    "step": 1,
    "next_step": "verify_otp",
    "otp_expires_in": 300,
    "session_expires_in": 1800
  }
}
```

### Step 2: Verify OTP

**Endpoint**: `POST /wp-json/wp-auth/v1/register/verify-otp`

**Required Parameters**:
- `session_token` (string) - Token from Step 1
- `otp` (string) - 6-digit OTP code from email

**Response**:
```json
{
  "success": true,
  "message": "Email verified successfully. Please complete your registration by setting a username and password.",
  "data": {
    "session_token": "abc123...",
    "email": "user@example.com",
    "step": 2,
    "next_step": "complete_registration",
    "email_verified": true,
    "session_expires_in": 1800
  }
}
```

### Step 3: Complete Registration

**Endpoint**: `POST /wp-json/wp-auth/v1/register/complete`

**Required Parameters**:
- `session_token` (string) - Token from previous steps
- `username` (string) - Desired username
- `password` (string) - User's password (minimum 6 characters)

**Response**:
```json
{
  "success": true,
  "message": "Registration completed successfully! You are now logged in.",
  "data": {
    "user_id": 123,
    "username": "johndoe",
    "email": "user@example.com",
    "token": "jwt_token_here...",
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

## Helper Endpoints

### Check Registration Status

**Endpoint**: `GET /wp-json/wp-auth/v1/register/status?session_token=abc123...`

**Response**:
```json
{
  "success": true,
  "data": {
    "session_token": "abc123...",
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

### Resend OTP (Existing Endpoint)

**Endpoint**: `POST /wp-json/wp-auth/v1/resend-otp`

**Required Parameters**:
- `email` (string) - User's email address

## Error Handling

Common error responses:

### Invalid Session
```json
{
  "code": "invalid_session",
  "message": "Invalid or expired registration session.",
  "data": {"status": 400}
}
```

### Email Already Exists
```json
{
  "code": "email_exists",
  "message": "An account with this email address already exists.",
  "data": {"status": 400}
}
```

### Invalid OTP
```json
{
  "code": "invalid_otp",
  "message": "Invalid OTP code.",
  "data": {"status": 400}
}
```

### Username Exists
```json
{
  "code": "username_exists",
  "message": "Username already exists. Please choose a different username.",
  "data": {"status": 400}
}
```

## Session Management

- Registration sessions last for **30 minutes**
- OTP codes expire after **5 minutes**
- Maximum **3 OTP attempts** before requiring resend
- Session tokens are randomly generated 32-character strings
- Sessions are stored using WordPress transients for automatic cleanup

## Backward Compatibility

The original single-step registration endpoint is still available at:

**Endpoint**: `POST /wp-json/wp-auth/v1/register`

This endpoint works exactly as before but now uses OTP verification internally.

## Frontend Integration Example

```javascript
// Step 1: Start registration
const startResponse = await fetch('/wp-json/wp-auth/v1/register/start', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    email: 'user@example.com',
    first_name: 'John',
    last_name: 'Doe'
  })
});

const startData = await startResponse.json();
const sessionToken = startData.data.session_token;

// Step 2: Verify OTP (after user enters OTP)
const verifyResponse = await fetch('/wp-json/wp-auth/v1/register/verify-otp', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    session_token: sessionToken,
    otp: '123456'
  })
});

// Step 3: Complete registration
const completeResponse = await fetch('/wp-json/wp-auth/v1/register/complete', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    session_token: sessionToken,
    username: 'johndoe',
    password: 'securepassword123'
  })
});

const completeData = await completeResponse.json();
const userToken = completeData.data.token; // JWT token for authentication
```

## Benefits

1. **Enhanced Security**: Email verification before account creation
2. **Better UX**: Step-by-step process feels less overwhelming
3. **Spam Prevention**: OTP verification prevents automated registrations
4. **Email Validation**: Ensures users provide accessible email addresses
5. **Session Recovery**: Users can check status and continue where they left off
6. **Flexible Implementation**: Frontend can implement as single form or multiple steps
