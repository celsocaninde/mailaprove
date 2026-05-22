# 📧 Approval By Mail for GLPI 11

> **Streamline ticket approvals directly from your inbox!** No login required, secure tokens, instant responses.

[![License: GPL-3.0+](https://img.shields.io/badge/License-GPLv3%2B-blue.svg)](https://github.com/YOUR_REPO/mailaprove/blob/main/LICENSE)
[![GLPI 11](https://img.shields.io/badge/GLPI-11.x-2C8DBF.svg)](https://github.com/glpi-project/glpi)
[![PHP 8.2+](https://img.shields.io/badge/PHP-8.2%2B-777BB4.svg)](https://www.php.net/)

---

## ✨ Overview

**Mail Approve** is a powerful GLPI 11 plugin that transforms ticket workflows by allowing users to **approve, reject, and respond to surveys directly from their email inbox**. No GLPI login required, no authentication hassles – just secure, one-click actions with cryptographic token validation.

### 🎯 Perfect For:
- 📝 **Ticket Validation Approvals** – Approve/reject ticket validations instantly
- ✅ **Solution Acceptance** – Accept or reject proposed solutions  
- ⭐ **Satisfaction Surveys** – Rate ticket solutions with star ratings
- 🔒 **Secure Workflows** – Token-based authentication with SHA-256 hashing
- 📱 **Mobile-Friendly** – Beautiful responsive UI for all devices

---

## 🚀 Features

| Feature | Description |
|---------|-------------|
| 🔐 **No Login Required** | Actions are authenticated via secure, single-use cryptographic tokens |
| 🛡️ **Enterprise Security** | Compliant with GLPI 11 security standards, CSRF protection, SHA-256 token hashing |
| ⏱️ **Auto-Expiration** | Tokens expire automatically (configurable, default: 72 hours) |
| 📱 **Fully Responsive** | Perfect rendering on desktop, tablet, and mobile devices |
| 🎨 **Customizable UI** | Rejection forms, star ratings, and styled HTML responses |
| 📊 **Audit Logging** | Complete audit trail of all token actions and approvals |
| ⚙️ **Easy Configuration** | GUI-based plugin settings in GLPI admin panel |
| 🌍 **Multi-Language** | Supports English and Portuguese (pt_BR) |

---

## 📦 Requirements

- **GLPI**: 11.0.0 or higher (< 11.99.99)
- **PHP**: 8.2 or higher
- **Database**: MySQL/MariaDB compatible

---

## 🔧 Installation

### Step 1: Download & Deploy
```bash
# Navigate to your GLPI plugins directory
cd /path/to/glpi/plugins/

# Clone or download mailaprove
git clone https://github.com/YOUR_REPO/mailaprove.git
# OR
unzip mailaprove.zip
```

### Step 2: Enable in GLPI
1. Log into **GLPI** as a **Super-Administrator**
2. Navigate to: **Setup** → **Plugins**
3. Locate **"Approval By Mail"** in the plugin list
4. Click **Install** button
5. Click **Enable** button
6. ✅ Ready to go!

---

## ⚙️ Configuration

After installation, access plugin settings:

**Setup** → **Plugins** → **Approval By Mail** (or click the ⚙️ gear icon)

### Available Options:
- ⏱️ **Token Expiration Time** – Set how long tokens remain valid (default: 72 hours)
- 🔄 **Token Retention Period** – Keep used tokens for audit (default: 30 days)
- 📋 **Audit Log Retention** – Archive audit logs after N days (default: 180 days)
- ✅ **Enable Validations** – Turn ticket validation approvals on/off
- ✅ **Enable Solutions** – Turn solution acceptance on/off
- ✅ **Enable Satisfaction** – Turn satisfaction surveys on/off

---

## 📧 Notification Template Setup

To activate Mail Approve features, **add custom tags to your email templates** in GLPI.

### Step 1: Access Templates
**Setup** → **Notifications** → **Notification Templates**

### Step 2: Add Custom Tags

#### 🎯 For Ticket Validation (e.g., "Ticket Validation" template)
```html
<p>To approve this request, click here: 
   <a href="##ticket.validation.accepturl##" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">
      ✅ Approve
   </a>
</p>

<p>To reject this request, click here: 
   <a href="##ticket.validation.rejecturl##" style="background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">
      ❌ Reject
   </a>
</p>
```

#### ✅ For Solutions (e.g., "Ticket Solution" template)
```html
<p>To accept this solution, click here: 
   <a href="##ticket.solution.accepturl##" style="background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">
      ✓ Accept Solution
   </a>
</p>

<p>To reject this solution, click here: 
   <a href="##ticket.solution.rejecturl##" style="background: #ffc107; color: black; padding: 10px 20px; text-decoration: none; border-radius: 4px;">
      ✗ Reject Solution
   </a>
</p>
```

#### ⭐ For Satisfaction Surveys (e.g., "Ticket Satisfaction" template)
```html
<p>Please rate this ticket solution: 
   <a href="##ticket.satisfaction.url##" style="background: #6f42c1; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">
      ⭐ Rate Now
   </a>
</p>
```

---

## 🔒 Security

### 🛡️ Security Highlights:
- ✅ **SHA-256 Token Hashing** – Tokens are hashed in database, never stored in plain text
- ✅ **Single-Use Tokens** – Each token can only be used once
- ✅ **Automatic Expiration** – Configurable token lifespan prevents abuse
- ✅ **CSRF Protection** – Full compliance with GLPI 11 CSRF standards
- ✅ **Audit Logging** – Every action is logged with IP, user agent, timestamp
- ✅ **Stateless Paths** – Mail endpoints bypass authentication without compromising security

### 📊 Audit Trail:
All approvals and rejections are logged with:
- User identifier
- IP address
- User agent (browser/client info)
- Timestamp
- Action type and result

---

## 📁 Project Structure

```
mailaprove/
├── README.md                 # This file
├── LICENSE                   # GPLv3+ License
├── composer.json             # PHP dependencies
├── hook.php                  # Plugin lifecycle hooks
├── setup.php                 # Plugin metadata & installation
├── ajax/
│   └── template.preview.php  # Email template preview
├── front/
│   ├── approve.php           # Validation approval form
│   ├── reject.php            # Validation rejection form
│   ├── solution_approve.php  # Solution acceptance
│   ├── solution_reject.php   # Solution rejection
│   ├── satisfaction.php      # Satisfaction survey
│   ├── config.form.php       # Plugin configuration UI
│   └── audit.php             # Audit log viewer
├── src/
│   ├── AuditLog.php          # Audit logging service
│   ├── Config.php            # Configuration management
│   ├── NotificationHandler.php # Email tag injector
│   ├── PublicAction.php      # Public endpoint handler
│   └── Token.php             # Token generation & validation
├── templates/
│   ├── action_confirm.php    # Confirmation page
│   ├── reject_form.php       # Rejection form UI
│   └── satisfaction_form.php # Satisfaction survey UI
└── locale/
    ├── en_US.po              # English translations
    └── pt_BR.po              # Portuguese (Brazil) translations
```

---

## 🎓 Usage Examples

### ✅ Example: Approval via Email

1. **User receives email** with validation notice
2. **User clicks** "Approve" link from inbox
3. **Browser opens** secure form (no login required)
4. **Action is recorded** in GLPI automatically
5. **Audit log updated** with timestamp and IP

### ⭐ Example: Satisfaction Survey

1. **User receives** satisfaction request email
2. **User clicks** "Rate Now" link
3. **Beautiful star rating form** opens
4. **User submits** rating + optional comments
5. **Survey recorded** in GLPI + audit trail created

---

## 🔄 API Endpoints

Mail Approve exposes public endpoints for email actions:

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/plugins/mailaprove/front/approve.php` | GET/POST | Approve ticket validation |
| `/plugins/mailaprove/front/reject.php` | GET/POST | Reject ticket validation |
| `/plugins/mailaprove/front/solution_approve.php` | GET/POST | Accept solution |
| `/plugins/mailaprove/front/solution_reject.php` | GET/POST | Reject solution |
| `/plugins/mailaprove/front/satisfaction.php` | GET/POST | Submit satisfaction rating |

**Authentication**: Token-based (no session required)

---

## 🐛 Troubleshooting

### ❓ Links don't work in emails
- ✅ Verify custom tags are added to notification templates
- ✅ Check token expiration settings in plugin config
- ✅ Ensure `##ticket.validation.accepturl##` tags are used correctly

### ❓ "Token Expired" error
- ✅ Default expiration is 72 hours – adjust in plugin settings
- ✅ Check server time synchronization
- ✅ Review audit logs for token details

### ❓ Plugin won't install
- ✅ Verify PHP version ≥ 8.2
- ✅ Check GLPI version is 11.x
- ✅ Ensure file permissions: `chmod 755 mailaprove`
- ✅ Clear GLPI plugin cache: **Setup** → **Plugins** → **Reinstall**

---

## 📝 Localization

Mail Approve supports multiple languages:

- 🇺🇸 **English (en_US)**
- 🇧🇷 **Portuguese - Brazil (pt_BR)**

To add more languages, edit `.po` files in `locale/` and contribute!

---

## 🤝 Contributing

We welcome contributions! Please feel free to:

- 🐛 Report bugs via [Issues](https://github.com/YOUR_REPO/mailaprove/issues)
- 💡 Suggest features via [Discussions](https://github.com/YOUR_REPO/mailaprove/discussions)
- 🔧 Submit pull requests for improvements
- 🌍 Help translate to new languages

---

## 📄 License

This project is licensed under the **GNU General Public License v3.0 or later** (GPLv3+).

See [LICENSE](LICENSE) file for details.

---

## 👨‍💻 Author

**Community-driven development**  
Built with ❤️ for GLPI administrators and users worldwide.

---

## 🙏 Acknowledgments

- GLPI team for the excellent ticket management system
- Contributors who've submitted fixes and improvements
- Community for feedback and feature requests

---

## 📞 Support

- 📖 **Documentation**: [Wiki](https://github.com/YOUR_REPO/mailaprove/wiki)
- 🐛 **Issues**: [GitHub Issues](https://github.com/YOUR_REPO/mailaprove/issues)
- 💬 **Discussions**: [GitHub Discussions](https://github.com/YOUR_REPO/mailaprove/discussions)
- 📧 **Email**: contact@example.com

---

**Made with ❤️ for GLPI** | ⭐ If you find this plugin useful, please star this repository!
<p>To reject this solution, click here: <a href="##ticket.solution.rejecturl##">Reject Solution</a></p>
```

### For Satisfaction (e.g., "Tickets - Satisfaction survey" template)
```html
<p>Please click here to answer our survey: <a href="##ticket.satisfaction.url##">Answer Survey</a></p>
```

## How it Works under the hood
- When GLPI generates a notification, the plugin intercepts the data building process (via the `ITEM_GET_DATA` hook).
- It generates a cryptographically secure random token and saves its SHA-256 hash in the database.
- The raw token is appended to the action URL and injected into the email template.
- When the user clicks the link, they hit a stateless endpoint (e.g., `front/approve.php`).
- The endpoint hashes the token from the URL, looks it up in the database, verifies expiration and usage, and processes the action as the authorized user.

## Translations
Includes English (`en_US`) and Brazilian Portuguese (`pt_BR`) translations. To add more, use Poedit to translate `locale/en_US.po` and save it as `your_lang.mo` in the `locale` directory.
