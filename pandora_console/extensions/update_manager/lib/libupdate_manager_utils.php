<?php

function add_prefix (&$string, $key, $prefix) {
	$string = $prefix.'/'.$string;
}

function is_binary ($filepath) {
	$output = array ();
	exec ('file -b -i '.$filepath.' | cut -f1 -d"/"', $output);
	if (isset ($output[0]))
		return $output[0] != 'text';
	return false;
}

function directory_to_array ($directory, $ignores =  NULL, $only_binary_files = false) {
	if (! $ignores)
		$ignores = array ('.', '..');
	
	$array_items = array ();
	$handle = @opendir ($directory);
	if (! $handle) {
		return array ();
	}
	$file = readdir ($handle);
	$dirs = array ();
	while ($file !== false) {
		if (in_array ($file, $ignores)) {
			$file = readdir ($handle);
			continue;
		}
		$filepath = realpath ($directory."/".$file);
		$dir = array_pop (explode ("/", $directory));
		if (! is_readable ($filepath)) {
			$file = readdir ($handle);
			continue;
		}
		if (is_dir ($filepath)) {
			array_push ($dirs, $filepath);
		} else {
			if ($only_binary_files && ! is_binary ($filepath)) {
				$file = readdir ($handle);
				continue;
			}
			$relative_path = $dir != '' ? $dir. "/" : '';
			$array_items[] = preg_replace("/\/\//si", "/", $relative_path.$file);
		}
		$file = readdir ($handle);
	}
	sort ($array_items);
	sort ($dirs);
	foreach ($dirs as $filepath) {
		$files = directory_to_array ($filepath, $ignores, $only_binary_files);
		if ($dir != '')
			array_walk ($files, 'add_prefix', $dir);
		$array_items = array_merge ($array_items, $files);
	}
	closedir($handle);
	
	return $array_items;
}

?>
