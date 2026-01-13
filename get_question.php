<?php
// get_question.php
require_once 'db_init.php';

// 1. 【情報の受け取り】JSから「どのクイズ束の、何問目が欲しいか」を受け取る
$session_id = $_POST['session_id'] ?? null;
$question_num = $_POST['question_num'] ?? 1;

$response = ['success' => false, 'data' => null];

if ($session_id) {
    try {
        // 2. 【処理の意味】指定されたセッションIDと問題番号に一致するデータを1件だけ探す
        $stmt = $pdo->prepare("SELECT * FROM quiz_details WHERE session_id = ? AND level = ?");
        $stmt->execute([$session_id, $question_num]);
        $question = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($question) {
            $response['success'] = true;
            $response['data'] = $question;
            // console.log の代わりに、PHPからJSへ送るデータに含める
            $response['debug_msg'] = "Question {$question_num} retrieved.";
        } else {
            $response['message'] = "問題が見つかりませんでした。";
        }
    } catch (Exception $e) {
        $response['message'] = "DBエラー: " . $e->getMessage();
    }
}

// 3. 【送り先】JSON形式でJSに送り返す
header('Content-Type: application/json');
echo json_encode($response);