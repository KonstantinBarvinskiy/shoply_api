<?php
class Images {
	
	private $alldata, $db;	
	private $allowed_exts = array('gif','jpg','jpeg','png','tif','tiff', 'svg');

	function AddImage($file, $module, $module_id = 0, $file_name = '', $is_main = false) {
		if (is_file($file)) {

            $db = db();
            $sql_order = 0;

			//Альтернативный ключ (не по integer id)
			if (!empty($module_id) && !is_numeric($module_id)) {
				if (preg_match('%[\\\\/:*?<>|]+%', $module_id)) {
					return false;
				} else {
					$sql_select = "`alter_key` = '$module_id'";
					$sql_update = "`alter_key` = '$module_id'";
					$sql_insert = "`alter_key`";
				}
			} else {
				$sql_select = "`module_id` = '$module_id'";
				$sql_update = "`module_id` = '$module_id'";
				$sql_insert = "`module_id`";
				if ($last_image = $db->query_value("SELECT `order` FROM `images` WHERE `module_id` = '{$module_id}' ORDER BY `order` DESC LIMIT 1")) {
				    $last_image++;
                    $sql_order = $last_image;
                } else $sql_order = 1;
			}

			$md5 = md5_file($file);
			if (isset($this->alldata[$module][$module_id][$md5]))
			return false;
			$ext = '';
			if (!empty($file_name)) {
				$ext = explode('.', $file_name);
				$ext = strtolower(end($ext));
			}

			//Проверка расширения
			if (!in_array($ext, $this->allowed_exts))
			return false;

			if(!isset($GLOBALS['container'])) {
				$GLOBALS['selectelStorage'] = new SelectelStorage($GLOBALS['config']['cs']['login'], $GLOBALS['config']['cs']['password']);
				if($GLOBALS['config']['cs']['container']) $GLOBALS['container'] = $GLOBALS['selectelStorage']->getContainer($GLOBALS['config']['cs']['container']);
			}
			$src = 'moduleImages/' . $module . '/' . $module_id . '/' . $md5 . '.' . $ext;
			$GLOBALS['container']->putFile($file, $src);

			$db->query("SELECT * FROM `images` WHERE `module` = '$module' AND $sql_select AND `main` = 'Y'");
			if ($db->row_count() == 0)
			$is_main = true;

			if ($is_main) {
				$db->query("UPDATE `images` SET `main` = 'N'  WHERE `module` = '$module' AND $sql_update AND `main` = 'Y'");
			}

			$sql = "INSERT INTO `images` (`src`, `md5`, `module`, $sql_insert, `main`, `order`) VALUES (
				'$src',
				'$md5',
				'$module',
				'$module_id',
				'" . ($is_main ? 'Y' : 'N') . "',
				'{$sql_order}'
				)";
			$db->query($sql);

			return $db->last_insert_id();
		} else {
			return false;
		}
	}

	function ResizeImage($src, $width, $height, $format='jpg') {
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
                file_put_contents(__DIR__.$temp_file, $file['content']);
                system('convert '.__DIR__.$temp_file.' -resize '.$width.'x'.$height.'\> '.__DIR__.$temp_file);
                $GLOBALS['container']->putFile(__DIR__.$temp_file, $new_file_src);
                db()->query('INSERT INTO `thumbs` SET src="'.q($src).'", width='.intval($width).', height='.intval($height).', md5="'.md5($temp_file).'"');
                unlink(__DIR__.$temp_file);

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
	
	function StarImage($id) {
		$id = (int) $id;
		$img = $this->GetImage($id);
		if (!$img) return false;
		db()->query("UPDATE images SET main = 'N' WHERE module = '{$img['module']}' AND ((module_id={$img['module_id']} AND module_id != 0) OR (alter_key='{$img['alter_key']}' AND alter_key != ''))");
		db()->query("UPDATE images SET main = 'Y' WHERE id = {$id}");
		return true;
	}
	
	function DelImage($id) {
		$id = (int) $id;
		$img = $this->GetImage($id);
		if (!$img) return false;
		if (!isset($GLOBALS['container'])) {
			$GLOBALS['selectelStorage'] = new SelectelStorage($GLOBALS['config']['cs']['login'], $GLOBALS['config']['cs']['password']);
			if($GLOBALS['config']['cs']['container']) $GLOBALS['container'] = $GLOBALS['selectelStorage']->getContainer($GLOBALS['config']['cs']['container']);
		}
		$img['src'] = str_replace($GLOBALS['config']['cs']['path'], '', $img['src']);
		db()->query("DELETE FROM `thumbs` WHERE src = '".q($img['src'])."'");
		db()->query("DELETE FROM `images` WHERE id = {$id}");
		if ($img['main'] == 'Y') db()->query("UPDATE images SET main='Y' WHERE module='{$img['module']}' AND ((module_id = {$img['module_id']} AND module_id != 0) OR (alter_key = '{$img['alter_key']}' AND alter_key = '')) LIMIT 1");
		$GLOBALS['container']->delete($img['src']);
		$files = $GLOBALS['container']->listFiles(10000, null, null, 'thumbs/'.substr($img['src'], 0, strrpos($img['src'], '/'))); // delete thumbs
		foreach ($files as $thumb) {
			if(strstr($thumb, substr($img['src'], 0, strrpos($img['src'], '.')))) $GLOBALS['container']->delete($thumb);
		}
		unset($files);
		return true;
	}

	function DelImages($module, $module_id) {
        $imgs = $this->GetImages($module, $module_id);
        if($imgs) {
			if(!isset($GLOBALS['container'])) {
				$GLOBALS['selectelStorage'] = new SelectelStorage($GLOBALS['config']['cs']['login'], $GLOBALS['config']['cs']['password']);
				if($GLOBALS['config']['cs']['container']) $GLOBALS['container'] = $GLOBALS['selectelStorage']->getContainer($GLOBALS['config']['cs']['container']);
			}
			foreach($imgs as $img) {
				$img['src'] = str_replace($GLOBALS['config']['cs']['path'], '', $img['src']);
				$GLOBALS['container']->delete($img['src']);
				$files = $GLOBALS['container']->listFiles(10000, null, null, 'thumbs/'.substr($img['src'], 0, strrpos($img['src'], '/'))); // delete thumbs
				foreach ($files as $thumb) {
					if(strstr($thumb, substr($img['src'], 0, strrpos($img['src'], '.')))) $GLOBALS['container']->delete($thumb);
				}
				unset($files);
				db()->query("DELETE FROM `thumbs` WHERE src = '".q($img['src'])."'");
			}
		}
		db()->query("DELETE FROM images WHERE module='{$module}' AND ((module_id = '{$module_id}' AND module_id != 0) OR (alter_key = '{$module_id}' AND alter_key != ''))");
	}

	function GetImage($id) {
		$id = (int) $id;
		$ret = db()->query_first("SELECT * FROM images WHERE id = {$id}");
		if($ret) $ret['src'] = $GLOBALS['config']['cs']['path'].$ret['src'];
		return $ret;
	}
	
	function GetImages($module, $module_id) {
		$ret = db()->rows("SELECT * FROM images WHERE module = '{$module}' AND ((module_id = '{$module_id}' AND module_id != 0) OR (alter_key = '{$module_id}' AND alter_key != ''))");
		if($ret) foreach($ret as &$r) $r['src'] = $GLOBALS['config']['cs']['path'].$r['src'];
		return $ret;
	}

	function GetMainImage($module, $module_id) {
		if (isset($this->preparedImgs[$module][$module_id])) {
			if (!$this->preparedImgs[$module][$module_id])
			return false;
			return current($this->preparedImgs[$module][$module_id]);
		}
		$ret = db()->query_first("SELECT * FROM images WHERE module = '{$module}' AND ((module_id = '{$module_id}' AND module_id != 0) OR (alter_key = '{$module_id}' AND alter_key != '')) AND main = 'Y' LIMIT 1");
		if($ret) $ret['src'] = $GLOBALS['config']['cs']['path'].$ret['src'];
		return $ret;
	}

	function GetSecondImages($module, $module_id) {
		$ret = db()->query_first("SELECT * FROM images WHERE module = '{$module}' AND ((module_id = '{$module_id}' AND module_id != 0) OR (alter_key = '{$module_id}' AND alter_key != '')) AND main = 'N' LIMIT 1");	
		if($ret) $ret['src'] = $GLOBALS['config']['cs']['path'].$ret['src'];
		return $ret;			    
	}	


	function PrepareImages($module, $module_ids, $only_main = true) {
		if (empty($module_ids))
			return false;
		$only_main = $only_main ? " AND `main` = 'Y'" : '';
		$pi = db()->rows("SELECT * FROM images WHERE module = '{$module}' AND (module_id IN (" . implode(',', $module_ids) . ") OR alter_key IN ('" . implode("','", $module_ids) . "')) $only_main");
		if($pi) foreach ($pi as $p) {
			$p['src'] = $GLOBALS['config']['cs']['path'].$p['src'];
			$this->preparedImgs[$module][$p['module_id']][] = $p;
		}
		foreach ($module_ids as $mids) {
			if (!isset($this->preparedImgs[$module][$mids])) {
			$this->preparedImgs[$module][$mids] = false;
			}
		}
	}

	// function PrepareImages($module, $module_ids, $only_main=true) {
	// 	if(empty($module_ids)) return false;
	// 	$only_main = $only_main?" AND `main` = 'Y'":'';
	// 	$pi = db()->rows("SELECT * FROM images WHERE module = '{$module}' AND (module_id IN (".implode(',', $module_ids).") OR alter_key IN ('".implode("','", $module_ids)."')) $only_main");
		
	// 	foreach ($pi as $p) $this->preparedImgs[$module][$p['module_id']][] = $p;
	// 	foreach ($module_ids as $mids) {
	// 		if(!isset($this->preparedImgs[$module][$mids])) {
	// 			$this->preparedImgs[$module][$mids] = false;
	// 		}
	// 	}
	// }
}
