# Implementing JWT Authentication with WP Authenticator: Complete Frontend Guide

## Introduction

JSON Web Tokens (JWT) have revolutionized how we handle authentication in modern web applications. In this comprehensive guide, we'll explore how to implement JWT authentication using the WP Authenticator WordPress plugin with both React and Angular frontends.

## Understanding JWT Response Structure

When you successfully authenticate with the WP Authenticator API, you receive a response containing three crucial values:

```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vYmxvZ2NoZXRoYW5zcG9vamFyeWNvbS5sb2NhbCIsImF1ZCI6Imh0dHA6Ly9ibG9nY2hldGhhbnNwb29qYXJ5Y29tLmxvY2FsIiwiaWF0IjoxNzU1ODY4MDk3LCJleHAiOjE3NTU4NzE2OTcsInVzZXJfaWQiOjExLCJ1c2VyX2xvZ2luIjoia2lyYW4ifQ.D8J5ddYcwqO4KRbEQX44wIwb3Zd3bRLPAjhnSfyaGEI",
    "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vYmxvZ2NoZXRoYW5zcG9vamFyeWNvbS5sb2NhbCIsImF1ZCI6Imh0dHA6Ly9ibG9nY2hldGhhbnNwb29qYXJ5Y29tLmxvY2FsIiwiaWF0IjoxNzU1ODY4MDk3LCJleHAiOjE3NTY0NzI4OTcsInVzZXJfaWQiOjExLCJ0eXBlIjoicmVmcmVzaCJ9.0D60K-RK4_0tUJvevNjIGe9hFXGNJzYj-1FY5RfjX5E",
    "expires": 1755871697
  }
}
```

### Breaking Down the JWT Response

#### 1. **Access Token (`token`)**
- **Purpose**: Primary authentication token for API requests
- **Lifespan**: Short-lived (typically 1 hour)
- **Usage**: Include in Authorization header for protected endpoints
- **Structure**: Contains user information (user_id, user_login, etc.)

#### 2. **Refresh Token (`refresh_token`)**
- **Purpose**: Used to obtain new access tokens without re-authentication
- **Lifespan**: Long-lived (typically 7 days)
- **Usage**: Exchange for new access token when current token expires
- **Security**: Should be stored securely and used only for token refresh

#### 3. **Expiration Timestamp (`expires`)**
- **Purpose**: Unix timestamp indicating when the access token expires
- **Usage**: Determine when to refresh the token
- **Format**: Seconds since Unix epoch (1755871697 = specific date/time)

## React Implementation

### 1. Authentication Service Setup

```typescript
// services/authService.ts
import axios, { AxiosResponse } from 'axios';

interface LoginResponse {
  success: boolean;
  message: string;
  data: {
    token: string;
    refresh_token: string;
    expires: number;
    user: {
      id: number;
      username: string;
      email: string;
    };
  };
}

interface AuthTokens {
  accessToken: string;
  refreshToken: string;
  expiresAt: number;
}

class AuthService {
  private baseURL = 'https://your-site.com/wp-json/wp-auth/v1';
  
  // Login method
  async login(username: string, password: string): Promise<LoginResponse> {
    try {
      const response: AxiosResponse<LoginResponse> = await axios.post(
        `${this.baseURL}/login`,
        { username, password }
      );
      
      if (response.data.success) {
        this.storeTokens({
          accessToken: response.data.data.token,
          refreshToken: response.data.data.refresh_token,
          expiresAt: response.data.data.expires
        });
      }
      
      return response.data;
    } catch (error) {
      throw new Error('Login failed');
    }
  }

  // Store tokens securely
  private storeTokens(tokens: AuthTokens): void {
    localStorage.setItem('accessToken', tokens.accessToken);
    localStorage.setItem('refreshToken', tokens.refreshToken);
    localStorage.setItem('tokenExpiry', tokens.expiresAt.toString());
  }

  // Get stored access token
  getAccessToken(): string | null {
    return localStorage.getItem('accessToken');
  }

  // Check if token is expired
  isTokenExpired(): boolean {
    const expiry = localStorage.getItem('tokenExpiry');
    if (!expiry) return true;
    
    return Date.now() / 1000 > parseInt(expiry);
  }

  // Refresh access token
  async refreshAccessToken(): Promise<boolean> {
    const refreshToken = localStorage.getItem('refreshToken');
    if (!refreshToken) return false;

    try {
      const response = await axios.post(`${this.baseURL}/refresh-token`, {
        refresh_token: refreshToken
      });

      if (response.data.success) {
        this.storeTokens({
          accessToken: response.data.data.token,
          refreshToken: response.data.data.refresh_token,
          expiresAt: response.data.data.expires
        });
        return true;
      }
    } catch (error) {
      this.logout();
    }
    
    return false;
  }

  // Logout
  async logout(): Promise<void> {
    const token = this.getAccessToken();
    
    if (token) {
      try {
        await axios.post(`${this.baseURL}/logout`, {}, {
          headers: { Authorization: `Bearer ${token}` }
        });
      } catch (error) {
        console.warn('Logout request failed');
      }
    }

    localStorage.removeItem('accessToken');
    localStorage.removeItem('refreshToken');
    localStorage.removeItem('tokenExpiry');
  }
}

export default new AuthService();
```

### 2. Axios Interceptor for Automatic Token Management

```typescript
// services/httpClient.ts
import axios from 'axios';
import AuthService from './authService';

const httpClient = axios.create({
  baseURL: 'https://your-site.com/wp-json/wp-auth/v1',
});

// Request interceptor - Add token to requests
httpClient.interceptors.request.use(
  async (config) => {
    let token = AuthService.getAccessToken();
    
    // Check if token is expired and refresh if needed
    if (token && AuthService.isTokenExpired()) {
      const refreshed = await AuthService.refreshAccessToken();
      if (refreshed) {
        token = AuthService.getAccessToken();
      } else {
        AuthService.logout();
        window.location.href = '/login';
        return Promise.reject('Token refresh failed');
      }
    }
    
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    
    return config;
  },
  (error) => Promise.reject(error)
);

// Response interceptor - Handle 401 errors
httpClient.interceptors.response.use(
  (response) => response,
  async (error) => {
    if (error.response?.status === 401) {
      const originalRequest = error.config;
      
      if (!originalRequest._retry) {
        originalRequest._retry = true;
        
        const refreshed = await AuthService.refreshAccessToken();
        if (refreshed) {
          const token = AuthService.getAccessToken();
          originalRequest.headers.Authorization = `Bearer ${token}`;
          return httpClient(originalRequest);
        } else {
          AuthService.logout();
          window.location.href = '/login';
        }
      }
    }
    
    return Promise.reject(error);
  }
);

export default httpClient;
```

### 3. React Hook for Authentication

```typescript
// hooks/useAuth.ts
import { useState, useEffect, createContext, useContext } from 'react';
import AuthService from '../services/authService';

interface AuthContextType {
  isAuthenticated: boolean;
  login: (username: string, password: string) => Promise<boolean>;
  logout: () => void;
  loading: boolean;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export const AuthProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [isAuthenticated, setIsAuthenticated] = useState(false);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const token = AuthService.getAccessToken();
    if (token && !AuthService.isTokenExpired()) {
      setIsAuthenticated(true);
    }
    setLoading(false);
  }, []);

  const login = async (username: string, password: string): Promise<boolean> => {
    try {
      const response = await AuthService.login(username, password);
      if (response.success) {
        setIsAuthenticated(true);
        return true;
      }
    } catch (error) {
      console.error('Login failed:', error);
    }
    return false;
  };

  const logout = () => {
    AuthService.logout();
    setIsAuthenticated(false);
  };

  return (
    <AuthContext.Provider value={{ isAuthenticated, login, logout, loading }}>
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within AuthProvider');
  }
  return context;
};
```

### 4. Protected Route Component

```typescript
// components/ProtectedRoute.tsx
import React from 'react';
import { Navigate } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';

interface ProtectedRouteProps {
  children: React.ReactNode;
}

const ProtectedRoute: React.FC<ProtectedRouteProps> = ({ children }) => {
  const { isAuthenticated, loading } = useAuth();

  if (loading) {
    return <div>Loading...</div>;
  }

  return isAuthenticated ? <>{children}</> : <Navigate to="/login" />;
};

export default ProtectedRoute;
```

### 5. Login Component

```typescript
// components/Login.tsx
import React, { useState } from 'react';
import { useAuth } from '../hooks/useAuth';
import { useNavigate } from 'react-router-dom';

const Login: React.FC = () => {
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  
  const { login } = useAuth();
  const navigate = useNavigate();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError('');

    const success = await login(username, password);
    
    if (success) {
      navigate('/dashboard');
    } else {
      setError('Invalid credentials');
    }
    
    setLoading(false);
  };

  return (
    <form onSubmit={handleSubmit}>
      <input
        type="text"
        placeholder="Username"
        value={username}
        onChange={(e) => setUsername(e.target.value)}
        required
      />
      <input
        type="password"
        placeholder="Password"
        value={password}
        onChange={(e) => setPassword(e.target.value)}
        required
      />
      {error && <div className="error">{error}</div>}
      <button type="submit" disabled={loading}>
        {loading ? 'Logging in...' : 'Login'}
      </button>
    </form>
  );
};

export default Login;
```

## Angular Implementation

### 1. Authentication Service

```typescript
// services/auth.service.ts
import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { BehaviorSubject, Observable, throwError } from 'rxjs';
import { map, catchError } from 'rxjs/operators';

interface LoginResponse {
  success: boolean;
  message: string;
  data: {
    token: string;
    refresh_token: string;
    expires: number;
    user: any;
  };
}

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  private baseUrl = 'https://your-site.com/wp-json/wp-auth/v1';
  private isAuthenticatedSubject = new BehaviorSubject<boolean>(false);
  public isAuthenticated$ = this.isAuthenticatedSubject.asObservable();

  constructor(private http: HttpClient) {
    this.checkAuthStatus();
  }

  login(username: string, password: string): Observable<boolean> {
    return this.http.post<LoginResponse>(`${this.baseUrl}/login`, {
      username,
      password
    }).pipe(
      map(response => {
        if (response.success) {
          this.storeTokens(
            response.data.token,
            response.data.refresh_token,
            response.data.expires
          );
          this.isAuthenticatedSubject.next(true);
          return true;
        }
        return false;
      }),
      catchError(error => {
        console.error('Login error:', error);
        return throwError(error);
      })
    );
  }

  private storeTokens(token: string, refreshToken: string, expires: number): void {
    localStorage.setItem('accessToken', token);
    localStorage.setItem('refreshToken', refreshToken);
    localStorage.setItem('tokenExpiry', expires.toString());
  }

  getAccessToken(): string | null {
    return localStorage.getItem('accessToken');
  }

  isTokenExpired(): boolean {
    const expiry = localStorage.getItem('tokenExpiry');
    if (!expiry) return true;
    return Date.now() / 1000 > parseInt(expiry);
  }

  refreshToken(): Observable<boolean> {
    const refreshToken = localStorage.getItem('refreshToken');
    if (!refreshToken) return throwError('No refresh token');

    return this.http.post<LoginResponse>(`${this.baseUrl}/refresh-token`, {
      refresh_token: refreshToken
    }).pipe(
      map(response => {
        if (response.success) {
          this.storeTokens(
            response.data.token,
            response.data.refresh_token,
            response.data.expires
          );
          return true;
        }
        return false;
      }),
      catchError(() => {
        this.logout();
        return throwError('Token refresh failed');
      })
    );
  }

  logout(): void {
    const token = this.getAccessToken();
    
    if (token) {
      this.http.post(`${this.baseUrl}/logout`, {}, {
        headers: new HttpHeaders({
          'Authorization': `Bearer ${token}`
        })
      }).subscribe();
    }

    localStorage.removeItem('accessToken');
    localStorage.removeItem('refreshToken');
    localStorage.removeItem('tokenExpiry');
    this.isAuthenticatedSubject.next(false);
  }

  private checkAuthStatus(): void {
    const token = this.getAccessToken();
    this.isAuthenticatedSubject.next(token !== null && !this.isTokenExpired());
  }
}
```

### 2. HTTP Interceptor

```typescript
// interceptors/auth.interceptor.ts
import { Injectable } from '@angular/core';
import { 
  HttpInterceptor, 
  HttpRequest, 
  HttpHandler, 
  HttpEvent,
  HttpErrorResponse 
} from '@angular/common/http';
import { Observable, throwError, BehaviorSubject } from 'rxjs';
import { catchError, filter, take, switchMap } from 'rxjs/operators';
import { AuthService } from '../services/auth.service';
import { Router } from '@angular/router';

@Injectable()
export class AuthInterceptor implements HttpInterceptor {
  private isRefreshing = false;
  private refreshTokenSubject: BehaviorSubject<any> = new BehaviorSubject<any>(null);

  constructor(
    private authService: AuthService,
    private router: Router
  ) {}

  intercept(req: HttpRequest<any>, next: HttpHandler): Observable<HttpEvent<any>> {
    const token = this.authService.getAccessToken();
    
    if (token && !this.authService.isTokenExpired()) {
      req = this.addTokenToRequest(req, token);
    }

    return next.handle(req).pipe(
      catchError((error: HttpErrorResponse) => {
        if (error.status === 401) {
          return this.handle401Error(req, next);
        }
        return throwError(error);
      })
    );
  }

  private addTokenToRequest(req: HttpRequest<any>, token: string): HttpRequest<any> {
    return req.clone({
      setHeaders: {
        Authorization: `Bearer ${token}`
      }
    });
  }

  private handle401Error(req: HttpRequest<any>, next: HttpHandler): Observable<HttpEvent<any>> {
    if (!this.isRefreshing) {
      this.isRefreshing = true;
      this.refreshTokenSubject.next(null);

      return this.authService.refreshToken().pipe(
        switchMap(() => {
          this.isRefreshing = false;
          const newToken = this.authService.getAccessToken();
          this.refreshTokenSubject.next(newToken);
          return next.handle(this.addTokenToRequest(req, newToken!));
        }),
        catchError((error) => {
          this.isRefreshing = false;
          this.authService.logout();
          this.router.navigate(['/login']);
          return throwError(error);
        })
      );
    } else {
      return this.refreshTokenSubject.pipe(
        filter(token => token != null),
        take(1),
        switchMap(token => next.handle(this.addTokenToRequest(req, token)))
      );
    }
  }
}
```

### 3. Auth Guard

```typescript
// guards/auth.guard.ts
import { Injectable } from '@angular/core';
import { CanActivate, Router } from '@angular/router';
import { Observable } from 'rxjs';
import { map } from 'rxjs/operators';
import { AuthService } from '../services/auth.service';

@Injectable({
  providedIn: 'root'
})
export class AuthGuard implements CanActivate {
  constructor(
    private authService: AuthService,
    private router: Router
  ) {}

  canActivate(): Observable<boolean> {
    return this.authService.isAuthenticated$.pipe(
      map(isAuthenticated => {
        if (!isAuthenticated) {
          this.router.navigate(['/login']);
          return false;
        }
        return true;
      })
    );
  }
}
```

### 4. Login Component

```typescript
// components/login/login.component.ts
import { Component } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { AuthService } from '../../services/auth.service';

@Component({
  selector: 'app-login',
  templateUrl: './login.component.html'
})
export class LoginComponent {
  loginForm: FormGroup;
  loading = false;
  error = '';

  constructor(
    private fb: FormBuilder,
    private authService: AuthService,
    private router: Router
  ) {
    this.loginForm = this.fb.group({
      username: ['', Validators.required],
      password: ['', Validators.required]
    });
  }

  onSubmit(): void {
    if (this.loginForm.valid) {
      this.loading = true;
      this.error = '';

      const { username, password } = this.loginForm.value;

      this.authService.login(username, password).subscribe({
        next: (success) => {
          if (success) {
            this.router.navigate(['/dashboard']);
          } else {
            this.error = 'Invalid credentials';
          }
          this.loading = false;
        },
        error: (error) => {
          this.error = 'Login failed. Please try again.';
          this.loading = false;
        }
      });
    }
  }
}
```

## Best Practices and Security Considerations

### 1. Token Storage
- **For Web Apps**: Use localStorage for access tokens (short-lived)
- **For Mobile Apps**: Use secure storage (Keychain/Android Keystore)
- **Never** store tokens in cookies if using HTTPS

### 2. Token Refresh Strategy
```typescript
// Proactive token refresh (before expiration)
const refreshTokenBeforeExpiry = () => {
  const expiry = localStorage.getItem('tokenExpiry');
  if (expiry) {
    const timeUntilExpiry = parseInt(expiry) - (Date.now() / 1000);
    
    // Refresh 5 minutes before expiry
    if (timeUntilExpiry < 300) {
      authService.refreshAccessToken();
    }
  }
};

// Check every minute
setInterval(refreshTokenBeforeExpiry, 60000);
```

### 3. Error Handling
```typescript
const handleApiError = (error: any) => {
  switch (error.response?.status) {
    case 401:
      // Token expired or invalid
      authService.logout();
      break;
    case 403:
      // Insufficient permissions
      showErrorMessage('Access denied');
      break;
    case 429:
      // Rate limited
      showErrorMessage('Too many requests. Please try again later.');
      break;
    default:
      showErrorMessage('An error occurred. Please try again.');
  }
};
```

### 4. Logout on Tab Close
```typescript
// Clear tokens when all tabs are closed
window.addEventListener('beforeunload', () => {
  // Only if this is the last tab
  if (navigator.userAgent.indexOf('Chrome') > -1) {
    localStorage.setItem('lastTabClosed', Date.now().toString());
  }
});

window.addEventListener('storage', (e) => {
  if (e.key === 'lastTabClosed') {
    // Clear tokens if no other tabs open
    setTimeout(() => {
      if (!document.hasFocus()) {
        authService.logout();
      }
    }, 1000);
  }
});
```

## Advanced Features

### 1. Remember Me Functionality
```typescript
const login = async (username: string, password: string, remember: boolean) => {
  const response = await AuthService.login(username, password, remember);
  
  if (response.success && remember) {
    // Store refresh token in more persistent storage
    sessionStorage.setItem('refreshToken', response.data.refresh_token);
  }
};
```

### 2. Token Validation
```typescript
const validateToken = async (): Promise<boolean> => {
  const token = AuthService.getAccessToken();
  if (!token) return false;

  try {
    const response = await httpClient.get('/validate-token', {
      params: { token }
    });
    return response.data.success;
  } catch (error) {
    return false;
  }
};
```

### 3. Multiple Device Management
```typescript
// Logout from all devices
const logoutEverywhere = async () => {
  try {
    await httpClient.post('/logout-all-devices');
    authService.logout();
  } catch (error) {
    console.error('Failed to logout from all devices');
  }
};
```

## Conclusion

Implementing JWT authentication with the WP Authenticator plugin provides a robust, scalable solution for modern web applications. The combination of short-lived access tokens and long-lived refresh tokens ensures both security and user experience.

Key takeaways:
- Always validate tokens on both client and server
- Implement proper error handling and token refresh logic
- Use interceptors for automatic token management
- Store tokens securely based on your platform
- Implement logout functionality for security

By following these patterns and best practices, you'll have a secure, maintainable authentication system that works seamlessly with your WordPress backend and modern frontend frameworks.

---

*This implementation guide covers the essential aspects of JWT authentication. Remember to adapt the code to your specific requirements and always keep security best practices in mind.*