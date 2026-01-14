<?php
// db_init.sample.php
// データベースの構造を定義する設計図の見本。


require_once 'config.php'; 

// 処理：アクセス環境の判別（ローカルか本番か）
const LOCAL_HOSTS = ['localhost', '127.0.0.1'];
$is_local = in_array($_SERVER['HTTP_HOST'], LOCAL_HOSTS);

if ($is_local) {
    // ローカル開発環境の接続設定
    $db_host = 'localhost';
    $db_name = 'your_local_db_name';
    $db_user = 'root';
    $db_pass = ''; 
} else {
    // 本番環境（config.phpで定義した定数を使用）
    $db_host = DB_HOST;
    $db_name = DB_NAME;
    $db_user = DB_USER;
    $db_pass = DB_PASS;
}

try {
    // 処理：データベースへ接続（さくらサーバー等の制約に合わせたDSN指定）
    $dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";
    $pdo = new PDO($dsn, $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // --- 処理：テーブルの作成（予約語回避のためバッククォートを使用） ---

    // 1. 家族グループテーブル
    $pdo->exec("CREATE TABLE IF NOT EXISTS `groups` (
        id INT AUTO_INCREMENT PRIMARY KEY,
        group_name VARCHAR(100) NOT NULL,
        login_pass VARCHAR(255) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");

    // 2. ユーザーテーブル
    $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
        id INT AUTO_INCREMENT PRIMARY KEY,
        group_id INT NOT NULL,
        name VARCHAR(50) NOT NULL,
        role ENUM('father', 'mother', 'child', 'other') NOT NULL,
        birthday DATE,
        icon_url VARCHAR(255),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (group_id) REFERENCES `groups`(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;");

    // 4. クイズセッションテーブル
    $pdo->exec("CREATE TABLE IF NOT EXISTS quiz_sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        group_id INT NOT NULL,
        host_id INT NOT NULL,
        player_id INT NOT NULL,
        theme VARCHAR(100),
        total_points INT DEFAULT 0,
        reached_level INT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (group_id) REFERENCES `groups`(id) ON DELETE CASCADE,
        FOREIGN KEY (player_id) REFERENCES `users`(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;");

    // 5. クイズ詳細テーブル
    $pdo->exec("CREATE TABLE IF NOT EXISTS quiz_details (
        id INT AUTO_INCREMENT PRIMARY KEY,
        session_id INT NOT NULL,
        level INT NOT NULL,
        question TEXT NOT NULL,
        choice1 VARCHAR(255),
        choice2 VARCHAR(255),
        choice3 VARCHAR(255),
        choice4 VARCHAR(255),
        correct_answer VARCHAR(255),
        user_answer VARCHAR(255),
        is_family_q TINYINT(1) DEFAULT 0,
        is_correct TINYINT(1) DEFAULT 0,
        ai_comment TEXT,
        FOREIGN KEY (session_id) REFERENCES quiz_sessions(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;");

    // 開発時のみコメントアウトを解除して使用
    // echo "Database initialized successfully.";

} catch (PDOException $e) {
    // 情報をどこへ送るか：エラーメッセージをブラウザに表示（デバッグ用）
    echo "DB Error: " . $e->getMessage();
}