<?php
class View_Admin__Congregations extends View
{
	static function getMenuPermissionLevel()
	{
		return PERM_SYSADMIN;
	}

	function getTitle()
	{
		return 'Congregations';
	}

	function processView()
	{
	}

	function printView()
	{
		?>
		<div class="standard float-right">
			<h3>Actions</h3>
			<a href="?view=_add_congregation">Add New Congregation</a>
		</div>
		<table class="standard" style="width: 90%">
			<tr>
				<th>ID</th>
				<th>Long Name</th>
				<th>Short Name</th>
				<th>Code Name</th>
				<th>Print Qty</th>
				<th>&nbsp;</th>
			</tr>
		<?php
		$congs = $GLOBALS['system']->getDBObjectData('congregation', Array(), 'OR', 'meeting_time');
		foreach ($congs as $id => $cong) {
			?>
			<tr>
				<td><?php echo $id; ?></td>
				<td><?php echo htmlentities($cong['long_name']); ?></td>
				<td><?php echo htmlentities($cong['name']); ?></td>
				<td><?php echo htmlentities($cong['meeting_time']); ?></td>
				<td><?php echo (int)($cong['print_quantity']); ?></td>
				<td><a href="?view=_edit_congregation&congregationid=<?php echo $id; ?>">Edit</a></td>
			</tr>
			<?php
		}
		?>
		</table>
		<?php
	}
}
?>
