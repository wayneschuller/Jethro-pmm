<?php
require_once 'abstract_view_edit_object.class.php';
class View__Edit_Group extends Abstract_View_Edit_Object
{
	var $_editing_type = 'person_group';
	var $_on_success_view = 'groups';
	var $_on_cancel_view = 'groups';
	var $_submit_button_label = 'Update Group ';
	var $_object_id_field = 'groupid';

	static function getMenuPermissionLevel()
	{
		return PERM_EDITPERSON;
	}

	function __construct()
	{
		$this->_on_cancel_view = array_get($_REQUEST, 'back_to', 'groups');
	}

	function _processObjectEditing()
	{
		$mod_count = 0;
		$processed = FALSE;
		switch (array_get($_REQUEST, 'action')) {
			case 'add_member':
			case 'add_members':
				$personids = array_get($_POST, 'personid', Array());
				if (!empty($personids)) {
					if (!is_array($personids)) {
						$personids = Array($personids);
					}
					foreach ($personids as $personid) {
						$new_member =& $GLOBALS['system']->getDBObject('person', (int)$personid);
						if ($new_member->id) {
							if ($this->_edited_object->addMember((int)$personid)) {
								$mod_count++;
							}
						}
					}
					if (count($personids) > 1) {
						add_message($mod_count.' persons added to group');
					} else {
						add_message('Person added to group');
					}
				}
				$processed = TRUE;
				break;

			case 'remove_member':
			case 'remove_members':
				$personids = $_POST['personid'];
				if (!empty($personids)) {
					if (!is_array($personids)) {
						$personids = Array($personids);
					}
					foreach ($personids as $personid) {
						if ($this->_edited_object->removeMember((int)$personid)) {
							$mod_count++;
						}
					}
					if (count($personids) > 1) {
						add_message($mod_count.' persons removed from group');
					} else {
						add_message('Person removed from group');
					}
				}
				$processed = TRUE;
				break;

			case 'delete':
				if ($_POST['action'] == 'delete') { // must be POSTed
					$GLOBALS['user_system']->checkPerm(PERM_EDITGROUP);
					$name = $this->_edited_object->toString();
					$this->_edited_object->delete();
					add_message('Group "'.$name.'" deleted');
					redirect('groups__list_all', Array('groupid' => NULL, 'action' => NULL)); // exits
				}
				break;
		}


		if (!$processed) {
			// normal group edit
			$GLOBALS['user_system']->checkPerm(PERM_EDITGROUP);
			$processed = parent::_processObjectEditing();
		}
		
		if ($processed) {
		
			switch (array_get($_REQUEST, 'back_to')) {
				case 'persons':
					redirect('persons', Array('personid' => (int)reset($personids)), 'groups');
				case 'groups__list_all':
					redirect('groups__list_all', Array('groupid' => NULL, 'action' => NULL)); // exits
				case 'groups':
				default:
					redirect('groups', Array('groupid' => $this->_edited_object->id)); // exits
			}
		}


	}
	
}
?>
