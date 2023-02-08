<?php

function image($src, $width, $height, $format='jpg') {
	$src = str_replace($GLOBALS['config']['cs']['path'], '', $src);
    if($pt = strrpos($src, '.')) $ext = strtolower(substr($src, $pt+1));
    $filename = $ext ? substr($src, 0, $pt) : $src;
    $new_file_src = 'thumbs/'.$filename.'_'.$width.'x'.$height.'.'.($ext?$ext:$format);

    if(db()->query_value('SELECT id FROM `thumbs` WHERE src="'.q($src).'" AND width='.intval($width).' AND height='.intval($height))) return $GLOBALS['config']['cs']['path'].$new_file_src;
	else {
        if(!isset($GLOBALS['container'])) {
            $GLOBALS['selectelStorage'] = new SelectelStorage($GLOBALS['config']['cs']['login'], $GLOBALS['config']['cs']['password']);
            if($GLOBALS['config']['cs']['container']) $GLOBALS['container'] = $GLOBALS['selectelStorage']->getContainer($GLOBALS['config']['cs']['container']);
        }
        if($GLOBALS['container']->getFileInfo($src)) {
            $file = $GLOBALS['container']->getFile($src);
            $temp_file = md5($file['content'].$width.$height);
            file_put_contents('top-file', $file['content']);

            system('convert '.$temp_file.' -resize '.$width.'x'.$height.'\> '.$temp_file);
            $GLOBALS['container']->putFile($temp_file, $new_file_src);
			db()->query('INSERT INTO `thumbs` SET src="'.q($src).'", width='.intval($width).', height='.intval($height).', md5="'.md5($temp_file).'"');
			unlink($temp_file);

			//Первый кадр анимированного гифа
			/*if(!is_file($new_file)) {
				$check_anim = substr($new_file, 0, -(strlen($format)+1)).'-0.'.$format;
				if(is_file($check_anim)) {
					rename($check_anim, $new_file);
				} else {
					return false;
				}
			}*/
		} else {
			return false;
		}
		
		return $GLOBALS['config']['cs']['path'].$new_file_src;
	}
}

function imageLandscape($src) {
	list($width, $height) = getimagesize($src);
	if($width>0 && $height>0) {
		if($width > $height) return true;
	}
	return false;
}

//function img() {
//	require_once 'Images.php';
//	return giveObject('Images');
//}

//function files() {
//	require_once DIR.'/admin/lib/MediaFiles.php';
//	return giveObject('MediaFiles');
//}

function methods() {
	require_once DIR.'/admin/lib/CatalogMethods.php';
	return giveObject('CatalogMethods');
}