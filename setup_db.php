<?php
// Simple DB setup script for local development (SQLite)
// Run: php setup_db.php

$dataDir = __DIR__ . '/data';
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}

$dbFile = $dataDir . '/database.sqlite';
$dsn = 'sqlite:' . $dbFile;

try {
    $pdo = new PDO($dsn);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create tables
    $pdo->exec("CREATE TABLE IF NOT EXISTS news (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        content TEXT NOT NULL,
        image TEXT,
        date_posted TEXT NOT NULL,
        category TEXT
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS documents (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        description TEXT,
        category TEXT,
        drive_id TEXT,
        drive_url TEXT,
        uploaded_at TEXT,
        tags TEXT,
        views INTEGER DEFAULT 0
    )");

    // Insert sample news if empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM news");
    if ($stmt->fetchColumn() == 0) {
        $now = date('Y-m-d H:i:s');
        $pdo->prepare("INSERT INTO news (title, content, image, date_posted, category) VALUES (?, ?, ?, ?, ?)")
            ->execute([
                'งานเปิดตัวโครงการ',
                'รายละเอียดเบื้องต้นของการเปิดตัวโครงการ สาระสำคัญ และการเข้าร่วมกิจกรรม',
                '',
                $now,
                'ประกาศ'
            ]);
        $pdo->prepare("INSERT INTO news (title, content, image, date_posted, category) VALUES (?, ?, ?, ?, ?)")
            ->execute([
                'รับสมัครอาสาสมัคร',
                'เชิญชวนผู้สนใจสมัครเป็นอาสาสมัครเพื่อร่วมกิจกรรมต่าง ๆ',
                '',
                $now,
                'ข่าว'
            ]);
    }

    // Insert sample documents if empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM documents");
    if ($stmt->fetchColumn() == 0) {
        $now = date('Y-m-d H:i:s');
        $pdo->prepare("INSERT INTO documents (title, description, category, drive_id, drive_url, uploaded_at, tags, views) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute([
                'แบบฟอร์มขออนุมัติ',
                'แบบฟอร์มสำหรับการขออนุมัติการดำเนินงาน',
                'แบบฟอร์ม',
                'drive-file-id-1',
                'https://drive.google.com/file/d/drive-file-id-1/view?usp=sharing',
                $now,
                'form,approval',
                0
            ]);
        $pdo->prepare("INSERT INTO documents (title, description, category, drive_id, drive_url, uploaded_at, tags, views) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute([
                'แผนปฏิบัติการ 2568',
                'แผนปฏิบัติการประจำปี 2568',
                'แผนงาน',
                'drive-file-id-2',
                'https://drive.google.com/file/d/drive-file-id-2/view?usp=sharing',
                $now,
                'plan,2568',
                0
            ]);
    }

    echo "Database created/updated at: " . $dbFile . PHP_EOL;
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

?>
