<?php
class View__Add_Congregation extends View
{
	var $_congregation;

	static function getMenuPermissionLevel()
	{
		return PERM_SYSADMIN;
	}

	function processView()
	{
		$GLOBALS['system']->includeDBClass('congregation');
		$this->_congregation = new Congregation();

		if (array_get($_REQUEST, 'new_congregation_submitted')) {
			$this->_congregation->processForm();
			if ($this->_congregation->create()) {
				add_message('Congregation Created');
				redirect('admin__congregations');
			} else {
				$this->_congregation->id = 0;
				add_message('Error during congregation creation, congregation not created', 'failure');
			}
		}
	}
	
	function getTitle()
	{
		return 'Add Congregation';
	}


	function printView()
	{
		?>
		<form method="post" id="add-congregation">
			<input type="hidden" name="new_congregation_submitted" value="1" />
			<h3>Congregation Details</h3>
			<?php
			$this->_congregation->printForm();
			?>
			<input type="submit" value="Create Congregation" />
		</form>
		<?php
	}
}
?>