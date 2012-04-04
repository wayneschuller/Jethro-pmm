<?php

class View_Persons__Statistics extends View
{
	static function getMenuPermissionLevel()
	{
		return PERM_RUNREPORT;
	}

	function getTitle()
	{
		return 'System-wide Person Statistics';
	}
	
	function printView()
	{
		$GLOBALS['system']->includeDBClass('person');
		$stats = Person::getStatusStats();
		?>
		<table class="standard">
		<?php
		foreach ($stats as $status_name => $count) {
			?>
			<tr>
				<th><?php echo htmlentities($status_name); ?></th>
				<td><?php echo (int)$count; ?></td>
			</tr>
			<?php
		}
		?>
		</table>
		<?php
	}


}
?>
