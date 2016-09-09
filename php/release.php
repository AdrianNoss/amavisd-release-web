<?php

if(isset($_POST['isHuman'])) {
	$captcha = $_POST['isHuman'];
} else {
	die();
}
$response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=SECRET_KEX_FROM_GOOGLE&response=".$captcha."&remoteip=".$_SERVER['REMOTE_ADDR']);
$responseKeys = json_decode($response,true);
if(intval($responseKeys["success"]) !== 1) {
    die();
}
$ID = $_POST['mailid'];
exec("sudo amavisd-release $ID  2>&1", $out, $retcode );
$msg = $out[0];
$code = substr($msg, 0, 3);
$retstring = $code."|".$msg;

echo $retstring;
?>
