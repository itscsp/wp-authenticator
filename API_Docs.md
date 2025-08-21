# WP Authenticator REST API Documentation

Base URL: `/wp-json/wp-auth/v1/`

---

## 1. Login
- **Endpoint:** `/login`
- **Method:** POST
- **Parameters:**
  - `username` (string, required)
  - `password` (string, required)
  - `remember` (boolean, optional, default: false)
- **Response:**
  - `success` (bool)
  - `message` (string)
  - `data`: user info, JWT token, refresh token, expiry

---

## 2. Register
- **Endpoint:** `/register`
- **Method:** POST
- **Parameters:**
  - `username` (string, required)
  - `email` (string, required)
  - `password` (string, required)
  - `first_name` (string, optional)
  - `last_name` (string, optional)
- **Response:**
  - `success` (bool)
  - `message` (string)
  - `data`: email, OTP expiry, verification required, next step

---

## 3. Logout
- **Endpoint:** `/logout`
- **Method:** POST
- **Permission:** User must be logged in
- **Parameters:**
  - `token` (string, optional)
  - `refresh_token` (string, optional)
- **Response:**
  - `success` (bool)
  - `message` (string)

---

## 4. Profile
- **Endpoint:** `/profile`
- **Method:** GET
- **Permission:** User must be logged in
- **Response:**
  - `success` (bool)
  - `data`: user info

- **Endpoint:** `/profile`
- **Method:** PUT
- **Permission:** User must be logged in
- **Parameters:**
  - `first_name` (string, optional)
  - `last_name` (string, optional)
  - `email` (string, optional)
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
