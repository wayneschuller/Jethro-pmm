<!doctype html public "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"> 
<html>
	<head>
		<title><?php echo SYSTEM_NAME.' - Login'; ?></title>
		<link type="text/css" rel="stylesheet" href="<?php echo BASE_URL; ?>/resources/main.css" />
		<script type="text/javascript">
			window.onload = function() {
				document.getElementById('username').focus();
			};
		</script>
	</head>

	<body id="body">
		<form method="post" id="login-form">
			<div id="login-box" class="standard">
			<h3>Jethro PMM - <?php echo SYSTEM_NAME; ?></h3>
			<?php
			if (!empty($this->_error)) {
				echo '<div class="failure">'.$this->_error.'</div>';
			}
			?>
			<p>Enter your username and password to log in</p>
			<table>
				<tr>
					<th>Username:</th>
					<td><input type="text" name="username" id="username" value="" /></td>
				</tr>
				<tr>
					<th>Password:</th>
					<td><input type="password" name="password" value="" /></td>
				</tr>
				<tr>
					<td colspan="2" style="text-align: right"><input type="submit" value="Log In" /></td>
				</tr>
			</table>
			<input type="hidden" name="login_key" value="<?php echo $login_key; ?>" />
			</div>
		</form>

		<div class="failure" id="js-warning"><b>Error: Javascript is Disabled</b><br />For Jethro to function correctly you must enable javascript, which is done most simply by lowering the security level your browser uses for this website</div>
		<script type="text/javascript">
		document.getElementById('js-warning').style.display = 'none';
		</script>

	</body>
</html>