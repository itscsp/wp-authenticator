# WP Authenticator Documentation

Welcome to the comprehensive documentation for WP Authenticator plugin.

## 📚 Documentation Index

### 🚀 Getting Started
- **[README](../README.md)** - Plugin overview and quick start
- **[API Documentation](../API_Docs.md)** - Complete API reference with examples

### 🔐 Registration System
- **[3-Step Registration](./3-step-registration.md)** - Detailed guide to the new registration process
- **[Token Management](./token-management.md)** - Comprehensive guide to JWT and refresh token handling
- **[Registration Comparison](./registration-comparison.md)** - Legacy vs 3-step process comparison

### 🏗️ Development
- **[Endpoint Organization](./endpoint-organization.md)** - Codebase structure and organization
- **[Reorganization Summary](./reorganization-summary.md)** - Changes made to improve code structure

### 📋 Project Management
- **[Changelog](../CHANGELOG.md)** - Version history and feature updates
- **[JWT Implementation](./jwt_implemention.md)** - Technical details of JWT usage
- **[React Native Integration](./jwt_plugin_react_nativev.md)** - Mobile app integration guide

## 🔍 Quick Reference

### Registration Endpoints
| Endpoint | Purpose | Type |
|----------|---------|------|
| `/register` | Legacy single-step registration | Legacy |
| `/register/start` | Start 3-step process | New |
| `/register/verify-otp` | Verify email with OTP | New |
| `/register/complete` | Complete registration | New |
| `/register/status` | Check registration status | New |

### Authentication Endpoints
| Endpoint | Purpose |
|----------|---------|
| `/login` | User authentication |
| `/logout` | End user session |
| `/validate-token` | Verify JWT token |

### OTP Endpoints
| Endpoint | Purpose |
|----------|---------|
| `/verify-otp` | Verify OTP code |
| `/resend-otp` | Request new OTP |
| `/otp-status` | Check OTP status |

## 🛠️ Testing Resources

- **[Test Script](../test-3-step-registration.php)** - Automated testing for registration process
- **Postman Collection** - Available in API documentation
- **cURL Examples** - Command-line testing examples

## 🔧 Configuration

### JWT Settings
Configure in WordPress admin: Settings > WP Authenticator

### OTP Settings
- **Expiration**: 5 minutes
- **Length**: 6 digits
- **Max Attempts**: 3
- **Rate Limiting**: Enabled

### Session Management
- **Duration**: 30 minutes
- **Storage**: WordPress transients
- **Cleanup**: Automatic

## 🚀 Latest Features (v1.1.0)

### ✨ New in This Version
- 3-step registration process with email verification
- Organized endpoint structure in subfolders
- Enhanced session management
- Comprehensive documentation updates
- Test scripts and examples

### 🔄 Migration Guide
- **Existing Integrations**: No changes required - fully backward compatible
- **New Projects**: Use 3-step registration for enhanced security
- **Gradual Migration**: Move to new endpoints at your own pace

## 🆘 Support

### Common Issues
1. **OTP Not Received**: Check email configuration and spam folders
2. **Session Expired**: Registration sessions last 30 minutes
3. **Token Issues**: Verify JWT configuration in admin settings

### Getting Help
- Check documentation first
- Review API examples
- Test with provided scripts
- Create GitHub issue if needed

## 🔮 Roadmap

### Upcoming Features
- Frontend components for registration forms
- Advanced password validation
- Multi-language support
- Registration analytics

### Long-term Goals
- OAuth integration
- Social login support
- Enhanced security options
- Mobile SDK development

---

**Version**: 1.1.0  
**Last Updated**: August 23, 2025  
**Author**: Chethan S Poojary
