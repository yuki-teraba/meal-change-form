<?php
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
            $mail->Username = 'terabayasiyuuki@gmail.com'; // あなたのGmail
            $mail->Password = 'yvvxhksukvxlfbyc';           // アプリパスワード（スペースなし）
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
