<?php
class View_Admin__User_Accounts extends View
{
	static function getMenuPermissionLevel()
	{
		return PERM_SYSADMIN;
	}

	function getTitle()
	{
		return 'User Accounts';
	}

	function processView()
	{
	}

	function printView()
	{
		?>
		<div class="standard float-right">
			<h3>Actions</h3>
			<a href="?view=_add_user_account">Add User Account</a>
		</div>
		<table class="standard" style="width: 90%">
		<?php
		$congs = $GLOBALS['system']->getDBObjectData('staff_member');
		foreach ($congs as $id => $sm) {
			?>
			<tr<?php if (!$sm['active']) echo ' class="archived"'; ?>>
				<td><?php echo $id; ?></td>
				<td><?php echo $sm['first_name'].' '.$sm['last_name']; ?></td>
				<td><?php echo $sm['active'] ? 'Active' : 'Inactive'; ?></td>
				<td><a href="?view=_edit_user_account&staff_member_id=<?php echo $id; ?>">Edit</a></td>
			</tr>
			<?php
		}
		?>
		</table>
		<?php
	}
}
?>
