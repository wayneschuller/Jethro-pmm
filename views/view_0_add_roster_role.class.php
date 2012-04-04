<?php
class View__Add_Roster_Role extends View
{
	var $_role;

	static function getMenuPermissionLevel()
	{
		return PERM_MANAGEROSTERS;
	}

	function processView()
	{
		$GLOBALS['system']->includeDBClass('roster_role');
		$this->_role = new Roster_Role();
		$this->_role->setValue('congregationid', (int)array_get($_GET, 'congregationid', 0));
		if (!empty($_REQUEST['new_role_submitted'])) {
			$this->_role->processForm();
			if ($this->_role->create()) {
				add_message('Role added');
				redirect('rosters__define_roster_roles', Array()); // exits		
			}
		}
	}
	
	function getTitle()
	{
		return 'Add Roster Role';
	}


	function printView()
	{
		?>
		<form method="post">
			<input type="hidden" name="new_role_submitted" value="1" />
			<h3>New Role Details</h3>
			<?php
			$this->_role->printForm();
			?>	
			<input type="submit" value="Add Role" />
		</form>
		<?php

	}
}
?>
