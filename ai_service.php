<?php
// ai_service.php
require_once 'config.php';

function generate_quiz_from_ai($theme, $hint4, $hint7, $player_data, $host_name) {
    // 宛先URLの作成
    $url = "https://generativelanguage.googleapis.com/v1beta/models/" . GEMINI_MODEL . ":generateContent?key=" . GEMINI_API_KEY;

    // 年齢計算
    $bday = new DateTime($player_data['birthday']);
    $today = new DateTime('now');
    $age = $today->diff($bday)->y;

    // プロンプト作成
    $prompt = "
あなたはテレビ番組『クイズ・ミリオネア』のような、緊張感と楽しさを提供するプロのクイズ出題者です。
テーマ：「{$theme}」
対象者：{$age}歳の「{$player_data['name']}」さん

以下の条件を厳守して、クイズを10問作成し、JSON形式で出力してください。

【最優先事項：特定問題の採用】
・第4問と第7問について、以下の入力がある場合は、その内容（問題と正解）を「必ず」採用してください。
・第4問の指定：{$hint4}
・第7問の指定：{$hint7}
・これらが「未記入」または「特に指定なし」の場合は、テーマに沿ってあなたが作成してください。
・あなたが採用・作成した正解に対し、紛らわしい「誤答選択肢」を3つ、あなたが作成して4択を完成させてください。

【難易度設計】
・第1問〜第3問：誰でもわかる超簡単なウォーミングアップ問題
・第4問〜第6問：少し考えさせる中級問題（※第4問は上記指定を優先）
・第7問〜第9問：大人でも「へぇ〜」となる難問（※第7問は上記指定を優先）
・第10問：全員が驚く超難問、または対象者の思い出に触れるフィナーレ問題。

【クイズの形式】
1. 4択形式とし、choice1, choice2, choice3, choice4 に選択肢を入れてください。
2. 正解（answer）は、上記の選択肢の中から1つ選び、その「文字列」をそのまま入れてください。
3. 重要：正解がchoice1ばかりにならないよう、正解の位置を全10問の中でランダムにバラけさせてください。

【ハルシネーション（嘘）の防止】
・事実に基づいた正確な情報を出力してください。
・不明な事実や根拠のない情報を「正解」にしないでください。
・解説（commentary）には、納得感のある正確な補足情報を短く記載してください。

【出力形式】
以下の構造のJSON配列のみを出力してください。説明文や挨拶は一切不要です。
[
  {
    \"question_num\": 1,
    \"question\": \"問題文\",
    \"choice1\": \"選択肢1\",
    \"choice2\": \"選択肢2\",
    \"choice3\": \"選択肢3\",
    \"choice4\": \"選択肢4\",
    \"answer\": \"正解の文字列\",
    \"commentary\": \"解説文\"
  }
]
";

    // 送信データの組み立て
    $data = [
        "contents" => [["parts" => [["text" => $prompt]]]],
        "generationConfig" => ["response_mime_type" => "application/json"]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    // ローカル環境（localhost）かどうかを判定して切り替える
    $is_local = ($_SERVER['SERVER_NAME'] === 'localhost');

    if ($is_local) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    } else {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    }

    $result = curl_exec($ch);
    $errno = curl_errno($ch);
    $error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // 通信エラーの処理
    if ($errno) {
        error_log("CURL通信エラー: " . $error);
        return null;
    }

    // Google APIからのエラー返却
    if ($http_code !== 200) {
        error_log("Gemini API Error (Code: $http_code): " . $result);
        return null;
    }

    $response = json_decode($result, true);
    $raw_text = $response['candidates'][0]['content']['parts'][0]['text'] ?? null;
    
    // デバッグ：AIの返答をファイルに保存する
    if ($raw_text) {
        file_put_contents('ai_response_log.txt', "--- " . date('Y-m-d H:i:s') . " ---\n" . $raw_text . "\n\n", FILE_APPEND);
    }

    return $raw_text;
}