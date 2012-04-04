<?
/**
 * LEVI CPM
 * 
 *
 * @author Tom Barrett <tom@tombarrett.id.au>
 * @version $Id: call_display_roster.class.php,v 1.1 2011/04/18 09:32:37 tbar0970 Exp $
 * @package jethro-pmm
 */
class Call_Display_Roster extends Call
{
	/**
	 * Execute this call
	 *
	 * @return void
	 * @access public
	 */
	function run()
	{
		$roster_id = (int)array_get($_REQUEST, 'viewid');
		if (empty($roster_id)) return;
		$view = $GLOBALS['system']->getDBObject('roster_view', $roster_id);

		?>
		<html>
			<head>
				<style media="print">
					html body * {
						color: black;
						text-decoration: none;
					}
				</style>
				<style>
					html td {
						height: 4.5ex; 
					}
					td, th {
						padding: 3px 1ex;
					}
				</style>
			</head>
			<body>
				<h1>Roster: <?php $view->printFieldValue('name'); ?></h1>
				<?php

				$start_date = substr(array_get($_REQUEST, 'start_date', ''), 0, 10);
				$end_date = substr(array_get($_REQUEST, 'end_date', ''), 0, 10);
				$view->printView($start_date, $end_date, FALSE, TRUE);
				?>
			</body>
		</html>
		<?php
	}
}
?>
