<?php

include('../include/start.php');

# explicit recipient, default empty, only required in case of "banned"
#  see also: https://mailing.unix.amavis-user.narkive.com/Zr5XTPyl/amavis-user-releasing-mail-from-clean-quarantine
$R="";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	echo "550|not a POST request";
	die();
}

if(isset($_POST['isHuman']) && (strlen($_POST['isHuman']) > 0)) {
	$captcha = $_POST['isHuman'];
} else {
	echo "550|POST data missing or empty: 'isHuman'";
	die();
}

if(isset($_POST['mailid']) && (strlen($_POST['mailid']) > 0)) {
	if (!test_id_valid($_POST['mailid'])) {
		echo "550|POST data invalid: 'mailid'";
		die();
	};
        $ID = escapeshellarg($_POST['mailid']);
} else {
        echo "550|POST data missing or empty: 'mailid'";
        die();
}

if(isset($_POST['rcpt']) && (strlen($_POST['rcpt']) > 0)) {
	if (!filter_var($_POST['rcpt'], FILTER_VALIDATE_EMAIL)) {
		echo "550|POST data invalid: 'rcpt'";
		die();
	};

	if (preg_match("/^(banned)/", $_POST['mailid'])) {
		$R = escapeshellarg($_POST['rcpt']);
	};
}

$postdata_array = array();

switch ($captcha_service) {
  case 'hCaptcha':
	$url = "https://hcaptcha.com/siteverify";
	$postdata_array['secret']   = $hcaptcha_secret_key;
	$postdata_array['response'] = $captcha;
	$postdata_array['sitekey']  = $hcaptcha_site_key;
	$postdata_array['remoteip'] = $_SERVER['REMOTE_ADDR'];
    break;

  case 'FriendlyCaptcha':
	$url = "https://api.friendlycaptcha.com/api/v1/siteverify";
	$postdata_array['secret']   = $friendlycaptcha_secret_key;
	$postdata_array['solution'] = $captcha;
	$postdata_array['sitekey']  = $friendlycaptcha_site_key;
    break;

  default:
	$url = "https://www.google.com/recaptcha/api/siteverify";
	$postdata_array['secret']   = $recaptcha_secret_key;
	$postdata_array['response'] = $captcha;
	$postdata_array['remoteip'] = $_SERVER['REMOTE_ADDR'];
    break;
}

$postdata = http_build_query($postdata_array);

$opts = array('http' =>
    array(
	'method' => 'POST',
	'header' => 'Content-type: application/x-www-form-urlencoded',
	'content' => $postdata
    )
);

$context = stream_context_create($opts);

$response = file_get_contents($url, false, $context);

$responseKeys = json_decode($response,true);
if(intval($responseKeys["success"]) !== 1) {
    echo "550|captcha verification failed";
    die();
}

exec("sudo amavisd-release $ID $R 2>&1", $out, $retcode );
if ($retcode != 0) {
    echo "550|release execution failed";
    die();
};

$msg = $out[0];
$code = substr($msg, 0, 3);
if($code !== "250") {
  if($code == "450") {
    $retstring = $code."|ID not found: " . $ID;
  } else {
    $retstring = $code."|unexpected error occured, contact administrator";
  };
} else {
  $retstring = $code."|ID released: " . $ID;
  if (strlen($R) > 0) {
    $retstring = $retstring . " recipient: " . $R;
  };
};

echo $retstring;
?>
