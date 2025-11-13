# SMTP Email Setup Guide

## ✅ SMTP Configuration Applied

Your email system is now configured to use SMTP. You just need to add your email credentials.

---

## 📧 Quick Setup Steps

### Step 1: Choose Your Email Provider

#### Option A: Gmail (Recommended for Testing)

1. **Enable 2-Factor Authentication** on your Gmail account
   - Go to: https://myaccount.google.com/security
   - Enable 2-Step Verification

2. **Generate App Password**
   - Go to: https://myaccount.google.com/apppasswords
   - Select "Mail" and "Other (Custom name)"
   - Enter "ChakaNoks SCMS" as the name
   - Click "Generate"
   - Copy the 16-character password (no spaces)

3. **Update Configuration**
   - Edit `app/Config/Email.php`
   - Set `$SMTPUser = 'your-email@gmail.com'`
   - Set `$SMTPPass = 'your-16-char-app-password'`
   - Set `$fromEmail = 'your-email@gmail.com'`

---

#### Option B: Outlook/Hotmail

1. **Update Configuration** in `app/Config/Email.php`:
   ```php
   public string $SMTPHost = 'smtp-mail.outlook.com';
   public string $SMTPUser = 'your-email@outlook.com';
   public string $SMTPPass = 'your-password';
   public int $SMTPPort = 587;
   public string $SMTPCrypto = 'tls';
   ```

---

#### Option C: Yahoo Mail

1. **Update Configuration** in `app/Config/Email.php`:
   ```php
   public string $SMTPHost = 'smtp.mail.yahoo.com';
   public string $SMTPUser = 'your-email@yahoo.com';
   public string $SMTPPass = 'your-app-password';  // Generate from Yahoo Account Security
   public int $SMTPPort = 587;
   public string $SMTPCrypto = 'tls';
   ```

---

#### Option D: Custom SMTP Server

1. **Update Configuration** in `app/Config/Email.php`:
   ```php
   public string $SMTPHost = 'smtp.yourdomain.com';
   public string $SMTPUser = 'noreply@yourdomain.com';
   public string $SMTPPass = 'your-password';
   public int $SMTPPort = 587;  // or 465 for SSL
   public string $SMTPCrypto = 'tls';  // or 'ssl' for port 465
   ```

---

## 🔧 Configuration File Location

**File:** `app/Config/Email.php`

**What to Update:**
```php
public string $SMTPHost = 'smtp.gmail.com';        // Your SMTP server
public string $SMTPUser = 'your-email@gmail.com';  // Your email
public string $SMTPPass = 'your-app-password';     // Your password/app password
public string $fromEmail = 'your-email@gmail.com'; // Sender email
public string $fromName = 'ChakaNoks SCMS';        // Sender name
public int $SMTPPort = 587;                        // Port (587 for TLS, 465 for SSL)
public string $SMTPCrypto = 'tls';                 // Encryption (tls or ssl)
```

---

## 🧪 Testing Your Email Setup

### Method 1: Test via Stock Update

1. Login to your system
2. Update a product's stock to go below the minimum level
3. Check your email inbox
4. Check logs: `writable/logs/` for email send status

### Method 2: Test via Command

Run the alert check command:
```bash
php spark inventory:check-alerts
```

This will check all branches and send alerts if needed.

### Method 3: Check Logs

Check the log files in `writable/logs/`:
- Look for messages like: "Low stock alert email sent to: email@example.com"
- Or errors like: "Failed to send low stock alert email"

---

## ⚠️ Common Issues & Solutions

### Issue 1: "SMTP connection failed"

**Solution:**
- Check if `$SMTPHost` is correct
- Verify port number (587 for TLS, 465 for SSL)
- Check firewall/antivirus isn't blocking port 587

### Issue 2: "Authentication failed"

**Solution:**
- For Gmail: Make sure you're using App Password, not regular password
- Check username and password are correct
- Verify 2FA is enabled (for Gmail)

### Issue 3: "Connection timeout"

**Solution:**
- Increase `$SMTPTimeout` (currently 5 seconds)
- Check internet connection
- Verify SMTP server is accessible

### Issue 4: Emails go to spam

**Solution:**
- Use a proper domain email (not free Gmail/Yahoo)
- Set up SPF/DKIM records for your domain
- Use a professional `fromName` and `fromEmail`

---

## 🔒 Security Best Practices

1. **Never commit credentials to Git**
   - Keep `app/Config/Email.php` in `.gitignore` (if you modify it)
   - Or use environment variables

2. **Use App Passwords** (for Gmail/Yahoo)
   - More secure than regular passwords
   - Can be revoked individually

3. **Use Environment Variables** (Production)
   ```php
   public string $SMTPUser = getenv('SMTP_USER') ?: '';
   public string $SMTPPass = getenv('SMTP_PASS') ?: '';
   ```

---

## 📋 SMTP Settings Reference

### Gmail
- Host: `smtp.gmail.com`
- Port: `587` (TLS) or `465` (SSL)
- Encryption: `tls` or `ssl`
- Requires: App Password

### Outlook/Hotmail
- Host: `smtp-mail.outlook.com`
- Port: `587`
- Encryption: `tls`
- Uses: Regular password

### Yahoo
- Host: `smtp.mail.yahoo.com`
- Port: `587`
- Encryption: `tls`
- Requires: App Password

### SendGrid (Email Service)
- Host: `smtp.sendgrid.net`
- Port: `587`
- Encryption: `tls`
- Username: `apikey`
- Password: Your SendGrid API key

---

## ✅ Current Configuration

Your system is now set to:
- ✅ Use SMTP protocol
- ✅ Gmail SMTP server (default)
- ✅ Port 587 with TLS encryption
- ✅ Ready for your credentials

**Next Step:** Just add your email credentials in `app/Config/Email.php`!

---

## 🎯 Quick Start (Gmail Example)

1. Open `app/Config/Email.php`
2. Find these lines and update:
   ```php
   public string $SMTPUser = 'your-email@gmail.com';
   public string $SMTPPass = 'your-16-char-app-password';
   public string $fromEmail = 'your-email@gmail.com';
   ```
3. Save the file
4. Test by updating stock below threshold
5. Check your email!

---

**Status:** ✅ SMTP configured and ready for your credentials!

