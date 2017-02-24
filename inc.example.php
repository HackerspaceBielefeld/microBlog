<?php
$pw = '';
$absender = '';
$a = 'login';
$url = '';

class MyDB extends SQLite3 {
    function __construct()
    {
        $this->open('data/data.db');
    }
}

function umlaute($str,$a = true) {
	$str = str_replace('Ä','-&Auml;-',$str);
	$str = str_replace('Ö','-&Ouml;-',$str);
	$str = str_replace('Ü','-&Ouml;-',$str);
        $str = str_replace('ä','-&auml;-',$str);
        $str = str_replace('ö','-&ouml;-',$str);
        $str = str_replace('ü','-&uuml;-',$str);
        $str = str_replace('ß','-&szlig;-',$str);
	if($a) $str = htmlentities($str);
	return $str;
}

function mailer($an, $betreff, $text, $ok='', $error='') {
	global $absender;
	$header = 'From: '. $absender . "\r\n";
	$header .= 'Reply-To: '. $absender . "\r\n";
	$header .= 'Content-Type:text/html' . "\r\n";
	$header .= 'Content-Transfer-Encoding: 8bit' . "\r\n";
	$header .= 'X-Mailer: PHP/' . phpversion();


	if (@mail($an, $betreff, $text, $header) === true) {
		echo $ok;
	} else {
		echo $error;
	}
}

?>
