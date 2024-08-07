<?php
// Start tracking time
$start_time = microtime(true);

// Set unlimited maximum execution time and set timezone to Kuala Lumpur
ini_set('max_execution_time', '0');
date_default_timezone_set("Asia/Kuala_Lumpur");

// Start session
session_start();

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

// Function to print readable arrays
function pr($s)
{
    echo "<pre>";
    print_r($s);
    echo "</pre>";
}

// Function to create folders if they don't exist
function createFolder($basePath, $folderPrefix, $index)
{
    $folderPath = $basePath . DIRECTORY_SEPARATOR . $folderPrefix . $index;
    if (!file_exists($folderPath)) {
        mkdir($folderPath, 0777, true);
    }
    return $folderPath;
}

// Function to convert images to WebP format
function webpImage($source, $quality = 100, $offset = 0)
{
    // Create the WebP folder if it doesn't exist
    $folder_url = createFolder("M://images/products", "webp", $offset);
    $name = pathinfo($source, PATHINFO_FILENAME);
    $destination = $folder_url . DIRECTORY_SEPARATOR . $name . '.webp';
    $info = getimagesize($source);
    $isAlpha = false;

    // Check image mime type and create image from source
    if ($info['mime'] == 'image/jpeg') {
        $image = @imagecreatefromjpeg($source);
    } elseif ($isAlpha = $info['mime'] == 'image/gif') {
        $image = @imagecreatefromgif($source);
    } elseif ($isAlpha = $info['mime'] == 'image/png') {
        $image = @imagecreatefrompng($source);
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
    imagewebp($image, $destination, $quality);

    return $destination;
}

// $limit = 4;// Set the limit for the of records to retrieve
$offset = 0; // Initial folder index
$batch_size = 30; // Limit the number of concurrent downloads
$initial_folder_index = 76; // Starting folder index
$max_retries = 5; // Maximum number of retries for failed downloads

// Fetch product records from the database
$sql = "SELECT `id`, `category_id`, `sub_category_id`, `image`, `supp1`, `supp2`, `supp3`, `remark` FROM product WHERE id >= 15855 AND id <= 21689 ORDER BY id asc";
//id thresholds 8,5314 done!    
//id thresholds 5315,10534 done!
//id thresholds 10535,15854 done!
//id thresholds 15855,21689

$rs = mq($sql);

$product_arr = array();
$count = 0;
$totalCount = 0; // To keep track of the total processed items

// Loop through the fetched records
while ($r = mfa($rs)) {

    $linkImage = $r['image'];   // variable to hold image link
    $linkSupp1 = $r['supp1'];   // variable to hold supplementary image link 1
    $linkSupp2 = $r['supp2'];   // variable to hold supplementary image link 2
    $linkSupp3 = $r['supp3'];   // variable to hold supplementary image link 3

    // Conditions to check if links are not empty for each image link
    if (!empty($linkSupp1) || $linkSupp1 === null) {
        $suppPath1 = "https://merchant9.com" . str_replace('../', '/', $linkSupp1);
        $linkSupp1 = null; // reset variable to null after processing
    }
    if (!empty($linkSupp2) || $linkSupp2 === null) {
        $suppPath2 = "https://merchant9.com" . str_replace('../', '/', $linkSupp2);
        $linkSupp2 = null; // reset variable to null after processing
    }
    if (!empty($linkSupp3) || $linkSupp3 === null) {
        $suppPath3 = "https://merchant9.com" . str_replace('../', '/', $linkSupp3);
        $linkSupp3 = null; // reset variable to null after processing
    }
    if (!empty($linkImage) || $linkImage === null) {
        $imagePath = "https://merchant9.com" . str_replace('../', '/', $linkImage);
        $linkImage = null; // reset variable to null after processing
    }

    // Calculate the folder index based on the total count
    $folderIndex = $initial_folder_index + intdiv($totalCount, 200); // Set the limit to 200 product items per folder can be 2-4 images per item

    // Store product information in the array
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
        'extension' => pathinfo($imagePath, PATHINFO_EXTENSION),
        'extension1' => pathinfo($suppPath1, PATHINFO_EXTENSION),
        'extension2' => pathinfo($suppPath2, PATHINFO_EXTENSION),
        'extension3' => pathinfo($suppPath3, PATHINFO_EXTENSION),
        'folderIndex' => $folderIndex
    ];

    $count++;
    $totalCount++;
}

// Function to download an image with retries
function downloadImage($url, $max_retries = 3)
{
    $retry = 0;
    $content = false;
    while ($retry < $max_retries) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification (for debugging purposes)
        $content = curl_exec($ch);
        $info = curl_getinfo($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($info['http_code'] == 200 && !empty($content)) {
            return $content;
        } else {
            echo "Retry $retry for image: $url, HTTP Code: {$info['http_code']}, cURL Error: $error\n";
            $retry++;
        }
    }
    return false;
}

// Function to process a batch of images
function processBatch($batch, $start_time, $max_retries)
{
    foreach ($batch as $value) {
        $urls = [
            'image' => $value['image'],
            'supp1' => $value['supp1'],
            'supp2' => $value['supp2'],
            'supp3' => $value['supp3']
        ];

        foreach ($urls as $type => $url) {
            if ($url) {
                $content = downloadImage($url, $max_retries);
                $image_name = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_FILENAME);
                $extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
                $folderIndex = $value['folderIndex'];

                if ($content) {
                    // Save the original image in the 'img' folder with its original extension
                    $img_folder_url = createFolder("M://images/products", "img", $folderIndex);
                    $img_stored_name = $img_folder_url . DIRECTORY_SEPARATOR . $image_name . '.' . $extension;
                    file_put_contents($img_stored_name, $content);
                    echo "downloaded image: $url" . PHP_EOL;

                    // Verify the saved image before conversion
                    if (getimagesize($img_stored_name) !== false) {
                        // Convert the image to WebP format and save it in the 'webp' folder
                        webpImage($img_stored_name, 100, $folderIndex);
                        echo "downloaded webp: $img_stored_name" . PHP_EOL;
                    } else {
                        echo "Invalid image file: $img_stored_name\n";
                    }
                } else {
                    echo "Failed to download image after $max_retries retries: $url\n";
                }
            }
        }
    }

    $end_time_download = microtime(true);
    $execution_time = $end_time_download - $start_time;
    echo "Batch processed in $execution_time seconds.\n";
}

// Process the images in batches
$batches = array_chunk($product_arr, $batch_size);
foreach ($batches as $batch) {
    processBatch($batch, $start_time, $max_retries);
}

// Print the processed product array
pr($product_arr);

// End tracking time and display the execution time
$end_time = microtime(true);
$execution_time = $end_time - $start_time;
echo "Script executed in $execution_time seconds.\n";
?>
