<?php
// Set unlimited maximum execution time and set timezone to Kuala Lumpur
ini_set('max_execution_time', '0');
date_default_timezone_set("Asia/Kuala_Lumpur");

// Database connection settings
$db_host = "localhost";
$db_user = "root";
$db_password = "";
$db_name = "dummy_database";

// Connect to the database
$db = mysqli_connect($db_host, $db_user, $db_password, $db_name) or die("Cannot connect to database.");

// Function to execute a MySQL query
function mq($s)
{
    global $db;
    return mysqli_query($db, $s);
}

// Function to fetch associative array from query result
function mfa($s)
{
    return mysqli_fetch_array($s, MYSQLI_ASSOC);
}

// Function to create folders if they don't exist
function createFolder($basePath)
{
    if (!file_exists($basePath)) {
        mkdir($basePath, 0777, true);
    }
    return $basePath;
}

// Function to resize an image
function resizeImage($source, $destination, $width, $height)
{
    list($orig_width, $orig_height) = getimagesize($source);
    $image_p = imagecreatetruecolor($width, $height);

    $image = null;
    $ext = strtolower(pathinfo($source, PATHINFO_EXTENSION));
    if ($ext == 'jpeg' || $ext == 'jpg') {
        $image = imagecreatefromjpeg($source);
    } elseif ($ext == 'png') {
        $image = imagecreatefrompng($source);
    } elseif ($ext == 'gif') {
        $image = imagecreatefromgif($source);
    } else {
        echo "Unsupported image format: $source\n";
        return false;
    }

    if ($image !== null) {
        imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $orig_width, $orig_height);
        if ($ext == 'jpeg' || $ext == 'jpg') {
            imagejpeg($image_p, $destination);
        } elseif ($ext == 'png') {
            imagepng($image_p, $destination);
        } elseif ($ext == 'gif') {
            imagegif($image_p, $destination);
        }
        imagedestroy($image_p);
        imagedestroy($image);
        return true;
    }
    return false;
}

// Fetch image URLs from the database
$sql = "SELECT `image` FROM product  WHERE id >= 10535 AND id <= 21689 ORDER BY id asc";
//id thresholds 8,5314 done!    
//id thresholds 5315,10534 
//id thresholds 10535,15718 
//id thresholds 15719,21689
$rs = mq($sql);

while ($r = mfa($rs)) {
    $image_url = $r['image'];  // Example: ../webp0/t7fs6f9mkrvhbm24.webp

    // Parse the URL to extract the folder number and filename
    if (preg_match('/webp(\d+)\/(.+)\.webp$/', $image_url, $matches)) {
        $folder_number = $matches[1];   // Extracted folder number (e.g., 0)
        $filename = $matches[2];        // Extracted filename (e.g., t7fs6f9mkrvhbm24)
        
        // Construct the path to the original image in the img folder
        $img_folder = "M:/images/products/img$folder_number";
        $img_path = "$img_folder/$filename";

        // Find the file with the same name but different extensions
        $extensions = ['jpg', 'jpeg', 'png', 'gif'];
        foreach ($extensions as $ext) {
            $full_img_path = "$img_path.$ext";
            if (file_exists($full_img_path)) {
                // Resize the image to 400x400 pixels and save it with an underscore
                $new_filename = "$img_folder/{$filename}_.$ext";
                if (resizeImage($full_img_path, $new_filename, 400, 400)) {
                    echo "Resized and saved $new_filename\n";
                } else {
                    echo "Failed to resize $full_img_path\n";
                }
                break;  // Stop after finding and processing the first match
            }
        }
    } else {
        echo "Invalid image URL format: $image_url\n";
    }
}

echo "Image processing completed.\n";
?>
