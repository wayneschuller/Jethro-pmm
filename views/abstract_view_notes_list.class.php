<?php
class Abstract_View_Notes_List extends View
{
	var $_reassigning = FALSE;
	var $_notes = Array();

	function _compareNoteDates($a, $b)
	{
		return $a['action_date'] > $b['action_date'];
	}

	function processView()
	{
		$this->_notes = $this->_getNotesToShow();

		$this->_reassigning = $GLOBALS['user_system']->havePerm(PERM_BULKNOTE) && !empty($_REQUEST['reassigning']);
		if ($this->_reassigning && !empty($_POST['reassignments_submitted'])) {
			$dummy_note = new Abstract_Note();
			foreach ($this->_notes as $id => $note) {
				$dummy_note->populate($id, $note);
				$dummy_note->setValue('assignee', $_POST['note_'.$id.'_assignee']);
				$dummy_note->save();
				$dummy_note->releaseLock();
			}
			add_message("Assignments Saved");
			$this->_reassigning = FALSE;

			// these will have changed
			$this->_notes = $this->_getNotesToShow();
		}
	}


	function getTitle()
	{
		return '';
	}


	function printView()
	{
		$reassigning = $this->_reassigning;
		$notes =& $this->_notes;
		if (!$reassigning && $GLOBALS['user_system']->havePerm(PERM_BULKNOTE)) {
			?>
			<div class="standard float-right">
				<h3>Actions</h3>
				<a href="<?php echo build_url(Array('reassigning' => 1)); ?>">Edit the assignees for all these notes</a>
			</div>
			<?php
		}
		include 'templates/list_notes_assorted.template.php';
	}
}
?>