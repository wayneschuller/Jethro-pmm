<?php
class View_Attendance__Absentees_Report extends View
{
	var $_percent = 50;
	var $_weeks = 2;
	var $_congregation = 0;
	var $_group = 0;
	var $_operator = '<';
	var $_persons = NULL;

	static function getMenuPermissionLevel()
	{
		return PERM_VIEWATTENDANCE;
	}

	function getTitle()
	{
		return 'Absentees Report';
	}

	function processView()
	{
		$this->_weeks = (int)array_get($_REQUEST, 'weeks', $this->_weeks);
		$this->_percent = (int)array_get($_REQUEST, 'percent', $this->_percent);
		if (array_get($_REQUEST, 'for_type') == 'group') {
			$this->_group = (int)array_get($_REQUEST, 'group', $this->_group);
			$this->_congregation = 0;
		} else {
			$this->_group = 0;
			$this->_congregation = (int)array_get($_REQUEST, 'congregation', $this->_congregation);
		}
		$this->_operator = array_get($_REQUEST, 'operator') == '>' ? '>' : '<';

		if (!empty($_REQUEST['weeks'])) {
			$cutoff_ts = strtotime('-'.$this->_weeks.' weeks');
			include_once 'db_objects/attendance_record_set.class.php';
			$this->_persons = Attendance_Record_Set::getPersonsByAttendance($this->_percent, $cutoff_ts, $this->_congregation, $this->_operator, $this->_group);
		}
	}
	
	function printView()
	{
		?>
		<div class="standard">
		<?php
		$this->_printParams();
		?>
		</div>
		<?php
		$this->_printResults();
	}

	function _printParams()
	{
		$operator_params = Array(
							'type'		=> 'select',
							'options'	=> Array('<' => 'less than', '>' => 'more than'),
						   );
		?>
		<form method="get">
		<input type="hidden" name="view" value="<?php echo $_REQUEST['view']; ?>" />
		Show people whose attendance <br />

		<input type="radio" class="select-chooser-radio" name="for_type" value="congregation" <?php if (empty($this->_group)) echo 'checked="checked"'; ?> id="for_type_cong" />
		<label for="for_type_cong"> in their congregation</label>
		<?php print_widget('congregation', Array('type' => 'reference', 'references' => 'congregation', 'order_by' => 'name', 'allow_empty' => 'true', 'empty_text' => 'All congregations'), array_get($_REQUEST, 'congregation')); ?><br />

		<input type="radio" class="select-chooser-radio" name="for_type" value="group" <?php if (!empty($this->_group)) echo 'checked="checked"'; ?> id="for_type_group" />
		<label for="for_type_group">
		in the group</label>
		<?php print_widget('group', Array('type' => 'reference', 'references' => 'person_group', 'filter' => Array('can_record_attendance' => '1', 'is_archived' => 0)), array_get($_REQUEST, 'group')); ?><br />

		has been 

		<?php print_widget('operator', $operator_params, $this->_operator); ?>
		<input name="percent" type="text" size="2" class="int-box" value="<?php echo (int)$this->_percent; ?>" />%
		<br />over the last <input name="weeks" type="text" size="2" class="int-box" value="<?php echo (int)$this->_weeks; ?>" /> weeks
		<input type="submit" value="Go" />
		<p class="smallprint">Note: Any weeks where a person's attendance is left blank (neither present nor absent) are ignored when calculating attendance percentages</p>
		</form>
		<?php
		
	}

	function _printResults()
	{
		if (is_null($this->_persons)) return;
		if (empty($this->_persons)) {
			?>
			<p>There are no persons matching these criteria</p>
			<?php
		} else {
			$persons =& $this->_persons;
			include 'templates/person_list.template.php';
			// we want the links to open in a new window, and view-person links
			// to show attendance tab
			?>
			<script type="text/javascript">
				$('table.standard a').click(handleMedPopupLinkClick).attr('title', '(Opens in a new window)').each(function() {
					if (this.innerHTML == 'View') {
						this.href += '#attendance';
					}
				});
			</script>
			<?php
		}
	}
}
?>
