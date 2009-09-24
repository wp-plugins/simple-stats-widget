<?php
//Author: 偶爱偶家
//Author URI: http://blog.2i2j.com 
//UTF-8 to GB
function utf82gb($utfstr)
{
	$UC2GBTABLE = "";
	$okstr = "";
	if(trim($utfstr)=="") return $utfstr;
	if(empty($UC2GBTABLE)){
		$filename = dirname(__FILE__)."/gb2312-utf8.table";
		$fp = fopen($filename,"r");
		while($l = fgets($fp,15))
		{	$UC2GBTABLE[hexdec(substr($l, 7, 6))] = hexdec(substr($l, 0, 6));}
		fclose($fp);
	}
	$okstr = "";
	$ulen = strlen($utfstr);
	for($i=0;$i<$ulen;$i++)
	{
		$c = $utfstr[$i];
		$cb = decbin(ord($utfstr[$i]));
		if(strlen($cb)==8){ 
			$csize = strpos(decbin(ord($cb)),"0");
			for($j=0;$j < $csize;$j++){
				$i++; $c .= $utfstr[$i];
			}
			$c = utf82u($c);
			if(isset($UC2GBTABLE[$c])){
				$c = dechex($UC2GBTABLE[$c]+0x8080);
				$okstr .= chr(hexdec($c[0].$c[1])).chr(hexdec($c[2].$c[3]));
			}
			else
			{ $okstr .= "&#".$c.";";}
		}
		else $okstr .= $c;
	}
	$okstr = trim($okstr);
	return $okstr;
}

//GB to UTF-8
function gb2utf8($gbstr) {
	$CODETABLE = "";
	if(trim($gbstr)=="") return $gbstr;
	if(empty($CODETABLE)){
		$filename = dirname(__FILE__)."/gb2312-utf8.table";
		$fp = fopen($filename,"r");
		while ($l = fgets($fp,15))
		{ $CODETABLE[hexdec(substr($l, 0, 6))] = substr($l, 7, 6); }
		fclose($fp);
	}
	$ret = "";
	$utf8 = "";
	while ($gbstr) {
		if (ord(substr($gbstr, 0, 1)) > 0x80) {
			$thisW = substr($gbstr, 0, 2);
			$gbstr = substr($gbstr, 2, strlen($gbstr));
			$utf8 = "";
			@$utf8 = u2utf8(hexdec($CODETABLE[hexdec(bin2hex($thisW)) - 0x8080]));
			if($utf8!=""){
				for ($i = 0;$i < strlen($utf8);$i += 3)
					$ret .= chr(substr($utf8, $i, 3));
			}
		}
		else
		{
			$ret .= substr($gbstr, 0, 1);
			$gbstr = substr($gbstr, 1, strlen($gbstr));
		}
	}
	return $ret;
}
//Unicode to utf8
function u2utf8($c) {
	for ($i = 0;$i < count($c);$i++)
		$str = "";
	if ($c < 0x80) {
		$str .= $c;
	} else if ($c < 0x800) {
		$str .= (0xC0 | $c >> 6);
		$str .= (0x80 | $c & 0x3F);
	} else if ($c < 0x10000) {
		$str .= (0xE0 | $c >> 12);
		$str .= (0x80 | $c >> 6 & 0x3F);
		$str .= (0x80 | $c & 0x3F);
	} else if ($c < 0x200000) {
		$str .= (0xF0 | $c >> 18);
		$str .= (0x80 | $c >> 12 & 0x3F);
		$str .= (0x80 | $c >> 6 & 0x3F);
		$str .= (0x80 | $c & 0x3F);
	}
	return $str;
}
//utf8 to Unicode
function utf82u($c)
{
  switch(strlen($c)) {
    case 1:
      return ord($c);
    case 2:
      $n = (ord($c[0]) & 0x3f) << 6;
      $n += ord($c[1]) & 0x3f;
      return $n;
    case 3:
      $n = (ord($c[0]) & 0x1f) << 12;
      $n += (ord($c[1]) & 0x3f) << 6;
      $n += ord($c[2]) & 0x3f;
      return $n;
    case 4:
      $n = (ord($c[0]) & 0x0f) << 18;
      $n += (ord($c[1]) & 0x3f) << 12;
      $n += (ord($c[2]) & 0x3f) << 6;
      $n += ord($c[3]) & 0x3f;
      return $n;
  }
}
if(!function_exists('iconv')):
function iconv($in_charset,$out_charset,$str){
	if((strtolower($in_charset)=='gbk' || strtolower($in_charset)=='gb2312') && (strtolower($out_charset)=='utf-8' || strtolower($out_charset)=='utf8')){
		$str=gb2utf8($str);
	}elseif((strtolower($out_charset)=='gbk' || strtolower($out_charset)=='gb2312') && (strtolower($in_charset)=='utf-8' || strtolower($in_charset)=='utf8')){
		$str=utf82gb($str);
	}else{}
    return $str;
}
endif;
?>