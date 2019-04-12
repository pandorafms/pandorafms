<?php

$bases = [
    'gray'           => 'png',
    'blue-marble'    => 'jpg',
    'living'         => 'jpg',
    'night-electric' => 'jpg',
];
$width = isset($_GET['w']) ? min((int) $_GET['w'], 1280) : 600;
$height = round($width / 2);

if (isset($_GET['base']) && isset($bases[$_GET['base']])) {
    $base = $_GET['base'];
    $extension = $bases[$_GET['base']];
} else {
    $base = reset(array_keys($bases));
    $extension = reset($bases);
}

$source = $base.'-1280.'.$extension;
$open_extension = str_replace('jpg', 'jpeg', $extension);
$open_func = 'imagecreatefrom'.$open_extension;

$im = $open_func($source);
if (!$im) {
    return false;
}

list($original_width, $original_height) = getimagesize($source);

$res = imagecreatetruecolor($width, $height);
if ($extension == 'png') {
    $transparency = imagecolorallocatealpha($res, 0, 0, 0, 127);
    imagealphablending($res, false);
    imagefilledrectangle($res, 0, 0, $width, $height, $transparency);
    imagealphablending($res, true);
    imagesavealpha($res, true);
} else if ($extension == 'gif') {
    // If we have a specific transparent color.
    $transparency_index = imagecolortransparent($im);
    if ($transparency_index >= 0) {
        // Get the original image's transparent color's RGB values.
        $transparent_color = imagecolorsforindex($im, $transparency_index);
        // Allocate the same color in the new image resource.
        $transparency_index = imagecolorallocate($res, $transparent_color['red'], $transparent_color['green'], $transparent_color['blue']);
        // Completely fill the background of the new image with allocated color.
        imagefill($res, 0, 0, $transparency_index);
        // Set the background color for new image to transparent.
        imagecolortransparent($res, $transparency_index);
        // Find number of colors in the images palette.
        $number_colors = imagecolorstotal($im);
        // Convert from true color to palette to fix transparency issues.
        imagetruecolortopalette($res, true, $number_colors);
    }
}

imagecopyresampled($res, $im, 0, 0, 0, 0, $width, $height, $original_width, $original_height);

header('Content-Type: image/'.$extension);
header('Cache-Control: public, max-age: 3600');

$close_function = 'image'.$open_extension;
$close_function($res);

imagedestroy($res);
imagedestroy($im);
