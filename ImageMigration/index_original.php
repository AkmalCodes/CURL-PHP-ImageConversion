<?php
ini_set('max_execution_time', '0');
?>
<?php

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
  return mysqli_fetch_array($s);
}
function mnr($s)
{
  return mysqli_num_rows($s);
}

function pr($s)
{
  echo "<pre>";
  print_r($s);
  echo "</pre>";
}

function hsc($s)
{
  return htmlspecialchars($s, ENT_QUOTES);
}

function frmp($s)
{
  if (isset($_POST[$s]))
    return hsc($_POST[$s]);
  else
    return "";
}

function frmg($s)
{
  if (isset($_GET[$s]))
    return hsc($_GET[$s]);
  else
    return "";
}

function frm($s)
{
  if (frmp($s) != "")
    return frmp($s);
  else if (frmg($s) != "")
    return frmg($s);
  else
    return "";
}

function webpImage($source, $quality = 100, $removeOld = false, $offset)
{
  $folder_url = "M://images/products/test/webp" . $offset . "/";
  $dir = pathinfo($source, PATHINFO_DIRNAME);
  $name = pathinfo($source, PATHINFO_FILENAME);
  $destination = $folder_url . DIRECTORY_SEPARATOR . $name . '.webp';
  $info = getimagesize($source);
  $isAlpha = false;
  if ($info['mime'] == 'image/jpeg')
    $image = imagecreatefromjpeg($source);
  elseif ($isAlpha = $info['mime'] == 'image/gif') {
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

  if ($removeOld)
    unlink($source);

  return $destination;
}

$limit = 5;
$offset = 0;

$sql = "SELECT `id`, `category_id`, `sub_category_id`, `image`, `supp1`, `supp2`, `supp3`, `remark` FROM product ORDER BY id desc LIMIT " . $limit . " OFFSET " . $offset;
echo $sql;
$rs = mq($sql);

$product_arr = array();

$count = 0;

while ($r = mfa($rs)) {
  $linkImage = $r['image'];
  if ($linkImage != "" || $linkImage != NULL) {
    $imagePath = "https://merchant9.com" . str_replace('../', '/', $linkImage);
    $product_arr[$count]['image'] = $imagePath;
    $filename = pathinfo($imagePath, PATHINFO_FILENAME);
    $extension = pathinfo($imagePath, PATHINFO_EXTENSION);
    // $product_arr[$count]['remark'] = $r['remark'];
    $product_arr[$count]['filename'] = $filename;
    $product_arr[$count]['extension'] = $extension;
  }

  for ($i = 1; $i <= 3; $i++) {
    $linkSupp = $r['supp' . $i];
    if ($linkSupp != "" || $linkSupp != NULL) {
      $suppPath = "https://merchant9.com" . str_replace('../', '/', $linkSupp);
      $product_arr[$count]['supp' . $i] = $suppPath;
      $filename = pathinfo($suppPath, PATHINFO_FILENAME);
      $extension = pathinfo($suppPath, PATHINFO_EXTENSION);
      // $product_arr[$count]['remark'] = $r['remark'];
      $product_arr[$count]['filename' . $i] = $filename;
      $product_arr[$count]['extension' . $i] = $extension;
    }
  }

  $count++;
}

pr($product_arr);

foreach ($product_arr as $key => $value) {
  if (isset($value['image'])) {
    bulk_image_rename($value['image'], $value['filename']);
    webpImage($value['image'], $quality = 100, $removeOld = false, $offset);
    bulk_image_rename(str_replace('.' . $value['extension'], '_.' . $value['extension'], $value['image']), $value['filename'] . '_');
    webpImage(str_replace('.' . $value['extension'], '_.' . $value['extension'], $value['image']), $quality = 100, $removeOld = false, $offset);
  }

  for ($j = 1; $j <= 3; $j++) {
    if (isset($value['supp' . $j])) {
      webpImage($value['supp' . $j], $quality = 100, $removeOld = false, $offset);
      bulk_image_rename($value['supp' . $j], $value['filename' . $j]);
    }
  }


}

function bulk_image_rename($download_image, $image_name)
{

  $error = "";
  // Assume this URL for $download_image from your CSV
// $download_image = 'https://merchant9.com/products/qv95gqf1qfwq0ew5.jpg';
// $image_name = 'RLPIXX59K15G';

  // Store the original filename
  $original_name = basename($download_image); // "img1.jpg"
// Original extension by string manipulation
  $original_extension = substr($original_name, strrpos($original_name, '.')); // ".jpg"

  // An array to match mime types from finfo_file() with extensions
// Use of finfo_file() is recommended if you can't trust the input
// filename's extension
  $types = array(
    'image/jpeg' => '.jpg',
    'image/png' => '.png',
    'image/gif' => '.gif'
    // Other types as needed...
  );

  $folder_url = "M://images/products/test/img" . $offset . "/";

  // Get the file and save it
  $img = file_get_contents($download_image);
  $stored_name = $folder_url . $image_name . $original_extension;
  if ($img) {
    file_put_contents($stored_name, $img);

    // Get the filesize if needed
    $size = filesize($stored_name);

    // If you don't care about validating the mime type, skip all of this...
    // Check the file information
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimetype = finfo_file($finfo, $stored_name);

    // Lookup the type in your array to get the extension
    if (isset($types[$mimetype])) {
      // if the reported type doesn't match the original extension, rename the file
      if ($types[$mimetype] != $original_extension) {
        rename($stored_name, $folder_url . $image_name . $types[$mimetype]);
      }
    } else {
      // unknown type, handle accordingly...
      $error .= "Unknown Error file " . $download_image . "<br>";
    }
    finfo_close($finfo);

    // Now save all the extra info you retrieved into your database however you normally would
    // $mimetype, $original_name, $original_extension, $filesize
  } else {
    // Error, couldn't get file
    $error .= "Couldn't get file " . $download_image . "<br>";
  }

  return $error;
}

?>