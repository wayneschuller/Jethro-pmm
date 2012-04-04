<?php
require_once 'views/abstract_view_notes_list.class.php';
class View_Notes__For_Future_Action extends Abstract_View_Notes_List
{
	static function getMenuPermissionLevel()
	{
		return PERM_VIEWNOTE;
	}

	function _getNotesToShow()
	{
		return $GLOBALS['system']->getDBObjectData('person_note', Array('status' => 'pending', '>action_date' => date('Y-m-d')), 'AND') + $GLOBALS['system']->getDBObjectData('family_note', Array('status' => 'pending', '>action_date' => date('Y-m-d')), 'AND');
		uasort($notes, Array($this, '_compareNoteDates'));
	}


	function getTitle()
	{
		return 'Notes For Future Action';
	}
}
?>