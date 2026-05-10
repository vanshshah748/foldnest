<?php
// backend/api/test_address_api.php
// DIAGNOSTIC TOOL — Open in browser to test the address save flow
// URL: http://localhost/foldnest/backend/api/test_address_api.php
// DELETE THIS FILE AFTER DEBUGGING!

header("Content-Type: text/html; charset=UTF-8");

echo "<h1>🔍 FoldNest Address API Diagnostic</h1>";
echo "<hr>";

// =============================================
// TEST 1: Database Connection
// =============================================
echo "<h3>Test 1: Database Connection</h3>";
include_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

if ($db === null) {
    echo "<p style='color:red;'>❌ FAIL — Database connection returned null.</p>";
    echo "<p>Fix: Check if MySQL is running in XAMPP. Check .env file DB_HOST, DB_NAME, DB_USER, DB_PASS.</p>";
    die("<hr><p style='color:red; font-weight:bold;'>Cannot continue — fix database connection first.</p>");
} else {
    echo "<p style='color:green;'>✅ PASS — Database connected successfully.</p>";
}

// =============================================
// TEST 2: user_addresses table exists
// =============================================
echo "<h3>Test 2: user_addresses Table</h3>";
try {
    $stmt = $db->query("SHOW TABLES LIKE 'user_addresses'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color:green;'>✅ PASS — Table 'user_addresses' exists.</p>";
        
        // Show columns
        $cols = $db->query("DESCRIBE user_addresses")->fetchAll(PDO::FETCH_ASSOC);
        echo "<table border='1' cellpadding='5'><tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th></tr>";
        foreach ($cols as $col) {
            echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:red;'>❌ FAIL — Table 'user_addresses' does NOT exist!</p>";
        echo "<p><strong>Fix:</strong> Run the migration: <a href='../run_migration.php'>Click here to run migration</a></p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ ERROR: " . $e->getMessage() . "</p>";
}

// =============================================
// TEST 3: Users table has data
// =============================================
echo "<h3>Test 3: Users Table</h3>";
try {
    $stmt = $db->query("SELECT id, full_name, email FROM users LIMIT 5");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($users) > 0) {
        echo "<p style='color:green;'>✅ PASS — Found " . count($users) . " user(s).</p>";
        echo "<table border='1' cellpadding='5'><tr><th>ID</th><th>Name</th><th>Email</th></tr>";
        foreach ($users as $u) {
            echo "<tr><td>{$u['id']}</td><td>{$u['full_name']}</td><td>{$u['email']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:orange;'>⚠️ No users found. Register a user first.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ ERROR: " . $e->getMessage() . "</p>";
}

// =============================================
// TEST 4: CORS Headers
// =============================================
echo "<h3>Test 4: API File Check</h3>";
$apiDir = __DIR__;
$files = ['add_address.php', 'update_address.php', 'delete_address.php', 'get_addresses.php'];
foreach ($files as $f) {
    if (file_exists($apiDir . '/' . $f)) {
        echo "<p style='color:green;'>✅ $f — exists</p>";
    } else {
        echo "<p style='color:red;'>❌ $f — MISSING!</p>";
    }
}

// =============================================
// TEST 5: Quick Insert Test
// =============================================
echo "<h3>Test 5: Insert Test (if table exists)</h3>";
try {
    $stmt = $db->query("SHOW TABLES LIKE 'user_addresses'");
    if ($stmt->rowCount() > 0) {
        // Check if any user exists to test with
        $userStmt = $db->query("SELECT id FROM users LIMIT 1");
        if ($userStmt->rowCount() > 0) {
            $testUserId = $userStmt->fetch()['id'];
            echo "<p style='color:green;'>✅ Ready to test — You can save an address for user ID: $testUserId</p>";
        } else {
            echo "<p style='color:orange;'>⚠️ No users to test with. Register first.</p>";
        }
    } else {
        echo "<p style='color:red;'>⚠️ Skipped — Table doesn't exist. Run migration first.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ ERROR: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p style='color:#666;'>🔒 Delete this file after debugging: <code>backend/api/test_address_api.php</code></p>";
?>
