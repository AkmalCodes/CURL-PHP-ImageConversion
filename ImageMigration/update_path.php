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

// Function to update image paths in the database
function updateImagePaths($id, $image, $supp1, $supp2, $supp3)
{
    global $db;
    $sql = "UPDATE product SET image = '$image', supp1 = '$supp1', supp2 = '$supp2', supp3 = '$supp3' WHERE id = $id";
    return mysqli_query($db, $sql);
}

// Fetch product records from the database
$sql = "SELECT `id`, `image`, `supp1`, `supp2`, `supp3` FROM product";
$rs = mq($sql);

$count = 0;
$directory_index = 0;

while ($r = mfa($rs)) {
    $id = $r['id'];
    $image = $r['image'];
    $supp1 = $r['supp1'];
    $supp2 = $r['supp2'];
    $supp3 = $r['supp3'];

    $webp_dir = "webp" . $directory_index;

    // Update paths
    if (!empty($image)) {
        $image = str_replace('../products/', "../images/products/$webp_dir/", $image);
        $image = str_replace(['.png', '.jpg', '.jpeg'], '.webp', $image);
    }
    if (!empty($supp1)) {
        $supp1 = str_replace('../products/', "../images/products/$webp_dir/", $supp1);
        $supp1 = str_replace(['.png', '.jpg', '.jpeg'], '.webp', $supp1);
    }
    if (!empty($supp2)) {
        $supp2 = str_replace('../products/', "../images/products/$webp_dir/", $supp2);
        $supp2 = str_replace(['.png', '.jpg', '.jpeg'], '.webp', $supp2);
    }
    if (!empty($supp3)) {
        $supp3 = str_replace('../products/', "../images/products/$webp_dir/", $supp3);
        $supp3 = str_replace(['.png', '.jpg', '.jpeg'], '.webp', $supp3);
    }

    // Update database
    if (!updateImagePaths($id, $image, $supp1, $supp2, $supp3)) {
        echo "Failed to update paths for product ID: $id\n";
    } else {
        echo "Updated paths for product ID: $id\n";
    }

    $count++;
    if ($count % 200 == 0) {
        $directory_index++;
    }
}

echo "Path update process completed.\n";
?>
