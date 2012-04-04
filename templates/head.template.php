		<link type="text/css" rel="stylesheet" href="<?php echo BASE_URL; ?>resources/main.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="<?php echo BASE_URL; ?>resources/screen.css" />
		<link type="text/css" rel="stylesheet" media="print" href="<?php echo BASE_URL; ?>resources/print.css" />
		<script type="text/javascript" src="<?php echo BASE_URL; ?>resources/jquery.js?v=<?php echo JETHRO_VERSION; ?>"></script>
                <script type="text/javascript" src="<?php echo BASE_URL; ?>resources/ckeditor/ckeditor.js?v=<?php echo JETHRO_VERSION; ?>"></script>

	<?php
	$debug = FALSE;
	if (!$debug) {
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
