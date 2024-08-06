<?php
ini_set('max_execution_time', '0');
date_default_timezone_set("Asia/Kuala_Lumpur");
session_start();

$db_host = "localhost";
$db_user = "root";
$db_password = "";
$db_name = "dummy_database";

$db = mysqli_connect($db_host, $db_user, $db_password, $db_name) or die("Cannot connect to database.");

function mq($s)
{
    global $db;
    return mysqli_query($db, $s);
}

function mfa($s)
{
    return mysqli_fetch_array($s, MYSQLI_ASSOC);
}

function pr($s)
{
    echo "<pre>";
    print_r($s);
    echo "</pre>";
}

function createFolder($basePath, $folderPrefix, $index) // function to create folders
{
    $folderPath = $basePath . DIRECTORY_SEPARATOR . $folderPrefix . $index;
    if (!file_exists($folderPath)) { // check if folder does not exist
        mkdir($folderPath, 0777, true);
    }
    return $folderPath;
}

function webpImage($source, $quality = 100, $removeOld = false, $offset = 0)
{
    $folder_url = createFolder("M://images/products", "webp", $offset);
    $name = pathinfo($source, PATHINFO_FILENAME);
    $destination = $folder_url . DIRECTORY_SEPARATOR . $name . '.webp';
    $info = getimagesize($source);
    $isAlpha = false;
    if ($info['mime'] == 'image/jpeg') {
        $image = imagecreatefromjpeg($source);
    } elseif ($isAlpha = $info['mime'] == 'image/gif') {
        $image = imagecreatefromgif($source);
    } elseif ($isAlpha = $info['mime'] == 'image/png') {
        $image = imagecreatefrompng($source);
    } else {
        return $source;
    }
    if ($isAlpha) {
        imagepalettetotruecolor($image);
        imagealphablending($image, true);
        imagesavealpha($image, true);
    }
    imagewebp($image, $destination, $quality);

    if ($removeOld) {
        unlink($source);
    }

    return $destination;
}

function bulk_image_rename($download_image, $image_name, $offset = 0)
{
    $error = "";
    $original_name = basename($download_image);
    $original_extension = substr($original_name, strrpos($original_name, '.'));
    $types = array(
        'image/jpeg' => '.jpg',
        'image/png' => '.png',
        'image/gif' => '.gif'
    );

    $folder_url = createFolder("M://images/products", "img", $offset);
    $img = file_get_contents($download_image);
    $stored_name = $folder_url . DIRECTORY_SEPARATOR . $image_name . $original_extension;
    if ($img) {
        file_put_contents($stored_name, $img);

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimetype = finfo_file($finfo, $stored_name);

        if (isset($types[$mimetype])) {
            if ($types[$mimetype] != $original_extension) {
                rename($stored_name, $folder_url . DIRECTORY_SEPARATOR . $image_name . $types[$mimetype]);
            }
        } else {
            $error .= "Unknown Error file " . $download_image . "<br>";
        }
        finfo_close($finfo);
    } else {
        $error .= "Couldn't get file " . $download_image . "<br>";
    }

    return $error;
}

$limit = 4; // to test amount of products to process
$offset = 0; // Initial folder index

$sql = "SELECT `id`, `category_id`, `sub_category_id`, `image`, `supp1`, `supp2`, `supp3`, `remark` FROM product ORDER BY id asc LIMIT $limit";
$rs = mq($sql);

$product_arr = array();
$count = 0;
$totalCount = 0; // To keep track of the total processed items

while ($r = mfa($rs)) {
    $linkImage = $r['image'];   // variable to hold image link
    $linkSupp1 = $r['supp1'];   // variable to hold supplementary image link 1
    $linkSupp2 = $r['supp2'];   // variable to hold supplementary image link 2
    $linkSupp3 = $r['supp3'];   // variable to hold supplementary image link 3

    // conditions to check if links are not empty for each image link image
    if (!empty($linkSupp1)) {
        $suppPath1 = "https://merchant9.com" . str_replace('../', '/', $linkSupp1);
    } 
    if (!empty($linkSupp2)) {
        $suppPath2 = "https://merchant9.com" . str_replace('../', '/', $linkSupp2);
    } 
    if (!empty($linkSupp3)) {
        $suppPath3 = "https://merchant9.com" . str_replace('../', '/', $linkSupp3);
    } 
    if (!empty($linkImage)) {
        $imagePath = "https://merchant9.com" . str_replace('../', '/', $linkImage);
    }


    $folderIndex = intdiv($totalCount, 3); // Set the limit to 200 product items per folder can be 2-4 images per item

    $product_arr[$count] = [
        'id' => $r['id'],
        'image' => $imagePath,
        'supp1' => $suppPath1,
        'supp2' => $suppPath2,
        'supp3' => $suppPath3,
        'filename' => pathinfo($imagePath, PATHINFO_FILENAME),
        'filename1' => pathinfo($suppPath1, PATHINFO_FILENAME),
        'filename2' => pathinfo($suppPath2, PATHINFO_FILENAME),
        'filename3' => pathinfo($suppPath3, PATHINFO_FILENAME),
        'folderIndex' => $folderIndex
    ];

    $count++;
    $totalCount++;
}

$productCounter = 0;
foreach ($product_arr as $key => $value) {

    $folderIndex = $value['folderIndex'];
    if (isset($value['image'])) {
        $image_name = $value['filename'];
        bulk_image_rename($value['image'], $image_name, $folderIndex);
        webpImage($value['image'], 100, false, $folderIndex);
    }

    for ($j = 1; $j <= 3; $j++) {
        if (isset($value['supp' . $j])) {
            $supp_name = $value['filename' . $j];
            bulk_image_rename($value['supp' . $j], $supp_name, $folderIndex);
            webpImage($value['supp' . $j], 100, false, $folderIndex);
        }
    }
    echo 'product counter: ' . $productCounter . '-----' . $value['id'] . PHP_EOL;
    $productCounter++;
}

pr($product_arr);
?>