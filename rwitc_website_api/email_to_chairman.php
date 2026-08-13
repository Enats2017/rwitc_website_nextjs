<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");

// --------------------------------------------------
// CONFIG / DEPENDENCIES
// --------------------------------------------------
// These three PHPMailer files are expected to exist locally at this
// same relative path (PHPMailer/), as confirmed. Kept unchanged from
// the original — only the code around them was rewritten.

// require_once "config.php"; // must provide $conn (mysqli connection)  // Live config.php (used in production)
require_once __DIR__ . "/config/config.php";  // Local config.php (used in production)
// require_once 'PHPMailer/PHPMailerAutoload.php';
require_once 'PHPMailer/class.phpmailer.php';
require_once 'PHPMailer/class.smtp.php';

// --------------------------------------------------
// MAIL / RECAPTCHA CONFIG
// --------------------------------------------------
// TODO: move these out of source control and into config.php (or env
// vars) — they were hardcoded in the original file too, this just
// centralizes them at the top instead of scattering them inline.

// const RECAPTCHA_SECRET_KEY = '6Ld4_qcgAAAAAPvbqUjY5ErbJJT27T-CLke-XtIQ';  // Live key (used in production)
const RECAPTCHA_SECRET_KEY = '6Ldq-IEtAAAAAF0iWpY7CmTsTpUy8FOFUXFD_wR3';     // Test key (used in development)
 
const RECAPTCHA_VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

// const SMTP_HOST     = 'smtp-relay.sendinblue.com'; // Live host (used in production)

const SMTP_HOST     = 'smtp.gmail.com'; // local test host (used in development)
const SMTP_PORT     = 587;
 
// const SMTP_USERNAME = 'info3@rwitc.com'; // Live username (used in production)
const SMTP_USERNAME = 'rajangupta7790@gmail.com'; // local test username (used in development)

// const SMTP_PASSWORD = '5A7BgS9aTjdUy6Ib';  // Live password (used in production)
const SMTP_PASSWORD = 'mhenpctylpkjozwu';  // Test password (used in development)
const SMTP_FROM_NAME = 'Rwitc';

// const CONTACT_TO_EMAIL = 'secretary@rwitc.com';  // Live email (used in production)

const CONTACT_TO_EMAIL = 'rajangupta7790@gmail.com'; // Test email (used in development)

const CONTACT_SUBJECT  = 'Royal Western India Turf Club Ltd.';

// --------------------------------------------------
// HELPERS
// --------------------------------------------------

function respondJson(array $payload, int $httpCode = 200): void
{
    http_response_code($httpCode);
    echo json_encode($payload);
    exit;
}

// --------------------------------------------------
// ONLY ALLOW POST
// --------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respondJson(['success' => false, 'message' => 'Method not allowed'], 405);
}

// --------------------------------------------------
// VALIDATE INPUT
// --------------------------------------------------

$name        = trim($_POST['name'] ?? '');
$email       = trim($_POST['email'] ?? '');
$date        = trim($_POST['date'] ?? '');
$messageText = trim($_POST['message'] ?? '');
$recaptcha   = $_POST['g-recaptcha-response'] ?? '';

if ($name === '' || $email === '' || $messageText === '') {
    respondJson(['success' => false, 'message' => 'Name, email, and message are required'], 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respondJson(['success' => false, 'message' => 'Invalid email address'], 400);
}

if ($date === '') {
    $date = date('m/d/y');
}

if ($recaptcha === '') {
    respondJson(['success' => false, 'message' => 'reCAPTCHA verification is required'], 400);
}

// --------------------------------------------------
// VERIFY RECAPTCHA (via cURL, not file_get_contents — works even
// when allow_url_fopen is disabled, and lets us set a timeout)
// --------------------------------------------------

$ch = curl_init(RECAPTCHA_VERIFY_URL);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => http_build_query([
        'secret'   => RECAPTCHA_SECRET_KEY,
        'response' => $recaptcha,
    ]),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT        => 8,
]);

$recaptchaResponseRaw = curl_exec($ch);
$recaptchaError       = curl_errno($ch);
curl_close($ch);

if ($recaptchaError !== 0 || $recaptchaResponseRaw === false) {
    respondJson(['success' => false, 'message' => 'Could not verify reCAPTCHA, please try again'], 502);
}

$recaptchaResponse = json_decode($recaptchaResponseRaw);

if (!$recaptchaResponse || empty($recaptchaResponse->success)) {
    respondJson(['success' => false, 'message' => 'reCAPTCHA verification failed'], 400);
}

// --------------------------------------------------
// SAVE TO DATABASE (prepared statement — was raw string
// interpolation before, which was a SQL injection vulnerability)
// --------------------------------------------------

$insertStmt = $conn->prepare(
    "INSERT INTO email_to_chairman (name, email, date, text) VALUES (?, ?, ?, ?)"
);

if ($insertStmt === false) {
    respondJson(['success' => false, 'message' => 'Internal server error'], 500);
}

$insertStmt->bind_param("ssss", $name, $email, $date, $messageText);

if (!$insertStmt->execute()) {
    $insertStmt->close();
    respondJson(['success' => false, 'message' => 'Internal server error'], 500);
}

$insertStmt->close();

// --------------------------------------------------
// BUILD EMAIL BODY (escape user input before embedding in HTML —
// the original interpolated raw $name/$messages straight into HTML)
// --------------------------------------------------

$html  = '<html><body>';
$html .= '<p>Date: ' . htmlspecialchars($date, ENT_QUOTES, 'UTF-8') . '</p><br>';
$html .= '<p>Name: ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</p><br>';
$html .= '<p>Message: ' . nl2br(htmlspecialchars($messageText, ENT_QUOTES, 'UTF-8')) . '</p><br>';
$html .= '</body></html>';

// --------------------------------------------------
// SEND MAIL
// --------------------------------------------------

$mail = new PHPMailer();

$mail->IsSMTP();
$mail->SMTPAuth   = true;
$mail->Host       = SMTP_HOST;
$mail->Port       = SMTP_PORT;
$mail->Username   = SMTP_USERNAME;
$mail->Password   = SMTP_PASSWORD;
$mail->SMTPSecure = 'tls';
$mail->SMTPOptions = [
    'ssl' => [
        'verify_peer'       => false,
        'verify_peer_name'  => false,
        'allow_self_signed' => true,
    ],
];

// Note: SetFrom() with a user-supplied address can fail SPF/DKIM checks
// on the receiving end depending on mail provider config — kept as-is
// to match existing behaviour, but worth switching to a fixed "from"
// address + Reply-To: $email if deliverability becomes an issue.
$mail->SetFrom($email, SMTP_FROM_NAME);
$mail->AddReplyTo($email, $name);
$mail->Subject = CONTACT_SUBJECT;
$mail->isHTML(true);
$mail->Body = $html;
$mail->AddAddress(CONTACT_TO_EMAIL);

if ($mail->Send()) {
    respondJson(['success' => true, 'message' => 'Mail sent successfully']);
}

respondJson(['success' => false, 'message' => 'Mail could not be sent'], 502);