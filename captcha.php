<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate random 5-character security code
$md5_hash = md5(rand(0, 99999) . microtime()); 
$security_code = substr($md5_hash, 15, 5); 

// Store in session
$_SESSION["security_code"] = $security_code;

// Set image dimensions
$width = 120; 
$height = 38;  

// Create image resource
$image = ImageCreate($width, $height);  

// Color palette
$bg_color = ImageColorAllocate($image, 15, 92, 51); // RWITC Green
$text_color = ImageColorAllocate($image, 255, 255, 255); // White text
$line_color = ImageColorAllocate($image, 140, 195, 165); // Soft green line noise

// Fill background
ImageFill($image, 0, 0, $bg_color); 

// Render text with padding
ImageString($image, 5, 36, 10, $security_code, $text_color); 

// Draw noise lines for anti-bot protection
ImageRectangle($image, 0, 0, $width-1, $height-1, $line_color); 
imageline($image, 0, 19, $width, 19, $line_color); 
imageline($image, 40, 0, 40, $height, $line_color); 
imageline($image, 80, 0, 80, $height, $line_color); 

// Headers to prevent caching
header("Content-Type: image/jpeg"); 
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

// Output image
ImageJpeg($image); 

// Free resources
ImageDestroy($image);
?>