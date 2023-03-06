<?php
	$path_extension = '/..';
	include('../include/start.php');
?>
<!DOCTYPE html>
<html lang="<?php echo $language?>">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $site_title;?> - <?php lang('Mail Release')?></title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/starter-template.css" rel="stylesheet">
</head>
<body>
	<div class="navbar navbar-inverse navbar-fixed-top" role="navigation">
		<div class="container">
			<div class="navbar-header">
				<a class="navbar-brand" href="<?php echo $company_url ?>"><?php echo $site_title;?> - <?php lang('Antispam Mail Release') ?></a>
			</div>
		</div>
	</div>
	<div class="container">
		<div class="starter-template">
			<div class="col-md-8 col-md-offset-2">
				<div class="alert alert-danger" role="alert">
					<b><?php if(isset($_GET["ID"]) && ($_GET["ID"] == 'invalid')) { lang('Invalid ID Provided'); } else { lang('No ID Provided'); };?></b>
				</div>
			</div>
		</div>
	</div>
</body>
</html>
