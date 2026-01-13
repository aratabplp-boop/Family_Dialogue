<?php
require_once 'db_init.php';

$action = $_POST['action'] ?? '';
$response = ['success' => false, 'message' => ''];

if ($action === 'save_game_result') {
    // 情報をどこから受け取り：JSからのリクエストデータ
    $session_id  = $_POST['session_id'] ?? 0;
    $final_level = $_POST['final_level'] ?? 0;
    $points      = $_POST['points'] ?? 0;

    try {
        // 処理の意味：DBの quiz_sessions テーブルを「完了」の状態にする
        $stmt = $pdo->prepare("UPDATE quiz_sessions SET reached_level = ?, total_points = ? WHERE id = ?");
        $stmt->execute([$final_level, $points, $session_id]);

        $response['success'] = true;
        $response['message'] = '今回のクイズの記録を大切に保存しました！';
    } catch (Exception $e) {
        $response['message'] = '保存に失敗しました：' . $e->getMessage();
    }
}

header('Content-Type: application/json');
echo json_encode($response);