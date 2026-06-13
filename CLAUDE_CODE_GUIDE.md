# 🚀 Using Claude Code for Automated Deployment

**Claude Code** is a command-line tool that lets you delegate coding tasks to Claude directly from your terminal. This guide shows how to use it for fully automated Fullstack SIG deployment.

---

## ✅ Prerequisites

### 1. Install Claude Code
```bash
# macOS / Linux
brew install anthropic/claude/claude

# Or from source
npm install -g @anthropic-ai/claude-code
```

Check version:
```bash
claude --version
```

### 2. Have These Ready
- ✅ GitHub account with fullstack-sig repo pushed
- ✅ Railway account (free at railway.app)
- ✅ MySQL client installed locally (`mysql` command)
- ✅ Project folder with `deploy.sh` and `CLAUDE_CODE_MANIFEST.json`

---

## 🎯 Workflow: Using Claude Code

### Method 1: Run Existing Automation Script (Easiest)

```bash
# Navigate to fullstack-sig folder
cd /path/to/fullstack-sig

# Run the deployment script
claude task "Execute the deploy.sh script to prepare project for Railway deployment"
```

This will:
1. ✅ Update all koneksi.php files with env variables
2. ✅ Create Dockerfile, railway.json, .env.example
3. ✅ Commit changes to Git
4. ✅ Show next steps

---

### Method 2: Interactive Claude Code Session

```bash
# Start interactive session
claude chat fullstack-sig

# Then give these commands one by one:

# Command 1: Prepare the project
> Read CLAUDE_CODE_MANIFEST.json and execute Phase 1 (file preparation and git setup)

# Command 2: Setup databases
> After Railway MySQL service is running, execute Phase 3: create 3 databases and import schemas

# Command 3: Verify deployment
> Test all endpoints and verify the deployment works
```

---

### Method 3: Full Automation with a Custom Task

Create a file `claude-deploy.task`:

```json
{
  "task": "Deploy Fullstack SIG to Railway",
  "description": "Fully automated deployment including file prep, Git, and database setup",
  "context": {
    "read_files": [
      "CLAUDE_CODE_MANIFEST.json",
      ".env.example",
      "Dockerfile"
    ]
  },
  "steps": [
    {
      "name": "Phase 1: Prepare Files",
      "action": "run_bash",
      "command": "bash deploy.sh"
    },
    {
      "name": "Phase 2: Manual Railway Setup",
      "action": "print",
      "message": "Go to https://railway.app and:\n1. Create new project\n2. Connect this GitHub repo\n3. Add MySQL service\n4. Set environment variables"
    },
    {
      "name": "Phase 3: Database Initialization",
      "action": "wait_for_input",
      "prompt": "Enter Railway MySQL host (e.g., db.railway.internal): "
    },
    {
      "name": "Phase 4: Verify Deployment",
      "action": "run_bash",
      "command": "curl https://fullstack-sig-XXX.railway.app/sig-03/get_data.php"
    }
  ]
}
```

Then run:
```bash
claude task claude-deploy.task
```

---

## 📋 Claude Code Commands for Each Phase

### Phase 1: File Preparation & Git Setup

```bash
claude task "
1. Read CLAUDE_CODE_MANIFEST.json to understand the project
2. Update sig-01/koneksi.php, sig-02/koneksi.php, sig-03/koneksi.php to use environment variables (getenv function)
3. Create .env.example file with all required variables
4. Create Dockerfile for PHP 8.2 Apache with MySQL support
5. Create railway.json configuration file
6. Update .gitignore to exclude .env and secrets
7. Run: git add . && git commit -m 'Prepare for Railway deployment' && git push origin main
"
```

### Phase 2: Manual Railway Dashboard Steps

_(This step requires manual UI interaction - Claude Code will guide you)_

```bash
claude task "
Guide me through Railway setup:
1. Go to railway.app
2. Create new project from GitHub repo
3. Show me how to add MySQL service
4. Show me how to set environment variables: DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_NAME_02, DB_NAME_03
5. Once done, prompt me to continue to Phase 3
"
```

### Phase 3: Database Initialization

```bash
claude task "
Initialize the three databases on Railway:
1. Prompt for Railway MySQL host, user, and password
2. Create 3 databases: sig_spbu, sig_tanah_jalan, sig_bansos
3. Import SQL schemas from sig-01/setup.sql, sig-02/schema.sql, sig-03/database.sql
4. Optionally load seed data from sig-03/seed.sql
5. Verify tables were created correctly
"
```

Commands it will run:
```bash
mysql -h $RAILWAY_HOST -u $RAILWAY_USER -p$RAILWAY_PASS -e "CREATE DATABASE IF NOT EXISTS sig_spbu;"
mysql -h $RAILWAY_HOST -u $RAILWAY_USER -p$RAILWAY_PASS sig_spbu < sig-01/setup.sql
# ... etc for other databases
```

### Phase 4: Test & Verify

```bash
claude task "
Verify the Railway deployment:
1. Get the Railway app URL from the user
2. Test these endpoints:
   - GET / (landing page)
   - GET /sig-03/ (main app)
   - GET /sig-03/get_data.php (API)
3. Show response times and HTTP status codes
4. Identify any errors and suggest fixes
"
```

Commands it will run:
```bash
curl -v https://fullstack-sig-XXX.railway.app/
curl -v https://fullstack-sig-XXX.railway.app/sig-03/get_data.php
```

---

## 🚀 One-Liner for Complete Automation

Once everything is setup (Phase 2 complete), run:

```bash
claude task "
Using CLAUDE_CODE_MANIFEST.json as context:
- Execute Phase 3: Database initialization
- Execute Phase 4: Verify deployment
- Show summary of deployment status
" --context CLAUDE_CODE_MANIFEST.json
```

---

## 📁 File Structure for Claude Code

Your project should look like:

```
fullstack-sig/
├── deploy.sh                          # Main automation script
├── CLAUDE_CODE_MANIFEST.json          # Knowledge base for Claude Code
├── Dockerfile                         # Docker config
├── railway.json                       # Railway config
├── .env.example                       # Env template
├── .gitignore                         # Git ignore rules
│
├── sig-01/
│   ├── koneksi.php                    # ✅ Uses getenv()
│   ├── setup.sql
│   └── ...
│
├── sig-02/
│   ├── koneksi.php                    # ✅ Uses getenv()
│   ├── schema.sql
│   └── ...
│
├── sig-03/
│   ├── koneksi.php                    # ✅ Uses getenv()
│   ├── database.sql
│   ├── seed.sql
│   ├── get_data.php
│   └── ... (other API endpoints)
│
└── index.php                          # Landing page
```

---

## 🔄 Complete Example Workflow

### Step 1: Start Claude Code Session
```bash
cd fullstack-sig/
claude chat
```

### Step 2: Run Phase 1 Automatically
```
You: Execute deploy.sh to prepare all files for Railway
Claude: [runs bash deploy.sh]
✅ Updated koneksi.php files
✅ Created Dockerfile
✅ Committed to Git
Push to GitHub? (yes/no)
```

### Step 3: Manual Railway Setup
```
You: Guide me through Railway setup
Claude: [shows detailed steps for railway.app]
...
Once complete, enter your Railway MySQL host:
Railway Host: xyz.railway.app
```

### Step 4: Auto Database Initialization
```
You: Initialize the databases using the connection details I just gave you
Claude: [runs MySQL commands]
✅ Created sig_spbu
✅ Created sig_tanah_jalan
✅ Created sig_bansos
✅ Imported all schemas
```

### Step 5: Verify Everything Works
```
You: Test the deployed app and show me the status
Claude: [runs curl tests]
✅ / responding (200)
✅ /sig-03/ responding (200)
✅ /sig-03/get_data.php responding (200)
✅ DEPLOYMENT SUCCESSFUL!
```

---

## 💡 Pro Tips for Claude Code

1. **Give it context:** Always reference `CLAUDE_CODE_MANIFEST.json`
   ```bash
   claude task "Deploy using CLAUDE_CODE_MANIFEST.json" --context CLAUDE_CODE_MANIFEST.json
   ```

2. **Be specific:** Instead of "deploy it", say:
   ```bash
   "Execute Phase 3: Create sig_bansos database and import sig-03/database.sql using mysql CLI"
   ```

3. **Verify first:** Ask Claude to read and understand before executing:
   ```bash
   "Read deploy.sh and CLAUDE_CODE_MANIFEST.json, then explain what will happen when I run it"
   ```

4. **Save outputs:** Keep terminal logs for troubleshooting
   ```bash
   claude task "..." | tee deployment.log
   ```

5. **Error handling:** If a step fails, ask Claude to debug:
   ```bash
   "I got this error: [paste error]. What went wrong and how do I fix it?"
   ```

---

## 🆘 Troubleshooting Claude Code

### Issue: "Command not found: claude"
**Solution:**
```bash
# Reinstall Claude Code
npm install -g @anthropic-ai/claude

# Or use npx
npx @anthropic-ai/claude-code task "..."
```

### Issue: Permission Denied on deploy.sh
**Solution:**
```bash
chmod +x deploy.sh
bash deploy.sh
```

### Issue: MySQL connection fails during Phase 3
**Solution:**
Ask Claude to debug:
```bash
claude task "
Test MySQL connection with these credentials:
Host: $RAILWAY_HOST
User: $RAILWAY_USER
Password: $RAILWAY_PASS

Troubleshoot why it's failing
"
```

---

## 📞 Quick Reference

| Task | Command |
|------|---------|
| Prepare files & Git | `bash deploy.sh` |
| Database init | `claude task "Initialize databases on Railway"` |
| Test endpoints | `claude task "Test all endpoints and verify deployment"` |
| Full automation | `claude task "Deploy using CLAUDE_CODE_MANIFEST.json"` |
| Troubleshoot | `claude chat` + describe error |

---

## 🎓 Learning More

- **Claude Code Docs:** https://docs.claude.com/claude-code
- **Anthropic API:** https://console.anthropic.com/
- **Railway Docs:** https://docs.railway.app/
- **PHP Documentation:** https://www.php.net/manual/en/

---

**Ready?** Let's automate this! 🚀

```bash
cd fullstack-sig/
claude chat < CLAUDE_CODE_MANIFEST.json
```
