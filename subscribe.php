<?php
include_once('inc.php');
//echo 'a1';
if(isset($_GET['email']) && filter_var($_GET['email'], FILTER_VALIDATE_EMAIL)) {
	$db = new MyDB();

//echo 'a2';
	
	$st = $db->prepare("SELECT id FROM entries ORDER BY id DESC LIMIT 1;");
	$res = $st->execute();
//	echo $db->lastErrorMsg();
	if($r = $res->fetchArray()) {
		$last = $r['id'];
	}else{
		$last = 0;
	}

	if(isset($_GET['submit']) && $_GET['submit'] == 'Anmelden') {
//echo 'a3';
		
		$stm = $db->prepare("INSERT OR IGNORE INTO mails (email, cat, intervall, last) VALUES (:email, '', :intervall, :last);");
		$stm->bindValue(':email', $_GET['email']);
		$stm->bindValue(':intervall',$_GET['intervall']);
		$stm->bindValue(':last', $last);
		$res = $stm->execute();
//		echo $db->lastErrorMsg();
		$stm = $db->prepare("UPDATE mails SET intervall = :intervall WHERE email = :email;");
		$stm->bindValue(':intervall', $_GET['intervall']);
		$stm->bindValue(':email', $_GET['email']);
		$stm->execute();
//		echo $db->lastErrorMsg();
	}

	if(isset($_GET['submit']) && ($_GET['submit'] == 'Abmelden' || $_GET['submit'] == 'abmelden')) {
		$stm = $db->prepare("DELETE FROM mails WHERE email = :email;");
                $stm->bindValue(':email', $_GET['email']);
                $res = $stm->execute();
	}
}	
//echo 'a4';
header("Location: index.html");
?>
