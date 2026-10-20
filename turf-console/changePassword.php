<?php

// JSON endpoint: popup se aaya hua naya password save karta hai.

ini_set('display_errors', 0);

require_once("../bootstrap.php");
require_once("../lib/dbTools.php");
require_once("../lib/passwordpolicy.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

function pwOut($ok, $message)
{
    echo json_encode(array('ok' => $ok, 'message' => $message));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    pwOut(false, 'Invalid request.');
}

// Sirf wahi user jiske liye password change compulsory hai
if (empty($_SESSION['uid']) || empty($_SESSION['force_pw_change'])) {
    pwOut(false, 'Not allowed.');
}

$newPw  = isset($_POST['new_password']) ? $_POST['new_password'] : '';
$confPw = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

if ($newPw !== $confPw) {
    pwOut(false, 'Passwords do not match.');
}

$err = rwPasswordStrengthError($newPw);
if ($err) {
    pwOut(false, $err);
}

try {

    $db  = new dbTool();
    $uid = (int)$_SESSION['uid'];

    $row = $db->getSingleRowAssoc("SELECT password FROM admins WHERE id = $uid");

    if (!$row) {
        pwOut(false, 'User not found.');
    }

    // users.php jaisa hi hash
    $newHash = '*' . strtoupper(sha1(sha1($newPw, true)));

    if (strtoupper($row['password']) === $newHash) {
        pwOut(false, 'New password cannot be the same as the temporary/old password.');
    }

    $db->update(
        "UPDATE admins SET
            password = '" . $db->escape($newHash) . "',
            must_change_password = 0,
            password_changed_at = NOW()
         WHERE id = $uid"
    );

    unset($_SESSION['force_pw_change']);

    pwOut(true, 'Password changed successfully.');
} catch (Exception $e) {

    pwOut(false, 'Could not save the password. Please try again.');
}