<?php

include('../include/start.php');

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

switch ($captcha_service) {
  case 'hCaptcha':
	$url = "https://hcaptcha.com/siteverify";
	$secret_key = $hcaptcha_secret_key;
    break;

  default:
	$url = "https://www.google.com/recaptcha/api/siteverify";
	$secret_key = $recaptcha_secret_key;
    break;
}

$postdata = http_build_query(
    array(
	'secret'   => $secret_key,
	'response' => $captcha,
	'remoteip' => $_SERVER['REMOTE_ADDR']
    )
);

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

exec("sudo amavisd-release $ID  2>&1", $out, $retcode );
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
};

echo $retstring;
?>
