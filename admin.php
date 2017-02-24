<?php
include_once('inc.php');

if(isset($_POST['pw'])) {
	setcookie('mystic',$_POST['pw']);
	$_COOKIE['mystic'] = $_POST['pw'];
}

if(!(isset($_COOKIE['mystic']) && $_COOKIE['mystic'] == $pw )) {
	$a = 'login';
}else{
	if(!file_exists('data/data.db')) {
		$db = new MyDB();
	        $db->query("CREATE TABLE entries (
	          id INTEGER PRIMARY KEY AUTOINCREMENT,
	          cat TEXT,
	          created DATE DEFAULT '0000-00-00',
	          subj TEXT,
	          txt TEXT,
		  links TEXT,
		  publ INTEGER DEFAULT 0);");
		
		$db->query("CREATE TABLE mails (
		  email TEXT PRIMARY KEY, 
		  cat TEXT,
		  intervall TEXT,
		  last INTEGER);");
	}else{
		$db = new MyDB();  
	}
	
	if(isset($_GET['a'])) {
		$a = $_GET['a'];
	}else{
		$a = '';
	}
}

if($a == 'pub') {
	$time = date("Y-m-d");
	$db->query("UPDATE entries SET created = '{$time}', publ = 1 WHERE publ = 0;");

	$statement = $db->prepare("SELECT * FROM entries ORDER BY created DESC ,cat,subj LIMIT 100;");

	$result = $statement->execute();

	$header = '<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="cache-control" content="max-age=0" />
		<meta http-equiv="cache-control" content="no-cache" />
		<meta http-equiv="expires" content="0" />
		<meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT" />
		<meta http-equiv="pragma" content="no-cache" />

		<title>Space Blog</title>
		<style>
			h2 {margin-bottom: 0px;}
			h3 {margin-bottom: 0px;}
	</style>
	</head>
	<body>
		<header>
			<h1>Neuigkeiten</h1>
		</header>
		<main>
';
	$footer = '
			</ul>
		</main>
		<footer>
			<div>RSS: '. $url .'/rss.xml</div>
			<div><form action="subscribe.php" method="get">
				E-Mail:<input type="text" name="email" value="" />
				<select name="intervall">
					<option value="n">Sofort</option>
					<option value="d">t&auml;glich</option>
					<option value="w" checked="true">w&ouml;chentlich</option>
				</select>
				<input type="submit" name="submit" value="Anmelden" />
				<input type="submit" name="submit" value="Abmelden" />
			</form></div>
			<div><a href="suggest.php">Artikel schreiben</a></div>
		</footer>
	</body>
</html>';

	$rssheader = '<?xml version="1.0"?>
<rss version="0.92">
<channel>
<title>microBlog</title>
<link>'. $url .'</link>
<description>Alle Kleinigkeiten die man wissen sollte</description>';

	$rssfooter = '</channel></rss>';

	$f = fopen('index.html','w');
	$rssf = fopen('rss.xml','w');
	flock($f,2);
	fputs($f,$header);

	$x = '';

	flock($rssf,2);
	fputs($rssf,$rssheader);

	while($res = $result->fetchArray()) {
		$links = '';
		$line = '';

		$date = explode('-',$res['created']);

		if($x != $date[2].'.'.$date[1].'.'.$date[0]){
			if($x != '') {
				$line .= '</ul>';
			}
			$x = $date[2].'.'.$date[1].'.'.$date[0];
			$line .= '<h2>'. $x .'</h2><ul>';
		}

		if($res['links'] != '') {
			$l = explode('
',$res['links']);
			foreach($l as $link) {
				$links .= '<li><a href="'. $link .'" target="_blank" >'. $link .'</a></li>';
			}
		}

		$line .= '
				<li>
					<h3>['. umlaute($res['cat']) .'] '. umlaute($res['subj']) .'</h3>
					<div>'. nl2br(umlaute($res['txt'])) .'</div>
					<ul>'. $links .'</ul>
					<!-- ID: '. $res['id'] .' -->
				</li>';
		fputs($f,$line);
		$rssline = '<item>
<title>'. $res['subj'] .'</title>
<guid>'. $res['id'] .'</guid>
<description>'. $res['txt'] .'</description>
<pubDate>'. date("D, d M Y H:i:s T",strtotime($res['created'])) .'</pubDate>
</item>';
		fputs($rssf,$rssline);
	}

	fputs($f,$footer);
	flock($f,3);
	fclose($f);
	
	fputs($rssf,$rssfooter);
	flock($rssf,3);
	fclose($rssf);

	$a = '';

	$db->close();
	include('cron.php');
	prepare('n');
	//TODO alles älter als 100 ins archiv sortiert nach monat und jahr
}

if($a == 'new') {
	$statement = $db->prepare("INSERT INTO entries (
          cat,subj,txt,links) VALUES (:cat,:subj,:txt,:links);");
        $statement->bindValue(':cat', ($_POST['newcat']==''?$_POST['cat']:$_POST['newcat']));
        $statement->bindValue(':subj', $_POST['subj']);
        $statement->bindValue(':txt', $_POST['txt']);
        $statement->bindValue(':links', $_POST['links']);

        $result = $statement->execute();
	$a = '';
}

if($a == 'edit') {
	if(isset($_POST['submit'])) {
		$statement = $db->prepare("UPDATE entries SET cat = :cat,subj = :subj,txt = :txt,links = :links WHERE id = :id;");
		$statement->bindValue(':id', $_GET['id']);
		$statement->bindValue(':cat', ($_POST['newcat']==''?$_POST['cat']:$_POST['newcat']));
	        $statement->bindValue(':subj', $_POST['subj']);
        	$statement->bindValue(':txt', $_POST['txt']);
        	$statement->bindValue(':links', $_POST['links']);

 		$statement->execute();
		$a = '';
	}else{
		$statement = $db->prepare("SELECT * FROM  entries WHERE id = :id;");
	        $statement->bindValue(':id', $_GET['id']);
	       	
		$result = $statement->execute();

		if($r = $result->fetchArray()) {
			$statement = $db->prepare("SELECT DISTINCT cat FROM entries ORDER BY cat;");

        		$result = $statement->execute();


        		echo '<form action="admin.php?a=edit&id='. $r['id'] .'" method="post">
                		Kategorie: <select name="cat">';
			while($s = $result->fetchArray()) {
				if($r['cat'] == $s['cat']) {
					echo '<option checked="true">'. $s['cat'] .'</option>';
				}else{
                			echo '<option>'. $s['cat'] .'</option>';
				}
        		}
        		echo '</select><input type="text" name="newcat" /><br/>
        			Titel: <input type="text" name="subj" value="'. $r['subj'] .'" /><br/>
        			Text: <textarea name="txt">'. $r['txt'] .'</textarea><br/>
        			Links: <textarea name="links">'. $r['links'] .'</textarea><br/>
        			<input type="submit" name="submit" value="Senden" />
        		</form>';
		}
	}
}

if($a == 'del') {
	if(isset($_POST['submit'])) {
		$statement = $db->prepare("DELETE FROM entries WHERE id = :id;");
		$statement->bindValue(':id', $_GET['id']);

 		$statement->execute();
		$a = '';
	}else{
		echo '<form action="admin.php?a=del&id='. $_GET['id'] .'" method="post">
          	<input type="submit" name="submit" value="L&ouml;schen" />
        </form>';
		
	}
	
}

if($a == '') {
	//form zum anlegen
	$statement = $db->prepare("SELECT DISTINCT cat FROM entries ORDER BY cat;");
	echo $db->lastErrorMsg();
        $result = $statement->execute();


	echo '<form action="admin.php?a=new" method="post" accept-charset="UTF-8">
		Kategorie: <select name="cat">';
	while($r = $result->fetchArray()) {
		echo '<option>'. $r['cat'] .'</option>';
	}
	echo '</select><input type="text" name="newcat" /><br/>
	Titel: <input type="text" name="subj" /><br/>
	Text: <textarea name="txt" style="width:100%;"></textarea><br/>
	Links: <textarea name="links" style="width:100%;"></textarea><br/>
	<input type="submit" value="Senden" />
	</form>';
	//unpubl einträge

	$statement = $db->prepare("SELECT * FROM entries WHERE publ = 0 ORDER BY cat,subj;");
	echo $db->lastErrorMsg();
        $result = $statement->execute();

	echo '<h2>Nicht publizierte</h2><ul>';

	while($res = $result->fetchArray()) {
                $links = '';
		if($res['links'] != '') {
                	$l = explode('
',$res['links']);
                	foreach($l as $link) {
                	        $links .= '<li><a href="'. $link .'" target="_blank" >'. $link .'</a></li>';
                	}
		}
                echo '
                	<li>
                        	<h2>['. $res['cat'] .'] '. $res['subj'] .'</h2>
                               	<div>'. nl2br($res['txt']) .'</div>
                        	<ul>'. $links .'</ul>
				<a href="admin.php?a=edit&id='. $res['id'] .'">edit</a>
				<a href="admin.php?a=del&id='. $res['id'] .'">del</a>
                     	</li>';

	}
	echo '</ul><a href="admin.php?a=pub">Publizieren</a>';

	// letzte einträge mit edit btn???
}

if($a == 'login') {
	echo '<form action="admin.php" method="post">
		<input type="password" name="pw" /><input type="submit" value="Login" />
	</form>';
}

?>
