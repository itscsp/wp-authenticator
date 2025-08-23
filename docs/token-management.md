# Token Management in WP Authenticator

## Overview

WP Authenticator uses a dual-token system with both access tokens and refresh tokens for enhanced security and better user experience.

## Token Types

### Access Token (JWT)
- **Purpose**: Authenticate API requests
- **Lifetime**: 1 hour (3600 seconds)
- **Usage**: Include in Authorization header for protected endpoints
- **Security**: Short-lived to minimize security risks

### Refresh Token
- **Purpose**: Obtain new access tokens without re-authentication
- **Lifetime**: 7 days (604800 seconds)
- **Usage**: Exchange for new access tokens when they expire
- **Security**: Longer-lived but can be revoked

## Token Flow

### Registration Process
```
1. Complete Registration → Receive both tokens
2. Store tokens securely
3. Use access token for API calls
4. Use refresh token when access token expires
```

### Login Process
```
1. Login → Receive both tokens
2. Store tokens securely
3. Use access token for API calls
4. Use refresh token when access token expires
```

## API Endpoints That Provide Tokens

### Registration Complete
**Endpoint**: `POST /register/complete`

**Response**:
```json
{
  "success": true,
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "token_expires": 1640995200,
    "user": { ... }
  }
}
```

### Login
**Endpoint**: `POST /login`

**Response**:
```json
{
  "success": true,
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "expires": 1640995200,
    "user_id": 123
  }
}
```

### Refresh Token
**Endpoint**: `POST /refresh-token`

**Request**:
```json
{
  "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
}
```

**Response**:
```json
{
  "success": true,
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "expires_in": 3600
}
```

## Frontend Implementation

### Token Storage
```javascript
// Store tokens after login/registration
const storeTokens = (authData) => {
  localStorage.setItem('access_token', authData.token);
  localStorage.setItem('refresh_token', authData.refresh_token);
  localStorage.setItem('token_expires', authData.token_expires || authData.expires);
};

// Get stored tokens
const getTokens = () => ({
  accessToken: localStorage.getItem('access_token'),
  refreshToken: localStorage.getItem('refresh_token'),
  expiresAt: localStorage.getItem('token_expires')
});
```

### Automatic Token Refresh
```javascript
// Check if token needs refresh
const needsRefresh = (expiresAt) => {
  const now = Math.floor(Date.now() / 1000);
  const buffer = 300; // 5 minutes buffer
  return now >= (expiresAt - buffer);
};

// Refresh token function
const refreshAccessToken = async (refreshToken) => {
  try {
    const response = await fetch('/wp-json/wp-auth/v1/refresh-token', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        refresh_token: refreshToken
      })
    });
    
    const data = await response.json();
    
    if (data.success) {
      localStorage.setItem('access_token', data.token);
      localStorage.setItem('token_expires', Math.floor(Date.now() / 1000) + data.expires_in);
      return data.token;
    }
    
    throw new Error('Token refresh failed');
  } catch (error) {
    // Refresh failed, redirect to login
    localStorage.clear();
    window.location.href = '/login';
    throw error;
  }
};

// API request with automatic token refresh
const apiRequest = async (url, options = {}) => {
  const tokens = getTokens();
  
  // Check if token needs refresh
  if (needsRefresh(tokens.expiresAt)) {
    await refreshAccessToken(tokens.refreshToken);
    tokens.accessToken = localStorage.getItem('access_token');
  }
  
  // Make API request with current token
  return fetch(url, {
    ...options,
    headers: {
      ...options.headers,
      'Authorization': `Bearer ${tokens.accessToken}`,
      'Content-Type': 'application/json'
    }
  });
};
```

### Registration Flow Implementation
```javascript
// Complete 3-step registration
const completeRegistration = async (sessionToken, username, password) => {
  try {
    const response = await fetch('/wp-json/wp-auth/v1/register/complete', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        session_token: sessionToken,
        username: username,
        password: password
      })
    });
    
    const data = await response.json();
    
    if (data.success) {
      // Store tokens immediately after successful registration
      storeTokens(data.data);
      
      // User is now authenticated and can make API calls
      console.log('Registration complete! User is logged in.');
      
      // Redirect to dashboard or profile
      window.location.href = '/dashboard';
    } else {
      throw new Error(data.message);
    }
  } catch (error) {
    console.error('Registration completion failed:', error);
    throw error;
  }
};
```

## Security Best Practices

### Token Storage
- **Web Apps**: Use secure httpOnly cookies or secure localStorage
- **Mobile Apps**: Use secure keychain/keystore
- **Never**: Store in plain text or unsecured locations

### Token Validation
- **Always**: Validate tokens on the server side
- **Never**: Trust client-side token validation alone
- **Implement**: Token blacklisting for logout

### Token Rotation
- **Refresh Tokens**: Rotate refresh tokens on each use
- **Access Tokens**: Keep short expiration times
- **Revocation**: Implement token revocation endpoints

## Benefits of Dual Token System

### Security Benefits
1. **Reduced Attack Surface**: Short-lived access tokens limit exposure
2. **Graceful Degradation**: Refresh tokens can be revoked without affecting other sessions
3. **Audit Trail**: Better tracking of token usage and refresh patterns

### User Experience Benefits
1. **Seamless Authentication**: No need to re-login after registration
2. **Persistent Sessions**: Users stay logged in longer with refresh tokens
3. **Background Refresh**: Tokens refresh automatically without user intervention

### Developer Benefits
1. **Consistent API**: Same token structure across login and registration
2. **Error Handling**: Clear token expiration and refresh flows
3. **Scalability**: Stateless tokens work well with distributed systems

## Token Payload Structure

### Access Token Payload
```json
{
  "iss": "https://your-site.com",
  "aud": "https://your-site.com", 
  "iat": 1640908800,
  "exp": 1640912400,
  "user_id": 123,
  "user_login": "johndoe"
}
```

### Refresh Token Payload
```json
{
  "iss": "https://your-site.com",
  "aud": "https://your-site.com",
  "iat": 1640908800,
  "exp": 1641513600,
  "user_id": 123,
  "type": "refresh"
}
```

## Error Handling

### Common Scenarios

#### Expired Access Token
- **Response**: 401 Unauthorized
- **Action**: Use refresh token to get new access token
- **Fallback**: Redirect to login if refresh fails

#### Expired Refresh Token
- **Response**: 401 Unauthorized  
- **Action**: Clear stored tokens and redirect to login
- **Prevention**: Implement token refresh before expiration

#### Invalid Tokens
- **Response**: 401 Unauthorized
- **Action**: Clear stored tokens and redirect to login
- **Logging**: Log security events for monitoring

## Monitoring and Analytics

### Metrics to Track
- Token generation frequency
- Token refresh patterns
- Failed refresh attempts
- Token expiration events

### Security Monitoring
- Unusual token usage patterns
- Multiple concurrent sessions
- Failed authentication attempts
- Token validation errors

This dual-token system provides the perfect balance of security and user experience, ensuring users have seamless access while maintaining robust security practices.
