# WP Authenticator REST API Documentation

Base URL: `/wp-json/wp-auth/v1/`

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
