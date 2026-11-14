# 🛡️ VeriTruth - Fake News Detection & Misinformation Monitoring Portal

An intelligent, multi-layer verification portal designed to crosscheck viral forwards, headlines, and claims for authenticity, track verification history, and monitor community reports via an Administrative Console.

---

## 🌟 Live Demo (GitHub Pages Ready)
This project is engineered to work **100% serverless** directly on **GitHub Pages**, browser, or mobile devices with **zero server setup and no XAMPP required**.

- **Main Portal**: `index.html`
- **Admin Console**: `admin.html`
- **User Profile**: `profile.html`

### 🔑 Default Demo Credentials:
- **Admin Account**: `admin@fakenews.com` | Password: `password123`
- **Standard User**: `vineet@gmail.com` | Password: `password123`
- *Or register any new account instantly through the built-in Create Account tab!*

---

## 🚀 Key Features

1. **Mandatory Authentication Gate (`index.html`)**:
   - Secures all portal tools behind an authentication barrier.
   - Allows users to Sign In or Create an Account.
   - Automatically unlocks the full dashboard and personal history upon login.

2. **Strict & Evidence-Based News Verification Engine (`db.js` / `analyze.php`)**:
   - **No Arbitrary / Random Scores**: Does not fabricate percentages for made-up sentences.
   - **Personal Thought & Casual Chat Filter**: Rejects non-news statements (e.g., *"today I ate lunch with my friends"*, *"i think tomorrow i will go to market"*) with a clear **`⚠️ Not a Recognized News Event (0%)`** verdict.
   - **Gibberish Filter**: Detects random keystrokes or meaningless characters with **`❌ Unrecognized / Invalid Text (0%)`**.
   - **Confirmed Scams & Viral Hoaxes**: Identifies documented fraud phrases (e.g., free money, lotteries, miracle cures) with **`⚠️ High Risk: Known Viral Scam (10-15%)`**.
   - **Official Attribution**: Matches authoritative bodies (PIB, ISRO, RBI, Supreme Court, Gazette, Reuters) with **`✓ High Credibility (88-96%)`**.
   - **Database Cross-Referencing**: Real-time crosscheck against confirmed fake news in the database.

3. **Administrative Dashboard (`admin.html` / `admin.php`)**:
   - Live KPI overview cards (Pending Reports, Verified Real News, Fake Cases, Active Users).
   - Interactive report verification workflow (**"✓ Verify Real"**, **"✗ Mark Fake"**, **"Reset to Pending"**, **"Delete"**).
   - Live Verification Logs viewer.
   - User Accounts management.

4. **Community Misinformation Reporting**:
   - Allows citizens and researchers to submit suspicious viral forwards, phishing links, and rumors for admin review.

5. **Personal Verification History**:
   - Keeps track of all past news verifications in persistent storage with search and filtering.

6. **User Profile Settings (`profile.html` / `profile.php`)**:
   - View and update Name, Role, Age, Address, and Profile Picture.

---

## 📦 How to Deploy on GitHub Pages (Step-by-Step)

1. **Create a GitHub Repository**:
   - Go to [GitHub.com](https://github.com) and click **New Repository**.
   - Name it `fakenews-portal` (or any name you like) and set it to **Public**.

2. **Upload Project Files**:
   - In your new repository page, click **Upload files**.
   - Drag and drop all files from this folder (`index.html`, `admin.html`, `profile.html`, `style.css`, `db.js`, `database.sql`, `README.md`, etc.).
   - Click **Commit changes**.

3. **Enable GitHub Pages**:
   - In your repository, click **Settings** (top tab).
   - On the left sidebar, click **Pages**.
   - Under **Build and deployment** -> **Branch**, select `main` (or `master`) and folder `/ (root)`.
   - Click **Save**.
   - Within 1–2 minutes, your live website link will be displayed:
     ```text
     https://<your-username>.github.io/<repo-name>/
     ```

Anyone (including your teacher, evaluators, and friends) can open that link on any device without XAMPP!

---

## 💻 Tech Stack
- **Frontend**: HTML5, CSS3, JavaScript (ES6+), Bootstrap 5
- **Storage / Logic Engine**: Client-side Database Engine (`db.js`) + LocalStorage
- **Backend / Submission Backup**: PHP 8.2 & MySQL (`database.sql`, `admin.php`, `profile.php`, `analyze.php`)
