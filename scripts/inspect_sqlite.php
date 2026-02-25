<?php
$dbFile = __DIR__ . '/../database/database.sqlite';
if (!file_exists($dbFile)) {
    echo "SQLite file not found: $dbFile\n";
    exit(1);
}
try {
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    echo "Failed to open SQLite: " . $e->getMessage() . "\n";
    exit(1);
}
$tables = ['messages','contacts','keywords','groups','users','message_recipients','contact_group'];
foreach ($tables as $table) {
    // check if table exists
    $stmt = $pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name=?");
    $stmt->execute([$table]);
    $exists = $stmt->fetchColumn();
    if (!$exists) continue;
    echo "\n== $table (last 20 rows) ==\n";
    try {
        $rows = $pdo->query("SELECT * FROM $table ORDER BY id DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
        if (!$rows) { echo "(no rows)\n"; continue; }
        foreach ($rows as $r) {
            echo json_encode($r, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) . "\n";
        }
    } catch (Exception $e) {
        // if no id column or ordering fails, fallback
        try {
            $rows = $pdo->query("SELECT * FROM $table LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $r) {
                echo json_encode($r, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) . "\n";
            }
        } catch (Exception $e2) {
            echo "(could not read rows: " . $e2->getMessage() . ")\n";
        }
    }
}
echo "\nDone.\n";
