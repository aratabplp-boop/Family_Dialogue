<?php
// test_ai.php
require_once 'ai_service.php';

// 仮のデータでAIにクイズを作らせてみる
$dummy_player = ['name' => 'たろう', 'role' => 'child', 'birthday' => '2018-01-01'];
echo "AIにクイズを依頼中...しばらくお待ちください...<br>";

$result = generate_quiz_from_ai('日本の果物', 'パパの好きなリンゴ', 'ママのミカン', $dummy_player, 'パパ');

if ($result) {
    echo "<h3>成功！AIからの回答：</h3>";
    echo "<pre>" . htmlspecialchars($result) . "</pre>";
} else {
    echo "<h3>失敗... APIキーか通信設定を確認してください。</h3>";
}