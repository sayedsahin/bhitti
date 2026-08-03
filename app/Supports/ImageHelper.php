<?php

declare(strict_types=1);

namespace App\Supports;

class ImageHelper
{
    public static function resize($file, $w, $h, $crop=FALSE) {
		list($width, $height) = getimagesize($file);
		$r = $width / $height;
		if ($crop) {
			if ($width > $height) {
				$width = ceil($width-($width*abs($r-$w/$h)));
			} else {
				$height = ceil($height-($height*abs($r-$w/$h)));
			}
			$newWidth = $w;
			$newHeight = $h;
		} else {
			if ($w/$h > $r) {
				$newWidth = $h*$r;
				$newHeight = $h;
			} else {
				$newHeight = $w/$r;
				$newWidth = $w;
			}
		}
		$ex = explode('.', $file);
		$ex = end($ex);
		if ($ex == 'jpg' || $ex == 'jpeg' || $ex == 'jfif') {
			$src = imagecreatefromjpeg($file);
			$dst = imagecreatetruecolor($newWidth, $newHeight);
		}elseif ($ex == 'png') {
			$src = imagecreatefrompng($file);
			$dst = imagecreatetruecolor($newWidth,$newHeight);
			imagealphablending($dst, false);
			imagesavealpha($dst, true);
		}
		imagecopyresampled($dst, $src,0,0,0,0,$newWidth,$newHeight,$width, $height);
		return $dst;
	}
}
