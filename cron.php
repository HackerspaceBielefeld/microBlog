<?php
include_once('inc.php');
/*
CREATE TABLE mails (
  email TEXT PRIMARY KEY,
  cat TEXT,
  intervall TEXT,
  last INTEGER);
*/

if(isset($_GET['step']))
	$step = $_GET['step'];
else
	$step = '';
$db = new myDB();

if($step == 'daily') {
	prepare('d');
}

if($step == 'weekly') {
	prepare('w');
}

function sende($an,$betreff,$text) {
	global $absender;
	//TODO einträge holen
	$data = array(array('cat'=>"Kategorie",'subj'=>"Betreff",'txt'=>"Text"));

	//function send_mail($an, $betreff, $text) 
        $header = 'From: '. $absender . "\r\n";
        $header .= 'Reply-To: '. $absender . "\r\n";
        $header .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
        $header .= 'Content-Transfer-Encoding: quoted-printable' . "\r\n";
        $header .= 'X-Mailer: PHP/' . phpversion();

        if (@mail($an, $betreff, $text, $header) === true) {
                return true;
        } else {
                return false;
        }
}

function prepare($interv) {
	global $db;
	global $url;
	$minlast = 9999999;
	//TODO user holen
	$subscriber = array();
	$st = $db->prepare("SELECT email,last FROM mails WHERE intervall = :inter;");
	$st->bindValue(':inter',$interv);
	$res = $st->execute();

	while($r = $res->fetchArray()) {
		if($minlast > $r['last'])
			$minlast = $r['last'];
		$subscriber[$r['email']] = $r['last'];
	}
	print_r($subscriber);
	//TODO Schauen welche infos wer noch braucht
	$st = $db->prepare("SELECT * FROM entries WHERE id > :last AND publ = 1 ORDER BY created,cat,subj;");
	$st->bindValue(':last',$minlast);
	$res = $st->execute();
	$news = array();
	while($r = $res->fetchArray()) {
		$news[] = array('id'=>$r['id'],'date'=>$r['created'],'cat'=>$r['cat'],'subj'=>$r['subj'],'text'=>$r['txt'],'links'=>$r['links']);
	}

	//daten versenden
	foreach($subscriber as $mail=>$last) {
		$footer = '
		
		
		
		<br/><br/><hr/>Wenn du diese E-Mails nicht mehr erhalten willst klicke folgenden Link:
		<a href="https://blog.space.bi/subscribe.php?email='. $mail .'&submit=abmelden">https://blog.space.bi/subscribe.php?email='. $mail .'&submit=abmelden</a></div>';
		$mindate = date("Y-m-d");
		$maxdate = $mindate;
		
		$text = '';
		$curdate = $maxdate;
		$newlast = $last;
		$send = false;

		foreach($news as $n) {
			if($n['id'] > $last) {
				if($curdate != $n['date']) {
					$curdate = $n['date'];
					$text .= '<h2>'. $curdate .'</h2>';
					$send = true;
				}
				$text .= '<h3>['. umlaute($n['cat']) .'] '. umlaute($n['subj']) .'</h3>
'. nl2br(umlaute($n['text'])) .'
';
				/*
				$text .= '<h3>['. $n['cat'] .'] '. $n['subj'] .'</h3>
'. nl2br($n['text']) .'
';//*/
				if($n['links'] != '') {
					$l = explode('<br/>',$n['links']);
					foreach($l as $link) {
						$text .= '<a href="'. $link .'">'. $link .'</a><br/>
';
					}
					$text .= '

';
					
				}

				if($mindate > $curdate) {
					$mindate = $curdate;
				}
			}
			if($newlast < $n['id']) {
				$newlast = $n['id'];
			}
		}

		$betreff = 'Space-Newsletter: '. $mindate .' - '. $maxdate;
		$text .= $footer;

		if($send) {
			if(isset($_GET['debug'])){
				echo $text;
			}else{
				sende($mail,$betreff,$text);
				$st = $db->prepare("UPDATE mails SET last = :last WHERE email = :mail;");
				$st->bindValue(':mail',$mail);
				$st->bindValue(':last',$newlast);
				$st->execute();
			}
		}
	}
}
?>
