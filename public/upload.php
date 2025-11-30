<?php
require __DIR__ . '/../config/cloudinary.php';

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    die("Upload error: No file selected or upload failed");
}

$path = $_FILES['image']['tmp_name'];

try {
    $result = $cloudinary->uploadApi()->upload($path);

    echo "<h2>Upload Successful!</h2>";
    echo "<img src='" . $result['secure_url'] . "' width='300'><br>";
    echo "<strong>URL:</strong> " . $result['secure_url'] . "<br>";
    echo "<strong>Public ID:</strong> " . $result['public_id'] . "<br>";

} catch (Exception $e) {
    echo "Upload failed: " . $e->getMessage();
}
