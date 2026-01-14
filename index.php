<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>$$クイズ☆ファミリオネア$$</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kiwi+Maru:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    
<div class="container">
    <h1>きいてきいて！</h1>

    <section id="login-section">
        <h3>家族の合言葉をいれてね</h3>
        <input type="text" id="family_name" placeholder="家族の名前（例：山田家）">
        <input type="password" id="family_pass" placeholder="合言葉">
        <br>
        <button id="btn-login">入室する</button>
    </section>

    <section id="member-select-section" class="hidden">
        <h3>回答者を選んでね</h3>
        <div id="member-list" class="member-list-container">
            </div>
        <button id="btn-show-add-member">新しいメンバーを追加</button>
    </section>

    <section id="member-add-section" class="hidden">
        <h3>メンバーを教えてね</h3>
        <input type="text" id="new_member_name" placeholder="おなまえ">
        <select id="new_member_role">
            <option value="child">こども</option>
            <option value="father">パパ</option>
            <option value="mother">ママ</option>
            <option value="other">その他</option>
        </select>
        <input type="date" id="new_member_birthday">
        <br>
        <button id="btn-save-member">登録する！</button>
        <button id="btn-back-select" style="background:#ccc;">もどる</button>
    </section>

<section id="quiz-setup-section" class="hidden">
        <h2 id="display-player-name">主役のクイズタイム！</h2>
        
        <div style="background: #fff5cc; padding: 20px; border-radius: 15px; margin-top: 20px;">
            <p><strong>1. だれがクイズを出しますか？</strong></p>
            <select id="host_user_id">
            </select>

            <p><strong>2. クイズのテーマは？</strong></p>
            <input type="text" id="quiz_theme" placeholder="例：恐竜、お花、世界の歴史">
            
            <div id="family-hints-area" style="margin-top: 20px; border-top: 1px dashed #ffa500; padding-top: 10px;">
                <p style="font-size: 0.9em; color: #d35400;"><strong>★家族だけの特別問題（第4問・第7問）</strong></p>
                <input type="text" id="hint_q4" placeholder="第4問（例：パパの好きな食べ物は？　答:カレー）">
                <input type="text" id="hint_q7" placeholder="第7問（例：あさこの得意なことは？　答:ダンス）">
                <p style="font-size: 0.7em;">※空欄の場合はAIが自動で考えます</p>
            </div>

            <br>
            <button id="btn-start-quiz" style="background: #ff4400; color: white; width: 100%;">
                クイズスタート！
            </button>
            <button id="btn-back-to-members" style="background:#ccc; margin-top:10px; border:none; padding:5px; border-radius:5px; width:100%;">主役をえらびなおす</button>
        </div>
    </section>

<section id="quiz-game-section" class="hidden">
    <div class="quiz-container">
        <div class="quiz-status-bar">
            <div class="status-item">
                <span class="label">QUESTION</span>
                <span id="current-q-num" class="value">1</span>
            </div>
            <div class="status-item">
                <span class="label">GET POINT</span>
                <span id="display-get-point" class="value">10P</span>
            </div>
            <div class="timer-container">
                <span id="quiz-timer" class="value">60</span>
                <button id="btn-pause" class="pause-btn">ll</button>
            </div>
        </div>

        <div id="quiz_question_area">
            <p id="current_question_text">ここに問題が表示されます</p>
        </div>

        <div id="quiz_choices_area">
            <button class="choice_btn" data-choice="1">
                <span class="choice-label">A:</span> <span id="choice_text_1"></span>
            </button>
            <button class="choice_btn" data-choice="2">
                <span class="choice-label">B:</span> <span id="choice_text_2"></span>
            </button>
            <button class="choice_btn" data-choice="3">
                <span class="choice-label">C:</span> <span id="choice_text_3"></span>
            </button>
            <button class="choice_btn" data-choice="4">
                <span class="choice-label">D:</span> <span id="choice_text_4"></span>
            </button>
        </div>

        <div id="quiz-judgement-area" class="hidden">
            <div id="judgement-result"></div> <div id="quiz-commentary"></div> <button id="btn-next-question" class="action-btn">次へ進む</button>
            <div id="game-over-controls" class="hidden">
                <button id="btn-retry" class="action-btn">もう一度挑戦</button>
                <button id="btn-save-exit" class="action-btn secondary">保存して終了</button>
            </div>
        </div>
    </div>
</section>

</div>

<script src="js/main.js"></script>
</body>

</html>