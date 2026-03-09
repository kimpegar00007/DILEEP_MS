# Namecheap Quick Start Guide
## DOLE DILP Monitoring System - Fast Deployment

**Estimated Time:** 30-45 minutes

---

## 🚀 Quick Deployment Steps

### 1️⃣ Create Database (5 minutes)

**cPanel > MySQL Databases**

```
1. Create Database: dilp_monitoring
2. Create User: dilp_admin
3. Generate strong password
4. Add user to database (ALL PRIVILEGES)
5. Note credentials:
   - Database: username_dilp_monitoring
   - User: username_dilp_admin
   - Password: [generated password]
```

### 2️⃣ Upload Files (10 minutes)

**cPanel > File Manager > public_html**

```
1. Create folder: dilp-system (or upload to root)
2. Upload all project files
3. Extract if uploaded as ZIP
4. Verify all files present
```

### 3️⃣ Configure Environment (5 minutes)

**Create .env file:**

```env
APP_ENV=production
APP_DEBUG=false
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=username_dilp_monitoring
DB_USERNAME=username_dilp_admin
DB_PASSWORD=your_password_here
DB_NAME=username_dilp_monitoring
DB_USER=username_dilp_admin
DB_PASS=your_password_here
DB_SOCKET=
```

**Set .env permissions to 600**

### 4️⃣ Import Database (5 minutes)

**cPanel > phpMyAdmin**

```
1. Select your database
2. Click "Import" tab
3. Choose file: namecheap-migration.sql
4. Click "Go"
5. Verify 7 tables created
```

### 5️⃣ Test & Secure (10 minutes)

**Test Login:**
```
URL: https://yourdomain.com/dilp-system/
Username: admin
Password: admin123
```

**IMMEDIATELY:**
1. ✅ Change admin password
2. ✅ Verify functionality
3. ✅ Enable SSL/HTTPS
4. ✅ Add .htaccess security

---

## 📋 Files You Need

| File | Purpose | Required |
|------|---------|----------|
| `namecheap-migration.sql` | Database schema | ✅ Yes |
| `.env.namecheap.example` | Config template | ✅ Yes |
| All project files | Application | ✅ Yes |

---

## ⚡ Common Issues & Quick Fixes

### "Database connection failed"
```
✓ Check DB_HOST=localhost
✓ Verify database name includes username prefix
✓ Ensure DB_SOCKET is empty
✓ Test credentials in phpMyAdmin
```

### "Table doesn't exist"
```
✓ Re-import namecheap-migration.sql
✓ Check phpMyAdmin shows 7 tables
✓ Verify database selected correctly
```

### "Permission denied"
```
✓ Set directories to 755
✓ Set files to 644
✓ Set .env to 600
```

---

## 🔒 Security Checklist

- [ ] Changed admin password from default
- [ ] Set APP_DEBUG=false
- [ ] Protected .env file
- [ ] Enabled HTTPS
- [ ] Added .htaccess security rules

---

## 📞 Need Help?

**Full Documentation:** `docs/NAMECHEAP_DEPLOYMENT_GUIDE.md`

**Namecheap Support:** 24/7 Live Chat

---

## ✅ Success Indicators

- ✓ Login page loads
- ✓ Can login with admin credentials
- ✓ Dashboard displays with map
- ✓ Can create test beneficiary
- ✓ Can create test proponent
- ✓ Activity logs recording

---

**You're ready to go live! 🎉**
