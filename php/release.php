<?php

include('../include/start.php');

if(isset($_POST['isHuman'])) {
	$captcha = $_POST['isHuman'];
} else {
	die();
}
$response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=" . $recaptcha_secret_key . "&response=" . $captcha . "&remoteip=".$_SERVER['REMOTE_ADDR']);
$responseKeys = json_decode($response,true);
if(intval($responseKeys["success"]) !== 1) {
    die();
}
$ID = escapeshellarg($_POST['mailid']);
exec("sudo amavisd-release $ID  2>&1", $out, $retcode );
$msg = $out[0];
$code = substr($msg, 0, 3);
if($code !== "250") {
  if($code == "450") {
    $retstring = $code."|ID not found";
  } else {
    $retstring = $code."|unexpected error occured, contact administrator";
  };
} else {
  $retstring = $code."|ID released";
};

echo $retstring;
?>
