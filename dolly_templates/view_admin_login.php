<!DOCTYPE html>
<html>
<head>
	<title>DollySites - AdminPanel Auth</title>
	<meta charset="utf-8" />
	<link rel="shortcut icon" href="../dolly_images/dolly_favicon.png" type="image/png">
	<!--[if lt IE 9]><script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script><![endif]-->
	<title></title>
	<meta name="keywords" content="" />
	<meta name="description" content="" />
	<link href="../dolly_css/dolly_style.css" rel="stylesheet">
	<meta name="robots" content="noindex"/>
</head>
<body>
<div class="page" data-page="3">
	<div class="form-login1">
		<div class="logo"><div class="title"><img src="dolly_templates/images/logo_auth.png" alt=""></div></div>
		<form  action="../admin.php?action=login" method="post">
			<div class="form-type-textfield">
				<label for="username"><?=l10n()->login?>:</label>
				<input type="text" name="user" id="username">
			</div>
			<div class="form-type-textfield">
				<label for="pass"><?=l10n()->password?>:</label>
				<input type="password" name="password" id="pass">
			</div>
			<div class="form-actions1">
				<input class="form_submit1" type="submit" value="<?=l10n()->enter?>">
			</div>
		</form>
	</div>
</div>
</body>
</html>
</html>
</body>
</html>