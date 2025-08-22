# JWT Authentication with WP Authenticator Plugin: React Implementation Guide

## Table of Contents
1. [Overview](#overview)
2. [API Endpoints Documentation](#api-endpoints-documentation)
3. [React Implementation](#react-implementation)
4. [Security Best Practices](#security-best-practices)
5. [Advanced Features](#advanced-features)
6. [Troubleshooting](#troubleshooting)

## Overview

This guide demonstrates how to implement JWT-based authentication in React applications using the WP Authenticator WordPress plugin. The plugin provides secure REST API endpoints for user authentication, profile management, and token validation.

### Key Features
- JWT-based stateless authentication
- Automatic token refresh mechanism
- Secure token storage and management
- Protected route implementation
- Error handling and user feedback

## API Endpoints Documentation

### Base URL
```
https://your-wordpress-site.com/wp-json/wp-auth/v1
```

### Authentication Endpoints

#### 1. Login
```
POST /login
```

**Request Body:**
```json
{
  "username": "string",
  "password": "string",
  "remember": "boolean" // optional
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "expires": 1755871697,
    "user": {
      "id": 11,
      "username": "kiran",
      "email": "kiran@example.com",
      "display_name": "Kiran",
      "roles": ["subscriber"]
    }
  }
}
```

**Error Response (401):**
```json
{
  "code": "login_failed",
  "message": "Invalid username or password",
  "data": {
    "status": 401
  }
}
```

#### 2. Register
```
POST /register
```

**Request Body:**
```json
{
  "username": "string",
  "email": "string",
  "password": "string",
  "first_name": "string", // optional
  "last_name": "string"   // optional
}
```

#### 3. Logout
```
POST /logout
```

**Headers:**
```
Authorization: Bearer <access_token>
```

**Request Body:**
```json
{
  "token": "string",        // optional
  "refresh_token": "string" // optional
}
```

#### 4. Validate Token
```
GET /validate-token?token=<jwt_token>
```

**Success Response:**
```json
{
  "success": true,
  "message": "Token is valid",
  "data": {
    "user_id": 11,
    "expires": 1755871697
  }
}
```

#### 5. Refresh Token
```
POST /refresh-token
```

**Request Body:**
```json
{
  "refresh_token": "string"
}
```

### Profile Endpoints

#### 6. Get Profile
```
GET /profile
```

**Headers:**
```
Authorization: Bearer <access_token>
```

#### 7. Update Profile
```
PUT /profile
```

**Headers:**
```
Authorization: Bearer <access_token>
```

**Request Body:**
```json
{
  "first_name": "string",
  "last_name": "string",
  "email": "string",
  "description": "string"
}
```

### OTP Endpoints

#### 8. Verify OTP
```
POST /verify-otp
```

#### 9. Resend OTP
```
POST /resend-otp
```

#### 10. OTP Status
```
GET /otp-status?email=<email>
```

## React Implementation

### 1. Project Setup

First, install the required dependencies:

```bash
npm install axios react-router-dom
npm install --save-dev @types/react @types/react-dom @types/node
```

### 2. Authentication Service

Create a comprehensive authentication service to handle all API interactions:

```typescript
// src/services/authService.ts
import axios, { AxiosResponse } from 'axios';

// Types
interface User {
  id: number;
  username: string;
  email: string;
  display_name: string;
  roles: string[];
}

interface LoginResponse {
  success: boolean;
  message: string;
  data: {
    token: string;
    refresh_token: string;
    expires: number;
    user: User;
  };
}

interface AuthTokens {
  accessToken: string;
  refreshToken: string;
  expiresAt: number;
}

interface RegisterData {
  username: string;
  email: string;
  password: string;
  first_name?: string;
  last_name?: string;
}

interface ProfileData {
  first_name?: string;
  last_name?: string;
  email?: string;
  description?: string;
}

class AuthService {
  private baseURL = process.env.REACT_APP_API_URL || 'https://your-site.com/wp-json/wp-auth/v1';
  private tokenRefreshPromise: Promise<boolean> | null = null;

  // Login method
  async login(username: string, password: string, remember = false): Promise<LoginResponse> {
    try {
      const response: AxiosResponse<LoginResponse> = await axios.post(
        `${this.baseURL}/login`,
        { username, password, remember }
      );
      
      if (response.data.success) {
        this.storeTokens({
          accessToken: response.data.data.token,
          refreshToken: response.data.data.refresh_token,
          expiresAt: response.data.data.expires
        });
        
        // Store user data
        this.storeUserData(response.data.data.user);
      }
      
      return response.data;
    } catch (error: any) {
      throw new Error(error.response?.data?.message || 'Login failed');
    }
  }

  // Register method
  async register(data: RegisterData): Promise<any> {
    try {
      const response = await axios.post(`${this.baseURL}/register`, data);
      return response.data;
    } catch (error: any) {
      throw new Error(error.response?.data?.message || 'Registration failed');
    }
  }

  // Logout method
  async logout(): Promise<void> {
    const token = this.getAccessToken();
    
    if (token) {
      try {
        await axios.post(
          `${this.baseURL}/logout`,
          { token, refresh_token: this.getRefreshToken() },
          { headers: { Authorization: `Bearer ${token}` } }
        );
      } catch (error) {
        console.warn('Logout request failed, clearing local tokens anyway');
      }
    }

    this.clearTokens();
  }

  // Token management
  private storeTokens(tokens: AuthTokens): void {
    localStorage.setItem('accessToken', tokens.accessToken);
    localStorage.setItem('refreshToken', tokens.refreshToken);
    localStorage.setItem('tokenExpiry', tokens.expiresAt.toString());
  }

  private storeUserData(user: User): void {
    localStorage.setItem('userData', JSON.stringify(user));
  }

  getAccessToken(): string | null {
    return localStorage.getItem('accessToken');
  }

  getRefreshToken(): string | null {
    return localStorage.getItem('refreshToken');
  }

  getUserData(): User | null {
    const userData = localStorage.getItem('userData');
    return userData ? JSON.parse(userData) : null;
  }

  isTokenExpired(): boolean {
    const expiry = localStorage.getItem('tokenExpiry');
    if (!expiry) return true;
    
    // Add 30 seconds buffer to account for request time
    return (Date.now() / 1000) > (parseInt(expiry) - 30);
  }

  // Refresh access token
  async refreshAccessToken(): Promise<boolean> {
    // Prevent multiple simultaneous refresh requests
    if (this.tokenRefreshPromise) {
      return this.tokenRefreshPromise;
    }

    this.tokenRefreshPromise = this.performTokenRefresh();
    const result = await this.tokenRefreshPromise;
    this.tokenRefreshPromise = null;
    
    return result;
  }

  private async performTokenRefresh(): Promise<boolean> {
    const refreshToken = this.getRefreshToken();
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
      console.error('Token refresh failed:', error);
      this.clearTokens();
    }
    
    return false;
  }

  // Validate token
  async validateToken(token?: string): Promise<boolean> {
    const tokenToValidate = token || this.getAccessToken();
    if (!tokenToValidate) return false;

    try {
      const response = await axios.get(`${this.baseURL}/validate-token`, {
        params: { token: tokenToValidate }
      });
      return response.data.success;
    } catch (error) {
      return false;
    }
  }

  // Profile methods
  async getProfile(): Promise<User> {
    const response = await this.authenticatedRequest('GET', '/profile');
    return response.data.data;
  }

  async updateProfile(data: ProfileData): Promise<User> {
    const response = await this.authenticatedRequest('PUT', '/profile', data);
    
    // Update stored user data
    if (response.data.success) {
      this.storeUserData(response.data.data);
    }
    
    return response.data.data;
  }

  // Authenticated request helper
  private async authenticatedRequest(method: string, endpoint: string, data?: any): Promise<any> {
    let token = this.getAccessToken();
    
    // Check if token needs refresh
    if (token && this.isTokenExpired()) {
      const refreshed = await this.refreshAccessToken();
      if (!refreshed) {
        throw new Error('Authentication failed');
      }
      token = this.getAccessToken();
    }

    if (!token) {
      throw new Error('No valid token available');
    }

    const config = {
      method,
      url: `${this.baseURL}${endpoint}`,
      headers: { Authorization: `Bearer ${token}` },
      ...(data && { data })
    };

    try {
      return await axios(config);
    } catch (error: any) {
      if (error.response?.status === 401) {
        // Try to refresh token once more
        const refreshed = await this.refreshAccessToken();
        if (refreshed) {
          config.headers.Authorization = `Bearer ${this.getAccessToken()}`;
          return await axios(config);
        } else {
          this.clearTokens();
          throw new Error('Authentication failed');
        }
      }
      throw error;
    }
  }

  private clearTokens(): void {
    localStorage.removeItem('accessToken');
    localStorage.removeItem('refreshToken');
    localStorage.removeItem('tokenExpiry');
    localStorage.removeItem('userData');
  }

  // Check if user is authenticated
  isAuthenticated(): boolean {
    const token = this.getAccessToken();
    return token !== null && !this.isTokenExpired();
  }
}

export default new AuthService();
```

### 3. HTTP Client with Interceptors

Create an axios instance with automatic token management:

```typescript
// src/services/httpClient.ts
import axios, { AxiosRequestConfig, AxiosResponse } from 'axios';
import authService from './authService';

const httpClient = axios.create({
  baseURL: process.env.REACT_APP_API_URL || 'https://your-site.com/wp-json/wp-auth/v1',
  timeout: 10000,
});

// Request interceptor
httpClient.interceptors.request.use(
  async (config: AxiosRequestConfig) => {
    // Add token to requests
    const token = authService.getAccessToken();
    
    if (token) {
      // Check if token is expired and refresh if needed
      if (authService.isTokenExpired()) {
        try {
          await authService.refreshAccessToken();
          const newToken = authService.getAccessToken();
          if (newToken) {
            config.headers = config.headers || {};
            config.headers.Authorization = `Bearer ${newToken}`;
          }
        } catch (error) {
          // Refresh failed, redirect to login
          window.location.href = '/login';
          return Promise.reject('Token refresh failed');
        }
      } else {
        config.headers = config.headers || {};
        config.headers.Authorization = `Bearer ${token}`;
      }
    }
    
    return config;
  },
  (error) => Promise.reject(error)
);

// Response interceptor
httpClient.interceptors.response.use(
  (response: AxiosResponse) => response,
  async (error) => {
    const originalRequest = error.config;
    
    if (error.response?.status === 401 && !originalRequest._retry) {
      originalRequest._retry = true;
      
      try {
        const refreshed = await authService.refreshAccessToken();
        if (refreshed) {
          const token = authService.getAccessToken();
          originalRequest.headers.Authorization = `Bearer ${token}`;
          return httpClient(originalRequest);
        }
      } catch (refreshError) {
        // Refresh failed, redirect to login
        authService.logout();
        window.location.href = '/login';
        return Promise.reject(refreshError);
      }
    }
    
    return Promise.reject(error);
  }
);

export default httpClient;
```

### 4. Authentication Context

Create a React context for managing authentication state:

```typescript
// src/contexts/AuthContext.tsx
import React, { createContext, useContext, useEffect, useState, ReactNode } from 'react';
import authService from '../services/authService';

interface User {
  id: number;
  username: string;
  email: string;
  display_name: string;
  roles: string[];
}

interface AuthContextType {
  user: User | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  login: (username: string, password: string, remember?: boolean) => Promise<void>;
  register: (data: any) => Promise<void>;
  logout: () => Promise<void>;
  updateProfile: (data: any) => Promise<void>;
  refreshUser: () => Promise<void>;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

interface AuthProviderProps {
  children: ReactNode;
}

export const AuthProvider: React.FC<AuthProviderProps> = ({ children }) => {
  const [user, setUser] = useState<User | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    initializeAuth();
  }, []);

  const initializeAuth = async () => {
    setIsLoading(true);
    
    try {
      // Check if user is authenticated
      if (authService.isAuthenticated()) {
        const userData = authService.getUserData();
        if (userData) {
          setUser(userData);
        } else {
          // If no user data in localStorage, fetch from API
          await refreshUser();
        }
      }
    } catch (error) {
      console.error('Auth initialization failed:', error);
      await authService.logout();
    } finally {
      setIsLoading(false);
    }
  };

  const login = async (username: string, password: string, remember = false) => {
    try {
      const response = await authService.login(username, password, remember);
      if (response.success) {
        setUser(response.data.user);
      } else {
        throw new Error(response.message);
      }
    } catch (error) {
      throw error;
    }
  };

  const register = async (data: any) => {
    try {
      const response = await authService.register(data);
      if (!response.success) {
        throw new Error(response.message);
      }
    } catch (error) {
      throw error;
    }
  };

  const logout = async () => {
    try {
      await authService.logout();
      setUser(null);
    } catch (error) {
      console.error('Logout failed:', error);
      // Clear local state anyway
      setUser(null);
    }
  };

  const updateProfile = async (data: any) => {
    try {
      const updatedUser = await authService.updateProfile(data);
      setUser(updatedUser);
    } catch (error) {
      throw error;
    }
  };

  const refreshUser = async () => {
    try {
      const userData = await authService.getProfile();
      setUser(userData);
    } catch (error) {
      console.error('Failed to refresh user data:', error);
      throw error;
    }
  };

  const value: AuthContextType = {
    user,
    isAuthenticated: !!user && authService.isAuthenticated(),
    isLoading,
    login,
    register,
    logout,
    updateProfile,
    refreshUser
  };

  return (
    <AuthContext.Provider value={value}>
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = (): AuthContextType => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};
```

### 5. Protected Route Component

```typescript
// src/components/ProtectedRoute.tsx
import React from 'react';
import { Navigate, useLocation } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';

interface ProtectedRouteProps {
  children: React.ReactNode;
  requiredRole?: string;
}

const ProtectedRoute: React.FC<ProtectedRouteProps> = ({ 
  children, 
  requiredRole 
}) => {
  const { isAuthenticated, isLoading, user } = useAuth();
  const location = useLocation();

  if (isLoading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="animate-spin rounded-full h-32 w-32 border-b-2 border-gray-900"></div>
      </div>
    );
  }

  if (!isAuthenticated) {
    return <Navigate to="/login" state={{ from: location }} replace />;
  }

  // Check role-based access
  if (requiredRole && user && !user.roles.includes(requiredRole)) {
    return <Navigate to="/unauthorized" replace />;
  }

  return <>{children}</>;
};

export default ProtectedRoute;
```

### 6. Login Component

```typescript
// src/components/Login.tsx
import React, { useState } from 'react';
import { useAuth } from '../contexts/AuthContext';
import { useNavigate, useLocation, Link } from 'react-router-dom';

const Login: React.FC = () => {
  const [formData, setFormData] = useState({
    username: '',
    password: '',
    remember: false
  });
  const [error, setError] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  
  const { login } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();

  const from = location.state?.from?.pathname || '/dashboard';

  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const { name, value, type, checked } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: type === 'checkbox' ? checked : value
    }));
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsLoading(true);
    setError('');

    try {
      await login(formData.username, formData.password, formData.remember);
      navigate(from, { replace: true });
    } catch (error: any) {
      setError(error.message || 'Login failed');
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
      <div className="max-w-md w-full space-y-8">
        <div>
          <h2 className="mt-6 text-center text-3xl font-extrabold text-gray-900">
            Sign in to your account
          </h2>
        </div>
        
        <form className="mt-8 space-y-6" onSubmit={handleSubmit}>
          {error && (
            <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
              {error}
            </div>
          )}
          
          <div>
            <label htmlFor="username" className="block text-sm font-medium text-gray-700">
              Username or Email
            </label>
            <input
              id="username"
              name="username"
              type="text"
              required
              value={formData.username}
              onChange={handleChange}
              className="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
              placeholder="Enter your username or email"
            />
          </div>
          
          <div>
            <label htmlFor="password" className="block text-sm font-medium text-gray-700">
              Password
            </label>
            <input
              id="password"
              name="password"
              type="password"
              required
              value={formData.password}
              onChange={handleChange}
              className="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
              placeholder="Enter your password"
            />
          </div>
          
          <div className="flex items-center justify-between">
            <div className="flex items-center">
              <input
                id="remember"
                name="remember"
                type="checkbox"
                checked={formData.remember}
                onChange={handleChange}
                className="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
              />
              <label htmlFor="remember" className="ml-2 block text-sm text-gray-900">
                Remember me
              </label>
            </div>
            
            <div className="text-sm">
              <Link to="/forgot-password" className="font-medium text-indigo-600 hover:text-indigo-500">
                Forgot your password?
              </Link>
            </div>
          </div>
          
          <button
            type="submit"
            disabled={isLoading}
            className="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {isLoading ? 'Signing in...' : 'Sign in'}
          </button>
          
          <div className="text-center">
            <span className="text-sm text-gray-600">
              Don't have an account?{' '}
              <Link to="/register" className="font-medium text-indigo-600 hover:text-indigo-500">
                Sign up
              </Link>
            </span>
          </div>
        </form>
      </div>
    </div>
  );
};

export default Login;
```

### 7. Registration Component

```typescript
// src/components/Register.tsx
import React, { useState } from 'react';
import { useAuth } from '../contexts/AuthContext';
import { useNavigate, Link } from 'react-router-dom';

const Register: React.FC = () => {
  const [formData, setFormData] = useState({
    username: '',
    email: '',
    password: '',
    confirmPassword: '',
    first_name: '',
    last_name: ''
  });
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [isLoading, setIsLoading] = useState(false);
  const [successMessage, setSuccessMessage] = useState('');
  
  const { register } = useAuth();
  const navigate = useNavigate();

  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
    
    // Clear error when user starts typing
    if (errors[name]) {
      setErrors(prev => ({ ...prev, [name]: '' }));
    }
  };

  const validateForm = (): boolean => {
    const newErrors: Record<string, string> = {};

    if (!formData.username.trim()) {
      newErrors.username = 'Username is required';
    } else if (formData.username.length < 3) {
      newErrors.username = 'Username must be at least 3 characters';
    }

    if (!formData.email.trim()) {
      newErrors.email = 'Email is required';
    } else if (!/\S+@\S+\.\S+/.test(formData.email)) {
      newErrors.email = 'Email is invalid';
    }

    if (!formData.password) {
      newErrors.password = 'Password is required';
    } else if (formData.password.length < 6) {
      newErrors.password = 'Password must be at least 6 characters';
    }

    if (formData.password !== formData.confirmPassword) {
      newErrors.confirmPassword = 'Passwords do not match';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!validateForm()) return;

    setIsLoading(true);
    setErrors({});

    try {
      await register({
        username: formData.username,
        email: formData.email,
        password: formData.password,
        first_name: formData.first_name,
        last_name: formData.last_name
      });
      
      setSuccessMessage('Registration successful! Please check your email for verification.');
      
      // Redirect to login after a delay
      setTimeout(() => {
        navigate('/login');
      }, 3000);
      
    } catch (error: any) {
      setErrors({ general: error.message || 'Registration failed' });
    } finally {
      setIsLoading(false);
    }
  };

  if (successMessage) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50">
        <div className="max-w-md w-full bg-white p-8 rounded-lg shadow-md">
          <div className="text-center">
            <div className="text-green-600 text-4xl mb-4">✓</div>
            <h2 className="text-xl font-semibold text-gray-900 mb-2">Registration Successful!</h2>
            <p className="text-gray-600">{successMessage}</p>
            <Link 
              to="/login" 
              className="mt-4 inline-block text-indigo-600 hover:text-indigo-500"
            >
              Go to Login
            </Link>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
      <div className="max-w-md w-full space-y-8">
        <div>
          <h2 className="mt-6 text-center text-3xl font-extrabold text-gray-900">
            Create your account
          </h2>
        </div>
        
        <form className="mt-8 space-y-6" onSubmit={handleSubmit}>
          {errors.general && (
            <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
              {errors.general}
            </div>
          )}
          
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
              <label htmlFor="first_name" className="block text-sm font-medium text-gray-700">
                First Name
              </label>
              <input
                id="first_name"
                name="first_name"
                type="text"
                value={formData.first_name}
                onChange={handleChange}
                className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              />
            </div>
            
            <div>
              <label htmlFor="last_name" className="block text-sm font-medium text-gray-700">
                Last Name
              </label>
              <input
                id="last_name"
                name="last_name"
                type="text"
                value={formData.last_name}
                onChange={handleChange}
                className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              />
            </div>
          </div>
          
          <div>
            <label htmlFor="username" className="block text-sm font-medium text-gray-700">
              Username *
            </label>
            <input
              id="username"
              name="username"
              type="text"
              required
              value={formData.username}
              onChange={handleChange}
              className={`mt-1 block w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm ${
                errors.username ? 'border-red-300' : 'border-gray-300'
              }`}
            />
            {errors.username && <p className="mt-1 text-sm text-red-600">{errors.username}</p>}
          </div>
          
          <div>
            <label htmlFor="email" className="block text-sm font-medium text-gray-700">
              Email *
            </label>
            <input
              id="email"
              name="email"
              type="email"
              required
              value={formData.email}
              onChange={handleChange}
              className={`mt-1 block w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm ${
                errors.email ? 'border-red-300' : 'border-gray-300'
              }`}
            />
            {errors.email && <p className="mt-1 text-sm text-red-600">{errors.email}</p>}
          </div>
          
          <div>
            <label htmlFor="password" className="block text-sm font-medium text-gray-700">
              Password *
            </label>
            <input
              id="password"
              name="password"
              type="password"
              required
              value={formData.password}
              onChange={handleChange}
              className={`mt-1 block w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm ${
                errors.password ? 'border-red-300' : 'border-gray-300'
              }`}
            />
            {errors.password && <p className="mt-1 text-sm text-red-600">{errors.password}</p>}
          </div>
          
          <div>
            <label htmlFor="confirmPassword" className="block text-sm font-medium text-gray-700">
              Confirm Password *
            </label>
            <input
              id="confirmPassword"
              name="confirmPassword"
              type="password"
              required
              value={formData.confirmPassword}
              onChange={handleChange}
              className={`mt-1 block w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm ${
                errors.confirmPassword ? 'border-red-300' : 'border-gray-300'
              }`}
            />
            {errors.confirmPassword && <p className="mt-1 text-sm text-red-600">{errors.confirmPassword}</p>}
          </div>
          
          <button
            type="submit"
            disabled={isLoading}
            className="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {isLoading ? 'Creating account...' : 'Create account'}
          </button>
          
          <div className="text-center">
            <span className="text-sm text-gray-600">
              Already have an account?{' '}
              <Link to="/login" className="font-medium text-indigo-600 hover:text-indigo-500">
                Sign in
              </Link>
            </span>
          </div>
        </form>
      </div>
    </div>
  );
};

export default Register;
```

### 8. Profile Management Component

```typescript
// src/components/Profile.tsx
import React, { useState, useEffect } from 'react';
import { useAuth } from '../contexts/AuthContext';

const Profile: React.FC = () => {
  const { user, updateProfile } = useAuth();
  const [formData, setFormData] = useState({
    first_name: '',
    last_name: '',
    email: '',
    description: ''
  });
  const [isLoading, setIsLoading] = useState(false);
  const [message, setMessage] = useState({ type: '', text: '' });

  useEffect(() => {
    if (user) {
      setFormData({
        first_name: user.first_name || '',
        last_name: user.last_name || '',
        email: user.email || '',
        description: user.description || ''
      });
    }
  }, [user]);

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsLoading(true);
    setMessage({ type: '', text: '' });

    try {
      await updateProfile(formData);
      setMessage({ type: 'success', text: 'Profile updated successfully!' });
    } catch (error: any) {
      setMessage({ type: 'error', text: error.message || 'Failed to update profile' });
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="max-w-2xl mx-auto p-6">
      <h1 className="text-2xl font-bold text-gray-900 mb-6">Profile Settings</h1>
      
      {message.text && (
        <div className={`mb-4 p-4 rounded-md ${
          message.type === 'success' 
            ? 'bg-green-100 border border-green-400 text-green-700'
            : 'bg-red-100 border border-red-400 text-red-700'
        }`}>
          {message.text}
        </div>
      )}
      
      <form onSubmit={handleSubmit} className="space-y-6">
        <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
          <div>
            <label htmlFor="first_name" className="block text-sm font-medium text-gray-700">
              First Name
            </label>
            <input
              type="text"
              id="first_name"
              name="first_name"
              value={formData.first_name}
              onChange={handleChange}
              className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
            />
          </div>
          
          <div>
            <label htmlFor="last_name" className="block text-sm font-medium text-gray-700">
              Last Name
            </label>
            <input
              type="text"
              id="last_name"
              name="last_name"
              value={formData.last_name}
              onChange={handleChange}
              className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
            />
          </div>
        </div>
        
        <div>
          <label htmlFor="email" className="block text-sm font-medium text-gray-700">
            Email
          </label>
          <input
            type="email"
            id="email"
            name="email"
            value={formData.email}
            onChange={handleChange}
            className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
          />
        </div>
        
        <div>
          <label htmlFor="description" className="block text-sm font-medium text-gray-700">
            Bio
          </label>
          <textarea
            id="description"
            name="description"
            rows={4}
            value={formData.description}
            onChange={handleChange}
            className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
            placeholder="Tell us about yourself..."
          />
        </div>
        
        <div className="flex justify-end">
          <button
            type="submit"
            disabled={isLoading}
            className="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {isLoading ? 'Updating...' : 'Update Profile'}
          </button>
        </div>
      </form>
    </div>
  );
};

export default Profile;
```

### 9. App Setup with Routing

```typescript
// src/App.tsx
import React from 'react';
import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import { AuthProvider } from './contexts/AuthContext';
import ProtectedRoute from './components/ProtectedRoute';
import Login from './components/Login';
import Register from './components/Register';
import Profile from './components/Profile';
import Dashboard from './components/Dashboard';
import Layout from './components/Layout';

function App() {
  return (
    <Router>
      <AuthProvider>
        <div className="App">
          <Routes>
            <Route path="/login" element={<Login />} />
            <Route path="/register" element={<Register />} />
            
            <Route path="/" element={
              <ProtectedRoute>
                <Layout />
              </ProtectedRoute>
            }>
              <Route index element={<Dashboard />} />
              <Route path="dashboard" element={<Dashboard />} />
              <Route path="profile" element={<Profile />} />
            </Route>
            
            <Route path="/admin" element={
              <ProtectedRoute requiredRole="administrator">
                <div>Admin Panel</div>
              </ProtectedRoute>
            } />
          </Routes>
        </div>
      </AuthProvider>
    </Router>
  );
}

export default App;
```

## Security Best Practices

### 1. Token Storage Security

```typescript
// Secure token storage for different environments
class SecureStorage {
  private static isProduction = process.env.NODE_ENV === 'production';

  static setItem(key: string, value: string): void {
    if (this.isProduction) {
      // In production, consider using secure storage methods
      // For web apps, localStorage is acceptable for JWTs
      localStorage.setItem(key, value);
    } else {
      localStorage.setItem(key, value);
    }
  }

  static getItem(key: string): string | null {
    return localStorage.getItem(key);
  }

  static removeItem(key: string): void {
    localStorage.removeItem(key);
  }

  // Clear all auth-related data
  static clearAuthData(): void {
    const authKeys = ['accessToken', 'refreshToken', 'tokenExpiry', 'userData'];
    authKeys.forEach(key => this.removeItem(key));
  }
}
```

### 2. Request Security Headers

```typescript
// Add security headers to requests
const secureHttpClient = axios.create({
  baseURL: process.env.REACT_APP_API_URL,
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json',
    'X-Requested-With': 'XMLHttpRequest'
  }
});

// Add CSRF protection if needed
secureHttpClient.interceptors.request.use((config) => {
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  if (csrfToken) {
    config.headers['X-CSRF-TOKEN'] = csrfToken;
  }
  return config;
});
```

### 3. Input Validation and Sanitization

```typescript
// Input validation utilities
export const validateInput = {
  email: (email: string): boolean => {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
  },

  username: (username: string): boolean => {
    // Username: 3-20 characters, alphanumeric and underscores only
    const usernameRegex = /^[a-zA-Z0-9_]{3,20}$/;
    return usernameRegex.test(username);
  },

  password: (password: string): { isValid: boolean; errors: string[] } => {
    const errors: string[] = [];
    
    if (password.length < 8) {
      errors.push('Password must be at least 8 characters long');
    }
    if (!/[A-Z]/.test(password)) {
      errors.push('Password must contain at least one uppercase letter');
    }
    if (!/[a-z]/.test(password)) {
      errors.push('Password must contain at least one lowercase letter');
    }
    if (!/\d/.test(password)) {
      errors.push('Password must contain at least one number');
    }
    if (!/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)) {
      errors.push('Password must contain at least one special character');
    }

    return {
      isValid: errors.length === 0,
      errors
    };
  },

  sanitizeString: (input: string): string => {
    return input.trim().replace(/[<>]/g, '');
  }
};
```

### 4. Error Handling and Logging

```typescript
// Error handling service
class ErrorHandler {
  static logError(error: any, context?: string): void {
    if (process.env.NODE_ENV === 'development') {
      console.error(`Error ${context ? `in ${context}` : ''}:`, error);
    }

    // In production, send to logging service
    if (process.env.NODE_ENV === 'production') {
      this.sendToLoggingService(error, context);
    }
  }

  private static sendToLoggingService(error: any, context?: string): void {
    // Implement your logging service here (e.g., Sentry, LogRocket)
    // Example:
    // Sentry.captureException(error, { extra: { context } });
  }

  static handleApiError(error: any): string {
    // Handle different types of API errors
    if (error.response) {
      const { status, data } = error.response;
      
      switch (status) {
        case 400:
          return data.message || 'Invalid request data';
        case 401:
          return 'Authentication failed. Please login again.';
        case 403:
          return 'You do not have permission to perform this action.';
        case 404:
          return 'The requested resource was not found.';
        case 429:
          return 'Too many requests. Please try again later.';
        case 500:
          return 'Server error. Please try again later.';
        default:
          return data.message || 'An unexpected error occurred';
      }
    } else if (error.request) {
      return 'Network error. Please check your connection.';
    } else {
      return error.message || 'An unexpected error occurred';
    }
  }
}
```

### 5. Rate Limiting on Frontend

```typescript
// Rate limiting utility
class RateLimiter {
  private static requests: Map<string, number[]> = new Map();

  static canMakeRequest(endpoint: string, maxRequests = 5, windowMs = 60000): boolean {
    const now = Date.now();
    const windowStart = now - windowMs;
    
    if (!this.requests.has(endpoint)) {
      this.requests.set(endpoint, []);
    }
    
    const endpointRequests = this.requests.get(endpoint)!;
    
    // Remove old requests outside the window
    const validRequests = endpointRequests.filter(time => time > windowStart);
    
    if (validRequests.length >= maxRequests) {
      return false;
    }
    
    // Add current request
    validRequests.push(now);
    this.requests.set(endpoint, validRequests);
    
    return true;
  }
}

// Usage in API calls
const makeApiCall = async (endpoint: string, data: any) => {
  if (!RateLimiter.canMakeRequest(endpoint)) {
    throw new Error('Rate limit exceeded. Please try again later.');
  }
  
  return await httpClient.post(endpoint, data);
};
```

### 6. Content Security Policy

```html
<!-- Add to public/index.html -->
<meta http-equiv="Content-Security-Policy" content="
  default-src 'self';
  script-src 'self' 'unsafe-inline';
  style-src 'self' 'unsafe-inline' https://fonts.googleapis.com;
  font-src 'self' https://fonts.gstatic.com;
  img-src 'self' data: https:;
  connect-src 'self' https://your-api-domain.com;
">
```

## Advanced Features

### 1. Token Refresh Background Service

```typescript
// Background token refresh service
class TokenRefreshService {
  private static refreshInterval: NodeJS.Timeout | null = null;
  private static readonly REFRESH_BUFFER = 5 * 60 * 1000; // 5 minutes

  static start(): void {
    // Check every minute
    this.refreshInterval = setInterval(() => {
      this.checkAndRefreshToken();
    }, 60 * 1000);
  }

  static stop(): void {
    if (this.refreshInterval) {
      clearInterval(this.refreshInterval);
      this.refreshInterval = null;
    }
  }

  private static async checkAndRefreshToken(): Promise<void> {
    const expiry = localStorage.getItem('tokenExpiry');
    if (!expiry) return;

    const expiryTime = parseInt(expiry) * 1000;
    const now = Date.now();
    const timeUntilExpiry = expiryTime - now;

    // Refresh if token expires within the buffer time
    if (timeUntilExpiry <= this.REFRESH_BUFFER && timeUntilExpiry > 0) {
      try {
        await authService.refreshAccessToken();
      } catch (error) {
        console.error('Background token refresh failed:', error);
        // Optionally redirect to login
      }
    }
  }
}

// Start the service when app initializes
TokenRefreshService.start();

// Stop when app unmounts
window.addEventListener('beforeunload', () => {
  TokenRefreshService.stop();
});
```

### 2. Multiple Tab Synchronization

```typescript
// Sync authentication state across tabs
class TabSyncService {
  private static readonly STORAGE_KEY = 'auth_state_sync';

  static startSync(): void {
    window.addEventListener('storage', this.handleStorageChange);
    window.addEventListener('focus', this.syncOnFocus);
  }

  static stopSync(): void {
    window.removeEventListener('storage', this.handleStorageChange);
    window.removeEventListener('focus', this.syncOnFocus);
  }

  private static handleStorageChange = (e: StorageEvent) => {
    if (e.key === 'accessToken') {
      if (e.newValue === null) {
        // Token was removed in another tab - logout
        window.location.href = '/login';
      } else if (e.oldValue !== e.newValue) {
        // Token was updated in another tab - refresh user data
        window.location.reload();
      }
    }
  };

  private static syncOnFocus = () => {
    // Check if token is still valid when tab regains focus
    if (!authService.isAuthenticated()) {
      window.location.href = '/login';
    }
  };
}
```

### 3. Biometric Authentication (WebAuthn)

```typescript
// WebAuthn integration for enhanced security
class BiometricAuth {
  static async isSupported(): Promise<boolean> {
    return !!(navigator.credentials && window.PublicKeyCredential);
  }

  static async registerBiometric(userId: string): Promise<boolean> {
    if (!await this.isSupported()) {
      throw new Error('Biometric authentication not supported');
    }

    try {
      const credential = await navigator.credentials.create({
        publicKey: {
          challenge: new Uint8Array(32),
          rp: { name: "Your App" },
          user: {
            id: new TextEncoder().encode(userId),
            name: userId,
            displayName: userId,
          },
          pubKeyCredParams: [{ alg: -7, type: "public-key" }],
          authenticatorSelection: {
            authenticatorAttachment: "platform",
            userVerification: "required"
          }
        }
      });

      // Send credential to server for storage
      await httpClient.post('/save-biometric-credential', {
        credentialId: credential?.id,
        publicKey: credential
      });

      return true;
    } catch (error) {
      console.error('Biometric registration failed:', error);
      return false;
    }
  }

  static async authenticateWithBiometric(): Promise<boolean> {
    try {
      const assertion = await navigator.credentials.get({
        publicKey: {
          challenge: new Uint8Array(32),
          userVerification: "required"
        }
      });

      // Verify with server
      const response = await httpClient.post('/verify-biometric', {
        credentialId: assertion?.id,
        assertion
      });

      return response.data.success;
    } catch (error) {
      console.error('Biometric authentication failed:', error);
      return false;
    }
  }
}
```

## Troubleshooting

### Common Issues and Solutions

#### 1. Token Expiry Issues
```typescript
// Debug token expiry
const debugTokenExpiry = () => {
  const expiry = localStorage.getItem('tokenExpiry');
  if (expiry) {
    const expiryDate = new Date(parseInt(expiry) * 1000);
    const now = new Date();
    console.log('Token expires at:', expiryDate);
    console.log('Current time:', now);
    console.log('Time until expiry:', (expiryDate.getTime() - now.getTime()) / 1000, 'seconds');
  }
};
```

#### 2. CORS Issues
```typescript
// Handle CORS in development
if (process.env.NODE_ENV === 'development') {
  // Add proxy to package.json or use a proxy middleware
  axios.defaults.withCredentials = true;
}
```

#### 3. Network Error Handling
```typescript
// Robust network error handling
const makeResilientRequest = async (requestFn: () => Promise<any>, retries = 3): Promise<any> => {
  for (let i = 0; i < retries; i++) {
    try {
      return await requestFn();
    } catch (error: any) {
      if (i === retries - 1) throw error;
      
      // Retry on network errors, not on 4xx/5xx
      if (!error.response) {
        await new Promise(resolve => setTimeout(resolve, 1000 * (i + 1)));
        continue;
      }
      
      throw error;
    }
  }
};
```

#### 4. State Management Debugging
```typescript
// Auth state debugger
const AuthDebugger = {
  logAuthState: () => {
    console.log('Auth Debug Info:', {
      hasToken: !!authService.getAccessToken(),
      hasRefreshToken: !!authService.getRefreshToken(),
      isTokenExpired: authService.isTokenExpired(),
      userData: authService.getUserData(),
      tokenExpiry: localStorage.getItem('tokenExpiry')
    });
  }
};

// Use in development
if (process.env.NODE_ENV === 'development') {
  (window as any).authDebugger = AuthDebugger;
}
```

## Conclusion

This comprehensive guide provides a robust foundation for implementing JWT authentication with the WP Authenticator plugin in React applications. The implementation includes:

- **Complete authentication flow** with login, registration, and logout
- **Automatic token refresh** and management
- **Protected routes** and role-based access control
- **Security best practices** and error handling
- **Advanced features** like tab synchronization and biometric auth

Remember to:
- Always validate tokens on the server side
- Implement proper error handling and user feedback
- Use HTTPS in production
- Monitor and log authentication events
- Keep dependencies updated for security patches

This implementation provides a secure, scalable foundation that can be adapted to your specific requirements while maintaining security best practices.