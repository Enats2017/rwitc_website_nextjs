<?php

/**
 * Password rules (server side).
 * Same rules browser ki checklist me dikhte hain.
 * Return: error message (string) ya null agar password sahi hai.
 */
function rwPasswordStrengthError($pw)
{
    if (strlen($pw) < 8) {
        return "Password must be at least 8 characters.";
    }
    if (!preg_match('/[A-Z]/', $pw)) {
        return "Password must contain at least 1 uppercase letter.";
    }
    if (!preg_match('/[a-z]/', $pw)) {
        return "Password must contain at least 1 lowercase letter.";
    }
    if (!preg_match('/[0-9]/', $pw)) {
        return "Password must contain at least 1 number.";
    }
    if (!preg_match('/[^A-Za-z0-9]/', $pw)) {
        return "Password must contain at least 1 special character.";
    }
    return null;
}