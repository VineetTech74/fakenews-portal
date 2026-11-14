<?php
header('Content-Type: application/json');
require_once 'db_config.php';

$headline = trim($_POST['headline'] ?? '');
$content  = trim($_POST['content'] ?? '');

if (empty($headline)) {
    echo json_encode([
        "status"         => "error",
        "error_type"     => "empty",
        "headline"       => "",
        "trustScore"     => 0,
        "verdict"        => "❌ Headline Required",
        "recommendation" => "Please enter a news headline, claim, or viral forward to verify."
    ]);
    exit;
}

// -------------------------------------------------------------
// STEP 1: Basic Length & Word Count Validation
// -------------------------------------------------------------
$words = preg_split('/\s+/', $headline);
$wordCount = count($words);
$charLength = mb_strlen($headline);

if ($charLength < 8 || $wordCount < 2) {
    echo json_encode([
        "status"         => "warning",
        "error_type"     => "too_short",
        "headline"       => $headline,
        "trustScore"     => 0,
        "verdict"        => "❌ Incomplete / Too Short",
        "recommendation" => "Please provide a complete news claim, statement, or headline (at least 3 words) to analyze."
    ]);
    exit;
}

// -------------------------------------------------------------
// STEP 2: Gibberish & Spam Text Detection
// -------------------------------------------------------------
$isGibberish = false;

// 1. Repetitive characters like "aaaaa", "11111"
if (preg_match('/(.)\1{3,}/', $headline)) {
    $isGibberish = true;
}

// 2. Words with 5+ consonants in a row (e.g. asdfgh, zxcvbn, dfghjk)
if (preg_match('/[bcdfghjklmnpqrstvwxyzBCDFGHJKLMNPQRSTVWXYZ]{5,}/', $headline)) {
    $isGibberish = true;
}

// 3. Words longer than 4 chars with zero vowels
foreach ($words as $w) {
    $alphaWord = preg_replace('/[^a-zA-Z]/', '', $w);
    if (strlen($alphaWord) >= 4 && !preg_match('/[aeiouyAEIOUY]/', $alphaWord)) {
        $isGibberish = true;
        break;
    }
}

// 4. Global vowel-to-consonant ratio
$cleanLetters = preg_replace('/[^a-zA-Z]/', '', $headline);
$vowelCount = preg_match_all('/[aeiouAEIOU]/', $cleanLetters);
$totalLetters = strlen($cleanLetters);
if ($totalLetters > 8 && ($vowelCount / $totalLetters < 0.12)) {
    $isGibberish = true;
}

if ($isGibberish) {
    echo json_encode([
        "status"         => "warning",
        "error_type"     => "gibberish",
        "headline"       => $headline,
        "trustScore"     => 0,
        "verdict"        => "❌ Unrecognized / Invalid Text",
        "recommendation" => "The entered text contains random keystrokes or meaningless characters. Please enter a valid news claim or headline."
    ]);
    exit;
}

$lowerText = strtolower($headline . ' ' . $content);

// -------------------------------------------------------------
// STEP 3: Personal Remarks / Non-News Statements Filter
// -------------------------------------------------------------
$personalNonNewsPatterns = [
    '/^(hi|hello|hey|good morning|good evening|good night|how are you|who are you|what is your name)\b/i',
    '/\b(i am|i feel|i think|i want|i ate|i have|i played|i like|i love|my name is|my dog|my cat|my friend|my mom|my dad|my teacher)\b/i',
    '/\b(today i|yesterday i|tomorrow i|we played|we ate|i went to|we went to)\b/i',
    '/^(what is this|testing|test 123|just checking|sample test|random test)\b/i',
    '/\b(ate pizza|feeling sleepy|going to sleep|doing homework|watching movie|having lunch|having dinner|went for a walk|playing game|sitting in room)\b/i'
];

$isPersonalNonNews = false;
foreach ($personalNonNewsPatterns as $pattern) {
    if (preg_match($pattern, $lowerText)) {
        $isPersonalNonNews = true;
        break;
    }
}

if ($isPersonalNonNews) {
    echo json_encode([
        "status"         => "warning",
        "error_type"     => "non_news",
        "headline"       => $headline,
        "trustScore"     => 0,
        "verdict"        => "⚠️ Not a Recognized News Event",
        "recommendation" => "The text entered appears to be a personal thought, casual chat, or daily life remark rather than a verifiable public news event or claim."
    ]);
    exit;
}

// -------------------------------------------------------------
// STEP 4: Database Cross-Referencing (Reported & Verified Claims)
// -------------------------------------------------------------
$dbMatchFound = false;
$dbStatus = "";
$dbReason = "";

$searchKeyword = "%" . $headline . "%";
$dbCheck = $conn->prepare("SELECT headline, reason, status FROM reported_news WHERE headline LIKE ? OR ? LIKE CONCAT('%', headline, '%') LIMIT 1");
if ($dbCheck) {
    $dbCheck->bind_param("ss", $searchKeyword, $headline);
    $dbCheck->execute();
    $dbRes = $dbCheck->get_result();
    if ($repRow = $dbRes->fetch_assoc()) {
        $dbMatchFound = true;
        $dbStatus = $repRow['status'] ?? 'Pending';
        $dbReason = $repRow['reason'] ?? '';
    }
    $dbCheck->close();
}

// -------------------------------------------------------------
// STEP 5: Misinformation / Viral Scam Signals & Keywords
// -------------------------------------------------------------
$fakeSignatures = [
    // Free Money / Lottery / Phishing
    "10 lakh", "10lakh", "free money", "giveaway", "free laptop scheme",
    "modi giving free", "pm giving free", "transfer money immediately",
    "lottery winner", "secret trick to earn", "forwarded as received",
    "10000 free", "free recharge 3 months", "earn 50000 daily from home",
    "click here to claim reward", "bank account blocked update pan",
    "electricity disconnected tonight call", "share with 10 groups to activate",
    // Viral Fabricated Hoaxes
    "gps nano chip inside currency", "unesco declared national anthem",
    "miracle cure kills cancer in 3 days", "5g radiation virus cure",
    "nasa confirmed world ending on", "world health organization declared lockdown again",
    "magic water cures all diabetes", "secret ingredient doctors dont want you to know"
];

// -------------------------------------------------------------
// STEP 6: Credible Official / Verified Journalistic Entities
// -------------------------------------------------------------
$officialSignatures = [
    "press information bureau", "pib fact check", "pib", "isro official", "isro press release",
    "reserve bank of india", "rbi notification", "supreme court of india", "delhi high court",
    "gazette of india", "official notification gov.in", "election commission of india",
    "cbse official circular", "world health organization", "reuters news", "bbc reality check",
    "press trust of india", "ministry of finance", "ministry of external affairs",
    "ministry of health", "cabinet committee approved", "parliament passed"
];

$matchedFake = [];
foreach ($fakeSignatures as $fakePhrase) {
    if (stripos($lowerText, $fakePhrase) !== false) {
        $matchedFake[] = $fakePhrase;
    }
}

$matchedOfficial = [];
foreach ($officialSignatures as $officialPhrase) {
    if (stripos($lowerText, $officialPhrase) !== false) {
        $matchedOfficial[] = $officialPhrase;
    }
}

// -------------------------------------------------------------
// STEP 7: Evidence-Based Verdict Computation (No Random Scores)
// -------------------------------------------------------------
$trustScore = 50;
$verdict = "";
$recommendation = "";

if ($dbMatchFound && $dbStatus === 'Fake / Scam') {
    $trustScore = 10;
    $verdict = "⚠️ Confirmed Fake / Viral Scam (Database Match)";
    $recommendation = "This claim matches a confirmed fake news record in our portal database. Remarks: " . ($dbReason ?: "Flagged as misinformation by verified reviewers.");
} elseif ($dbMatchFound && $dbStatus === 'Verified Real') {
    $trustScore = 96;
    $verdict = "✓ Verified Authentic (Database Match)";
    $recommendation = "This headline matches an official verified record in our fact-checking database.";
} elseif (count($matchedFake) > 0) {
    $trustScore = count($matchedFake) > 1 ? 12 : 22;
    $verdict = "⚠️ High Risk: Known Viral Scam / Misinformation";
    $recommendation = "Contains documented misinformation signatures: ['" . implode("', '", array_slice($matchedFake, 0, 3)) . "']. Official authorities advise not sharing this forward.";
} elseif (count($matchedOfficial) > 0) {
    $trustScore = count($matchedOfficial) > 1 ? 95 : 88;
    $verdict = "✓ High Credibility: Official / Regulatory Attribution";
    $recommendation = "Contains primary attribution to recognized official authorities: ['" . implode("', '", array_slice($matchedOfficial, 0, 2)) . "']. Reliable context.";
} else {
    // Check if the headline has journalistic structure (Subject + Action)
    $newsKeywords = ['government', 'court', 'police', 'minister', 'president', 'announced', 'approved', 'launched', 'hospital', 'railway', 'school', 'university', 'traffic', 'weather', 'budget', 'tax', 'economy', 'treaty', 'election', 'strike', 'cyclone', 'flood', 'isro', 'rbi', 'bank', 'parliament', 'assembly', 'supreme court', 'high court', 'modi', 'prime minister', 'cabinet'];
    
    $hasNewsContext = false;
    foreach ($newsKeywords as $nk) {
        if (stripos($lowerText, $nk) !== false) {
            $hasNewsContext = true;
            break;
        }
    }

    if ($hasNewsContext) {
        $trustScore = 50;
        $verdict = "⚡ Unconfirmed Claim: No Official Record Found";
        $recommendation = "This statement mentions public topics but has not been verified in official government gazettes or fact-checking databases. Cross-check with established news outlets before forwarding.";
    } else {
        $trustScore = 0;
        $verdict = "❓ Unverified Statement / Not a News Claim";
        $recommendation = "No verifiable public news record, event, or official release matches this text. Please verify with credible media channels.";
    }
}

// -------------------------------------------------------------
// STEP 8: Save to Search History
// -------------------------------------------------------------
$userId = $_SESSION['user_id'] ?? NULL;
$saveStmt = $conn->prepare("INSERT INTO search_history (user_id, headline, trust_score, status, checked_at) VALUES (?, ?, ?, ?, NOW())");
if ($saveStmt) {
    $saveStmt->bind_param("isis", $userId, $headline, $trustScore, $verdict);
    $saveStmt->execute();
    $saveStmt->close();
}

echo json_encode([
    "status"         => "success",
    "headline"       => $headline,
    "trustScore"     => $trustScore,
    "verdict"        => $verdict,
    "recommendation" => $recommendation
]);
?>
