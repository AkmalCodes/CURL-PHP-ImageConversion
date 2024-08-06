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

// Function to convert WebP to its original format
function convertWebP($source, $destinationDir)
{
    $name = pathinfo($source, PATHINFO_FILENAME);
    $destinationJPG = $destinationDir . DIRECTORY_SEPARATOR . $name . '.jpg';

    $image = imagecreatefromwebp($source);
    if ($image === false) {
        echo "Failed to create image from WebP: $source\n";
        return false;
    }

    // Save as JPG
    if (imagejpeg($image, $destinationJPG, 100)) {
        echo "Converted and saved as JPG: $destinationJPG\n";
    } else {
        echo "Failed to save as JPG: $destinationJPG\n";
    }

    imagedestroy($image);
    return true;
}

// Source and destination directories
$sourceDir = 'M:/images/products/webp24';
$destinationDir = createFolder('M:/images/products/img24');

// Get all WebP files in the source directory
$webpFiles = glob($sourceDir . '/*.webp');

// Process each WebP file
foreach ($webpFiles as $webpFile) {
    convertWebP($webpFile, $destinationDir);
}

echo "Conversion process completed.\n";
?>
