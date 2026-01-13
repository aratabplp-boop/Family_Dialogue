<?php
// --- 処理：DB接続と設定の読み込み ---
require_once 'db_init.php';
require_once 'ai_service.php';

// --- 処理：JSからの「クイズを作って！」というお願いを受け取る ---
$action = $_POST['action'] ?? '';
$response = ['success' => false, 'message' => ''];

if ($action === 'create_quiz') {
    // 情報をどこから受け取り：JSのAjax通信からクイズの設定情報を受けとる
    $family_id = $_POST['family_id'] ?? 0;
    $player_id = $_POST['player_id'] ?? 0;
    $theme     = $_POST['theme']     ?? '一般常識';
    $hint4     = $_POST['hint4']     ?? '';
    $hint7     = $_POST['hint7']     ?? '';
    
    // 【重要】 host_id が 'ai' のままだとDB（INT型）が受け取れないので、0 に変換します
    $raw_host_id = $_POST['host_id'] ?? 'ai';
    $host_id = ($raw_host_id === 'ai') ? 0 : $raw_host_id;

    try {
        // 1. 【情報の受け取り】DBから回答者の情報を詳しく取得
        $stmt = $pdo->prepare("SELECT name, role, birthday FROM users WHERE id = ?");
        $stmt->execute([$player_id]);
        $player_data = $stmt->fetch(PDO::FETCH_ASSOC);

        // 2. 出題者の名前を取得（AIが文中で使うため）
        $host_name = 'AI';
        if ($raw_host_id !== 'ai') {
            $stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
            $stmt->execute([$raw_host_id]);
            $host_name = $stmt->fetchColumn();
        }

        // 3. 【処理の意味】AIにクイズ生成を依頼
        $quiz_json_text = generate_quiz_from_ai($theme, $hint4, $hint7, $player_data, $host_name);
        $quiz_array = json_decode($quiz_json_text, true);

        if (!$quiz_array) {
            throw new Exception("AIがクイズの作成に失敗しました。");
        }

        // 4. 【処理】セッションとクイズをDBに保存（トランザクション開始）
        $pdo->beginTransaction();

        // クイズセッション（舞台）を作る
        $stmt = $pdo->prepare("INSERT INTO quiz_sessions (group_id, host_id, player_id, theme) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $family_id, 
            $host_id,   // 0 または ユーザーID
            $player_id, 
            $theme
        ]);
        $session_id = $pdo->lastInsertId();

// --- quiz_action.php の 循環処理（foreach） ---

foreach ($quiz_array as $q) {
    // 1. 【情報の受け取り】AIが作った各問題の封筒を開ける
    $q_num   = $q['question_num'] ?? 0;
    $q_text  = $q['question']     ?? '';
    $c1      = $q['choice1']      ?? '';
    $c2      = $q['choice2']      ?? '';
    $c3      = $q['choice3']      ?? '';
    $c4      = $q['choice4']      ?? '';
    $ans     = $q['answer']       ?? '';
    $comm    = $q['commentary']   ?? '';

    // 2. 【処理の意味】DBの正しい棚（level, question...）に配置する
    $stmt = $pdo->prepare("INSERT INTO quiz_details 
        (session_id, level, question, choice1, choice2, choice3, choice4, correct_answer, ai_comment) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    // 3. 【情報の送り先】DBという巨大な図書館へ保存
    $stmt->execute([
        $session_id, $q_num, $q_text, 
        $c1, $c2, $c3, $c4, $ans, $comm
    ]);
}

        $pdo->commit();

        // 5. 【送り先】JSに成功の合図を返す
        $response['success'] = true;
        $response['session_id'] = $session_id;
        $response['message'] = 'クイズが完成したよ！';

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $response['message'] = $e->getMessage();
    }
}

// 最後に JSON を出力
header('Content-Type: application/json');
echo json_encode($response);