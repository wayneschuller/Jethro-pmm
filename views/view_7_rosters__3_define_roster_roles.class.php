<?php
class View_Rosters__Define_Roster_Roles extends View
{

	static function getMenuPermissionLevel()
	{
		return PERM_MANAGEROSTERS;
	}

	function processView()
	{

	}

	function getTitle()
	{
		return 'Define Roster Roles';
	}

	
	function printView()
	{
		?>
		<div class="standard float-right">
			<h3>Actions</h3>
			<a href="?view=_add_roster_role">Add Role</a>
		</div>
		<?php

		$congs = $GLOBALS['system']->getDBObjectData('congregation', Array('!meeting_time' => ''), 'AND', 'meeting_time');
		$congs += Array(0 => Array('name' => 'Non-Congregational'));
		foreach ($congs as $cid => $details) {
			?>
			<h3><?php echo htmlentities($details['name']); ?> Roles</h3>
			<?php
			$roles = $GLOBALS['system']->getDBObjectData('roster_role', Array('congregationid' => $cid), 'OR', 'active DESC, title ASC');
			?>
			<table class="standard" width="70%">
				<thead>
					<tr>
						<th width="2%">ID</th>
						<th width="40%">Role Title</th>
						<th width="40%">Volunteer Group</th>
						<th>&nbsp;</th>
					</tr>
				</thead>
				<tbody>
				<?php
				foreach ($roles as $rid => $rdetails) {
					?>
					<tr<?php if (!$rdetails['active']) echo ' class="archived"'; ?>>
						<td><?php echo $rid; ?></td>
						<td><?php echo htmlentities($rdetails['title']); ?></td>
						<td>
							<?php
							if (!empty($rdetails['volunteer_group'])) {
								echo '<a target="jethro" href="'.BASE_URL.'?view=groups&groupid='.$rdetails['volunteer_group'].'">'.htmlentities($rdetails['volunteer_group_name'].' (#'.$rdetails['volunteer_group'].')').'</a>'; 
							}
							?>
						</td>
						<td><a href="?view=_edit_roster_role&roster_roleid=<?php echo $rid; ?>">Edit</a></td>
					</tr>
					<?php
				}
				?>
				</tbody>
			</table>
			<?php
		}
	}
}
?>
