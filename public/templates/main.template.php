<?php
if (empty($_GET['raw'])) {
?>
<!doctype html public "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
	<head>
		<title>Jethro PMM - Public Interface - <?php echo SYSTEM_NAME.' - '.$GLOBALS['system']->getTitle(); ?></title>
		<link type="text/css" rel="stylesheet" href="<?php echo BASE_URL; ?>resources/main.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="<?php echo BASE_URL; ?>resources/tabs-screen.css" />
		<link type="text/css" rel="stylesheet" media="print" href="<?php echo BASE_URL; ?>resources/tabs-print.css" />
		<link type="text/css" rel="stylesheet" media="print" href="<?php echo BASE_URL; ?>resources/print.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="<?php echo BASE_URL; ?>resources/autosuggest_inquisitor.css" />
		<script type="text/javascript" src="<?php echo BASE_URL; ?>resources/jquery.js"></script>
		<script type="text/javascript" src="<?php echo BASE_URL; ?>resources/tb_lib.js"></script>
		<script type="text/javascript" src="<?php echo BASE_URL; ?>resources/jethro.js"></script>
		<script type="text/javascript" src="<?php echo BASE_URL; ?>resources/tb_menus.js"></script>
		<script type="text/javascript" src="<?php echo BASE_URL; ?>resources/tabber.js"></script>
		<script type="text/javascript" src="<?php echo BASE_URL; ?>resources/bsn_autosuggest.js"></script>
	</head>

	<body>
		<div id="header">
			<div class="float-right" id="account-details"><a href="<?php echo build_url(Array('raw'=>1)); ?>">Raw version</a></div>
			<h1><?php echo SYSTEM_NAME; ?></h1>
			<?php $GLOBALS['system']->printNavigation(); ?>
		</div>

		<?php dump_messages(); ?>

		<div id="body">
<?php
}
?>
			<h2><?php echo $GLOBALS['system']->getTitle(); ?></h2>
			<?php $GLOBALS['system']->printBody(); ?>
<?php
if (empty($_GET['raw'])) {
?>
		</div>

	</body>
</html>
<?php
}
?>