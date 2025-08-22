# WP Authenticator API Documentation

## Overview
This document provides comprehensive information about the WP Authenticator REST API endpoints.
## Base URL
```
```

Authorization: Bearer YOUR_JWT_TOKEN

## Endpoints

### 1. Login
**Endpoint:** `POST /login`
**Description:** Authenticate user and receive JWT token

#### Request Body
  "password": "string"
```

#### Success Response (200)
```json
{
  "success": true,
  "message": "Login successful",
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "user": {
      "id": 1,
    }
  }
```

#### Error Responses
- **400 Bad Request**
```json
{
  "success": false,
  "message": "Username and password are required"
- **401 Unauthorized**
```json
{
  "success": false,

### 2. Register
**Description:** Register a new user
**Authentication Required:** No

#### Request Body
```json
{
  "email": "string",
  "password": "string"
}
```json
{
  "message": "User registered successfully",
  "data": {
    "user_id": 123,
}
```

- **400 Bad Request**
```json
}

- **409 Conflict**
```json
{
}
```
**Description:** Logout user and blacklist JWT token

#### Request Headers
```

#### Success Response (200)
  "message": "Logout successful"
}

#### Error Responses
- **401 Unauthorized**
```json
  "success": false,
  "message": "Invalid or expired token"
### 4. User Profile
**Description:** Get current user profile information
**Authentication Required:** Yes

#### Request Headers
Authorization: Bearer YOUR_JWT_TOKEN
```
  "success": true,
  "data": {
    "id": 1,
    "email": "john@example.com",
    "display_name": "John Doe",
  }
}

#### Error Responses
- **401 Unauthorized**
```json
  "success": false,
  "message": "Invalid or expired token"
### 5. Validate Token
**Description:** Validate JWT token
**Authentication Required:** Yes

#### Request Headers
Authorization: Bearer YOUR_JWT_TOKEN
```
{
  "message": "Token is valid",
  "data": {
    "user_id": 1,
  }
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

### 6. Security Stats
**Endpoint:** `GET /security-stats`
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
    "failed_login_attempts": 5,
    "blocked_ips": ["192.168.1.100", "10.0.0.1"],
    "last_security_scan": "2023-12-01 10:30:00"
  }
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

- **403 Forbidden**
```json
{
  "success": false,
  "message": "Admin access required"
}
```

### 7. Verify OTP
**Endpoint:** `POST /verify-otp`
**Description:** Verify OTP code for two-factor authentication
**Authentication Required:** No

#### Request Body
```json
{
  "user_id": "integer",
  "otp_code": "string"
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
  "message": "User ID and OTP code are required"
}
```

- **401 Unauthorized**
```json
{
  "success": false,
  "message": "Invalid OTP code"
}
```

- **410 Gone**
```json
{
  "success": false,
  "message": "OTP code has expired"
}
```

### 8. Resend OTP
**Endpoint:** `POST /resend-otp`
**Description:** Resend OTP code to user
**Authentication Required:** No

#### Request Body
```json
{
  "user_id": "integer"
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
  "message": "User ID is required"
}
```

- **404 Not Found**
```json
{
  "success": false,
  "message": "User not found"
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
**Endpoint:** `GET /otp-status/{user_id}`
**Description:** Get OTP verification status for a user
**Authentication Required:** No

#### URL Parameters
- `user_id` (integer): The ID of the user

#### Success Response (200)
```json
{
  "success": true,
  "data": {
    "is_verified": true,
    "last_sent": "2023-12-01 10:30:00",
    "attempts_remaining": 3
  }
}
```

#### Error Responses
- **400 Bad Request**
```json
{
  "success": false,
  "message": "Invalid user ID"
}
```

- **404 Not Found**
```json
{
  "success": false,
  "message": "User not found"
}
```

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
- OTP-based two-factor authentication
- Rate limiting and IP blocking (configurable)
- Secure token storage and validation

## Development Notes

- All timestamps are in UTC format
- User IDs are always integers
- Tokens expire after 24 hours by default (configurable)
- OTP codes expire after 5 minutes
- All sensitive data is properly sanitized and validated

---

## 1. Login
- **Endpoint:** `/login`
## 1. Login
**Postman Example:**
- Method: POST
- URL: `{{base_url}}/login`
- Headers:
  - Content-Type: application/json
- Body (raw, JSON):
  {
    "username": "your_username",
    "password": "your_password",
    "remember": true
  }
  - `remember` (boolean, optional, default: false)
- **Response:**
## 2. Register
**Postman Example:**
- Method: POST
- URL: `{{base_url}}/register`
- Headers:
  - Content-Type: application/json
- Body (raw, JSON):
  {
    "username": "your_username",
    "email": "your_email@example.com",
    "password": "your_password",
    "first_name": "First",
    "last_name": "Last"
  }
---

## 3. Logout
**Postman Example:**
- Method: POST
- URL: `{{base_url}}/logout`
- Headers:
  - Authorization: Bearer <your_jwt_token>
  - Content-Type: application/json
- Body (raw, JSON):
  {
    "token": "<your_jwt_token>",
    "refresh_token": "<your_refresh_token>"
  }
  - `username` (string, required)
  - `email` (string, required)
## 4. Profile
**Postman Example (GET):**
- Method: GET
- URL: `{{base_url}}/profile`
- Headers:
  - Authorization: Bearer <your_jwt_token>
  - Content-Type: application/json

**Postman Example (PUT):**
- Method: PUT
- URL: `{{base_url}}/profile`
- Headers:
  - Authorization: Bearer <your_jwt_token>
  - Content-Type: application/json
- Body (raw, JSON):
  {
    "first_name": "First",
    "last_name": "Last",
    "email": "your_email@example.com",
    "description": "Profile description"
  }
---

## 5. Validate Token
**Postman Example:**
- Method: GET
- URL: `{{base_url}}/validate-token?token=<your_jwt_token>`
- Headers:
  - Authorization: Bearer <your_jwt_token>
  - Content-Type: application/json
- **Parameters:**
  - `token` (string, optional)
## 6. Security Stats
**Postman Example:**
- Method: GET
- URL: `{{base_url}}/security/stats`
- Headers:
  - Authorization: Bearer <admin_jwt_token>
  - Content-Type: application/json

---
## 7. Verify OTP
**Postman Example:**
- Method: POST
- URL: `{{base_url}}/verify-otp`
- Headers:
  - Content-Type: application/json
- Body (raw, JSON):
  {
    "email": "your_email@example.com",
    "otp": "123456"
  }
- **Permission:** User must be logged in
- **Response:**
## 8. Resend OTP
**Postman Example:**
- Method: POST
- URL: `{{base_url}}/resend-otp`
- Headers:
  - Content-Type: application/json
- Body (raw, JSON):
  {
    "email": "your_email@example.com"
  }
- **Method:** PUT
- **Permission:** User must be logged in
## 9. OTP Status
**Postman Example:**
- Method: GET
- URL: `{{base_url}}/otp-status?email=your_email@example.com`
- Headers:
  - Content-Type: application/json
  - `description` (string, optional)
- **Response:**
  - `success` (bool)
  - `message` (string)
  - `data`: updated user info

---

## 5. Validate Token
- **Endpoint:** `/validate-token`
- **Method:** GET
- **Parameters:**
  - `token` (string, required)
- **Response:**
  - `success` (bool)
  - `message` (string)
  - `data`: user_id, expires

---

## 6. Security Stats
- **Endpoint:** `/security/stats`
- **Method:** GET
- **Permission:** Admin only (`manage_options`)
- **Response:**
  - `success` (bool)
  - `data`: security stats

---

## 7. Verify OTP
- **Endpoint:** `/verify-otp`
- **Method:** POST
- **Parameters:**
  - `email` (string, required)
  - `otp` (string, required)
- **Response:**
  - `success` (bool)
  - `message` (string)
  - `data`: verification result

---

## 8. Resend OTP
- **Endpoint:** `/resend-otp`
- **Method:** POST
- **Parameters:**
  - `email` (string, required)
- **Response:**
  - `success` (bool)
  - `message` (string)
  - `data`: email, OTP expiry

---

## 9. OTP Status
- **Endpoint:** `/otp-status`
- **Method:** GET
- **Parameters:**
  - `email` (string, required)
- **Response:**
  - `success` (bool)
  - `data`: OTP status

---

**Note:** All endpoints return errors in standard WordPress REST format with error codes and messages.
