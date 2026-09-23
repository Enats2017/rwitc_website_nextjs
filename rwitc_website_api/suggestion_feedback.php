<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");


include __DIR__ . '/config/config.php';

// require_once('PHPMailer/PHPMailerAutoload.php');
require_once('PHPMailer/class.phpmailer.php');
require_once('PHPMailer/class.smtp.php');

$current_date = date('Y-m-d');

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
    exit;
}

$name = isset($_POST['name']) ? $_POST['name'] : '';
$email = isset($_POST['email']) ? $_POST['email'] : '';
$message = isset($_POST['message']) ? $_POST['message'] : '';

if (empty($name) || empty($email) || empty($message)) {
    echo json_encode(["success" => false, "message" => "All fields are required"]);
    exit;
}

$name_safe = mysqli_real_escape_string($conn, $name);
$email_safe = mysqli_real_escape_string($conn, $email);
$message_safe = mysqli_real_escape_string($conn, $message);

$sql = "INSERT INTO suggestion_feedback SET name = '".$name_safe."', email = '".$email_safe."', date = '".$current_date."', text = '".$message_safe."'";
$res = mysqli_query($conn, $sql);

if (!$res) {
    echo json_encode(["success" => false, "message" => "Failed to save suggestion"]);
    exit;
}

// ---------------- reCAPTCHA verify ----------------
$recaptcha = $_POST['g-recaptcha-response'] ?? '';

if ($recaptcha === '') {
    echo json_encode(["success" => false, "message" => "Please verify the captcha"]);
    exit;
}

// Live & local key (used in production)
$secret_key = RECAPTCHA_SECRET_KEY;



$ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => http_build_query([
        'secret'   => $secret_key,
        'response' => $recaptcha,
    ]),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT        => 8,
]);

$recaptchaRaw = curl_exec($ch);
curl_close($ch);

$recaptchaResponse = json_decode($recaptchaRaw);

if (!$recaptchaResponse || empty($recaptchaResponse->success)) {
    echo json_encode(["success" => false, "message" => "Captcha verification failed"]);
    exit;
}

// ---------------- Email send ) ----------------
$html = '';
$html .= '<html>';
$html .= '<body>';
$html .= '<p>Date:'.$current_date.'</p>';
$html .= '</br>';
$html .= '<p>Name:'.$name.'</p>';
$html .= '</br>';
$html .= '<p>Message:'.$message.'</p>';
$html .= '</br>';
$html .= '</body>';
$html .= '</html>';

$mail = new PHPMailer();
$subject = "Royal Western India Turf Club Ltd.";
$mail->IsSMTP();
$mail->SMTPAuth = true;
$mail->Host = 'smtp-relay.sendinblue.com';
$mail->Port = '587';
$mail->Username = 'info3@rwitc.com';
$mail->Password = '5A7BgS9aTjdUy6Ib';
$mail->SMTPSecure = 'tls';
$mail->SMTPOptions = array(
    'ssl' => array(
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true
    )
);
$mail->SetFrom($email, 'Rwitc');
$mail->Subject = $subject;
$mail->isHTML(true);
$mail->Body = html_entity_decode($html);
$mail->AddAddress('dgmam@rwitc.com');

$mailSent = $mail->Send();

echo json_encode([
    "success" => true,
    "message" => "Suggestion submitted successfully",
    "mail_sent" => $mailSent
]);

exit;