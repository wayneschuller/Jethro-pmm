<?php
require_once 'views/abstract_view_notes_list.class.php';
class View_Notes__For_Immediate_Action extends Abstract_View_Notes_List
{
	static function getMenuPermissionLevel()
	{
		return PERM_VIEWNOTE;
	}

	function _getNotesToShow()
	{
		$res = $GLOBALS['system']->getDBObjectData('person_note', Array('status' => 'pending', '<action_date' => date('Y-m-d', strtotime('tomorrow'))), 'AND') + $GLOBALS['system']->getDBObjectData('family_note', Array('status' => 'pending', '<action_date' => date('Y-m-d', strtotime('tomorrow'))), 'AND');
		uasort($res, Array($this, '_compareNoteDates'));
		return $res;
	}


	function getTitle()
	{
		return 'Notes For Immediate Action';
	}
}
?>