# JWT Security Best Practices

⚠️ **CRITICAL SECURITY WARNING** ⚠️

This plugin implements a custom JWT solution for demonstration and educational purposes. For production applications, **always use well-maintained, security-audited libraries**.

## Recommended Production Libraries

### PHP JWT Libraries
- **firebase/php-jwt** - Most popular PHP JWT library
  ```bash
  composer require firebase/jwt
  ```
- **lcobucci/jwt** - Feature-rich JWT library with validation
  ```bash
  composer require lcobucci/jwt
  ```

## Security Requirements

### 1. HTTPS Only
- **NEVER** use JWT over HTTP in production
- JWT tokens contain sensitive information
- Tokens can be intercepted without HTTPS
- This plugin includes an automatic HTTPS warning

### 2. Secure Secret Keys
- Use cryptographically secure secret keys (64+ characters)
- Store secrets in environment variables, not in code
- Rotate secrets regularly
- Use different secrets for different environments

### 3. Token Expiration
- Keep access token lifetime short (15-60 minutes)
- Use refresh tokens for longer sessions
- Implement proper token refresh logic
- Set appropriate expiration times

### 4. Token Validation
- Always validate token signature
- Check expiration timestamps
- Validate issuer (iss) claims
- Validate audience (aud) claims
- Verify user still exists and is active

### 5. Token Revocation
- Implement token blacklisting for logout
- Store blacklisted tokens until expiration
- Handle revoked tokens gracefully
- Clear tokens on password changes

## Implementation Checklist

### Server-Side Security
- [ ] Use HTTPS in production
- [ ] Implement proper CORS policies
- [ ] Use secure secret key storage
- [ ] Validate all JWT claims
- [ ] Implement token blacklisting
- [ ] Log security events
- [ ] Rate limit authentication endpoints
- [ ] Implement proper error handling

### Client-Side Security
- [ ] Store tokens securely (not in localStorage for sensitive apps)
- [ ] Implement automatic token refresh
- [ ] Clear tokens on logout
- [ ] Handle token expiration gracefully
- [ ] Use secure HTTP headers
- [ ] Implement CSRF protection

## Common Vulnerabilities

### 1. None Algorithm Attack
- **Risk**: Accepting unsigned tokens
- **Prevention**: Always verify algorithms, reject "none"

### 2. Key Confusion
- **Risk**: Using public key as HMAC secret
- **Prevention**: Use separate keys for different algorithms

### 3. Weak Signatures
- **Risk**: Using weak signing algorithms
- **Prevention**: Use strong algorithms (HS256, RS256)

### 4. Token Sidejacking
- **Risk**: Token theft via XSS/network sniffing
- **Prevention**: HTTPS, secure storage, short expiration

### 5. Insufficient Validation
- **Risk**: Accepting invalid/expired tokens
- **Prevention**: Comprehensive validation of all claims

## WordPress-Specific Considerations

### User Sessions
- JWT tokens are stateless
- WordPress sessions are stateful
- Handle session conflicts carefully
- Consider hybrid approaches

### Plugin Conflicts
- Avoid conflicts with other auth plugins
- Test with caching plugins
- Handle multisite installations
- Consider performance impact

### Security Updates
- Monitor WordPress security advisories
- Update dependencies regularly
- Test security patches
- Maintain security logs

## Testing Security

### Automated Testing
```bash
# Test with invalid tokens
curl -H "Authorization: Bearer invalid_token" /api/endpoint

# Test with expired tokens
curl -H "Authorization: Bearer expired_token" /api/endpoint

# Test without authorization
curl /api/protected-endpoint
```

### Manual Security Review
- Code review for security issues
- Penetration testing
- Security audit of dependencies
- Monitor for vulnerabilities

## Production Deployment

### Environment Configuration
```php
// wp-config.php
define('WP_AUTH_JWT_SECRET', getenv('JWT_SECRET'));
define('WP_AUTH_JWT_EXPIRY', 3600); // 1 hour
define('WP_AUTH_REQUIRE_HTTPS', true);
```

### Server Configuration
```apache
# .htaccess - Force HTTPS
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### Monitoring
- Log authentication attempts
- Monitor for suspicious activity
- Set up security alerts
- Regular security audits

## Emergency Response

### Token Compromise
1. Immediately rotate JWT secret
2. Invalidate all existing tokens
3. Force users to re-authenticate
4. Investigate breach scope
5. Update security measures

### Security Incident
1. Document the incident
2. Assess impact and scope
3. Implement immediate fixes
4. Notify affected users
5. Review and improve security

## Additional Resources

- [OWASP JWT Security Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/JSON_Web_Token_for_Java_Cheat_Sheet.html)
- [JWT.io - JWT Debugger](https://jwt.io/)
- [RFC 7519 - JSON Web Token](https://tools.ietf.org/html/rfc7519)
- [WordPress Security Handbook](https://developer.wordpress.org/plugins/security/)

---

**Remember**: Security is not a feature you add at the end. It must be built into every layer of your application from the beginning.
