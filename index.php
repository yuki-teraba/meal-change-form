<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$error = "";
$success = "";

// メール送信処理
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $officeEmail = "terabayashi-yuuki.b24@mhlw.go.jp";
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $dates = $_POST['dates'];
    $notes = trim($_POST['notes']);

    if (empty($name)) {
        $error = "氏名は必須です。";
    } else {
        $validDates = [];
        foreach ($dates as $d) {
            if (!empty($d['date']) && !empty($d['meals'])) {
                $validDates[] = $d;
            }
        }
        if (count($validDates) === 0) {
            $error = "少なくとも1つの日付と食事区分を入力してください。";
        }
    }

    if (empty($error)) {
        $message = "氏名: {$name}\n\n欠食予定:\n";
        foreach ($validDates as $d) {
            $mealList = implode(", ", $d['meals']);
            $message .= "・{$d['date']} → {$mealList}\n";
        }
        if (!empty($notes)) {
            $message .= "\nその他連絡事項:\n{$notes}\n";
        }

        $headers = "From: noreply@yourdomain.com\r\n";

        mail($officeEmail, "欠食届", $message, $headers);

        if (!empty($email)) {
            $confirmMessage = "以下の内容で欠食届を受け付けました。\n\n" . $message;
            mail($email, "【確認】欠食届", $confirmMessage, $headers);
        }

        $success = "送信しました。";
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
.high-contrast { background: #000; color: #fff; }
label { display: block; margin-top: 10px; }
.date-block { border: 1px solid #ccc; padding: 10px; margin-top: 10px; }
button { margin-top: 10px; }
</style>
</head>
<body>
<h1>欠食届フォーム</h1>

<div>
  <button onclick="toggleContrast()">白黒反転</button>
  <button onclick="changeFontSize('small')">小</button>
  <button onclick="changeFontSize('medium')">中</button>
  <button onclick="changeFontSize('large')">大</button>
</div>

<?php if (!empty($error)): ?>
<p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
<?php endif; ?>
<?php if (!empty($success)): ?>
<p style="color:green;"><?php echo htmlspecialchars($success); ?></p>
<?php endif; ?>

<form method="post" aria-label="欠食届フォーム">
  <label for="name">氏名（必須）</label>
  <input type="text" id="name" name="name" required aria-required="true">

  <label for="email">メールアドレス（任意）</label>
  <input type="email" id="email" name="email">

  <h2>欠食日と食事区分（最大10日）</h2>
  <div id="dates-container">
    <div class="date-block">
      <label>日付（必須）</label>
      <input type="date" name="dates[0][date]" required aria-required="true">
      <fieldset>
        <legend>食事区分（必須）</legend>
        <label><input type="checkbox" name="dates[0][meals][]" value="朝"> 朝食</label>
        <label><input type="checkbox" name="dates[0][meals][]" value="昼"> 昼食</label>
        <label><input type="checkbox" name="dates[0][meals][]" value="夕"> 夕食</label>
      </fieldset>
    </div>
  </div>
  <button type="button" onclick="addDateBlock()">＋日付を追加</button>

  <label for="notes">その他連絡事項（任意）</label>
  <textarea id="notes" name="notes" rows="4" cols="40"></textarea>

  <button type="submit">送信</button>
</form>

<script>
let dateCount = 1;
function addDateBlock() {
  if (dateCount >= 10) {
    alert("最大10日まで追加できます。");
    return;
  }
  const container = document.getElementById('dates-container');
  const block = document.createElement('div');
  block.className = 'date-block';
  block.innerHTML = `
    <label>日付（必須）</label>
    <input type="date" name="dates[${dateCount}][date]" required aria-required="true">
    <fieldset>
      <legend>食事区分（必須）</legend>
      <label><input type="checkbox" name="dates[${dateCount}][meals][]" value="朝"> 朝食</label>
      <label><input type="checkbox" name="dates[${dateCount}][meals][]" value="昼"> 昼食</label>
      <label><input type="checkbox" name="dates[${dateCount}][meals][]" value="夕"> 夕食</label>
    </fieldset>
  `;
  container.appendChild(block);
  dateCount++;
}

function toggleContrast() {
  document.body.classList.toggle('high-contrast');
}

function changeFontSize(size) {
  if (size === 'small') document.body.style.fontSize = '14px';
  if (size === 'medium') document.body.style.fontSize = '18px';
  if (size === 'large') document.body.style.fontSize = '22px';
}
</script>
</body>
</html>