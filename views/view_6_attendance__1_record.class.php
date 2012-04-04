<?php
require_once 'db_objects/attendance_record_set.class.php';
class View_Attendance__Record extends View
{
	var $_record_set = NULL;

	static function getMenuPermissionLevel()
	{
		return PERM_EDITATTENDANCE;
	}

	function getTitle()
	{
		return 'Record Attendance';
	}

	function processView()
	{
		if (!empty($_REQUEST['params_submitted'])) {
			// Process step 1
			$attendance_date = process_widget('attendance_date', Array('type' => 'date'));
			$congregation_id = process_widget('congregation_id', Array('type' => 'reference', 'references' => 'congregation', 'allow_empty' => true));
			$group_id = process_widget('group_id', Array('type' => 'reference', 'references' => 'person_group', 'allow_empty' => true));
			$this->_record_set = new Attendance_Record_Set($attendance_date, $congregation_id, $group_id);

		} else if (!empty($_REQUEST['attendances_submitted'])) {
			// Process step 2
			$attendance_date = process_widget('attendance_date', Array('type' => 'date'));
			$congregation_id = process_widget('congregation_id', Array('type' => 'reference', 'references' => 'congregation', 'allow_empty' => true));
			$group_id = process_widget('group_id', Array('type' => 'reference', 'references' => 'person_group', 'allow_empty' => true));
			$this->_record_set = new Attendance_Record_Set($attendance_date, $congregation_id, 
			$group_id);
			if ($_SESSION['enter_attendance_token'] == $_REQUEST['enter_attendance_token']) {
				
				// Clear the token from the session on disk
				$_SESSION['enter_attendance_token'] = NULL;
				session_write_close();
				session_start();

				// Process the form
				$this->_record_set->processForm();
				$this->_record_set->save();
			} else {
				trigger_error('Could not save attendances - synchronizer token does not match.  This probably means the request was duplicated somewhere along the line.  If you see your changes below, they have been saved by the other request');
				sleep(3); // Give the other one time to finish before we load again

				// Pretend we are back in step 2
				$_POST['attendances_submitted'] = FALSE;
				$_SESSION['enter_attendance_token'] = md5(time());
				$this->_record_set = new Attendance_Record_Set($attendance_date, $congregation_id, $group_id);
			}
		}
	}
	
	function printView()
	{
		if (empty($this->_record_set)) {
			// STEP 1 - choose congregation and date
			?>
			<form method="get">
				<input type="hidden" name="view" value="<?php echo $_REQUEST['view']; ?>" />
				<table>
					<tr>
						<th rowspan="2">For</th>
						<td>
							<input class="select-chooser-radio" type="radio" name="for_type" value="congregation_id" id="for_type_congregation" checked="checked">
							<label for="for_type_congregation">Congregation</label>
						</td>
						<td>
							<?php print_widget('congregation_id', Array('type' => 'reference', 'references' => 'congregation', 'order_by' => 'name'), ''); ?>
						</td>
					</tr>
					<tr>
						<td>
							<input class="select-chooser-radio" type="radio" name="for_type" value="group_id" id="for_type_group">
							<label for="for_type_group">Group</label></td>
						<td>
							<?php print_widget('group_id', Array('type' => 'reference', 'references' => 'person_group', 'filter' => Array('can_record_attendance' => '1', 'is_archived' => 0)), 0); ?>
						</td>
					</tr>
					<tr>
						<th>On</th>
						<td colspan="2">
							<?php
							// Default to last Sunday, unless today is Sunday
							$d = date('Y-m-d', ((date('D') == 'Sun') ? time() : strtotime('last Sunday')));
							print_widget('attendance_date', Array('type' => 'date'), $d); ?>
						</td>
					</tr>
				</table>
				<input type="hidden" name="params_submitted" value="1" />
				<input type="submit" value="Continue" />
			</form>
			<?php

		} else if (empty($_POST['attendances_submitted'])) {
			// STEP 2 - enter attendances
			$attendance_date = $this->_record_set->date;
			if ($this->_record_set->congregationid) {
				$congregation = $GLOBALS['system']->getDBObject('congregation', (int)$this->_record_set->congregationid);
				$title = 'Record attendance for "'.$congregation->getValue('name').'" congregation on '.date('j M Y', strtotime($attendance_date));
			} else {
				$group =& $GLOBALS['system']->getDBObject('person_group', $this->_record_set->groupid);
				$title = 'Record attendance for "'.$group->getValue('name').'" group on '.date('j M Y', strtotime($attendance_date));
			}
			//$this->_record_set = new Attendance_Record_Set($attendance_date, $congregation_id);
			$_SESSION['enter_attendance_token'] = md5(time());
			?>
			<form method="post" class="attendance warn-unsaved" action="?view=<?php echo $_REQUEST['view']; ?>">
				<input type="hidden" name="attendance_date" value="<?php echo $attendance_date; ?>" />
				<input type="hidden" name="congregation_id" value="<?php echo $this->_record_set->congregationid; ?>" />
				<input type="hidden" name="group_id" value="<?php echo $this->_record_set->groupid; ?>" />
				<input type="hidden" name="enter_attendance_token" value="<?php echo $_SESSION['enter_attendance_token']; ?>" />
				<h3><?php echo htmlentities($title); ?></h3>
				<?php
				$this->_record_set->printForm();
				?>
				<input type="hidden" name="attendances_submitted" value="1" />
				<input type="submit" value="Save Attendances" />
			</form>
			<?php
		} else {
			// STEP 3 - confirmation
			$attendance_date = $this->_record_set->date;
			if ($this->_record_set->congregationid) {
				$congregation = $GLOBALS['system']->getDBObject('congregation', (int)$this->_record_set->congregationid);
				$title = 'Attendance has been recorded for the "'.$congregation->getValue('name').'" congregation on '.date('j M Y', strtotime($attendance_date));
			} else {
				$group =& $GLOBALS['system']->getDBObject('person_group', $this->_record_set->groupid);
				$title = 'Attendance has been recorded for the "'.$group->getValue('name').'" group on '.date('j M Y', strtotime($attendance_date));
			}
			echo '<p>'.$title.'</p>';
			$this->_record_set->printStats();
			?>
			<p><a href="?view=<?php echo $_REQUEST['view']; ?>">Record more attendances</a></p>
			<p><a href="?view=attendance__absentees_report">Generate an absentees report</a></p>
			<?php
		}
	}
}
?>
