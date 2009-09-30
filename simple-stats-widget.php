<?php
/*
Plugin Name: Simple Stats Widget
Author URI: http://xfuxing.com
Plugin URI: http://xfuxing.com/2009/plug-in-released-simple-stats-widget-for-wordpress.html
Description: A simple visit stats in sidebars!
Author: freephp
Version: 0.9.3
*/
load_plugin_textdomain('svS', 'wp-content/plugins/simple-stats-widget');

class svS_IpLocation 
{
	private $fp;
	private $firstip;
	private $lastip;
	private $totalip;
	public function __construct($filename = "QQWry.Dat") {
		if (!file_exists(dirname(__FILE__).'/'.$filename))
			$filename = "qqwry.dat";
		if (!file_exists(dirname(__FILE__).'/'.$filename)){
			_e('"QQWry.Dat" file dones\'t found!','svS');
			return;
		}
		$this->fp = 0;
		if (($this->fp = fopen(dirname(__FILE__).'/'.$filename, 'rb')) !== false) {
			$this->firstip = $this->getlong();
			$this->lastip = $this->getlong();
			$this->totalip = ($this->lastip - $this->firstip) / 7;
		}
	}
	private function getlong() {
		$result = unpack('Vlong', fread($this->fp, 4));
		return $result['long'];
	}
	private function getlong3() {
		$result = unpack('Vlong', fread($this->fp, 3).chr(0));
		return $result['long'];
	}
	private function packip($ip) {
		return pack('N', intval(ip2long($ip)));
	}
	private function getstring($data = "") {
		$char = fread($this->fp, 1);
		while (ord($char) > 0) {
			$data .= $char;
			$char = fread($this->fp, 1);
		}
		return $data;
	}
	private function getarea() {
		$byte = fread($this->fp, 1);
		switch (ord($byte)) {
			case 0:
				$area = "";
				break;
			case 1:
			case 2:
				fseek($this->fp, $this->getlong3());
				$area = $this->getstring();
				break;
			default:
				$area = $this->getstring($byte);
				break;
		}
		return $area;
	}
	public function getlocation($ip='') { 
		if (!$this->fp) return null;
		if(empty($ip)) $ip = $this->get_client_ip();
		$location['ip'] = gethostbyname($ip);
		$ip = $this->packip($location['ip']);
		$l = 0;
		$u = $this->totalip;
		$findip = $this->lastip;
		while ($l <= $u) {
			$i = floor(($l + $u) / 2);
			fseek($this->fp, $this->firstip + $i * 7);
			$beginip = strrev(fread($this->fp, 4));
			if ($ip < $beginip) {
				$u = $i - 1;
			} else {
				fseek($this->fp, $this->getlong3());
				$endip = strrev(fread($this->fp, 4));
				if ($ip > $endip) {
					$l = $i + 1;
				} else {
					$findip = $this->firstip + $i * 7;
					break;
				}
			}
		}
		fseek($this->fp, $findip);
		$location['beginip'] = long2ip($this->getlong());
		$offset = $this->getlong3();
		fseek($this->fp, $offset);
		$location['endip'] = long2ip($this->getlong());
		$byte = fread($this->fp, 1);
		switch (ord($byte)) {
			case 1:
				$countryOffset = $this->getlong3();
				fseek($this->fp, $countryOffset);
				$byte = fread($this->fp, 1);
				switch (ord($byte)) { 
					case 2:
						fseek($this->fp, $this->getlong3());
						$location['country'] = $this->getstring();
						fseek($this->fp, $countryOffset + 4);
						$location['area'] = $this->getarea();
						break;
					default:
						$location['country'] = $this->getstring($byte);
						$location['area'] = $this->getarea();
						break;
				}
				break;
			case 2:
				fseek($this->fp, $this->getlong3());
				$location['country'] = $this->getstring();
				fseek($this->fp, $offset + 8);
				$location['area'] = $this->getarea();
				break;
			default:
				$location['country'] = $this->getstring($byte);
				$location['area'] = $this->getarea();
				break;
		}
		if ($location['country'] == " CZ88.NET") {
			$location['country'] = "unknown";
		}
		if ($location['area'] == " CZ88.NET") {
			$location['area'] = "";
		}
		if(!function_exists('iconv')){
			require_once(dirname(__FILE__).'/iconv.php');
		}
		$location['country'] = iconv('GB2312', 'UTF-8',$location['country']);
		$location['area'] = iconv('GB2312', 'UTF-8',$location['area']);
		$this->code2flag($location['country'],$location['area'],$location['beginip']);
		return $location;
	}
	public function __destruct() {
		if ($this->fp) {
			fclose($this->fp);
		}
		$this->fp = 0;
	}
	function get_client_ip(){
		if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown"))
			$ip = getenv("HTTP_CLIENT_IP");
		else if (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown"))
			$ip = getenv("HTTP_X_FORWARDED_FOR");
		else if (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown"))
			$ip = getenv("REMOTE_ADDR");
		else if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown"))
			$ip = $_SERVER['REMOTE_ADDR'];
		else
			$ip = "unknown";
		return($ip);
	}
	function code2flag(&$tag_country,&$tag_area,$ip){
		$country = array(1 => array("亚","澳","奥","孟","白","比","伯","贝","百","玻","博","文","保","缅","喀","乍","智","刚","库","古","捷","丹","厄","萨","斐","芬","冈","德","直","希","关","危","几","圭","海","洪","匈","冰","以","意","牙","日","约","柬","哈","肯","韩","科","老","拉","黎","莱","列","立","卢","毛","墨","莫","纳","瑙","荷","朝","挪","秘","菲","葡","卡","罗","俄","沙","所","索","西","叙","塔","坦","泰","汤","特","突","英","美","委","越","也","津","扎","赞"),2 => array("安哥","安圭","安道","安提","阿曼","阿富","阿根","阿塞","阿拉","巴林","巴西","巴拉","巴哈","巴拿","巴基","巴巴","巴布","布基","布隆","加蓬","加纳","加拿","中国","中非","哥伦","哥斯","法国","伊朗","伊拉","爱尔","爱沙","马里","马耳","马拉","马来","马尔","马达","摩纳","摩洛","摩尔","蒙古","蒙特","埃及","埃塞","尼泊","尼加","波兰","波多","圣马","圣多","塞舌","塞浦","塞内","塞拉","新西","新加","斯里","斯威","格鲁","格林","苏丹","苏里","瑞典","瑞士","土耳","土库","乌干","乌克","乌拉","乌兹","南非","南斯","吉布","吉尔","多哥","多米","圣卢","圣文"),3 => array("北京","天津","河北","山西","内蒙","辽宁","吉林","黑龙","上海","江苏","浙江","安徵","安徽","福建","江西","山东","河南","湖北","湖南","广东","广西","海南","重庆","四川","贵州","云南","西藏","陕西","甘肃","青海","宁夏","新疆","香港","澳门","台湾","东北","东华","东南","中北","中南","中央","中山","中科","中经","佳木","全国","兰州","北方","华东","华中","华北","华南","南开","南昌","厦门","合肥","同济","哈尔","大庆","大连","太原","对外","成都","暨南","武汉","汉中","泉州","清华","澳门","福州","联通","聚友","艾提","西北","西华","西安","郑州","长城","长春","长江","长沙","集美","青岛","首都","黄河","宁波","复旦"),4 => array("阿尔巴","阿尔及","法属圭","法属玻","利比亚","利比里","尼日尔","尼日利","斯洛伐","斯洛文"),5 => array("印度","印度尼西亚")); //2009-9-30
//奇虎,欧洲,澳洲,联合国,雅虎,Intelsat公司,Microsoft,Teleglobe,Yahoo
		$flagcode = array(1 => array("am","au","at","bd","by","be","bz","bj","bm","bo","bw","bn","bg","mm","cm","td","cl","cg","ck","cu","cz","dk","ec","sv","fj","fi","gm","de","gi","gr","gu","gt","gn","gy","ht","hn","hu","is","il","it","jm","jp","jo","kh","kz","ke","kr","kw","la","lv","lb","ls","li","lt","lu","mu","mx","mz","na","nr","nl","kp","no","pe","ph","pt","qa","ro","ru","sa","sb","so","es","sy","tj","tz","th","to","tt","tn","gb","us","ve","vn","ye","zw","zr","zm"),2 => array("ao","ai","ad","ag","om","af","ar","az","ae","bh","br","py","bs","pa","pk","bb","pg","bf","bi","ga","gh","ca","cn","cf","co","cr","fr","ir","iq","ie","ee","ml","mt","mw","my","mv","mg","mc","ma","md","mn","ms","eg","et","np","ni","pl","pr","sm","st","sc","cy","sn","sl","nz","sg","lk","sz","ge","gd","sd","sr","se","ch","tr","tm","ug","ua","uy","uz","za","yu","dj","kg","tg","do","lc","vc"),3 => array("cn"),4 => array("al","dz","gf","pf","ly","lr","ne","ng","sk","si"),5 => array("in","id"));
		if (in_array($this->utf8Substr($tag_country,0,1),$country[1])) {
			$tag_country = $flagcode[1][array_search($this->utf8Substr($tag_country,0,1),$country[1])];
		} elseif (in_array ($this->utf8Substr($tag_country,0,2),$country[2])) {
			$tag_country = $flagcode[2][array_search($this->utf8Substr($tag_country,0,2),$country[2])];
		} elseif (in_array ($this->utf8Substr($tag_country,0,2),$country[3])) {
			$tag_area = $tag_country.'&nbsp;'.$tag_area;
			$tag_country = $flagcode[3][0];
		} elseif (in_array ($this->utf8Substr($tag_country,0,3),$country[4])) {
			$tag_country = $flagcode[4][array_search($this->utf8Substr($tag_country,0,3),$country[4])];
		} elseif (in_array ($tag_country,$country[5])) {
			$tag_country = $flagcode[5][array_search($tag_country,$country[5])];
		} else {
			$tag_area = $ip.'&nbsp;'.$tag_country.$tag_area;
			$tag_country = "unknown";
		}
	}
	function utf8Substr($str, $from, $len) {
		return preg_replace('#^(?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){0,'.$from.'}'.'((?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){0,'.$len.'}).*#s','$1',$str);
	}
}
function svS_translate($text){
	if( !class_exists("Snoopy") )
		require_once (ABSPATH . WPINC . "/class-snoopy.php");
	$snoopy = new Snoopy;
	$url = "http://ajax.googleapis.com/ajax/services/language/translate?v=1.0&q=".urlencode($text)."&langpair=zh-CN%7Cen";
	$submit_vars["text"] = $text;
	$submit_vars["ie"] = "UTF8";
	$submit_vars["hl"] = "zh-CN";
	$submit_vars["langpair"] = "zh|en";
	$snoopy->submit($url,$submit_vars);
	$htmlret = $snoopy->results;
	$htmlret = explode('translatedText',$htmlret);
	$htmlret = explode('}',$htmlret[1]);
	$htmlret = $htmlret[0];
	$htmlret = str_replace('"','',$htmlret);
	$htmlret = str_replace(':','',$htmlret);
	return $htmlret;
}
function svS_control() {
	$options = get_option('widget_svS');
	if (!is_array($options)) {
		$options = array('title' => __("Simple Stats","svS"), 'recordnum' => 10, 'widgetwidthpx' => 200, 'widgetheightpx' =>'', 'paddingn' => 0, 'bordern' => 1, 'bordercolor' => "#b9e2ff", 'fontsize' => 12, 'backgroundcolor' => "#ffffff", 'recordBot' => false);
	}
	if ($_POST['svS-submit']) {
		$options['title'] = trim(strip_tags(stripslashes($_POST["widget-title"])));
		$options['recordnum'] = trim(strip_tags(stripslashes($_POST["record-number"])));
		$options['widgetwidthpx'] = trim(strip_tags(stripslashes($_POST["widget-widthpx"])));
		$options['widgetheightpx'] = trim(strip_tags(stripslashes($_POST["widget-heightpx"])));
		$options['paddingn'] = trim(strip_tags(stripslashes($_POST["widget-paddingn"])));
		$options['bordern'] = trim(strip_tags(stripslashes($_POST["widget-bordern"])));
		$options['bordercolor'] = trim(strip_tags(stripslashes($_POST["widget-bordercolor"])));
		$options['fontsize'] = trim(strip_tags(stripslashes($_POST["widget-fontsize"])));
		$options['backgroundcolor'] = trim(strip_tags(stripslashes($_POST["widget-backgroundcolor"])));
		$options['recordBot'] = ($_POST['record-Bot'] == 'false' ? false : true);
		update_option('widget_svS', $options);
	}
	echo '<p><label for="widget-title">'._e('Widget Title:','svS').'</label>'."\n";
	echo '<input id="widget-title" name="widget-title" type="text" value="'.htmlspecialchars(stripslashes($options['title'])).'" /></p>'."\n";
	echo '<p><label for="record-number">'._e('Number of display:','svS').'</label>'."\n";
	echo '<input id="record-number" name="record-number" type="text" value="'.htmlspecialchars(stripslashes($options['recordnum'])).'" /><small>'.__('record','svS').'</small></p>'."\n";
	echo '<p><label>'._e('Records of search engines to crawl:','svS').'</label>'."\n";
		echo '<label><input name="record-Bot" type="radio" value="false" ';
			if(!$options['recordBot']) echo "checked='checked'";
		echo ' />'.__('Disable','svS').'</label>';
		echo '<label>&nbsp;&nbsp;<input name="record-Bot" type="radio" value="true" ';
			if($options['recordBot']) echo "checked='checked'";
		echo ' />'.__('Enable','svS').'</label>';
	echo '</p>'."\n";
	echo '<p><label for="widget-widthpx">'._e('Width of widget:','svS').'</label>'."\n";
	echo '<input id="widget-widthpx" name="widget-widthpx" type="text" value="'.htmlspecialchars(stripslashes($options['widgetwidthpx'])).'" /><small>px</small></p>'."\n";
	echo '<p><label for="widget-heightpx">'._e('Height of widget:','svS').'</label>'."\n";
	echo '<input id="widget-heightpx" name="widget-heightpx" type="text" value="'.htmlspecialchars(stripslashes($options['widgetheightpx'])).'" /><small>px</small><br /><small style="color:#FF0000;">'.__('If you set high there will be a scroll bar','svS').'</small></p>'."\n";
	echo '<p><label for="widget-paddingn">'._e('Padding of widget:','svS').'</label>'."\n";
	echo '<input id="widget-paddingn" name="widget-paddingn" type="text" value="'.htmlspecialchars(stripslashes($options['paddingn'])).'" /><small>px</small></p>'."\n";
	echo '<p><label for="widget-bordern">'._e('Border of widget:','svS').'</label>'."\n";
	echo '<input id="widget-bordern" name="widget-bordern" type="text" value="'.htmlspecialchars(stripslashes($options['bordern'])).'" /><small>px</small></p>'."\n";
	echo '<p><label for="widget-bordercolor">'._e('Color of border:','svS').'</label>'."\n";
	echo '<input id="widget-bordercolor" name="widget-bordercolor" type="text" value="'.htmlspecialchars(stripslashes($options['bordercolor'])).'" /><small>eg: #b9e2ff</small></p>'."\n";
	echo '<p><label for="widget-fontsize">'._e('Font size:','svS').'</label>'."\n";
	echo '<input id="widget-fontsize" name="widget-fontsize" type="text" value="'.htmlspecialchars(stripslashes($options['fontsize'])).'" /><small>px</small></p>'."\n";
	echo '<p><label for="widget-backgroundcolor">'._e('Color of background:','svS').'</label>'."\n";
	echo '<input id="widget-backgroundcolor" name="widget-backgroundcolor" type="text" value="'.htmlspecialchars(stripslashes($options['backgroundcolor'])).'" /><small>eg: #ffffff</small></p>'."\n";
	echo '<input type="hidden" id="svS-submit" name="svS-submit" value="1" />'."\n";
}
function svS_getbotname() {
	$useragent = strtolower($_SERVER['HTTP_USER_AGENT']);
	if (preg_match("/BlogPeople/i",$useragent)) return "BlogPeople Bot"; //2009-9-30
	if (preg_match("/sphere/i",$useragent)) return "Sphere.com Bot"; //2009-9-30
	if (preg_match("/Jakarta\sCommons-HttpClient/i",$useragent)) return "amazonaws.com bot"; //2009-9-30
	if (preg_match("/(googlebot|mediapartners-google)/i",$useragent)) return "Google bot";
	if (preg_match("/msnbot/i",$useragent)) return "MSN bot";
	if (preg_match("/(slurp|yahoo-mmcrawler)/i",$useragent)) return "Yahoo bot";
	if (preg_match("/(baiduspider|baidu\sspider)/i",$useragent)) return "Baidu spider";
	if (preg_match("/sohu-search/i",$useragent)) return "Sohu bot";
	if (preg_match("/lycos/i",$useragent)) return "Lycos";
	if (preg_match("/robozilla/i",$useragent)) return "Robozilla";
	if (preg_match("/yodaobot/i",$useragent)) return "YoDao bot";
	if (preg_match("/iaskspider/i",$useragent)) return "Iask bot";
	if (preg_match("/sogou\b.*\bspider/i",$useragent)) return "Sogou spider";
	if (preg_match("/sosospider/i",$useragent)) return "SoSo spider";
	if (preg_match("/inktomi/i",$useragent)) return "Inktomi spider";
	if (preg_match("/openbot/i",$useragent)) return "Open bot";
	if (preg_match("/alexa\srobot/i",$useragent)) return "Alexa bot";
	if (preg_match("/survey/i",$useragent)) return "Survey spider";
	return "false";
}
function svS_widget_write() {
	$useragent = strtolower($_SERVER['HTTP_USER_AGENT']);
	$botflag=false;
	if (preg_match("/(BlogPeople|sphere|Jakarta\sCommons-HttpClient|bot|survey|yahoo-mmcrawler|inktomi|crawl|spider|slurp|sohu-search|lycos|robozilla|mediapartners-google)/i", $useragent)) $botflag=true; //2009-9-30
	if (!$options['recordBot'])
		if ($botflag) return;
	$options = get_option('widget_svS');
	$recordnum = $options['recordnum'];
	$ipinfo = new svS_IpLocation();
	$iptodescribe = $ipinfo->getlocation();
	if (empty($iptodescribe))
		return;
	if ($botflag) {
		$country = 'spider';
		if (svS_getbotname() != "false") 
			$area = svS_getbotname();
		else
			$area = $iptodescribe['area'];
	} else {
		$country = $iptodescribe['country'];
		$area = $iptodescribe['area'];
	}
	$sourceurl = $_SERVER['HTTP_REFERER'];
	$sourceinfo = parse_url($sourceurl);
	$home = get_option('home');
	if (strstr($sourceurl,get_option('home'))) return;
	if (empty($sourceurl)){
		$sourceurl = __("Direct visit","svS");
		$title = '';
	} else {
		$title = $sourceinfo['host'];
	}
	$url_head=(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !='off')?'https://':'http://';
	$url_this = $url_head.$_SERVER ['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	$title_this = trim(wp_title('',false)); //2009-9-30
	if (empty($title_this)) $title_this = trim(get_option('blogname')); //2009-9-30
	$timeago = date("M-d-Y H:i:s", mktime ());
	$data = get_option('data_svS');
	if (empty($data)) {
		for ($i=0;$i<$recordnum;$i++){
			$data[$i]['country']="";
			$data[$i]['area']="";
			$data[$i]['sourceurl']="";
			$data[$i]['title']="";
			$data[$i]['url_this']="";
			$data[$i]['title_this']="";
			$data[$i]['timeago']="";
		}
		update_option('data_svS', $data);
	}
	$cacheArray[0]['country']=$country;
	$cacheArray[0]['area']=$area;
	$cacheArray[0]['sourceurl']=$sourceurl;
	$cacheArray[0]['title']=$title;
	$cacheArray[0]['url_this']=$url_this;
	$cacheArray[0]['title_this']=$title_this;
	$cacheArray[0]['timeago']=$timeago;
	for ($i=1;$i<$recordnum;$i++){
		$k = $i-1;
		$cacheArray[$i]['country']=$data[$k]['country'];
		$cacheArray[$i]['area']=$data[$k]['area'];
		$cacheArray[$i]['sourceurl']=$data[$k]['sourceurl'];
		$cacheArray[$i]['title']=$data[$k]['title'];
		$cacheArray[$i]['url_this']=$data[$k]['url_this'];
		$cacheArray[$i]['title_this']=$data[$k]['title_this'];
		$cacheArray[$i]['timeago']=$data[$k]['timeago'];
	}
	update_option('data_svS', $cacheArray);
}
function svS_widget_echo() {
	svS_widget_write();
	$options = get_option('widget_svS');
	$lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 4);
	$langflag = false;
	if (!(preg_match("/zh-c/i", $lang) | preg_match("/zh/i", $lang)))
		$langflag = true;
	$fontsize = $options['fontsize'];
	$widgetwidthpx = $options['widgetwidthpx'];
	$widgetheightpx = $options['widgetheightpx'];
	$paddingn = $options['paddingn'];
	$bordern = $options['bordern'];
	$bordercolor = $options['bordercolor'];
	$backgroundcolor = $options['backgroundcolor'];
	$path = get_option('siteurl').'/';
	$timeago = date("M-d-Y H:i:s", mktime ());
	$output = '<div style="height:'.$widgetheightpx.'px;width:'.$widgetwidthpx.'px;padding:0 '.$paddingn.'px;border:'.$bordern.'px solid '.$bordercolor.';background-color:'.$backgroundcolor.';"><div style="height:100%;overflow-y:auto;">'."\n";
	$data = get_option('data_svS');
	for ($i=0;$i<count($data);$i++){
		if (empty($data[$i]['country'])) break;
		$ago = floor((strtotime($timeago)-strtotime($data[$i]['timeago']))/60);
		if ($ago>59){
			$ago2 = $ago - (floor($ago/60)*60);
			$ago = floor($ago/60);
			if ($ago>23) {
				$ago = floor($ago/24);
				$agostr = $ago . __(' days ago ','svS');
				if ($ago>6) {
					$ago = floor($ago/7);
					$agostr = $ago . __(' weeks ago ','svS');
				}
			} else {
				if ($ago2 == 0)
					$agostr = $ago . __(' hour ago ','svS');
				else
					$agostr = $ago . __(' hour ','svS') . $ago2 . __(' minute ago','svS');
			}
		} else {
			if ($ago == 0)
				$agostr = __('just a moment ago ','svS');
			else
				$agostr = $ago . __(' minute ago ','svS');
		}
		if ($langflag)
			$data[$i]['area'] = svS_translate($data[$i]['area']);
		$output.='<div style="border-bottom:1px dotted #d3d3d3;padding:3px 0;"><img src="'.$path.'wp-content/plugins/simple-stats-widget/flags/'.$data[$i]['country'].'.gif" alt="'.$data[$i]['country'].'" style="border:none" />'."\n";
		$output.='<span style="font-size:'.$fontsize.'px;vertical-align:bottom;">&nbsp;'.$data[$i]['area'].'</span><br />'."\n";
		if ($data[$i]['sourceurl']==__("Direct visit","svS"))
			$output.='<span style="font-size:'.$fontsize.'px;">'.$agostr.$data[$i]['sourceurl'].'</span> '.'<a href="'.$data[$i]['url_this'].'"alt="'.$data[$i]['title_this'].'">'.'<span style="font-size:'.$fontsize.'px;">'.$data[$i]['title_this'].'</span></a>'."<br /></div>\n";
		else
			$output.='<span style="font-size:'.$fontsize.'px;">'.$agostr.__('from ','svS').'</span><a href="javascript:void(0);" onclick="window.open(\''.$data[$i]['sourceurl'].'\')" alt="'.$data[$i]['title'].'">'.'<span style="font-size:'.$fontsize.'px;">'.$data[$i]['title'].'</span></a>'.__(' visit ','svS').'<a href="'.$data[$i]['url_this'].'"alt="'.$data[$i]['title_this'].'">'.'<span style="font-size:'.$fontsize.'px;">'.$data[$i]['title_this'].'</span></a>'."<br /></div>\n";
	}
	$output.='</div><div style="display:block;clear:both;text-align:right;background-color:#ffffff;padding:3px;"><img src="'.$path.'wp-content/plugins/simple-stats-widget/flags/info.gif" alt="author blog" style="border:none" /></div></div>'."\n";
	echo $output;
}
function svS_widget($args) {
	$options = get_option('widget_svS');
	$title = $options['title'];
    extract($args);
	echo $before_widget;
	echo $before_title . ($title==""?__('Simple Stats','svS'):$title) . $after_title;
	svS_widget_echo();
	echo $after_widget; 
}
function init_svS_widget(){
	register_sidebar_widget("Simple Stats Widget", "svS_widget");
	register_widget_control("Simple Stats Widget", "svS_control");

}
add_action("plugins_loaded", "init_svS_widget");
?>