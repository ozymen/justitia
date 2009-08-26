<?php

// -----------------------------------------------------------------------------
// Page template
// -----------------------------------------------------------------------------

abstract class Template {
	abstract function title();
	abstract function write_body();
	function write_nav() {}
	
	// write out the page
	function __destruct() {
		$this->write();
	}
	
	function write_user_header() {
		$user = Authentication::current_user();
		if (!$user) return;
		echo '<div id="user">';
		echo $user->name();
		echo ' (<a href="logout.php">log out</a>)';
		echo "</div>";
	}
	
	static $messages;
	function add_message($what,$good,$msg) {
		global $messages;
		if (!isset($messages))        $messages = array();
		if (!isset($messages[$what])) $messages[$what] = array();
		$messages[$what][] = array('good'=>$good, 'msg'=>$msg);
	}
	function write_messages($what) {
		global $messages;
		if (!isset($messages,$messages[$what])) return;
		foreach($messages[$what] as $it) {
			echo '<div class="'.$it['good'].'-message">'.$it['msg'].'</div>';
		}
		unset($messages[$what]);
	}
	
	function write() {
		$base  = htmlspecialchars(Util::base_url());
		$title = htmlspecialchars($this->title());
		
		header('Content-Type', 'text/html; charset=UTF-8');
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
  <head>
    <title><?php echo $title; ?> - NewAthena</title>
    <link rel="stylesheet" type="text/css" href="<?php echo $base; ?>style/style.css">
    <base href="<?php echo $base; ?>">
  </head>
  <body>
    <div id="header">
      <div id="appname">NewAthena</div>
      <?php $this->write_user_header(); ?>
    </div>
    <div id="nav-wrap">
      <?php $this->write_nav(); ?>
    </div>
    <div id="main">
      <h1><?php echo $title; ?></h1>
      <?php $this->write_body(); ?>
    </div>
  </body>
</html>
<?php
	}
}
