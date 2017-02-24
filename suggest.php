<?php
include_once('inc.php');

function send_mail($an, $betreff, $text, $ok='', $error='') {
	global $absender;
	$header = 'From: '. $absender . "\r\n";
	$header .= 'Content-Type:text/html' . "\r\n";
	$header .= 'Content-Transfer-Encoding: 8bit' . "\r\n";
	$header .= 'X-Mailer: PHP/' . phpversion();


	if (@mail($an, $betreff, $text, $header) === true) {
		echo $ok;
	} else {
		echo $error;
	}
}

$db = new MyDB();  

if(isset($_GET['a'])) {
	$a = $_GET['a'];
}else{
	$a = '';
}

if($a == 'new') {
	$statement = $db->prepare("INSERT INTO entries (
          cat,subj,txt,links) VALUES (:cat,:subj,:txt,:links);");
        $statement->bindValue(':cat', $_POST['cat']);
        $statement->bindValue(':subj', $_POST['subj']);
        $statement->bindValue(':txt', $_POST['txt']);
        $statement->bindValue(':links', $_POST['links']);

        $result = $statement->execute();
		
		mailer($absender,"MicroBlog: ".$_POST['subj'],$_POST['txt'].'
		
'. $_POST['links']);
		
		echo 'Vorschlag wurde gespeichert. Er wird so schnell wie möglich kontrolliert ud veröffentlich.<hr/>';
	$a = '';
}

if($a == '') {
	//form zum anlegen
	$statement = $db->prepare("SELECT DISTINCT cat FROM entries ORDER BY cat;");

        $result = $statement->execute();

	echo '<form action="suggest.php?a=new" method="post" accept-charset="UTF-8">
		Kategorie: <select name="cat">';
	while($r = $result->fetchArray()) {
		echo '<option>'. $r['cat'] .'</option>';
	}
	echo '</select><br/>
	Titel: <input type="text" name="subj" /><br/>
	Text: <textarea name="txt" style="width:100%;"></textarea><br/>
	Links: <textarea name="links" style="width:100%;"></textarea><br/>
	<input type="submit" value="Senden" />
	</form>';
	//unpubl einträge

	$statement = $db->prepare("SELECT * FROM entries WHERE publ = 0 ORDER BY cat,subj;");
        $result = $statement->execute();

}
?>
