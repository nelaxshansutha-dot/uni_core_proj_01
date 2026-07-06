<?php
require_once __DIR__ . '/../config/Cors.php';
Cors::enable();
require_once __DIR__ . '/../utils/AuthMiddleware.php';
require_once __DIR__ . '/../utils/Response.php';


// Authenticate
AuthMiddleware::authenticate();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error("Method not allowed.", 405);
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    Response::error("No valid image file uploaded.");
}

$file      = $_FILES['image'];
$maxSize   = 5 * 1024 * 1024; // 5 MB
$allowed   = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

if ($file['size'] > $maxSize) {
    Response::error("Image must be smaller than 5 MB.");
}

$finfo    = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowed)) {
    Response::error("Only JPG, PNG, WebP and GIF images are allowed.");
}

// Build destination
$uploadDir = __DIR__ . '/../../uploads/marketplace/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = uniqid('img_', true) . '.' . $ext;
$dest     = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    Response::error("Failed to save uploaded image.", 500);
}

// Return the public URL
$protocol  = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$host      = $_SERVER['HTTP_HOST'];
$publicUrl = $protocol . '://' . $host . '/uni_core_proj_01/uploads/marketplace/' . $filename;

Response::success("Image uploaded successfully.", ['url' => $publicUrl]);
?>
