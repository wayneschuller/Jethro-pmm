<?php
require_once 'abstract_view_add_object.class.php';
class View_Groups__Add extends Abstract_View_Add_Object
{
	var $_create_type = 'person_group';
	var $_success_message = 'New group created';
	var $_on_success_view = 'groups';
	var $_failure_message = 'Error creating group';
	var $_submit_label = 'Create Group';
	var $_title = 'Add Person Group';

	function processView() {
		if (!empty($_REQUEST['create_another'])) {
			$this->_on_success_view = $_REQUEST['view'];
		}
		parent::processView();
	}

	static function getMenuPermissionLevel()
	{
		return PERM_EDITGROUP;
	}

	function printView()
	{
		?>
		<form method="post" id="add-<?php echo $this->_create_type; ?>">
			<input type="hidden" name="new_<?php echo $this->_create_type; ?>_submitted" value="1" />
			<?php
			$this->_new_object->printForm();
			?>
			<input type="submit" value="Create & view group" />
			<input name="create_another" type="submit" value="Create group & reload this form" />
		</form>
		<?php
	}

	function _afterCreate()
	{
		if (!empty($_POST['personid']) && is_array($_POST['personid'])) {
			// add some members
			foreach ($_POST['personid'] as $personid) {
				$this->_new_object->addMember((int)$personid);
			}
		}
	}
}
?>
