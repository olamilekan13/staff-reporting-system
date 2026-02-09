# SMTP Configuration Guide

This guide helps you configure SMTP for production email delivery in the Staff Reporting Management system.

## Table of Contents

- [Overview](#overview)
- [Gmail Setup](#gmail-setup)
- [SendGrid Setup](#sendgrid-setup)
- [Mailgun Setup](#mailgun-setup)
- [AWS SES Setup](#aws-ses-setup)
- [Mailtrap (Testing)](#mailtrap-testing)
- [Security Best Practices](#security-best-practices)
- [Troubleshooting](#troubleshooting)

## Overview

The application uses Laravel's mail system with queue-based email sending for better performance. All notification emails are queued and processed by the queue worker.

## Gmail Setup

### Prerequisites
- A Gmail account
- Two-factor authentication enabled

### Steps

1. **Enable 2FA on your Google Account**
   - Go to https://myaccount.google.com/security
   - Enable 2-Step Verification

2. **Generate App Password**
   - Visit https://myaccount.google.com/apppasswords
   - Select "Mail" and "Other (Custom name)"
   - Name it "Staff Reporting Management"
   - Copy the generated 16-character password

3. **Configure .env**
   ```env
   MAIL_MAILER=smtp
   MAIL_HOST=smtp.gmail.com
   MAIL_PORT=587
   MAIL_ENCRYPTION=tls
   MAIL_USERNAME=your-email@gmail.com
   MAIL_PASSWORD=your-16-char-app-password
   MAIL_FROM_ADDRESS=your-email@gmail.com
   MAIL_FROM_NAME="Staff Reporting Management"
   ```

4. **Test Configuration**
   ```bash
   php artisan tinker
   >>> Mail::raw('Test email', function($msg) { $msg->to('test@example.com')->subject('Test'); });
   ```

### Gmail Limits
- **Sending Limit**: 500 emails per day (free), 2000 per day (Google Workspace)
- **Rate Limit**: ~100 emails per hour
- **Recommendation**: Use Gmail for development/small deployments only

---

## SendGrid Setup

### Prerequisites
- SendGrid account (free tier: 100 emails/day)
- Verified sender identity

### Steps

1. **Create SendGrid Account**
   - Sign up at https://sendgrid.com
   - Verify your email address

2. **Create API Key**
   - Go to Settings → API Keys
   - Click "Create API Key"
   - Give it "Full Access" or "Mail Send" permission
   - Copy the API key (shown only once)

3. **Verify Sender**
   - Go to Settings → Sender Authentication
   - Choose "Single Sender Verification" or "Domain Authentication"
   - Follow verification steps

4. **Configure .env**
   ```env
   MAIL_MAILER=smtp
   MAIL_HOST=smtp.sendgrid.net
   MAIL_PORT=587
   MAIL_ENCRYPTION=tls
   MAIL_USERNAME=apikey
   MAIL_PASSWORD=your-sendgrid-api-key
   MAIL_FROM_ADDRESS=verified-sender@example.com
   MAIL_FROM_NAME="Staff Reporting Management"
   ```

   **Note**: Username must be literally `apikey`

### SendGrid Limits
- **Free Tier**: 100 emails/day
- **Essentials Plan**: $19.95/mo for 50,000 emails/month
- **Pro Plan**: Starting at $89.95/mo for 100,000 emails/month

---

## Mailgun Setup

### Prerequisites
- Mailgun account
- Domain verification (or use sandbox domain for testing)

### Steps

1. **Create Mailgun Account**
   - Sign up at https://www.mailgun.com
   - Verify your email

2. **Get SMTP Credentials**
   - Go to Sending → Domain Settings → SMTP Credentials
   - Note your SMTP hostname, username, and password

3. **Verify Domain (Production)**
   - Go to Sending → Domains → Add New Domain
   - Follow DNS configuration steps
   - Wait for verification (can take 24-48 hours)

4. **Configure .env**
   ```env
   MAIL_MAILER=smtp
   MAIL_HOST=smtp.mailgun.org
   MAIL_PORT=587
   MAIL_ENCRYPTION=tls
   MAIL_USERNAME=postmaster@your-domain.com
   MAIL_PASSWORD=your-mailgun-password
   MAIL_FROM_ADDRESS=noreply@your-domain.com
   MAIL_FROM_NAME="Staff Reporting Management"
   ```

### Mailgun Limits
- **Free Trial**: 5,000 emails for 3 months (requires credit card)
- **Foundation Plan**: $35/mo for 50,000 emails/month
- **Growth Plan**: $80/mo for 100,000 emails/month

---

## AWS SES Setup

### Prerequisites
- AWS Account
- Domain ownership (for production)
- AWS CLI configured (optional but recommended)

### Steps

1. **Request Production Access**
   - By default, SES is in sandbox mode
   - Go to SES → Account Dashboard → Request production access
   - Fill out the form explaining your use case

2. **Verify Email/Domain**
   - Go to Verified identities → Create identity
   - Choose Email or Domain
   - Complete verification process

3. **Create SMTP Credentials**
   - Go to SMTP Settings → Create SMTP Credentials
   - Download and save the credentials securely

4. **Configure .env**
   ```env
   MAIL_MAILER=smtp
   MAIL_HOST=email-smtp.us-east-1.amazonaws.com
   MAIL_PORT=587
   MAIL_ENCRYPTION=tls
   MAIL_USERNAME=your-smtp-username
   MAIL_PASSWORD=your-smtp-password
   MAIL_FROM_ADDRESS=verified-email@your-domain.com
   MAIL_FROM_NAME="Staff Reporting Management"
   ```

   **Note**: Replace `us-east-1` with your AWS region

### AWS SES Limits
- **Sandbox**: 200 emails/day, can only send to verified addresses
- **Production**: 50,000 emails/day initially, can request increase
- **Cost**: $0.10 per 1,000 emails

---

## Mailtrap (Testing)

Mailtrap is perfect for development and staging environments. It captures emails without sending them to real recipients.

### Steps

1. **Create Mailtrap Account**
   - Sign up at https://mailtrap.io (free tier available)
   - Create an inbox

2. **Get SMTP Credentials**
   - Go to your inbox → SMTP Settings
   - Copy the credentials

3. **Configure .env**
   ```env
   MAIL_MAILER=smtp
   MAIL_HOST=sandbox.smtp.mailtrap.io
   MAIL_PORT=2525
   MAIL_ENCRYPTION=null
   MAIL_USERNAME=your-mailtrap-username
   MAIL_PASSWORD=your-mailtrap-password
   MAIL_FROM_ADDRESS="noreply@example.com"
   MAIL_FROM_NAME="Staff Reporting Management"
   ```

### Mailtrap Limits
- **Free Tier**: 500 emails/month, 1 inbox
- **Individual Plan**: $9.99/mo for 5,000 emails/month, multiple inboxes

---

## Security Best Practices

### 1. Environment Variables
- **Never commit `.env` file** to version control
- Use different credentials for development, staging, and production
- Store production credentials in secure vault (AWS Secrets Manager, 1Password, etc.)

### 2. App Passwords
- For Gmail, **never use your actual password** - always use App Passwords
- Revoke unused App Passwords regularly

### 3. API Key Security
- For SendGrid/Mailgun, use API keys with minimal permissions
- Rotate API keys every 90 days
- Monitor API key usage for suspicious activity

### 4. TLS/SSL Encryption
- Always use `MAIL_ENCRYPTION=tls` for port 587
- Never send credentials over unencrypted connections

### 5. Rate Limiting
- Monitor your sending rates to avoid hitting provider limits
- Implement exponential backoff for failed emails
- Use queue workers with reasonable concurrency

### 6. SPF, DKIM, and DMARC
- Configure SPF records to authorize your mail server
- Enable DKIM signing to verify email authenticity
- Set up DMARC policy to protect against spoofing

---

## Troubleshooting

### Email Not Sending

**Check Queue Worker**
```bash
# Check if queue worker is running
ps aux | grep queue:work

# Start queue worker
php artisan queue:work --tries=3 --timeout=60

# Check failed jobs
php artisan queue:failed
```

**Check Logs**
```bash
tail -f storage/logs/laravel.log
```

### "Authentication Failed" Error

**Causes:**
- Incorrect username/password
- Gmail: Not using App Password
- SendGrid: Not using `apikey` as username
- 2FA not enabled (Gmail)

**Solution:**
- Double-check credentials in `.env`
- Verify credentials in provider dashboard
- For Gmail, regenerate App Password

### "Connection Timeout" Error

**Causes:**
- Firewall blocking port 587/465/2525
- Incorrect MAIL_HOST
- ISP blocking SMTP ports

**Solution:**
```bash
# Test SMTP connection
telnet smtp.gmail.com 587

# Try alternative port
MAIL_PORT=465
MAIL_ENCRYPTION=ssl
```

### Emails Going to Spam

**Causes:**
- Missing SPF/DKIM/DMARC records
- Using "no-reply" addresses
- Poor email content (too many links, spammy words)

**Solution:**
- Configure SPF:TXT record: `v=spf1 include:_spf.google.com ~all` (Gmail)
- Enable DKIM in provider settings
- Use a real reply-to address
- Test with https://www.mail-tester.com

### Queue Jobs Failing

**Check Failed Jobs:**
```bash
php artisan queue:failed
```

**Retry Failed Jobs:**
```bash
# Retry specific job
php artisan queue:retry job-id

# Retry all failed jobs
php artisan queue:retry all
```

**Clear Failed Jobs:**
```bash
php artisan queue:flush
```

---

## Testing Email Configuration

### 1. Send Test Email
```bash
php artisan tinker

>>> use Illuminate\Support\Facades\Mail;
>>> Mail::raw('Test email from Staff Reporting Management', function($msg) {
...     $msg->to('your-email@example.com')
...         ->subject('Test Email');
... });
>>> exit
```

### 2. Process Queue
```bash
# Process one job
php artisan queue:work --once

# Process all pending jobs
php artisan queue:work
```

### 3. Check Email Delivery
- Check your inbox (or Mailtrap inbox)
- Check spam folder
- Check provider dashboard for delivery logs

---

## Production Checklist

Before going to production, ensure:

- [ ] Valid SMTP credentials configured in `.env`
- [ ] `MAIL_MAILER` set to `smtp` (not `log`)
- [ ] `MAIL_FROM_ADDRESS` is a verified email
- [ ] Queue worker is running and supervised (systemd, Supervisor, or pm2)
- [ ] SPF/DKIM records configured for your domain
- [ ] Test emails sent successfully
- [ ] Failed job monitoring in place
- [ ] Credentials stored securely (not in `.env` on production server)
- [ ] Rate limiting configured appropriately
- [ ] Backup mail provider configured (optional but recommended)

---

## Recommended Providers by Use Case

| Use Case | Recommended Provider | Why |
|----------|---------------------|-----|
| Development | Mailtrap | Safe testing without sending real emails |
| Small Organization | Gmail | Free, familiar, easy setup |
| Medium Organization | SendGrid | Reliable, good free tier, excellent deliverability |
| Large Organization | AWS SES | Scalable, cost-effective at scale |
| Transactional Emails | Mailgun or SendGrid | Designed for high-volume transactional emails |
| Marketing Emails | SendGrid or Mailgun | Advanced analytics and segmentation |

---

## Additional Resources

- [Laravel Mail Documentation](https://laravel.com/docs/11.x/mail)
- [Laravel Queue Documentation](https://laravel.com/docs/11.x/queues)
- [SPF Record Generator](https://mxtoolbox.com/SPFRecordGenerator.aspx)
- [DKIM Record Generator](https://mxtoolbox.com/DKIMRecordGenerator.aspx)
- [Email Deliverability Test](https://www.mail-tester.com/)

---

## Support

For issues specific to this application, check:
- Application logs: `storage/logs/laravel.log`
- Queue logs: `php artisan queue:monitor`
- Database: Check `jobs` and `failed_jobs` tables

For provider-specific issues, consult their documentation or support channels.
