<?php
$upload_dir = 'uploads/quote_proofs/';

if (!file_exists($upload_dir)) {
    if (mkdir($upload_dir, 0755, true)) {
        echo "<p style='color: green;'>✅ Upload directory created successfully: $upload_dir</p>";
    } else {
        echo "<p style='color: red;'>❌ Failed to create upload directory: $upload_dir</p>";
    }
} else {
    echo "<p style='color: blue;'>ℹ️ Upload directory already exists: $upload_dir</p>";
}

// Create a test image for demonstration
$test_image_path = $upload_dir . 'sample_quote_proof.jpg';
if (!file_exists($test_image_path)) {
    // Create a simple test image using GD
    if (extension_loaded('gd')) {
        $img = imagecreate(400, 300);
        $bg_color = imagecolorallocate($img, 240, 240, 240);
        $text_color = imagecolorallocate($img, 50, 50, 50);
        
        imagestring($img, 5, 100, 140, "Sample Quote Proof", $text_color);
        
        if (imagejpeg($img, $test_image_path)) {
            echo "<p style='color: green;'>✅ Sample proof image created: $test_image_path</p>";
        }
        imagedestroy($img);
    } else {
        echo "<p style='color: orange;'>⚠️ GD extension not loaded, couldn't create test image</p>";
    }
}

echo "<h3>Testing File Upload Structure</h3>";
echo "<p>Upload directory: " . realpath($upload_dir) . "</p>";
echo "<p>Directory writable: " . (is_writable($upload_dir) ? 'Yes' : 'No') . "</p>";
?>