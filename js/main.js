$(document).ready(function() {
    console.log("System: Application initialized.");

    // --- 記憶のしおり（一貫して使用する変数） ---
    let currentFamilyId = null;   // 今の家族ID
    let familyMembersData = [];  // 家族名簿の生データ（出題者リスト作成用）
    let currentPlayerId = null;  // 選ばれた回答者のID
    let currentPlayerName = "";  // 選ばれた回答者の名前

    // --- 1. 家族ログイン処理 ---
    $('#btn-login').on('click', function() {
        const familyName = $('#family_name').val();
        const familyPass = $('#family_pass').val();

        console.log("Action: Requesting login for", familyName);

        $.ajax({
            url: 'reception.php',
            type: 'POST',
            data: { action: 'family_login', family_name: familyName, family_pass: familyPass },
            dataType: 'json'
        })
        .done(function(data) {
            if (data.success) {
                currentFamilyId = data.family_id;
                alert(data.message);
                $('#login-section').fadeOut(300, function() {
                    $('#member-select-section').fadeIn(300);
                    fetchMembers(); 
                });
            } else {
                alert(data.message);
            }
        })
        .fail(function(xhr, status, error) {
            console.error("Delivery Error:", status, error);
        });
    });

    // --- 2. メンバー名簿を呼ぶ関数 ---
    const fetchMembers = () => {
        if (!currentFamilyId) return;
        $.ajax({
            url: 'reception.php',
            type: 'POST',
            data: { action: 'fetch_members', family_id: currentFamilyId },
            dataType: 'json'
        })
        .done(function(data) {
            if (data.success) {
                familyMembersData = data.members; // 意味：後で「出題者リスト」を作るために名簿を保存
                renderMembers(data.members);
            }
        });
    };

    // --- 3. 名簿をお絵描きする関数 ---
    const renderMembers = (members) => {
        let html = "";
        members.forEach(m => {
            html += `<div class="member-card" data-id="${m.id}"><strong>${m.name}</strong><br><small>${m.role}</small></div>`;
        });
        $('#member-list').html(html);
    };

    // --- ★追加：回答者（主役）を選んだ時の処理 ---
    // 意味：動的に作られたカードをクリックしたとき、物語をクイズ設定へ進めます
    $('#member-list').on('click', '.member-card', function() {
        currentPlayerId = $(this).data('id');
        currentPlayerName = $(this).find('strong').text();

        console.log("Action: Player selected", currentPlayerName);

        // 演出：選んだ感を出すためのアニメーション
        $(this).css('background', '#ffcc00').fadeOut(100).fadeIn(100);

        setTimeout(function() {
            if(confirm(currentPlayerName + " さんがクイズに答えるよ。いいかな？")) {
                goToQuizSetup();
            }
        }, 200);
    });

    // --- ★追加：クイズ設定画面（出題者選び）への遷移 ---
    const goToQuizSetup = () => {
        console.log("Scene: Moving to Quiz Setup.");
        
        // 出題者セレクトボックスをリセット
        const $hostSelect = $('#host_user_id');
        $hostSelect.empty();
        // $hostSelect.append('<option value="ai">AIにおまかせ（全自動）</option>');

        // 回答者以外のメンバーを「出題者（パートナー）」として追加
        familyMembersData.forEach(member => {
            if (member.id != currentPlayerId) {
                $hostSelect.append(`<option value="${member.id}">${member.name}</option>`);
            }
        });

        // 画面を切り替える
        $('#display-player-name').text(currentPlayerName + " さんのクイズタイム！");
        $('#member-select-section').fadeOut(300, function() {
            $('#quiz-setup-section').fadeIn(300);
        });
    };

    // --- 4. メンバー登録ボタン ---
    $('#btn-save-member').on('click', function() {
        const name = $('#new_member_name').val();
        const role = $('#new_member_role').val();
        const bday = $('#new_member_birthday').val();

        $.ajax({
            url: 'reception.php',
            type: 'POST',
            data: { action: 'add_member', family_id: currentFamilyId, name: name, role: role, birthday: bday },
            dataType: 'json'
        })
        .done(function(data) {
            if (data.success) {
                $('#member-add-section').hide();
                $('#member-select-section').fadeIn(300);
                fetchMembers(); 
            }
        });
    });

    // --- 5. 画面切り替え系 ---
    $('#btn-show-add-member').on('click', function() {
        $('#member-select-section').hide();
        $('#member-add-section').fadeIn(300);
    });

    $('#btn-back-select').on('click', function() {
        $('#member-add-section').hide();
        $('#member-select-section').fadeIn(300);
    });

    // えらびなおすボタン（クイズ設定から戻る）
    $('#btn-back-to-members').on('click', function() {
        $('#quiz-setup-section').hide();
        $('#member-select-section').fadeIn(300);
    });

// --- 処理：クイズスタートボタン（AI生成開始）をクリック ---
// 意味：プロデューサーが入力した内容を掴み、AIにクイズ作成を依頼。
$('#btn-start-quiz').on('click', function() {
    // 1. 【情報の受け取り】HTMLの各入力欄から値を取得
    const hostId = $('#host_user_id').val();
    const theme  = $('#quiz_theme').val();
    const hint4  = $('#hint_q4').val();
    const hint7  = $('#hint_q7').val();

    // 入力チェック：テーマがないとAIが困るのでガード
    if (!theme) {
        alert("クイズのテーマを入力してね！");
        return;
    }

    console.log("Action: クイズ生成リクエスト送信", { hostId, theme, hint4, hint7 });
    
    // 2. 【処理の意味】二重押し防止と、ユーザーへの「ワクワク待機」演出
    $(this).prop('disabled', true).text('クイズを作っています...');
   $('#quiz-loading-section').removeClass('hidden'); // ローディング表示
    $('#quiz-setup-section').addClass('hidden');
    // 3. 【送り先】AJAX特急便で quiz_action.php へデータを運ぶ
    $.ajax({
        url: 'quiz_action.php', 
        type: 'POST',
        data: {
            action: 'create_quiz',
            family_id: currentFamilyId, // ログイン時に保持しているID
            player_id: currentPlayerId, // 選択された回答者のID
            host_id: hostId,
            theme: theme,
            hint4: hint4,
            hint7: hint7
        },
        dataType: 'json'
    })
    .done(function(data) {

        console.log("PHPからのレスポンス:", data);
        // 4. 【情報の受け取り】成功の合図とセッションIDが返ってきたら
     
        
        if (data.success) {
            // ステータス管理：現在のセッションを「実行中」として記憶
            currentSessionId = data.session_id; 
               console.log("Success: クイズセッションが作成されました ID:", data.session_id);
            // --- 処理：画面の切り替え ---
            // --- 【修正：画面の切り替え】 ---
            // .addClass('hidden') だけでなく .hide() を使って物理的に消し去る
             $('#quiz-setup-section').hide();   
             $('#quiz-loading-section').hide();

             // クイズ画面をフェードインで華やかに表示
             $('#quiz-game-section').hide().removeClass('hidden').fadeIn(500);
             console.log("Stage 4へ移行: クイズ画面を表示しました。");


            // 次の物語（Stage 4）へ：クイズ本番画面を表示する関数を呼び出す
            loadQuestion(data.session_id, 1); 

        } else {
            alert("エラー: " + data.message);
            $('#btn-start-quiz').prop('disabled', false).text('クイズスタート！');
        }
    })
    .fail(function(xhr) {
        console.error("通信エラー詳細:", xhr.responseText);
        alert("通信エラーが発生しました。");
        $('#btn-start-quiz').prop('disabled', false).text('クイズスタート！');
    });
});

// --- 処理：特定の1問をDBから取得して画面に表示する ---
// 意味：DBから届いた文字（問題文や選択肢）を、HTMLの空欄に流し込む。
const loadQuestion = (sessionId, qNum) => {
    console.log(`--- 第 ${qNum} 問の読み込み開始 ---`);

    // 【追加：掃除】新しい問題を表示する前に、画面の状態を元に戻す
    $('#quiz-judgement-area').addClass('hidden').hide(); // 判定エリアを隠す
    $('#quiz_choices_area').show();                      // 選択肢エリアを出す
    $('#current_question_text').css('opacity', '1');     // 文字の薄さを戻す
    $('.choice_btn').removeClass('selected-style');      // ボタンの選択色を消す
    firstSelectedBtn = null;                             // 選択記憶をリセット

    $.ajax({
        url: 'get_question.php',
        type: 'POST',
        dataType: 'json',
        data: {
            session_id: sessionId,
            question_num: qNum
        }
    })
    .done(function(response) {
        console.log('取得データ:', response);

        if (response.success) {
            const q = response.data;
            // ★重要：今の問題を「記憶」しておく（正誤判定で使うため）
             window.currentQuestionData = q;



            // 1. 【情報の受け取り】DBから届いたデータをHTMLに反映
            $('#current-q-num').text(q.level);
            $('#current_question_text').text(q.question);
            $('#choice_text_1').text(q.choice1);
            $('#choice_text_2').text(q.choice2);
            $('#choice_text_3').text(q.choice3);
            $('#choice_text_4').text(q.choice4);

            // 2. 【処理の意味】ポイントの表示更新（簡易版：10P * 問題数など）
            const points = [0, 10, 50, 100, 200, 500, 1000, 2000, 5000, 10000, 20000];
            $('#display-get-point').text(points[qNum] + 'P');

            console.log(`第 ${qNum} 問の表示完了`);
        } else {
            alert('問題の読み込みに失敗しました：' + response.message);
        }
    })
    .fail(function() {
        alert('通信エラーが発生しました。');
    });
};

// --- 状態管理のしおり ---
let firstSelectedBtn = null; // 1回目に選ばれたボタンを一時保存

// --- 6. 回答ボタンのクリック処理（2段階確定） ---
// 【情報の受け取り】 4つの選択肢（.choice_btn）のいずれかを押した時
$(document).on('click', '.choice_btn', function() {
    const $thisBtn = $(this);
    console.log("Button clicked:", $thisBtn.data('choice'));

    // 1回目：選択（色を変えて「ファイナルアンサー？」の状態を作る）
    if (firstSelectedBtn === null || firstSelectedBtn[0] !== $thisBtn[0]) {
        $('.choice_btn').removeClass('selected-style'); // 他のボタンの色を戻す
        $thisBtn.addClass('selected-style');           // 選んだボタンの色を変える
        firstSelectedBtn = $thisBtn;                    // 「今これを選んでるよ」と記憶
        console.log("Log: 1st choice made.");
        return;
    }

    // 2回目：確定（同じボタンをもう一度押した）
    console.log("Log: Answer confirmed.");
    const selectedText = $thisBtn.find('span:not(.choice-label)').text(); // ラベル(A:)以外の中身を取得
    executeJudgment(selectedText); 
});

// --- 7. 判定と「タメ」の演出関数 ---
const executeJudgment = (userAnswer) => {
    const qNum = parseInt($('#current-q-num').text()); // 今何問目？
    const correctAnswer = window.currentQuestionData.correct_answer; // DBの正解
    
    // 【処理の意味】 問題数に応じて「タメ」の秒数を変える
    let waitMs = 1000;
    if (qNum >= 4 && qNum <= 6) waitMs = 3000;
    if (qNum >= 7) waitMs = 5000;

    // 演出：選択ボタンエリアを隠し、「正解は・・・」という空気を作る
    $('#quiz_choices_area').fadeOut(300);
    $('#current_question_text').text("正解は・・・・");

    setTimeout(() => {
        showFinalResult(userAnswer === correctAnswer, correctAnswer);
    }, waitMs);
};

// --- 8. 結果表示と次のステップへの案内 ---
const showFinalResult = (isCorrect, correctText) => {
    const q = window.currentQuestionData;
    
    // 画面切り替え：問題文エリアを結果表示へ
    // $('#current_question_text').hide();
    $('#current_question_text').css('opacity', '0.6'); // 少し薄くして「過去の問題」感を出す
    $('#quiz-judgement-area').removeClass('hidden').fadeIn(300);
    
    // 結果メッセージの流し込み
    if (isCorrect) {
        $('#judgement-result').html('<h2 class="correct-text">正解！！</h2>');
    } else {
        $('#judgement-result').html('<h2 class="incorrect-text">残念...！</h2><p>正解は <strong>' + correctText + '</strong> でした。</p>');
    }

    // 解説の表示
    $('#quiz-commentary').text(q.ai_comment);

    // 次へ進めるかどうかの分岐
    if (isCorrect && parseInt($('#current-q-num').text()) < 10) {
        $('#btn-next-question').show();
        $('#game-over-controls').addClass('hidden');
    } else {
        $('#btn-next-question').hide();
        $('#game-over-controls').removeClass('hidden').fadeIn(300);
    }
};

// --- 9. 「次へ進む」ボタンの挙動 ---
$('#btn-next-question').on('click', function() {
    const nextLevel = parseInt($('#current-q-num').text()) + 1;
    
    // 画面をリセットして次の問題をロード
    $('#quiz-judgement-area').addClass('hidden');
    $('#quiz_choices_area').show();
    $('#current_question_text').show();
    $('.choice_btn').removeClass('selected-style');
    firstSelectedBtn = null;

    loadQuestion(currentSessionId, nextLevel);
});

// --- 10. 「もう一度挑戦」ボタンの挙動 ---
// 意味：今のセッションIDを使い、レベル1の問題を呼び出し直します。
$('#btn-retry').on('click', function() {
    console.log("Action: Retry quiz from Level 1.");
    // 道具箱の中にある「loadQuestion」を使って、最初からやり直す
    loadQuestion(currentSessionId, 1);
});

// --- 11. 「保存して終了」ボタンの挙動（修正版） ---
$('#btn-save-exit').on('click', function(event) { // event を受け取る
    event.preventDefault(); // ★重要：ブラウザの勝手なリロードを止める「おまじない」

    const finalLevel = parseInt($('#current-q-num').text());
    const pointsText = $('#display-get-point').text().replace('P', ''); 
    
    console.log("Action: Saving result...", { finalLevel, pointsText });

    $.ajax({
        url: 'archive_action.php',
        type: 'POST',
        data: {
            action: 'save_game_result',
            session_id: currentSessionId,
            final_level: finalLevel,
            points: parseInt(pointsText)
        },
        dataType: 'json'
    })
    .done(function(data) {
        if (data.success) {
            alert(data.message);

            // クイズ画面を隠して、メンバー選択を出す
            $('#quiz-game-section').hide().addClass('hidden'); // 確実に消す
            $('#member-select-section').fadeIn(300);
            
            
            // メンバーカードの色もリセット（お掃除）
            $('.member-card').css('background', ''); 

            console.log("System: Successfully returned to member-select-section.");
        } else {
            alert("保存エラー: " + data.message);
        }
    });
});
});