<?php
	include('include/start.php');	

	if(!isset($_GET["ID"]) || (strlen($_GET["ID"]) == 0))
	{
		header("Location: php/error.php");
		die();
	}

	if (!test_id_valid($_GET["ID"])) {
		header("Location: php/error.php?ID=invalid");
		die();
	};

	if(isset($_GET["R"]) && !filter_var($_GET["R"], FILTER_VALIDATE_EMAIL))
	{
		header("Location: php/error.php");
		die();
	}
?>

<!DOCTYPE html>
<html lang="<?php echo $language ?>">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo $site_title;?> - <?php lang('Mail Release')?></title>
	<link href="css/bootstrap.min.css" rel="stylesheet">
	<link href="css/starter-template.css" rel="stylesheet">
	<style>
		@keyframes blinking {
		      0%   { opacity: 0.2; }
		      50%  { opacity: 0.8; }
		      100% { opacity: 0.2; }
		}
		.btn-blink {
			animation: blinking 1500ms infinite;
		}
	</style>
<?php
	switch ($captcha_service) {
	  case 'hCaptcha':
?>
	<script src="https://js.hcaptcha.com/1/api.js?hl=<?php echo $language ?>" async defer></script>
<?php
		  break;

	  case 'FriendlyCaptcha':
?>
	<script src="https://cdn.jsdelivr.net/npm/friendly-challenge@0.9.1/widget.min.js" async defer></script>
<?php
		break;

	  default:
?>
	<script src='https://www.google.com/recaptcha/api.js?hl=<?php echo $language ?>' async defer></script>
<?php
		break;
	};
?>
</head>
<body>
	<div class="navbar navbar-inverse navbar-fixed-top" role="navigation">
		<div class="container">
			<div class="navbar-header">
				<a class="navbar-brand" href="<?php echo $company_url; ?>"><?php echo $site_title; ?> - <?php lang('Antispam Mail Release'); ?></a>
			</div>
		</div>
	</div>
	<div class="container">
		<div class="starter-template">
			<div class="col-md-8 col-md-offset-2">
				<div class="modal" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
					<div class="modal-dialog" role="document">
						<div class="modal-content">
							<div class="modal-header">
								<h4 class="modal-title" id="myModalLabel"><?php lang('Release result'); ?></h4>
							</div>
							<div class="modal-body">
								<div id="message"></div>
							</div>
							<div class="modal-footer">
								<p><?php lang('Now you can close the browser window');?></p>
							</div>
						</div>
					</div>
				</div>
				<div class="panel-<?php if (preg_match("/^(spam|badh|banned)/", $_GET["ID"])) print "warning"; else if (preg_match("/^(archive|clean)/", $_GET["ID"])) print "info"; else print "danger" ?>" id="panel">
					<div class="panel-heading">
						<h3 class="panel-title"><b><?php lang('Warning')?>!</b></h3>
					</div>
					<div class="panel-body">
						<p><?php lang('Sure to release message',$_GET["ID"]); ?></p>
						<p><?php lang('Release recommendation')?><p>
					</div>
				</div>
				<div class="alert alert-<?php if (preg_match("/^(spam|badh|banned)/", $_GET["ID"])) print "warning"; else if (preg_match("/^(archive|clean)/", $_GET["ID"])) print "info"; else print "danger" ?>" role="alert" id="alert">
					<b><?php if (preg_match("/^(spam|badh|banned)/", $_GET["ID"])) lang('Release warning'); else if (preg_match("/^(archive|clean)/", $_GET["ID"])) lang('Release info'); else lang('Release alert')?></b>
				</div>
				<noscript><b><font color="red"> You need Javascript for CAPTCHA verification to submit this form.</font></b></noscript>
				<form role="form" id="frmRelease" action="javascript:frmRelease()">
<?php
	switch ($captcha_service) {
	  case 'hCaptcha':
?>
					<div class="h-captcha" data-sitekey="<?php echo $hcaptcha_site_key ?>" data-callback="enableBtn" id="captcha"></div>
<?php
		  break;

	  case 'FriendlyCaptcha':
?>
					<div class="frc-captcha" data-sitekey="<?php echo $friendlycaptcha_site_key ?>" data-callback="enableBtn" data-lang="<?php echo $language ?>" id="captcha"></div>
<?php
		break;

	  default:
?>
					<div class="g-recaptcha" data-sitekey="<?php echo $recaptcha_api_key ?>" data-callback="enableBtn" id="captcha"></div>
<?php
		break;
	};
?>
					<p></p>
					<div class="form-group text-left">
						<button disabled type="submit" id="submitBtn" class="btn btn-<?php if (preg_match("/^(spam|badh|banned)/", $_GET["ID"])) print "warning"; else if (preg_match("/^(archive|clean)/", $_GET["ID"])) print "info"; else print "danger" ?> btn-lg"><?php lang('Release Mail')?></button>
					</div>
				</form>
			</div>
		</div>
	</div>
	<script>
		var isHuman;

		function enableBtn(captcha){
			document.getElementById("submitBtn").disabled = false;
			isHuman = captcha;
		}

		function frmRelease(){
			var formControl = true;
			var mailid = "<?php Print($_GET["ID"]); ?>";
			var rcpt = "<?php if (isset($_GET["R"])) { Print($_GET["R"]); } else { Print(""); }; ?>";

			if(isHuman.length == 0) {
				formControl = false;
			}

			if(formControl) {
				document.getElementById("message").className = 'alert';
				document.getElementById("submitBtn").disabled = true;
				document.getElementById("submitBtn").textContent = "<?php lang('Please wait')?>...";
				document.getElementById("submitBtn").className = "btn btn-info btn-lg btn-blink";
				var data = "PROBLEM"; // default
				var code;

				var data = new FormData();
				data.append("mailid", mailid);
				data.append("rcpt", rcpt);
				data.append("isHuman", isHuman);

				fetch("php/release.php", {
					method: 'POST',
					body: data
				})
				.then(response => {
					if (!response.ok) {
						document.getElementById("message").className = 'alert-warning';
						data = 'HTTP error ' + response.status;
						throw new Error();
					};
					return response.text();
				})
				.then(text => {
					if (text.length == 0) {
						data = 'HTTP result empty';
						throw new Error();
					};
					code = text.split('|')[0];
					data = text.substr(text.indexOf("|") + 1);
					if (code == "250") {
						document.getElementById("message").className = 'alert-success';
						data = "<?php lang('Release Succeeded')?><br> <br>" + data;
						success = 1;
					} else {
						document.getElementById("message").className = 'alert-danger';
						data = "<?php lang('Release Failed')?><br> <br>" + data;
					};
					data = data + ' (' + code + ')';
					throw new Error();
				})
				.catch(error => {
					document.getElementById("message").innerHTML = data;
					document.getElementById("myModal").style.display = 'block';
					document.getElementById("myModal").style.opacity = 1;
					document.getElementById("panel").style.opacity = 0.5;
					document.getElementById("alert").style.opacity = 0.5;
					document.getElementById("captcha").style.opacity = 0.5;

					if (code == "250") {
						document.getElementById("submitBtn").textContent = "<?php lang('Mail released')?>";
						document.getElementById("submitBtn").className = "alert alert-dismissable";
					} else {
						document.getElementById("submitBtn").textContent = "<?php lang('Problem')?>!";
						document.getElementById("submitBtn").className = "alert";
					};
					document.getElementById("submitBtn").style.opacity = 0.5;
				});
			};
		};
	</script>
</body>
</html>
