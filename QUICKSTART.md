# ⚡ QUICK START - Fastest Way to Deploy

Choose your path:

---

## Path 1️⃣: Fully Automated (Recommended for Speed)

**Time needed:** ~20 minutes total

### Step 1: Run the automated script
```bash
cd /path/to/fullstack-sig
bash deploy.sh
```

**What it does:**
- ✅ Updates koneksi.php (all 3 projects)
- ✅ Creates Dockerfile, railway.json, .env.example
- ✅ Commits to Git
- ✅ Shows next steps

### Step 2: Push to GitHub
```bash
git push origin main
```

### Step 3: Deploy on Railway (Manual - 10 min)
1. Go to https://railway.app
2. New Project → GitHub repo → Deploy
3. Wait for build (3-5 min)
4. Add MySQL service (1 min)
5. Copy MySQL variables to PHP app environment variables (1 min)

### Step 4: Initialize Databases (If have MySQL client)
```bash
# Get these from Railway MySQL service Variables
RAILWAY_HOST="xyz.railway.app"
RAILWAY_USER="root"
RAILWAY_PASS="your_password"

# Create databases
mysql -h $RAILWAY_HOST -u $RAILWAY_USER -p$RAILWAY_PASS -e "CREATE DATABASE IF NOT EXISTS sig_spbu;"
mysql -h $RAILWAY_HOST -u $RAILWAY_USER -p$RAILWAY_PASS -e "CREATE DATABASE IF NOT EXISTS sig_tanah_jalan;"
mysql -h $RAILWAY_HOST -u $RAILWAY_USER -p$RAILWAY_PASS -e "CREATE DATABASE IF NOT EXISTS sig_bansos;"

# Import schemas
mysql -h $RAILWAY_HOST -u $RAILWAY_USER -p$RAILWAY_PASS sig_spbu < sig-01/setup.sql
mysql -h $RAILWAY_HOST -u $RAILWAY_USER -p$RAILWAY_PASS sig_tanah_jalan < sig-02/schema.sql
mysql -h $RAILWAY_HOST -u $RAILWAY_USER -p$RAILWAY_PASS sig_bansos < sig-03/database.sql

# Load test data (optional)
mysql -h $RAILWAY_HOST -u $RAILWAY_USER -p$RAILWAY_PASS sig_bansos < sig-03/seed.sql
```

### Step 5: Test
```bash
curl https://fullstack-sig-XXXX.railway.app/sig-03/get_data.php
```

✅ **Done! You're live.**

---

## Path 2️⃣: With Claude Code (Hands-Free)

**Time needed:** ~15 minutes (mostly waiting for Railway)

### Step 1: Install Claude Code
```bash
npm install -g @anthropic-ai/claude
# or if using Anthropic's Claude Code
brew install anthropic/claude/claude
```

### Step 2: Run Automated Deploy
```bash
cd /path/to/fullstack-sig
claude task "
Read CLAUDE_CODE_MANIFEST.json.
1. Execute Phase 1: Run deploy.sh to prepare all files
2. Commit and push to GitHub
3. Guide me through Phase 2 (Railway setup)
4. Execute Phase 3: Initialize databases on Railway
5. Execute Phase 4: Verify deployment
"
```

✅ **Claude handles everything. You just follow the prompts.**

---

## Path 3️⃣: Manual (If you prefer control)

See `railway-deployment-guide.md` for detailed step-by-step instructions.

---

## 🎯 What You Get

After completing any path:

- ✅ Live URL: `https://fullstack-sig-XXXX.railway.app`
- ✅ All 3 projects accessible:
  - `https://fullstack-sig-XXXX.railway.app/sig-01/`
  - `https://fullstack-sig-XXXX.railway.app/sig-02/`
  - `https://fullstack-sig-XXXX.railway.app/sig-03/`
- ✅ APIs working:
  - `https://fullstack-sig-XXXX.railway.app/sig-03/get_data.php`
- ✅ Maps, forms, database all functioning
- ✅ Free tier: $5/month Railway credit

---

## 📊 Comparison

| | Path 1 | Path 2 | Path 3 |
|---|--------|--------|---------|
| **Automation** | Script | AI-Assisted | Manual |
| **Time** | 20 min | 15 min | 25 min |
| **Effort** | Low | Very Low | High |
| **Recommended** | ⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐ |

---

## 🚨 Critical Steps (All Paths)

1. ✅ Run `bash deploy.sh` OR equivalent automated task
2. ✅ Push to GitHub
3. ✅ Create Railway project from GitHub
4. ✅ Add MySQL service
5. ✅ Create 3 databases
6. ✅ Import SQL schemas

**Without these, it won't work.**

---

## 🧪 Quick Test

After deployment:

```bash
# Test landing page
curl https://fullstack-sig-XXXX.railway.app/

# Test main app
curl https://fullstack-sig-XXXX.railway.app/sig-03/

# Test API
curl https://fullstack-sig-XXXX.railway.app/sig-03/get_data.php
```

Should return success responses.

---

## 🆘 If Something Breaks

### "Database connection failed"
→ Check Railway dashboard → PHP app → Variables → DB_HOST, DB_USER, DB_PASS

### "Table doesn't exist"
→ MySQL client not working? Try via Railway Shell:
```bash
# Railway dashboard → MySQL service → Diagnostics → Shell
mysql -h $MYSQLHOST -u $MYSQLUSER -p$MYSQLPASSWORD sig_bansos -e "SHOW TABLES;"
```

### Still stuck?
→ Check `railway-deployment-guide.md` → Troubleshooting section

---

## 📚 Files You Need

### For Path 1 & 2
- `deploy.sh` - Main automation
- `CLAUDE_CODE_MANIFEST.json` - Knowledge base
- Your project folder with all source files

### For Path 3
- `railway-deployment-guide.md` - Full guide
- Template files (koneksi.php, Dockerfile, etc.)

---

## ⚡ TL;DR (Just Copy-Paste)

```bash
# Copy these 3 commands:

# 1. Automate file prep
bash deploy.sh && git push origin main

# 2. Create Railway project (go to https://railway.app, manual UI)

# 3. Initialize databases (after Railway MySQL is ready)
mysql -h $RAILWAY_HOST -u root -p$RAILWAY_PASS -e "CREATE DATABASE sig_spbu; CREATE DATABASE sig_tanah_jalan; CREATE DATABASE sig_bansos;" && \
mysql -h $RAILWAY_HOST -u root -p$RAILWAY_PASS sig_spbu < sig-01/setup.sql && \
mysql -h $RAILWAY_HOST -u root -p$RAILWAY_PASS sig_tanah_jalan < sig-02/schema.sql && \
mysql -h $RAILWAY_HOST -u root -p$RAILWAY_PASS sig_bansos < sig-03/database.sql
```

Then wait 2 minutes and test:
```bash
curl https://fullstack-sig-XXXX.railway.app/sig-03/get_data.php
```

✅ **Done!**

---

**Ready?** Pick a path above and get started! 🚀
