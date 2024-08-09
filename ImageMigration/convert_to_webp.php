<?php
// Set unlimited maximum execution time and set timezone to Kuala Lumpur
ini_set('max_execution_time', '0');
date_default_timezone_set("Asia/Kuala_Lumpur");

// Function to create folders if they don't exist
function createFolder($basePath)
{
    if (!file_exists($basePath)) {
        mkdir($basePath, 0777, true);
    }
    return $basePath;
}

// Function to convert images to WebP format
function convertToWebP($source, $destinationDir, $quality = 100)
{
    $name = pathinfo($source, PATHINFO_FILENAME);
    $destination = $destinationDir . DIRECTORY_SEPARATOR . $name . '.webp';
    $info = getimagesize($source);
    $isAlpha = false;

    // Check image mime type and create image from source
    if ($info['mime'] == 'image/jpeg') {
        $image = imagecreatefromjpeg($source);
    } elseif ($isAlpha = $info['mime'] == 'image/gif') {
        $image = imagecreatefromgif($source);
    } elseif ($isAlpha = $info['mime'] == 'image/png') {
        $image = imagecreatefrompng($source);
    } else {
        echo "Unsupported image type: $source\n";
        return false;
    }

    if ($image === false) {
        echo "Failed to create image from source: $source\n";
        return false;
    }

    // Preserve transparency for PNG and GIF
    if ($isAlpha) {
        imagepalettetotruecolor($image);
        imagealphablending($image, true);
        imagesavealpha($image, true);
    }

    // Convert and save the image as WebP
    if (imagewebp($image, $destination, $quality)) {
        echo "Converted and saved as WebP: $destination\n";
    } else {
        echo "Failed to save as WebP: $destination\n";
    }

    imagedestroy($image);
    return true;
}

// Loop through folders img0 to img49
for ($i = 0; $i < 50; $i++) {
    $imgFolder = "M:/images/products/img$i";
    $webpFolder = createFolder("M:/images/products/webp$i");

    // Get all image files in the img folder
    $imgFiles = glob($imgFolder . '/*.{jpg,jpeg,png,gif}', GLOB_BRACE);

    // Convert each image to WebP format
    foreach ($imgFiles as $imgFile) {
        convertToWebP($imgFile, $webpFolder);
    }
}

echo "Conversion process completed.\n";
?>
