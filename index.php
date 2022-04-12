<?php
	include('include/start.php');	

	if(!isset($_GET["ID"]) || (strlen($_GET["ID"]) == 0))
	{
		header("Location: php/error.php");
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
<?php
	switch ($captcha_service) {
	  case 'hCaptcha':
?>
	<script src="https://js.hcaptcha.com/1/api.js" async defer></script>
<?php
		break;

	  default:
?>
	<script src='https://www.google.com/recaptcha/api.js' async defer></script>
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
				<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
					<div class="modal-dialog" role="document">
						<div class="modal-content">
							<div class="modal-header">
								<button type="button" class="close" data-dismiss="modal" aria-label="<?php lang('Close'); ?>"><span aria-hidden="true">&times;</span></button>
								<h4 class="modal-title" id="myModalLabel"><?php lang('Release result'); ?></h4>
							</div>
							<div class="modal-body">
								<div id="message"></div>
								<br>
								<p><?php lang('Now you can close the browser window');?></p>
							</div>
							<div class="modal-footer">
								<button type="button" class="btn btn-default" data-dismiss="modal"><?php lang('Close')?></button>
							</div>
						</div>
					</div>
				</div>
				<div class="panel-<?php if (preg_match("/^(spam|badh|banned)/", $_GET["ID"])) print "warning"; else if (preg_match("/^(archive|clean)/", $_GET["ID"])) print "info"; else print "danger" ?>">
					<div class="panel-heading">
						<h3 class="panel-title"><b><?php lang('Warning')?>!</b></h3>
					</div>
					<div class="panel-body">
						<p><?php lang('Sure to release message',$_GET["ID"]); ?></p>
						<p><?php lang('Release recommendation')?><p>
					</div>
				</div>
				<div class="alert alert-<?php if (preg_match("/^(spam|badh|banned)/", $_GET["ID"])) print "warning"; else if (preg_match("/^(archive|clean)/", $_GET["ID"])) print "info"; else print "danger" ?>" role="alert">
					<b><?php if (preg_match("/^(spam|badh|banned)/", $_GET["ID"])) lang('Release warning'); else if (preg_match("/^(archive|clean)/", $_GET["ID"])) lang('Release info'); else lang('Release alert')?></b>
				</div>
				<form role="form" id="frmRelease">
<?php
	switch ($captcha_service) {
	  case 'hCaptcha':
?>
					<div class="h-captcha" data-sitekey="<?php echo $hcaptcha_site_key ?>" data-callback="enableBtn"></div>
<?php
		break;

	  default:
?>
					<div class="g-recaptcha" data-sitekey="<?php echo $recaptcha_api_key ?>" data-callback="enableBtn"></div>
<?php
		break;
	};
?>
					<p></p>
					<div class="form-group text-left">
						<button type="submit" id="submitBtn" class="btn btn-<?php if (preg_match("/^(spam|badh|banned)/", $_GET["ID"])) print "warning"; else if (preg_match("/^(archive|clean)/", $_GET["ID"])) print "info"; else print "danger" ?> btn-lg"><?php lang('Release Mail')?></button>
					</div>
				</form>
			</div>
		</div>
	</div>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
	<script src="js/bootstrap.min.js"></script>
	<script>
		document.getElementById("submitBtn").disabled = true;

		function enableBtn(){
			document.getElementById("submitBtn").disabled = false;
		}

		$( '#frmRelease').submit( function() {
			var formControl = true;
			var mailid = "<?php Print($_GET["ID"]); ?>";
<?php
	switch ($captcha_service) {
	  case 'hCaptcha':
?>
			var isHuman = hcaptcha.getResponse();
<?php
		break;

	  default:
?>
			var isHuman = grecaptcha.getResponse();
<?php
		break;
	};
?>

			if(isHuman.length == 0) {
				formControl = false;
			}

			if(formControl) {
				$.ajax({
					type: "POST",
					url: "php/release.php",
					data: {
							mailid:mailid,
							isHuman:isHuman
					}
				}).done(function(msg) {
					var code = msg.split('|')[0];
					var data = msg.substr(msg.indexOf("|") + 1);
					$( '#myModal' ).modal('show');
							$( '#message' ).addClass( 'alert' );
					if (code == "250") {
								$( '#message' ).addClass( 'alert-success' );
						data = "<?php lang('Release Succeeded')?><br> <br>" + data;
					} else {
						$( '#message' ).addClass( 'alert-danger' );
						data = "<?php lang('Release Failed')?><br> <br>" + data;
					}
							$( '#message').html( data );
					$( '#submitBtn' ).prop('disabled', true);
					});
			}
			return false;
		} );
	</script>
</body>
</html>
