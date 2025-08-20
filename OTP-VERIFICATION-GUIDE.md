# 🔐 OTP Email Verification for New User Registration

## Overview

The WP Authenticator plugin now includes **OTP (One-Time Password) email verification** for new user registrations. This adds an extra security layer by requiring users to verify their email address before their account is fully created.

---

## 🚀 How It Works

### **Before (Old Flow):**
1. User submits registration form
2. ✅ Account created immediately 
3. ✅ JWT token returned (if auto-login enabled)

### **After (New OTP Flow):**
1. User submits registration form
2. 🔄 Account NOT created yet
3. 📧 OTP sent to email address
4. 🔐 User enters OTP to verify email
5. ✅ Account created after verification
6. ✅ JWT token returned (if auto-login enabled)

---

## 📡 New API Endpoints

### **1. Registration (Modified)**
```
POST /wp-json/wp-auth/v1/register
```

**Request:**
```json
{
    "username": "johndoe",
    "email": "john@example.com",
    "password": "securepassword123",
    "first_name": "John",
    "last_name": "Doe"
}
```

**Response (New Format):**
```json
{
    "success": true,
    "message": "Registration initiated. Please check your email for the OTP verification code.",
    "data": {
        "email": "john@example.com",
        "otp_expires": 1755786614,
        "requires_verification": true,
        "next_step": "Please call /wp-json/wp-auth/v1/verify-otp with your email and OTP code"
    }
}
```

### **2. Verify OTP (New)**
```
POST /wp-json/wp-auth/v1/verify-otp
```

**Request:**
```json
{
    "email": "john@example.com",
    "otp": "123456"
}
```

**Success Response:**
```json
{
    "success": true,
    "message": "Email verified and registration completed successfully!",
    "data": {
        "user_id": 5,
        "username": "johndoe",
        "email": "john@example.com",
        "display_name": "John Doe",
        "email_verified": true,
        "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "expires": 1755790214
    }
}
```

**Error Response:**
```json
{
    "success": false,
    "message": "Invalid OTP. 2 attempts remaining."
}
```

### **3. Resend OTP (New)**
```
POST /wp-json/wp-auth/v1/resend-otp
```

**Request:**
```json
{
    "email": "john@example.com"
}
```

**Response:**
```json
{
    "success": true,
    "message": "OTP has been resent to your email address.",
    "data": {
        "email": "john@example.com",
        "otp_expires": 1755786614
    }
}
```

### **4. OTP Status (New)**
```
GET /wp-json/wp-auth/v1/otp-status?email=john@example.com
```

**Response:**
```json
{
    "success": true,
    "data": {
        "has_pending_otp": true,
        "expires": 1755786614,
        "attempts_remaining": 3,
        "email": "john@example.com"
    }
}
```

---

## 📧 Email Template

Users receive a professionally formatted HTML email:

```html
Subject: Email Verification - Your OTP Code

Email Verification Required
Thank you for registering! Please use the following OTP to complete your registration:

    [123456]

Important:
• This OTP is valid for 10 minutes only
• You have 3 attempts to enter the correct OTP  
• Do not share this code with anyone

If you didn't request this registration, please ignore this email.
```

---

## ⚙️ Configuration

### **OTP Settings:**
- **OTP Length:** 6 digits
- **Expiry Time:** 10 minutes  
- **Max Attempts:** 3 attempts per OTP
- **Auto-cleanup:** Expired OTPs cleaned hourly

### **WordPress Settings:**
- **Auto-login after registration:** Configurable (default: yes)
- **Email verification required:** Always enabled for new registrations
- **Email notifications:** Welcome email sent after verification

---

## 🔒 Security Features

### **OTP Security:**
- ✅ **6-digit random codes** (100,000 - 999,999)
- ✅ **Time-based expiration** (10 minutes)
- ✅ **Attempt limiting** (3 tries maximum)
- ✅ **Automatic cleanup** of expired OTPs
- ✅ **Secure storage** using WordPress transients + options

### **Data Protection:**
- ✅ **Email validation** before OTP generation
- ✅ **Username/email availability** checked before OTP
- ✅ **Duplicate prevention** via database checks
- ✅ **HTTPS recommended** for API calls

### **Anti-Abuse:**
- ✅ **Rate limiting** via existing security handler
- ✅ **IP blocking** for repeated failures  
- ✅ **OTP cleanup** prevents database bloat
- ✅ **Attempt tracking** per email address

---

## 💻 Frontend Integration Examples

### **React/JavaScript Example:**
```javascript
// Step 1: Register user
const registerUser = async (userData) => {
    const response = await fetch('/wp-json/wp-auth/v1/register', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(userData)
    });
    
    const result = await response.json();
    
    if (result.success && result.data.requires_verification) {
        // Show OTP input form
        showOTPVerification(result.data.email);
    }
};

// Step 2: Verify OTP
const verifyOTP = async (email, otp) => {
    const response = await fetch('/wp-json/wp-auth/v1/verify-otp', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, otp })
    });
    
    const result = await response.json();
    
    if (result.success) {
        // Registration complete - store JWT token
        localStorage.setItem('jwt_token', result.data.token);
        redirectToProfile();
    } else {
        // Show error message
        showError(result.message);
    }
};

// Step 3: Resend OTP if needed
const resendOTP = async (email) => {
    const response = await fetch('/wp-json/wp-auth/v1/resend-otp', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email })
    });
    
    const result = await response.json();
    showMessage(result.message);
};
```

### **PHP Example:**
```php
// Step 1: Register user
$registration_data = array(
    'username' => 'johndoe',
    'email' => 'john@example.com',
    'password' => 'securepassword123',
    'first_name' => 'John',
    'last_name' => 'Doe'
);

$response = wp_remote_post(home_url('/wp-json/wp-auth/v1/register'), array(
    'body' => json_encode($registration_data),
    'headers' => array('Content-Type' => 'application/json')
));

$result = json_decode(wp_remote_retrieve_body($response), true);

if ($result['success'] && $result['data']['requires_verification']) {
    // Show OTP form
    echo "Please check your email for OTP verification code.";
}

// Step 2: Verify OTP
$otp_data = array(
    'email' => 'john@example.com',
    'otp' => '123456'
);

$response = wp_remote_post(home_url('/wp-json/wp-auth/v1/verify-otp'), array(
    'body' => json_encode($otp_data),
    'headers' => array('Content-Type' => 'application/json')
));

$result = json_decode(wp_remote_retrieve_body($response), true);

if ($result['success']) {
    // Registration complete
    $jwt_token = $result['data']['token'];
    echo "Registration successful! Welcome " . $result['data']['display_name'];
}
```

---

## 🧪 Testing

### **Manual Testing:**
```bash
# 1. Test registration
curl -X POST https://your-site.com/wp-json/wp-auth/v1/register \
  -H "Content-Type: application/json" \
  -d '{
    "username": "testuser123",
    "email": "test@example.com",
    "password": "password123",
    "first_name": "Test",
    "last_name": "User"
  }'

# Expected: OTP email sent, no user created yet

# 2. Check email for OTP code (e.g., 123456)

# 3. Verify OTP
curl -X POST https://your-site.com/wp-json/wp-auth/v1/verify-otp \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "otp": "123456"
  }'

# Expected: User created, JWT token returned

# 4. Test resend OTP
curl -X POST https://your-site.com/wp-json/wp-auth/v1/resend-otp \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com"
  }'

# 5. Check OTP status
curl "https://your-site.com/wp-json/wp-auth/v1/otp-status?email=test@example.com"
```

---

## 🔧 Troubleshooting

### **Common Issues:**

#### **"OTP not found or expired"**
- **Cause:** OTP expired (10 minutes) or never generated
- **Solution:** Call `/resend-otp` endpoint

#### **"Maximum OTP attempts exceeded"**
- **Cause:** User entered wrong OTP 3 times
- **Solution:** Call `/resend-otp` to get new OTP

#### **"Email not sending"**
- **Cause:** WordPress mail configuration issue
- **Solution:** Check `wp_mail()` function, SMTP settings

#### **"Username/email already exists"**
- **Cause:** Another user registered with same details during OTP process
- **Solution:** User needs to try different username/email

---

## 🎯 Benefits

### **Security Benefits:**
- ✅ **Email verification** ensures valid email addresses
- ✅ **Prevents fake registrations** with invalid emails
- ✅ **Reduces spam accounts** significantly
- ✅ **Confirms user intent** to register

### **User Experience:**
- ✅ **Clear feedback** about verification process
- ✅ **Resend option** if email not received
- ✅ **Attempt tracking** shows remaining tries
- ✅ **Auto-cleanup** prevents clutter

### **Administrative:**
- ✅ **Verified users only** in database
- ✅ **Reduced spam** user accounts
- ✅ **Email deliverability** validation
- ✅ **Security audit trail** for registrations

---

## 🚀 Deployment

The OTP feature is included in the latest plugin package. After updating:

1. **Upload new plugin version**
2. **Activate/reactivate plugin**
3. **Test registration process**
4. **Configure email settings** if needed
5. **Update frontend** to handle new OTP flow

**The OTP verification system is now ready for production use!** 🎉
