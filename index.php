<?php
	if(!isset($_GET["ID"]))
	{
		header("Location: php/error.php");
	}
?>

<!DOCTYPE html>
<html lang="de">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>'bond' Mail Release</title>
	<link href="css/bootstrap.min.css" rel="stylesheet">
	<link href="css/starter-template.css" rel="stylesheet">
	<script src='https://www.google.com/recaptcha/api.js'></script>
</head>
<body>
	<div class="navbar navbar-inverse navbar-fixed-top" role="navigation">
		<div class="container">
			<div class="navbar-header">
				<a class="navbar-brand" href="http://www.bond.de">'bond' Antispam Mail Release</a>
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
								<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
								<h4 class="modal-title" id="myModalLabel">Release Ergebnis</h4>
							</div>
							<div class="modal-body">
								<div id="message"></div>
								<p>Sie können das Browserfenster nun schließen.</p>
							</div>
							<div class="modal-footer">
								<button type="button" class="btn btn-default" data-dismiss="modal">Schließen</button>
							</div>
						</div>
					</div>
				</div>
				<div class="panel-danger">
					<div class="panel-heading">
						<h3 class="panel-title"><b>Achtung!</b></h3>
					</div>
					<div class="panel-body">
						<p>Sie  möchten die Mail mit der Referenz ID <b><?php echo $_GET["ID"]; ?></b> aus der Quarantäne freigeben.</p>
						<p>Geben Sie die Mail nur frei, wenn Sie sich absolut sicher sind, dass es sich um eine <b>vertrauenswürdige</b> Mail handelt!<p>
					</div>
				</div>
				<div class="alert alert-danger" role="alert">
					<b>Sie handel auf eigene Verantwortung!<br> 
					Falls die Mail wirklich einen Virus enthält, kann es zu Systemschäden und Datenverlust kommen.</b>
				</div>
				<form role="form" id="frmRelease">
					<div class="g-recaptcha" data-sitekey="SECRET_KEY_FROM_GOOGLE" data-callback="enableBtn"></div>
					<p></p>
					<div class="form-group text-left">
						<button type="submit" id="submitBtn" class="btn btn-danger btn-lg">Release Mail</button>
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
			var isHuman = grecaptcha.getResponse();

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
						data = "<b>Release erfolgreich!</b> Bitte überprüfen Sie ihre Mailbox.<br> <br>" + data;
					} else {
						$( '#message' ).addClass( 'alert-danger' );
						data = "<b>Fehler!</b> Release konnte nicht durchgeführt werden. Wenden Sie sich an Ihren Administrator.<br> <br>" + data;
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