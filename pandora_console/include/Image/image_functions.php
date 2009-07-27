<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

//INI Lines of code for direct url call script
if (isset($_GET['getFile'])) {
	$file = $_GET['file'];
	
	if (isset($_GET['thumb'])) {
		if (!isset($_GET['thumb_size'])) {
			$newWidth = $newHeight = '50%';
		}
		else {
			$new_size_values = explode('x',$_GET['thumb_size']);
			$newWidth = (integer) $new_size_values[0];
			$newHeight = (integer) $new_size_values[1];
		}
		$temp = explode("/", $file);
		$fileName = end($temp);
		
		$fileTemp = sys_get_temp_dir() . "/tumb_" . $newWidth . "x" . $newHeight . "_" . $fileName;
		
		if (is_file($fileTemp)) {
			if (!is_readable($fileTemp)) {
				$fileTemp = sys_get_temp_dir() . "/tumb_" . $newWidth . "x" . $newHeight . "_" . uniqid() . "_" . $fileName;
				createthumb( $_SERVER['DOCUMENT_ROOT'] . $file, $fileTemp,$newWidth,$newHeight);
			}
		}
		else createthumb( $_SERVER['DOCUMENT_ROOT'] . $file, $fileTemp,$newWidth,$newHeight);
		getFile($fileName, $fileTemp);
		unlink($fileTemp);
	}
}
//END Lines of code for direct url call script

function getFile ($destFileName,$fileLocation) {
	error_reporting(E_ALL);
	
	//header('Content-type: aplication/octet-stream;');
	header('Content-type: ' . mime_content_type($fileLocation) . ';');
	header( "Content-Length: " . filesize($fileLocation));
	header('Content-Disposition: attachment; filename="' . $destFileName . '"');
	readfile($fileLocation);
}

function createthumb ($origFileName, $destFileName, $newWidth, $newHeight) {
	//TODO $newWidth and $newHeight values as percent.
	
	$system = explode(".",$origFileName);
	if (preg_match ("/jpg|jpeg/",$system[1])) 
		$src_img = imagecreatefromjpeg($origFileName);
	if (preg_match ("/png/",$system[1]))
		$src_img = imagecreatefrompng($origFileName);
	$oldWidth = imageSX($src_img);
	$oldHeight = imageSY($src_img); 

	$dst_img = ImageCreateTrueColor($newWidth, $newHeight);
	imagecopyresampled($dst_img, $src_img, 0, 0, 0, 0, $newWidth, $newHeight, $oldWidth, $oldHeight); 
	if (preg_match("/png/",$system[1]))
		imagepng($dst_img,$destFileName); 
	else
		imagejpeg($dst_img,$destFileName);
		
	imagedestroy($dst_img); 
	imagedestroy($src_img); 
}
?>
