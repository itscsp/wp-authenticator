# WP Authenticator - Swagger API Documentation

This document explains how to use the integrated Swagger documentation for the WP Authenticator plugin.

## 🚀 Quick Start

After installing the WP Authenticator plugin with Swagger support, you can access the API documentation in several ways:

### 1. **Interactive Swagger UI**
Access the full interactive API documentation at:
```
https://your-site.com/wp-json/wp-auth/v1/docs
```

### 2. **Admin Dashboard**
Go to your WordPress admin and navigate to:
```
WP Authenticator → API Docs
```

### 3. **OpenAPI JSON Specification**
Get the raw OpenAPI/Swagger JSON at:
```
https://your-site.com/wp-json/wp-auth/v1/swagger.json
```

## 📋 What's Included

The Swagger documentation includes:

- **Complete API Reference** - All 13+ endpoints with detailed descriptions
- **Interactive Testing** - Test API calls directly from the browser
- **Request/Response Examples** - See exactly what data to send and expect
- **Authentication Guide** - How to use JWT tokens with the API
- **Error Code Reference** - Understand all possible error responses

## 🔐 Authentication in Swagger

### Step 1: Login to Get Token
1. Open the Swagger UI
2. Find the `/login` endpoint under "Authentication"
3. Click "Try it out"
4. Enter your credentials:
   ```json
   {
     "username": "your_username",
     "password": "your_password"
   }
   ```
5. Click "Execute"
6. Copy the `token` from the response

### Step 2: Authorize for Protected Endpoints
1. Click the "Authorize" button at the top of Swagger UI
2. Enter: `Bearer YOUR_TOKEN_HERE`
3. Click "Authorize"
4. Now you can test all protected endpoints!

## 📊 API Endpoint Categories

### 🔑 Authentication
- `POST /login` - Get JWT tokens
- `POST /logout` - Invalidate tokens  
- `GET /validate-token` - Check token validity

### 👤 Registration (3-Step Process)
- `POST /register/start` - Begin registration with email/name
- `POST /register/verify-otp` - Verify email with OTP
- `POST /register/complete` - Set username/password & auto-login
- `GET /register/status` - Check registration progress

### 👤 Registration (Legacy)
- `POST /register` - Single-step registration (backward compatibility)

### 🔢 OTP (One-Time Password)
- `POST /verify-otp` - Verify OTP codes
- `POST /resend-otp` - Resend OTP via email
- `GET /otp-status` - Check OTP status

### 👤 Profile Management
- `GET /profile` - Get user profile
- `PUT /profile` - Update user profile

### 🛡️ Security (Admin Only)
- `GET /security/stats` - Authentication statistics

## 💡 Example Workflows

### Complete 3-Step Registration Flow

#### Step 1: Start Registration
```bash
curl -X POST "https://your-site.com/wp-json/wp-auth/v1/register/start" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "first_name": "John",
    "last_name": "Doe"
  }'
```

**Response:**
```json
{
  "success": true,
  "message": "Registration started. OTP sent to email.",
  "session_token": "abc123...",
  "expires_at": "2024-01-01T12:30:00Z"
}
```

#### Step 2: Verify OTP
```bash
curl -X POST "https://your-site.com/wp-json/wp-auth/v1/register/verify-otp" \
  -H "Content-Type: application/json" \
  -d '{
    "session_token": "abc123...",
    "otp": "123456"
  }'
```

#### Step 3: Complete Registration
```bash
curl -X POST "https://your-site.com/wp-json/wp-auth/v1/register/complete" \
  -H "Content-Type: application/json" \
  -d '{
    "session_token": "abc123...",
    "username": "johndoe",
    "password": "securepassword123"
  }'
```

### Simple Login & Profile Access

#### Login
```bash
curl -X POST "https://your-site.com/wp-json/wp-auth/v1/login" \
  -H "Content-Type: application/json" \
  -d '{
    "username": "johndoe",
    "password": "securepassword123"
  }'
```

**Response:**
```json
{
  "success": true,
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "user": {
    "ID": 123,
    "user_login": "johndoe",
    "display_name": "John Doe"
  },
  "expires_in": 86400
}
```

#### Get Profile (with JWT token)
```bash
curl -X GET "https://your-site.com/wp-json/wp-auth/v1/profile" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
```

## 🎯 Using Swagger UI Features

### Testing Endpoints
1. **Expand any endpoint** - Click on the endpoint name
2. **Try it out** - Click the "Try it out" button
3. **Fill parameters** - Enter required data in the form
4. **Execute** - Click "Execute" to make the API call
5. **View response** - See the actual server response below

### Understanding Responses
- **200-299**: Success responses
- **400-499**: Client errors (bad request, unauthorized, etc.)
- **500-599**: Server errors

### Export/Import
- **Download OpenAPI spec** - Use the download button to get the JSON file
- **Import to Postman** - Import the OpenAPI JSON into Postman for testing
- **Generate client code** - Use tools like Swagger Codegen with the spec

## 🛠️ Development & Testing

### Local Development
The Swagger UI is perfect for:
- **Testing new endpoints** during development
- **Debugging API responses** 
- **Sharing API documentation** with frontend developers
- **Validating request/response formats**

### Production Use
- Swagger UI can be safely used in production
- All authentication still applies (JWT tokens required)
- Admin-only endpoints remain protected
- No security compromises

## 🔧 Customization

### Adding Custom Endpoints
If you extend the plugin with custom endpoints, you can add them to the Swagger documentation by:

1. Editing `includes/class-swagger-handler.php`
2. Adding your endpoint to the `get_swagger_paths()` method
3. Following the existing pattern for documentation

### Styling
The Swagger UI uses the default Swagger styling. You can customize it by:
- Modifying the CSS in the `get_swagger_ui()` method
- Adding custom themes
- Overriding Swagger UI CSS classes

## 📚 Resources

- **[OpenAPI Specification](https://swagger.io/specification/)** - Full OpenAPI 3.0 documentation
- **[Swagger UI Documentation](https://swagger.io/tools/swagger-ui/)** - How to use Swagger UI
- **[JWT.io](https://jwt.io/)** - Debug and understand JWT tokens
- **[Postman](https://www.postman.com/)** - Alternative API testing tool

## 🐛 Troubleshooting

### Common Issues

#### "Authorization header missing"
- Make sure you're logged in and have copied the JWT token
- Use the "Authorize" button in Swagger UI
- Format: `Bearer YOUR_TOKEN_HERE`

#### "Invalid token"
- Tokens expire after 24 hours by default
- Login again to get a fresh token
- Check token format (should start with `eyJ`)

#### "CORS errors"
- Ensure your WordPress site allows CORS for API endpoints
- Check if HTTPS is required for your setup

#### "Swagger UI not loading"
- Check browser console for JavaScript errors
- Ensure internet connection (CDN resources needed)
- Try refreshing the page

### Getting Help
- Check the main [API Documentation](../API_Docs.md)
- Review [WordPress REST API documentation](https://developer.wordpress.org/rest-api/)
- Submit issues on [GitHub](https://github.com/itscsp/wp-authenticator/issues)
