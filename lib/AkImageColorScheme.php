<?php
$image_path = '/Users/bermi/Pictures/strawberry.jpg';
$image = imagecreatefromjpeg($image_path);
//getimagesize();
//imagecolorsforindex()
$imgage_width = imagesx($image);
$imgage_height = imagesy($image);
for ($y=0; $y < $imgage_height; $y++){
    for ($x=0; $x < $imgage_width; $x++){
        $index = imagecolorat($image, $x, $y);
        $image_colors = imagecolorsforindex($image, $index);
        $hex = '';
        foreach ($image_colors as $color=>$value){
            $image_colors[$color] = intval((($image_colors[$color])+15)/32)*32;
            $image_colors[$color] = $image_colors[$color] >= 256 ? 240 : $image_colors[$color];
            $hex .= substr('0'.dechex($image_colors[$color]), -2);
        }
        $frequency[$hex]++;
        $hexarray[] = $hex;
    }
}
array_unique($hexarray);
natsort($hexarray);
$hexarray=array_reverse($hexarray, true);
echo "<pre>".print_r($frequency,true)."</pre>";
echo "<pre>".print_r($hexarray,true)."</pre>";

?>