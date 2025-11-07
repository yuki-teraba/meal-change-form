<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

$error = "";
$success = "";

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

            $success = "送信しました。";
        } catch (Exception $e) {
            $error = "メールの送信に失敗しました: " . $mail->ErrorInfo;
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
  <?php if (!empty($success)): ?>
    <p style="color:green;"><?php echo htmlspecialchars($success); ?></p>
  <?php endif; ?>

  <form method="post">
    <label>氏名（必須）</label>
    <input type="text" name="name" required>

    <label>メールアドレス（任意）</label>
    <input type="email" name="email">

    <h2>欠食日と食事区分</h2>
    <div class="date-block">
      <label>日付（必須）</label>
      <input type="date" name="dates[0][date]" required>
      <fieldset>
        <legend>食事区分（必須）</legend>
        <label><input type="checkbox" name="dates[0][meals][]" value="朝"> 朝食</label>
        <label><input type="checkbox" name="dates[0][meals][]" value="昼"> 昼食</label>
        <label><input type="checkbox" name="dates[0][meals][]" value="夕"> 夕食</label>
      </fieldset>
    </div>

    <label>その他連絡事項（任意）</label>
    <textarea name="notes" rows="4" cols="40"></textarea>

    <button type="submit">送信</button>
  </form>
</body>
</html>
