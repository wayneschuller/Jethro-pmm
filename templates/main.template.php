<!doctype html public "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
	<head>
		<title>Jethro PMM - <?php echo SYSTEM_NAME.' - '.$GLOBALS['system']->getTitle(); ?></title>
		<link type="text/css" rel="stylesheet" href="<?php echo BASE_URL; ?>resources/main.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="<?php echo BASE_URL; ?>resources/screen.css" />
		<link type="text/css" rel="stylesheet" media="print" href="<?php echo BASE_URL; ?>resources/print.css" />
		<script type="text/javascript" src="<?php echo BASE_URL; ?>resources/jquery.js?v=<?php echo JETHRO_VERSION; ?>"></script>
		<script type="text/javascript" src="<?php echo BASE_URL; ?>resources/ckeditor/ckeditor.js?v=<?php echo JETHRO_VERSION; ?>"></script>

	<?php
	if (!defined('DEBUG')) {
		?>
		<script type="text/javascript" src="<?php echo BASE_URL; ?>resources/jethro_all.js?v=<?php echo JETHRO_VERSION; ?>"></script>
		<?php
	} else {
		?>
		<script type="text/javascript" src="<?php echo BASE_URL; ?>resources/jethro.js?t=<?php echo time(); ?>"></script>
		<script type="text/javascript" src="<?php echo BASE_URL; ?>resources/tb_lib.js?t=<?php echo time(); ?>"></script>
		<script type="text/javascript" src="<?php echo BASE_URL; ?>resources/tb_menus.js?t=<?php echo time(); ?>"></script>
		<script type="text/javascript" src="<?php echo BASE_URL; ?>resources/tabber.js?t=<?php echo time(); ?>"></script>
		<script type="text/javascript" src="<?php echo BASE_URL; ?>resources/bsn_autosuggest.js?t=<?php echo time(); ?>"></script>
		<?php
	}
	?>


	</head>

	<body>
		<div id="header">
			<table id="account-details">
				<tr>
					<td colspan="2">
						Logged in as 
						<strong><?php echo $GLOBALS['user_system']->getCurrentUser('first_name').' '.$GLOBALS['user_system']->getCurrentUser('last_name'); ?></strong>
					</td>
				</tr>
				<tr>
					<td class="middle"><a href="?view=_edit_me">Edit Account</a> </td>
					<td class="right"><form method="post" action=""><input type="submit" value="Log Out" /><input type="hidden" name="logout" value="1" /></form></td>
				</tr>
				<tr>
					<td colspan="2" class="">
						<?php
						if ($GLOBALS['user_system']->getCurrentRestrictions()) echo '<b title="This user account can only see persons in certain congregations or groups">[ Restrictions in effect ]</b>';
						?>
					</td>
				</tr>
			</table>
			<h1>Jethro PMM - <?php echo SYSTEM_NAME; ?></h1>
			<?php $GLOBALS['system']->printNavigation(); ?>
		</div>

		<div id="body">
			<?php dump_messages(); ?>
			<h2><?php echo $GLOBALS['system']->getTitle(); ?></h2>
			<?php $GLOBALS['system']->printBody(); ?>
		</div>

	</body>
</html>
