
<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

// CSRFトークン生成
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = "";

// フォーム送信処理
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "不正な送信が検出されました。";
    } else {
        $officeEmail = "terabayashi-yuuki.b24@mhlw.go.jp";
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $dates = $_POST['dates'] ?? [];
        $notes = trim($_POST['notes'] ?? '');

        if (empty($name)) {
            $error = "氏名は必須です。";
        } else {
            $validDates = [];
            if (is_array($dates)) {
                foreach ($dates as $d) {
                    $date = trim($d['date'] ?? '');
                    $meals = $d['meals'] ?? [];

                    if ($date !== '' || (is_array($meals) && count($meals) > 0)) {
                        $validDates[] = [
                            'date' => $date,
                            'meals' => $meals
                        ];
                    }
                }
            }

            if (count($validDates) === 0 && empty($notes)) {
                $error = "少なくとも欠食日と食事区分、またはその他連絡事項を入力してください。";
            }
        }

        if (empty($error)) {
            $message = "氏名: {$name}\n\n欠食予定:\n";
            foreach ($validDates as $d) {
                $mealList = !empty($d['meals']) ? implode(", ", $d['meals']) : "（食事区分なし）";
                $dateText = !empty($d['date']) ? $d['date'] : "（日付なし）";
                $message .= "・{$dateText} → {$mealList}\n";
            }

            if (!empty($notes)) {
                $message .= "\nその他連絡事項:\n{$notes}\n";
            }

            file_put_contents("php://stderr", "送信内容:\n" . $message);

            try {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'terabayasiyuuki@gmail.com';
                $mail->Password = 'yvvxhksukvxlfbyc';
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;
                $mail->CharSet = 'UTF-8';

                $mail->setFrom('terabayasiyuuki@gmail.com', '欠食届フォーム');
                $mail->addAddress($officeEmail);
                $mail->Subject = '欠食届';
                $mail->Body = $message;
                $mail->send();

                if (!empty($email)) {
                    $mail->clearAddresses();
                    $mail->addAddress($email);
                    $mail->Subject = '【確認】欠食届を受け付けました';
                    $mail->Body = "以下の内容で欠食届を受け付けました。\n\n" . $message;
                    $mail->send();
                }

                header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
                exit;
            } catch (Exception $e) {
                $error = "メールの送信に失敗しました: " . $mail->ErrorInfo;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>欠食届フォーム</title>
  <style>
    body { font-family: sans-serif; background: #fff; color: #000; }
    label { display: block; margin-top: 10px; }
    .date-block { border: 1px solid #ccc; padding: 10px; margin-top: 10px; }
    button { margin-top: 10px; }
  </style>
</head>
<body>
  <h1>欠食届フォーム</h1>

  <?php if (!empty($error)): ?>
    <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
  <?php endif; ?>
  <?php if (isset($_GET['success'])): ?>
    <p style="color:green;">送信しました。</p>
  <?php endif; ?>

  <form method="post" id="mealForm">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

    <label>氏名</label>
    <input type="text" name="name" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">

    <label>メールアドレス</label>
    <input type="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">

    <h2>欠食日と食事区分</h2>
    <div id="date-blocks">
      <div class="date-block">
        <label>日付</label>
        <input type="date" name="dates[0][date]" value="<?php echo htmlspecialchars($_POST['dates'][0]['date'] ?? ''); ?>">
        <fieldset>
          <legend>食事区分</legend>
          <?php $meals = $_POST['dates'][0]['meals'] ?? []; ?>
          <label><input type="checkbox" name="dates[0][meals][]" value="朝" <?php echo in_array("朝", $meals) ? "checked" : ""; ?>> 朝食</label>
          <label><input type="checkbox" name="dates[0][meals][]" value="昼" <?php echo in_array("昼", $meals) ? "checked" : ""; ?>> 昼食</label>
          <label><input type="checkbox" name="dates[0][meals][]" value="夕" <?php echo in_array("夕", $meals) ? "checked" : ""; ?>> 夕食</label>
        </fieldset>
      </div>
    </div>

    <label>その他連絡事項</label>
    <textarea name="notes" rows="4" cols="40"><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>

    <button type="button" onclick="addDateBlock()">日付を追加</button>
    <button type="submit">送信</button>
  </form>

  <script>
    let dateIndex = 1;

    function addDateBlock() {
      const container = document.createElement("div");
      container.className = "date-block";
      container.innerHTML = `
        <label>日付</label>
        <input type="date" name="dates[${dateIndex}][date]">
        <fieldset>
          <legend>食事区分</legend>
          <label><input type="checkbox" name="dates[${dateIndex}][meals][]" value="朝"> 朝食</label>
          <label><input type="checkbox" name="dates[${dateIndex}][meals][]" value="昼"> 昼食</label>
          <label><input type="checkbox" name="dates[${dateIndex}][meals][]" value="夕"> 夕食</label>
        </fieldset>
      `;
      document.getElementById("date-blocks").appendChild(container);
      dateIndex++;
    }

    document.querySelector("#mealForm").addEventListener("submit", function(e) {
     const name = document.querySelector("input[name='name']").value.trim();
  if (name === "") {
    alert("氏名を入力してください。");
    e.preventDefault();
    return;
  }
 let dateBlocks = document.querySelectorAll(".date-block");
      let hasValidDate = false;

      dateBlocks.forEach(block => {
        const date = block.querySelector("input[type='date']").value;
        const meals = block.querySelectorAll("input[type='checkbox']:checked");
        if (date || meals.length > 0) {
          hasValidDate = true;
        }
      });

      const notes = document.querySelector("textarea[name='notes']").value.trim();

      if (!hasValidDate && notes === "") {
        alert("少なくとも欠食日と食事区分のセット、またはその他連絡事項を入力してください。");
        e.preventDefault();
      }
    });
  </script>
</body>
</html>

