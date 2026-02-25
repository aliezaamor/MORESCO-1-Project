<?php
// Migrate missing rows from SQLite (database/database.sqlite) to MySQL (from .env)
// Safe merge: checks for duplicates and maps old IDs to new IDs for relations.


$env = __DIR__ . '/../.env';
if (!file_exists($env)) {
    echo ".env not found\n";
    exit(1);
}
$dotenv = parse_ini_file($env, false, INI_SCANNER_RAW);
// minimal parse: .env format is KEY=VALUE, parse into array
$envData = [];
foreach (file($env, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if ($line[0] === '#') continue;
    $parts = explode('=', $line, 2);
    if (count($parts) === 2) $envData[trim($parts[0])] = trim($parts[1]);
}

$sqliteFile = __DIR__ . '/../database/database.sqlite';
if (!file_exists($sqliteFile)) { echo "SQLite file not found: $sqliteFile\n"; exit(1);} 

try {
    $sqlite = new PDO('sqlite:' . $sqliteFile);
    $sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    echo "Failed opening sqlite: " . $e->getMessage() . "\n"; exit(1);
}

// MySQL connection details from .env
$host = $envData['DB_HOST'] ?? '127.0.0.1';
$port = $envData['DB_PORT'] ?? '3306';
$db   = $envData['DB_DATABASE'] ?? 'moresco';
$user = $envData['DB_USERNAME'] ?? 'root';
$pass = $envData['DB_PASSWORD'] ?? '';

try {
    $mysql = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (Exception $e) {
    echo "Failed opening mysql: " . $e->getMessage() . "\n"; exit(1);
}

echo "Connected to SQLite and MySQL.\n";

// Helper to check table existence in sqlite
function tableExists($pdo, $table) {
    $stmt = $pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name=?");
    $stmt->execute([$table]);
    return $stmt->fetchColumn() !== false;
}

$mapping = [
    'users' => [],
    'contacts' => [],
    'messages' => [],
];

$mysql->beginTransaction();
$mysql->exec('SET FOREIGN_KEY_CHECKS=0');

// 1) Users
if (tableExists($sqlite, 'users')) {
    echo "\nMigrating users...\n";
    $rows = $sqlite->query("SELECT * FROM users")->fetchAll(PDO::FETCH_ASSOC);
    $insert = $mysql->prepare('INSERT INTO users (name,email,email_verified_at,password,remember_token,created_at,updated_at) VALUES (?,?,?,?,?,?,?)');
    $find = $mysql->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    foreach ($rows as $r) {
        if (empty($r['email'])) continue;
        $find->execute([$r['email']]);
        $exists = $find->fetchColumn();
        if ($exists) {
            $mapping['users'][$r['id']] = $exists;
            continue;
        }
        $insert->execute([
            $r['name'] ?? null,
            $r['email'] ?? null,
            $r['email_verified_at'] ?? null,
            $r['password'] ?? null,
            $r['remember_token'] ?? null,
            $r['created_at'] ?? null,
            $r['updated_at'] ?? null,
        ]);
        $mapping['users'][$r['id']] = $mysql->lastInsertId();
        echo "Inserted user {$r['email']} as id " . $mapping['users'][$r['id']] . "\n";
    }
}

// 2) Contacts
if (tableExists($sqlite, 'contacts')) {
    echo "\nMigrating contacts...\n";
    $rows = $sqlite->query("SELECT * FROM contacts")->fetchAll(PDO::FETCH_ASSOC);
    $insert = $mysql->prepare('INSERT INTO contacts (name,phone_number,email,created_at,updated_at,last_keyword_id) VALUES (?,?,?,?,?,?)');
    $find = $mysql->prepare('SELECT id FROM contacts WHERE phone_number = ? LIMIT 1');
    foreach ($rows as $r) {
        $phone = $r['phone_number'] ?? null;
        if ($phone) {
            $find->execute([$phone]);
            $exists = $find->fetchColumn();
            if ($exists) { $mapping['contacts'][$r['id']] = $exists; continue; }
        }
        $insert->execute([
            $r['name'] ?? null,
            $r['phone_number'] ?? null,
            $r['email'] ?? null,
            $r['created_at'] ?? null,
            $r['updated_at'] ?? null,
            $r['last_keyword_id'] ?? null,
        ]);
        $mapping['contacts'][$r['id']] = $mysql->lastInsertId();
        echo "Inserted contact {$r['phone_number']} as id " . $mapping['contacts'][$r['id']] . "\n";
    }
}

// 3) Messages
if (tableExists($sqlite, 'messages')) {
    echo "\nMigrating messages...\n";
    $rows = $sqlite->query("SELECT * FROM messages")->fetchAll(PDO::FETCH_ASSOC);
    $insert = $mysql->prepare('INSERT INTO messages (user_id,content,type,created_at,updated_at) VALUES (?,?,?,?,?)');
    $find = $mysql->prepare('SELECT id FROM messages WHERE content = ? AND created_at = ? LIMIT 1');
    foreach ($rows as $r) {
        $content = $r['content'] ?? '';
        $created = $r['created_at'] ?? null;
        $find->execute([$content, $created]);
        $exists = $find->fetchColumn();
        if ($exists) { $mapping['messages'][$r['id']] = $exists; continue; }
        $oldUserId = $r['user_id'] ?? null;
        $newUserId = null;
        if ($oldUserId !== null && isset($mapping['users'][$oldUserId])) $newUserId = $mapping['users'][$oldUserId];
        $insert->execute([
            $newUserId,
            $r['content'] ?? null,
            $r['type'] ?? null,
            $r['created_at'] ?? null,
            $r['updated_at'] ?? null,
        ]);
        $mapping['messages'][$r['id']] = $mysql->lastInsertId();
        echo "Inserted message id {$r['id']} as " . $mapping['messages'][$r['id']] . "\n";
    }
}

// 4) Message recipients
if (tableExists($sqlite, 'message_recipients')) {
    echo "\nMigrating message_recipients...\n";
    $rows = $sqlite->query("SELECT * FROM message_recipients")->fetchAll(PDO::FETCH_ASSOC);
    $insert = $mysql->prepare('INSERT INTO message_recipients (message_id,contact_id,status,error_message,created_at,updated_at) VALUES (?,?,?,?,?,?)');
    $find = $mysql->prepare('SELECT id FROM message_recipients WHERE message_id = ? AND contact_id = ? LIMIT 1');
    foreach ($rows as $r) {
        $oldMsg = $r['message_id'];
        $oldContact = $r['contact_id'];
        $newMsg = $mapping['messages'][$oldMsg] ?? null;
        $newContact = $mapping['contacts'][$oldContact] ?? null;
        if (!$newMsg || !$newContact) {
            echo "Skipping recipient row id {$r['id']} because mapping missing\n"; continue;
        }
        $find->execute([$newMsg, $newContact]);
        $exists = $find->fetchColumn();
        if ($exists) continue;
        $insert->execute([
            $newMsg,
            $newContact,
            $r['status'] ?? null,
            $r['error_message'] ?? null,
            $r['created_at'] ?? null,
            $r['updated_at'] ?? null,
        ]);
        echo "Inserted message_recipient for message $newMsg contact $newContact\n";
    }
}

$mysql->exec('SET FOREIGN_KEY_CHECKS=1');
$mysql->commit();

echo "\nMigration complete.\n";

// show summary
foreach ($mapping as $k => $m) {
    echo ucfirst($k) . " mapped: " . count($m) . "\n";
}

exit(0);
