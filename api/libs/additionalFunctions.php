<?php

/**
 * Нормальная, хорошая дата, например 23 февраля 2010
 */
function goodDate($dateStr) {
	$date = strtotime($dateStr);
	$monthesIn = array('января','февраля','марта','апреля','мая','июня','июля','августа','сентября','октября','ноября','декабря');
	
	$Year = (int)date('Y',$date);
	$Month = (int)date('m',$date);
	$Day = (int)date('d',$date);
	
	return $Day.' '.$monthesIn[$Month-1].' '.$Year;
}

function stupDate($dateStr) {
    if ($dateStr == '0000-00-00 00:00:00') return NULL;

    $date = strtotime($dateStr);

    $Year = date('y',$date);
    $Month = date('m',$date);
    $Day = date('d',$date);

    return $Day.'.'.$Month.'.'.$Year;
}

/**
 * Генерация пароля
 */
function pass_gen($pass_size){
    $arr = array('a','b','c','d','e','f',
        'g','h','i','j','k','l',
        'm','n','o','p','r','s',
        't','u','v','x','y','z',
        'A','B','C','D','E','F',
        'G','H','I','J','K','L',
        'M','N','O','P','R','S',
        'T','U','V','X','Y','Z',
        '1','2','3','4','5','6',
        '7','8','9','0');
    // Генерируем пароль
    $pass = "";
    for($i = 0; $i < $pass_size; $i++)
    {
        // Вычисляем случайный индекс массива
        $index = rand(0, count($arr) - 1);
        $pass .= $arr[$index];
    }
    return $pass;
}

/**
 * Генерация пароля
 */
function checkPassword($pwd, &$errors) {
    $errors_init = $errors;

    if (strlen($pwd) < 8) {
        $errors[] = "Password too short!";
    }

    if (!preg_match("#[0-9]+#", $pwd)) {
        $errors[] = "Password must include at least one number!";
    }

    if (!preg_match("#[A-Z]+#", $pwd)) {
        $errors[] = "Password must include at least one big letter!";
    }

    if (!preg_match("#[a-z]+#", $pwd)) {
        $errors[] = "Password must include at least one small letter!";
    }

    return ($errors == $errors_init);
}

/**
 * Множественное число
 *
 * @param int $n
 * @param string $str0
 * @param string $str1
 * @param string $str2
 */
function plural($n, $str0, $str1, $str2) {
	$n = (int)$n;
	return $n%10==1&&$n%100!=11?$str1:($n%10>=2&&$n%10<=4&&($n%100<10||$n%100>=20)?$str2:$str0);
}

/**
 * Удобный вывод массивов и другой отладочной информации
 * @param mixed $data
 */
function debug($data) {
	if(!$GLOBALS['config']['develop']) return false;
	
	//Аяксовый запрос
	if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
		if(is_array($data)) {
			$output = print_r($data, 1);
		} else {
			$output = $data;
		}
	}
	//Обычный запрос
	else {
		if(is_array($data)) {
			$output = '<pre>'.print_r($data, 1).'</pre>';
		} else {
			$output = '<pre>'.$data.'</pre>';
		}
	}
	
	echo $output;
}


function jabber($to, $message) {
	@require_once(DIR.'/system/lib/XMPPHP/XMPP.php');
	  
	@$conn = new XMPPHP_XMPP(
		$GLOBALS['config']['jabber']['host'],
		$GLOBALS['config']['jabber']['port'],
		$GLOBALS['config']['jabber']['user'],
		$GLOBALS['config']['jabber']['password'],
		$GLOBALS['config']['jabber']['resource'],
		$GLOBALS['config']['jabber']['server'],
		$printlog = false,
		$loglevel = XMPPHP_Log::LEVEL_INFO
	);
	
	try {
		@$conn->connect();
		@$conn->processUntil('session_start');
		@$conn->presence();
		@$conn->message($to, $message);
		@$conn->disconnect();
		return true;
	} catch(XMPPHP_Exception $e) {
		return false;
	}
}

function icqStatus($uin, $return=false) {
	$stored = getVar('icq:'.$uin);
	$upd = true;
	$nowtime = time();
	if(!empty($stored)) {
		$stored = unserialize($stored);
		if($stored['when'] + 3600 <= $nowtime) {
			$upd = true;
		} else {
			$upd = false;
		}
	}
	
	if($upd) {
		//Проверка icq статуса
		$status = 'na';
		$fp = @fsockopen("status.icq.com", 80);
		if($fp) {
			fputs($fp, "GET /online.gif?icq=$uin&img=5 HTTP/1.0\n\n");
			while ($line = fgets($fp, 128)) {
				if (strpos($line, 'Location') !== false) {
					if (strpos($line, 'online1')!== false) $status = 'online';
					elseif (strpos($line, 'online0') !== false) $status = 'offline';
					break;
				}
			}
		}
		
		$stored = array(
			'when'		=> $nowtime,
			'status'	=> $status
		);
		
		setVar('icq:'.$uin, serialize($stored));
	}
	
	if($return) return $stored['status'];
	else echo '<img src="/Images/Icons/icq/'.$stored['status'].'.gif" width="15" height="15" alt="icq '.$stored['status'].'" /> '.$uin;
	
}

$modulesSettings = array();
function getSet($module, $callname, $default='') {
    global $modulesSettings;
    if(empty($modulesSettings)) {
        $sets = db()->rows("SELECT * FROM `settings`", MYSQL_ASSOC);
        foreach ($sets as $k=>$v) {
            $modulesSettings[$v['module']][$v['callname']] = $v;
        }
    }

    if(isset($modulesSettings[$module][$callname])) return $modulesSettings[$module][$callname]['value'];
    else return $default;
}

/**
 * Добавляет, изменяет или удаляет переменные
 * на основании текущего GET запроса
 * и отдает строку полученного запроса
 *
 * @param array $param array('key'=>'val'[, ...])
 * @param boolean $not_complete Не отдавать «?» в начале запроса
 */
function getget($param=array(), $not_complete=false) {
	$get = $_GET;
	
	if(!empty($param)) {
		foreach ($get as $k=>$v) {
			if(isset($param[$k])) {
				
				//unset
				if($param[$k] === false) {
					unset($get[$k]);
					continue;
				}
				
				$get[$k] = $param[$k];
				unset($param[$k]);
			}
		}
		
		//clean up params
		foreach ($param as $k=>$v) if(!$v) unset($param[$k]);
		
		$get = array_merge($get, $param);
	}
	
	$query = http_build_query($get,'','&amp;');
	return ($not_complete?'':'?').$query;
}

/* отправка  SMS-сообщения */
function sendSMS($phone, $text) {
	if(getSet('','sms')>0) {
		$sms = file_get_contents('http://api.infosmska.ru/interfaces/SendMessages.ashx?login=redworks66&pwd=shoply66ru&phones=7'.preg_replace('/^[78]/','',preg_replace('/\D/','',$phone)).'&sender=Informator&message='.urlencode($text));
		$length = mb_strlen($text)>70 ? ceil(mb_strlen($text)/67) : 1;
		if(stripos($sms, '=accepted') || substr($sms, 0, 2)=='Ok') db()->query('UPDATE `prefix_settings` SET `value`=`value`-'.$length.' WHERE `module`="" AND `callname`="sms" ORDER BY `id` LIMIT 1');
		return $sms;
	}
}

/**
 * Сумма прописью
 * @author runcore
 */
function num2str($inn, $stripkop=false) {
	$nol = 'ноль';
	$str[100]= array('','сто','двести','триста','четыреста','пятьсот','шестьсот','семьсот','восемьсот','девятьсот');
	$str[11] = array('','десять','одиннадцать','двенадцать','тринадцать','четырнадцать','пятнадцать','шестнадцать','семнадцать','восемнадцать','девятнадцать','двадцать');
	$str[10] = array('','десять','двадцать','тридцать','сорок','пятьдесят','шестьдесят','семьдесят','восемьдесят','девяносто');
	$sex = array(
		array('','один','два','три','четыре','пять','шесть','семь','восемь','девять'),// m
		array('','одна','две','три','четыре','пять','шесть','семь','восемь','девять') // f
	);
	$forms = array(
		array('копейка',  'копейки',   'копеек',     1), // 10^-2
		array('рубль',    'рубля',     'рублей',     0), // 10^ 0
		array('тысяча',   'тысячи',    'тысяч',      1), // 10^ 3
		array('миллион',  'миллиона',  'миллионов',  0), // 10^ 6
		array('миллиард', 'миллиарда', 'миллиардов', 0), // 10^ 9
		array('триллион', 'триллиона', 'триллионов', 0), // 10^12
	);
	$out = $tmp = array();
	// Поехали!
	$tmp = explode('.', str_replace(',','.', $inn));
	$rub = number_format($tmp[0],0,'','-');
	if ($rub==0) $out[] = $nol;
	// нормализация копеек
	$kop = isset($tmp[1]) ? substr(str_pad($tmp[1], 2, '0', STR_PAD_RIGHT),0,2) : '00';
	$segments = explode('-', $rub);
	$offset = sizeof($segments);
	if ((int)$rub==0) { // если 0 рублей
		$o[] = $nol;
		$o[] = morph(0, $forms[1][0],$forms[1][1],$forms[1][2]);
	}
	else {
		foreach ($segments as $k=>$lev) {
			$sexi= (int) $forms[$offset][3]; // определяем род
			$ri  = (int) $lev; // текущий сегмент
			if ($ri==0 && $offset>1) {// если сегмент==0 & не последний уровень(там Units)
				$offset--;
				continue;
			}
			// нормализация
			$ri = str_pad($ri, 3, '0', STR_PAD_LEFT);
			// получаем циферки для анализа
			$r1 = (int)substr($ri,0,1); //первая цифра
			$r2 = (int)substr($ri,1,1); //вторая
			$r3 = (int)substr($ri,2,1); //третья
			$r22= (int)$r2.$r3; //вторая и третья
			// разгребаем порядки
			if ($ri>99) $o[] = $str[100][$r1]; // Сотни
			if ($r22>20) {// >20
				$o[] = $str[10][$r2];
				$o[] = $sex[ $sexi ][$r3];
			}
			else { // <=20
				if ($r22>9) $o[] = $str[11][$r22-9]; // 10-20
				elseif($r22>0)  $o[] = $sex[ $sexi ][$r3]; // 1-9
			}
			// Рубли
			$o[] = morph($ri, $forms[$offset][0],$forms[$offset][1],$forms[$offset][2]);
			$offset--;
		}
	}
	// Копейки
	if (!$stripkop) {
		$o[] = $kop;
		$o[] = morph($kop,$forms[0][0],$forms[0][1],$forms[0][2]);
	}
	return preg_replace("/\s{2,}/",' ',implode(' ',$o));
}

function q($str) {
    return mysqli_real_escape_string(db()->link, $str);
}

function bytes_to_str($bytes) {
    $d = '';
    if($bytes >= 1048576) {
        $num = $bytes/1048576;
        $d = 'Mb';
    } elseif($bytes >= 1024) {
        $num = $bytes/1024;
        $d = 'kb';
    } else {
        $num = $bytes;
        $d = 'b';
    }

    return number_format($num, 2, ',', ' ').$d;
}

/**
 * Склоняем словоформу
 */
function morph($n, $f1, $f2, $f5) {
	$n = abs($n) % 100;
	$n1= $n % 10;
	if ($n>10 && $n<20)	return $f5;
	if ($n1>1 && $n1<5)	return $f2;
	if ($n1==1)		return $f1;
	return $f5;
}

function translate($ru_str) {
    $curlHandle = curl_init(); // init curl
    // options
    $postData=array();
    $postData['client']= 'x';
    $postData['text']= $ru_str;
    $postData['hl'] = 'en';
    $postData['sl'] = 'ru';
    $postData['tl'] = 'en';
    curl_setopt($curlHandle, CURLOPT_URL, 'http://translate.google.com/translate_a/t'); // set the url to fetch
    curl_setopt($curlHandle, CURLOPT_HTTPHEADER, array(
        'User-Agent: Mozilla/5.0 (X11; U; Linux i686; ru; rv:1.9.1.4) Gecko/20091016 Firefox/3.5.4',
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language: ru,en-us;q=0.7,en;q=0.3',
        'Accept-Charset: windows-1251,utf-8;q=0.7,*;q=0.7',
        'Keep-Alive: 300',
        'Connection: keep-alive'
    ));
    curl_setopt($curlHandle, CURLOPT_HEADER, 0); // set headers (0 = no headers in result)
    curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, 1); // type of transfer (1 = to string)
    curl_setopt($curlHandle, CURLOPT_TIMEOUT, 10); // time to wait in
    curl_setopt($curlHandle, CURLOPT_POST, 0);
    if ( $postData!==false ) {
        curl_setopt($curlHandle, CURLOPT_POSTFIELDS, http_build_query($postData));
    }

    $content = curl_exec($curlHandle); // make the call
    curl_close($curlHandle); // close the connection
    if(strpos($content, '302 Moved')) return strtr($ru_str, array('А'=>'A','Б'=>'B','В'=>'V','Г'=>'G','Д'=>'D','Е'=>'E','Ё'=>'Yo','Ж'=>'Zh','З'=>'Z','И'=>'I','Й'=>'J','К'=>'K','Л'=>'L','М'=>'M','Н'=>'N','О'=>'O','П'=>'P','Р'=>'R','С'=>'S','Т'=>'T','У'=>'U','Ф'=>'F','Х'=>'H','Ц'=>'Ts','Ч'=>'Ch','Ш'=>'Sh','Щ'=>'Sch','Ъ'=>'','Ы'=>'Y','Ь'=>'','Э'=>'E','Ю'=>'Yu','Я'=>'Ya','а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'yo','ж'=>'zh','з'=>'z','и'=>'i','й'=>'j','к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f','х'=>'h','ц'=>'ts','ч'=>'ch','ш'=>'sh','щ'=>'sch','ъ'=>'','ы'=>'y','ь'=>'','э'=>'e','ю'=>'yu','я'=>'ya','№'=>'No'));
    else return trim($content, '"');
}

function makeURI($str) {
    $str = translate($str);
    return strtolower(preg_replace('/[^\w]+/i', '-', $str));
}

class Lingua_Stem_Ru
{
    var $VERSION = "0.02";
    var $Stem_Caching = 0;
    var $Stem_Cache = array();
    var $VOWEL = '/аеиоуыэюя/';
    var $PERFECTIVEGROUND = '/((ив|ивши|ившись|ыв|ывши|ывшись)|((?<=[ая])(в|вши|вшись)))$/';
    var $REFLEXIVE = '/(с[яь])$/';
    var $ADJECTIVE = '/(ее|ие|ые|ое|ими|ыми|ей|ий|ый|ой|ем|им|ым|ом|его|ого|еых|ую|юю|ая|яя|ою|ею)$/';
    var $PARTICIPLE = '/((ивш|ывш|ующ)|((?<=[ая])(ем|нн|вш|ющ|щ)))$/';
    var $VERB = '/((ила|ыла|ена|ейте|уйте|ите|или|ыли|ей|уй|ил|ыл|им|ым|ены|ить|ыть|ишь|ую|ю)|((?<=[ая])(ла|на|ете|йте|ли|й|л|ем|н|ло|но|ет|ют|ны|ть|ешь|нно)))$/';
    var $NOUN = '/(а|ев|ов|ие|ье|е|иями|ями|ами|еи|ии|и|ией|ей|ой|ий|й|и|ы|ь|ию|ью|ю|ия|ья|я)$/';
    var $RVRE = '/^(.*?[аеиоуыэюя])(.*)$/';
    var $DERIVATIONAL = '/[^аеиоуыэюя][аеиоуыэюя]+[^аеиоуыэюя]+[аеиоуыэюя].*(?<=о)сть?$/';
 
    function s(&$s, $re, $to)
    {
        $orig = $s;
        $s = preg_replace($re, $to, $s);
        return $orig !== $s;
    }
 
    function m($s, $re)
    {
        return preg_match($re, $s);
    }
 
    function stem_word($word)
    {
        $word = mb_strtolower($word);
 
        $word = str_replace("ё","е",$word);
        # Check against cache of stemmed words
        if ($this->Stem_Caching && isset($this->Stem_Cache[$word])) {
            return $this->Stem_Cache[$word];
        }
        $stem = $word;
        do {
          if (!preg_match($this->RVRE, $word, $p)) break;
          $start = $p[1];
          $RV = $p[2];
          if (!$RV) break;
 
          # Step 1
          if (!$this->s($RV, $this->PERFECTIVEGROUND, '')) {
              $this->s($RV, $this->REFLEXIVE, '');
 
              if ($this->s($RV, $this->ADJECTIVE, '')) {
                  $this->s($RV, $this->PARTICIPLE, '');
              } else {
                  if (!$this->s($RV, $this->VERB, ''))
                      $this->s($RV, $this->NOUN, '');
              }
          }
 
          # Step 2
          $this->s($RV, '/и$/', '');
 
          # Step 3
          if ($this->m($RV, $this->DERIVATIONAL))
              $this->s($RV, '/ость?$/', '');
 
          # Step 4
          if (!$this->s($RV, '/ь$/', '')) {
              $this->s($RV, '/ейше?/', '');
              $this->s($RV, '/нн$/', 'н');
          }
 
          $stem = $start.$RV;
        } while(false);
        if ($this->Stem_Caching) $this->Stem_Cache[$word] = $stem;
        return $stem;
    }
 
    function stem_caching($parm_ref)
    {
        $caching_level = @$parm_ref['-level'];
        if ($caching_level) {
            if (!$this->m($caching_level, '/^[012]$/')) {
                die(__CLASS__ . "::stem_caching() - Legal values are '0','1' or '2'. '$caching_level' is not a legal value");
            }
            $this->Stem_Caching = $caching_level;
        }
        return $this->Stem_Caching;
    }
 
    function clear_stem_cache()
    {
        $this->Stem_Cache = array();
    }
}