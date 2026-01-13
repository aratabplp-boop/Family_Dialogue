<?php
// --- 処理：データベース接続設定を読み込み---
// 意味：db_init.phpで定義した接続ロジックを再利用し、金庫の鍵を借りる
require_once 'db_init.php';

// --- 処理：ブラウザから届いた「お願い（アクション）」を受け取る ---
// 意味：JSから送られてきた「何をしてほしいか」という指示書を読む
$action = $_POST['action'] ?? '';

// 処理：レスポンス（返事）用の箱を用意
$response = ['success' => false, 'message' => ''];

// --- 処理：アクションが「family_login（家族ログイン/登録）」の場合 ---
if ($action === 'family_login') {
    
    // 情報をどこから受け取り：JSのAjaxから送られてきたデータを受け取る
    $family_name = $_POST['family_name'] ?? '';
    $family_pass = $_POST['family_pass'] ?? '';

    if ($family_name && $family_pass) {
        try {
            // 処理：まずは同じ名前の家族がすでに登録されているか確認
            // 意味：二重登録を防ぐため、DBの名簿（groups）を一行ずつ探す
            $stmt = $pdo->prepare("SELECT * FROM groups WHERE group_name = ?");
            $stmt->execute([$family_name]);
            $family = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($family) {
                // 処理：家族が見つかった場合、パスワードが合っているか確認
                // 意味：合言葉が正しければ、その家族のIDを返事の袋に入れる
                if ($family_pass === $family['login_pass']) {
                    $response['success'] = true;
                    $response['family_id'] = $family['id'];
                    $response['message'] = 'おかえりなさい！';
                } else {
                    $response['message'] = '合言葉がちがうみたいだよ。';
                }
            } else {
                // 処理：家族が見つからない場合、新しく登録します
                // 意味：初めてのお客さんなので、新しく「家」を建てます
                $stmt = $pdo->prepare("INSERT INTO groups (group_name, login_pass) VALUES (?, ?)");
                $stmt->execute([$family_name, $family_pass]);
                
                $response['success'] = true;
                $response['family_id'] = $pdo->lastInsertId();
                $response['message'] = 'はじめまして！新しく登録したよ。';
            }
        } catch (PDOException $e) {
            $response['message'] = 'DBエラーが発生しました。';
        }
    } else {
        $response['message'] = '名前と合言葉をちゃんと教えてね。';
    }
}


// --- 処理：アクションが「fetch_members（メンバー一覧取得）」の場合 ---
// 意味：この家族に誰がいるのか、名簿をすべて取り出し
if ($action === 'fetch_members') {
    $family_id = $_POST['family_id'] ?? '';

    try {
        $stmt = $pdo->prepare("SELECT id, name, role, birthday FROM users WHERE group_id = ?");
        $stmt->execute([$family_id]);
        $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $response['success'] = true;
        $response['members'] = $members; // 家族全員のリストを返事の袋に入れる
    } catch (PDOException $e) {
        $response['message'] = '名簿がうまく取れなかったよ。';
    }
}

// --- 処理：アクションが「add_member（メンバー新規登録）」の場合 ---
// 意味：新しい家族メンバーをDBに刻む
if ($action === 'add_member') {
    $family_id = $_POST['family_id'] ?? '';
    $name = $_POST['name'] ?? '';
    $role = $_POST['role'] ?? '';
    $birthday = $_POST['birthday'] ?? '';

    try {
        $stmt = $pdo->prepare("INSERT INTO users (group_id, name, role, birthday) VALUES (?, ?, ?, ?)");
        $stmt->execute([$family_id, $name, $role, $birthday]);

        $response['success'] = true;
        $response['message'] = '新しいメンバーを登録したよ！';
    } catch (PDOException $e) {
        $response['message'] = '登録に失敗しちゃった。';
    }
}


// どこに送っているのか：最後に、結果をJSON形式でJS（メイン画面）へ返送します
header('Content-Type: application/json');
echo json_encode($response);


