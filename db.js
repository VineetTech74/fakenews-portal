/**
 * VeriTruth Client-Side Database & Verification Engine (db.js)
 * Enables 100% serverless operation on GitHub Pages without XAMPP or PHP.
 */

const DB = (function () {
    const STORAGE_KEY_USERS = "veritruth_users";
    const STORAGE_KEY_REPORTS = "veritruth_reports";
    const STORAGE_KEY_HISTORY = "veritruth_history";
    const STORAGE_KEY_CURRENT_USER = "veritruth_current_user";

    // 1. Initial Seed Data
    const defaultUsers = [
        {
            id: 1,
            name: "Vineet Rajesh Jadhav (Admin)",
            email: "admin@fakenews.com",
            password: "password123",
            role: "Fact Checker",
            age: 22,
            address: "Mumbai, Maharashtra",
            profile_pic: "default-avatar.png",
            created_at: "2026-07-26 18:13"
        },
        {
            id: 2,
            name: "Vineet Jadhav",
            email: "vineet@gmail.com",
            password: "password123",
            role: "Standard User",
            age: 21,
            address: "Mumbai, Maharashtra",
            profile_pic: "default-avatar.png",
            created_at: "2026-08-18 19:26"
        }
    ];

    const defaultReports = [
        {
            id: 1,
            user_id: 1,
            user_name: "Vineet Rajesh Jadhav (Admin)",
            headline: "Government to distribute free laptops and medical insurance to all citizens starting next week.",
            platform: "WhatsApp Groups",
            reason: "Phishing link designed to capture sensitive personal bank details.",
            status: "Verified Real",
            date_reported: "2026-07-26 20:12"
        },
        {
            id: 2,
            user_id: 2,
            user_name: "Vineet Jadhav",
            headline: "UNESCO declares Indian national anthem as best national anthem in the world",
            platform: "Twitter / X",
            reason: "Fabricated chain message that keeps resurfacing without any official decree.",
            status: "Fake / Scam",
            date_reported: "2026-07-26 20:59"
        },
        {
            id: 3,
            user_id: 2,
            user_name: "Vineet Jadhav",
            headline: "Viral forward claiming bank accounts will be blocked tonight if PAN is not linked via suspicious bit.ly link",
            platform: "Telegram Forward",
            reason: "Malicious APK download link targeting banking credentials and OTPs.",
            status: "Pending",
            date_reported: "2026-08-18 19:30"
        }
    ];

    const defaultHistory = [
        {
            id: 1,
            user_id: 1,
            user_name: "Vineet Rajesh Jadhav (Admin)",
            headline: "Government announces new education policy guidelines and scholarship matrix",
            trust_score: "90%",
            status: "✓ High Credibility: Official / Regulatory Attribution",
            checked_at: "2026-07-26 19:30"
        },
        {
            id: 2,
            user_id: 1,
            user_name: "Vineet Rajesh Jadhav (Admin)",
            headline: "VIRAL: Unbelievable WhatsApp chip found inside new bank notes guaranteed",
            trust_score: "12%",
            status: "⚠️ High Risk: Known Viral Scam / Misinformation",
            checked_at: "2026-07-26 23:18"
        },
        {
            id: 3,
            user_id: 2,
            user_name: "Vineet Jadhav",
            headline: "ISRO press release: official notification regarding lunar mission orbit trajectory",
            trust_score: "95%",
            status: "✓ High Credibility: Official / Regulatory Attribution",
            checked_at: "2026-08-18 19:59"
        }
    ];

    // Initialize LocalStorage if empty
    function init() {
        if (!localStorage.getItem(STORAGE_KEY_USERS)) {
            localStorage.setItem(STORAGE_KEY_USERS, JSON.stringify(defaultUsers));
        }
        if (!localStorage.getItem(STORAGE_KEY_REPORTS)) {
            localStorage.setItem(STORAGE_KEY_REPORTS, JSON.stringify(defaultReports));
        }
        if (!localStorage.getItem(STORAGE_KEY_HISTORY)) {
            localStorage.setItem(STORAGE_KEY_HISTORY, JSON.stringify(defaultHistory));
        }
    }

    // Helper: read array from localStorage
    function _getList(key) {
        init();
        try {
            return JSON.parse(localStorage.getItem(key)) || [];
        } catch (e) {
            return [];
        }
    }

    // Helper: write array to localStorage
    function _setList(key, list) {
        localStorage.setItem(key, JSON.stringify(list));
    }

    // 2. Authentication & User Management
    function getCurrentUser() {
        init();
        try {
            return JSON.parse(localStorage.getItem(STORAGE_KEY_CURRENT_USER));
        } catch (e) {
            return null;
        }
    }

    function setCurrentUser(user) {
        if (user) {
            localStorage.setItem(STORAGE_KEY_CURRENT_USER, JSON.stringify(user));
        } else {
            localStorage.removeItem(STORAGE_KEY_CURRENT_USER);
        }
    }

    function logout() {
        localStorage.removeItem(STORAGE_KEY_CURRENT_USER);
        // Also call backend logout if accessible
        fetch('logout.php').catch(() => {});
    }

    function login(email, password) {
        init();
        const users = _getList(STORAGE_KEY_USERS);
        const cleanEmail = (email || "").trim().toLowerCase();
        
        const matched = users.find(u => 
            (u.email.toLowerCase() === cleanEmail || u.name.toLowerCase() === cleanEmail) && 
            u.password === password
        );

        if (matched) {
            setCurrentUser(matched);
            return {
                status: "success",
                message: "Welcome back, " + matched.name + "!",
                user: matched
            };
        }

        // Check if user exists but password mismatch
        const userExists = users.some(u => u.email.toLowerCase() === cleanEmail);
        if (userExists) {
            return { status: "error", message: "Incorrect password. Please try again." };
        }
        return { status: "error", message: "No account found with this email. Please create an account." };
    }

    function register(name, email, password) {
        init();
        const users = _getList(STORAGE_KEY_USERS);
        const cleanName = (name || "").trim();
        const cleanEmail = (email || "").trim().toLowerCase();

        if (!cleanName || !cleanEmail || !password) {
            return { status: "error", message: "All fields are required." };
        }
        if (users.some(u => u.email.toLowerCase() === cleanEmail)) {
            return { status: "error", message: "This email address is already registered. Please sign in." };
        }

        const newUser = {
            id: Date.now(),
            name: cleanName,
            email: cleanEmail,
            password: password,
            role: "Standard User",
            age: null,
            address: "",
            profile_pic: "default-avatar.png",
            created_at: new Date().toISOString().slice(0, 16).replace('T', ' ')
        };

        users.push(newUser);
        _setList(STORAGE_KEY_USERS, users);
        setCurrentUser(newUser);

        return {
            status: "success",
            message: "Account created successfully! Welcome, " + cleanName + ".",
            user: newUser
        };
    }

    function updateProfile(userId, data) {
        const users = _getList(STORAGE_KEY_USERS);
        const idx = users.findIndex(u => u.id == userId);
        if (idx === -1) {
            return { status: "error", message: "User not found." };
        }

        users[idx].name = data.name !== undefined ? data.name.trim() : users[idx].name;
        users[idx].role = data.role || users[idx].role;
        users[idx].age = data.age ? parseInt(data.age) : null;
        users[idx].address = data.address !== undefined ? data.address.trim() : users[idx].address;
        if (data.profile_pic) {
            users[idx].profile_pic = data.profile_pic;
        }

        _setList(STORAGE_KEY_USERS, users);

        const current = getCurrentUser();
        if (current && current.id == userId) {
            setCurrentUser(users[idx]);
        }

        return { status: "success", message: "Profile updated successfully!", user: users[idx] };
    }

    function getUsers() {
        return _getList(STORAGE_KEY_USERS);
    }

    function deleteUser(userId) {
        let users = _getList(STORAGE_KEY_USERS);
        users = users.filter(u => u.id != userId);
        _setList(STORAGE_KEY_USERS, users);
        return { status: "success", message: "User deleted successfully." };
    }

    // 3. Community Reports
    function getReports(statusFilter) {
        const reports = _getList(STORAGE_KEY_REPORTS);
        if (!statusFilter || statusFilter === 'all') return reports;
        return reports.filter(r => r.status === statusFilter);
    }

    function addReport(reportData) {
        const reports = _getList(STORAGE_KEY_REPORTS);
        const currentUser = getCurrentUser();

        const newReport = {
            id: Date.now(),
            user_id: currentUser ? currentUser.id : null,
            user_name: currentUser ? currentUser.name : "Anonymous",
            headline: (reportData.title || "").trim(),
            platform: (reportData.platform || "Web / Social Media").trim(),
            reason: (reportData.reason || "").trim(),
            status: "Pending",
            date_reported: new Date().toISOString().slice(0, 16).replace('T', ' ')
        };

        reports.unshift(newReport);
        _setList(STORAGE_KEY_REPORTS, reports);
        return { status: "success", message: "Report submitted successfully! Our moderators will review it." };
    }

    function updateReportStatus(reportId, newStatus) {
        const reports = _getList(STORAGE_KEY_REPORTS);
        const report = reports.find(r => r.id == reportId);
        if (report) {
            report.status = newStatus;
            _setList(STORAGE_KEY_REPORTS, reports);
            return { status: "success", message: `Report #${reportId} status updated to ${newStatus}.` };
        }
        return { status: "error", message: "Report not found." };
    }

    function deleteReport(reportId) {
        let reports = _getList(STORAGE_KEY_REPORTS);
        reports = reports.filter(r => r.id != reportId);
        _setList(STORAGE_KEY_REPORTS, reports);
        return { status: "success", message: "Report deleted successfully." };
    }

    // 4. Verification History
    function getHistory(userId) {
        const history = _getList(STORAGE_KEY_HISTORY);
        if (userId) {
            return history.filter(h => h.user_id == userId);
        }
        return history;
    }

    function addHistory(record) {
        const history = _getList(STORAGE_KEY_HISTORY);
        const currentUser = getCurrentUser();

        const newEntry = {
            id: Date.now(),
            user_id: currentUser ? currentUser.id : null,
            user_name: currentUser ? currentUser.name : (record.user_name || "Guest"),
            headline: record.headline,
            trust_score: record.trustScore + "%",
            status: record.verdict,
            checked_at: new Date().toISOString().slice(0, 16).replace('T', ' ')
        };

        history.unshift(newEntry);
        if (history.length > 100) history.pop();
        _setList(STORAGE_KEY_HISTORY, history);
    }

    function deleteHistory(historyId) {
        let history = _getList(STORAGE_KEY_HISTORY);
        history = history.filter(h => h.id != historyId);
        _setList(STORAGE_KEY_HISTORY, history);
        return { status: "success", message: "History record deleted." };
    }

    // 5. Intelligent Evidence-Based News Verification Engine
    function analyzeNews(headline, content = "") {
        const trimmed = (headline || "").trim();

        if (!trimmed) {
            return {
                status: "error",
                error_type: "empty",
                headline: "",
                trustScore: 0,
                verdict: "❌ Headline Required",
                recommendation: "Please enter a news headline, claim, or viral forward to verify."
            };
        }

        const words = trimmed.split(/\s+/);
        const charLength = trimmed.length;

        // Step 1: Minimum Length & Word Count
        if (charLength < 8 || words.length < 2) {
            return {
                status: "warning",
                error_type: "too_short",
                headline: trimmed,
                trustScore: 0,
                verdict: "❌ Incomplete / Too Short",
                recommendation: "Please provide a complete news claim, statement, or headline (at least 3 words) to analyze."
            };
        }

        // Step 2: Gibberish & Spam Text Detection
        let isGibberish = false;
        if (/(.)\1{3,}/.test(trimmed)) isGibberish = true;
        if (/[bcdfghjklmnpqrstvwxyzBCDFGHJKLMNPQRSTVWXYZ]{5,}/.test(trimmed)) isGibberish = true;

        for (const w of words) {
            const alphaWord = w.replace(/[^a-zA-Z]/g, '');
            if (alphaWord.length >= 4 && !/[aeiouyAEIOUY]/.test(alphaWord)) {
                isGibberish = true;
                break;
            }
        }

        const cleanLetters = trimmed.replace(/[^a-zA-Z]/g, '');
        const vowels = cleanLetters.match(/[aeiouAEIOU]/g);
        const vowelCount = vowels ? vowels.length : 0;
        if (cleanLetters.length > 8 && (vowelCount / cleanLetters.length < 0.12)) {
            isGibberish = true;
        }

        if (isGibberish) {
            return {
                status: "warning",
                error_type: "gibberish",
                headline: trimmed,
                trustScore: 0,
                verdict: "❌ Unrecognized / Invalid Text",
                recommendation: "The entered text contains random keystrokes or meaningless characters. Please enter a valid news claim or headline."
            };
        }

        const lowerText = (trimmed + " " + content).toLowerCase();

        // Step 3: Personal Remarks / Non-News Statements Filter
        const personalPatterns = [
            /^(hi|hello|hey|good morning|good evening|good night|how are you|who are you|what is your name)\b/i,
            /\b(i am|i feel|i think|i want|i ate|i have|i played|i like|i love|my name is|my dog|my cat|my friend|my mom|my dad|my teacher)\b/i,
            /\b(today i|yesterday i|tomorrow i|we played|we ate|i went to|we went to)\b/i,
            /^(what is this|testing|test 123|just checking|sample test|random test)\b/i,
            /\b(ate pizza|feeling sleepy|going to sleep|doing homework|watching movie|having lunch|having dinner|went for a walk|playing game|sitting in room)\b/i
        ];

        if (personalPatterns.some(p => p.test(lowerText))) {
            return {
                status: "warning",
                error_type: "non_news",
                headline: trimmed,
                trustScore: 0,
                verdict: "⚠️ Not a Recognized News Event",
                recommendation: "The text entered appears to be a personal thought, casual chat, or daily life remark rather than a verifiable public news event or claim."
            };
        }

        // Step 4: Database Cross-Referencing
        const reports = _getList(STORAGE_KEY_REPORTS);
        const dbMatch = reports.find(r => 
            lowerText.includes(r.headline.toLowerCase()) || 
            r.headline.toLowerCase().includes(lowerText)
        );

        // Step 5: Fake Signatures & Viral Scams
        const fakeSignatures = [
            "10 lakh", "10lakh", "free money", "giveaway", "free laptop scheme",
            "modi giving free", "pm giving free", "transfer money immediately",
            "lottery winner", "secret trick to earn", "forwarded as received",
            "10000 free", "free recharge 3 months", "earn 50000 daily from home",
            "click here to claim reward", "bank account blocked update pan",
            "electricity disconnected tonight call", "share with 10 groups to activate",
            "gps nano chip inside currency", "unesco declared national anthem",
            "miracle cure kills cancer in 3 days", "5g radiation virus cure",
            "nasa confirmed world ending on", "world health organization declared lockdown again",
            "magic water cures all diabetes", "secret ingredient doctors dont want you to know"
        ];

        // Step 6: Official & Regulatory Signatures
        const officialSignatures = [
            "press information bureau", "pib fact check", "pib", "isro official", "isro press release",
            "reserve bank of india", "rbi notification", "supreme court of india", "delhi high court",
            "gazette of india", "official notification gov.in", "election commission of india",
            "cbse official circular", "world health organization", "reuters news", "bbc reality check",
            "press trust of india", "ministry of finance", "ministry of external affairs",
            "ministry of health", "cabinet committee approved", "parliament passed"
        ];

        const matchedFake = fakeSignatures.filter(f => lowerText.includes(f));
        const matchedOfficial = officialSignatures.filter(o => lowerText.includes(o));

        let trustScore = 50;
        let verdict = "";
        let recommendation = "";

        if (dbMatch && dbMatch.status === 'Fake / Scam') {
            trustScore = 10;
            verdict = "⚠️ Confirmed Fake / Viral Scam (Database Match)";
            recommendation = "This claim matches a confirmed fake news record in our portal database. Remarks: " + (dbMatch.reason || "Flagged as misinformation by verified reviewers.");
        } else if (dbMatch && dbMatch.status === 'Verified Real') {
            trustScore = 96;
            verdict = "✓ Verified Authentic (Database Match)";
            recommendation = "This headline matches an official verified record in our fact-checking database.";
        } else if (matchedFake.length > 0) {
            trustScore = matchedFake.length > 1 ? 12 : 22;
            verdict = "⚠️ High Risk: Known Viral Scam / Misinformation";
            recommendation = "Contains documented misinformation signatures: ['" + matchedFake.slice(0, 3).join("', '") + "']. Official authorities advise not sharing this forward.";
        } else if (matchedOfficial.length > 0) {
            trustScore = matchedOfficial.length > 1 ? 95 : 88;
            verdict = "✓ High Credibility: Official / Regulatory Attribution";
            recommendation = "Contains primary attribution to recognized official authorities: ['" + matchedOfficial.slice(0, 2).join("', '") + "']. Reliable context.";
        } else {
            const newsKeywords = [
                'government', 'court', 'police', 'minister', 'president', 'announced', 
                'approved', 'launched', 'hospital', 'railway', 'school', 'university', 
                'traffic', 'weather', 'budget', 'tax', 'economy', 'treaty', 'election', 
                'strike', 'cyclone', 'flood', 'isro', 'rbi', 'bank', 'parliament', 
                'assembly', 'supreme court', 'high court', 'modi', 'prime minister', 'cabinet'
            ];

            const hasNewsContext = newsKeywords.some(nk => lowerText.includes(nk));

            if (hasNewsContext) {
                trustScore = 50;
                verdict = "⚡ Unconfirmed Claim: No Official Record Found";
                recommendation = "This statement mentions public topics but has not been verified in official government gazettes or fact-checking databases. Cross-check with established news outlets before forwarding.";
            } else {
                trustScore = 0;
                verdict = "❓ Unverified Statement / Not a News Claim";
                recommendation = "No verifiable public news record, event, or official release matches this text. Please verify with credible media channels.";
            }
        }

        // Save to History (Non-rejected)
        addHistory({
            headline: trimmed,
            trustScore: trustScore,
            verdict: verdict
        });

        return {
            status: "success",
            headline: trimmed,
            trustScore: trustScore,
            verdict: verdict,
            recommendation: recommendation
        };
    }

    // Auto-initialize on load
    init();

    return {
        init,
        getCurrentUser,
        setCurrentUser,
        logout,
        login,
        register,
        updateProfile,
        getUsers,
        deleteUser,
        getReports,
        addReport,
        updateReportStatus,
        deleteReport,
        getHistory,
        addHistory,
        deleteHistory,
        analyzeNews
    };
})();
