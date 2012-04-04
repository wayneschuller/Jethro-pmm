<?php

// This is not a standard DB object but performs similarly
class Attendance_Record_Set
{

	var $date = NULL;
	var $congregationid = NULL;
	var $groupid = NULL;
	var $_attendance_records = Array();

//--        CREATING, LOADING AND SAVING        --//

	function Attendance_Record_Set($date=NULL, $congregationid=NULL, $groupid=NULL)
	{
		if ($date && ($congregationid || $groupid)) {
			$this->load($date, $congregationid, $groupid);
		}
	}

	function create()
	{
	}


	function getInitSQL()
	{
		return "
			CREATE TABLE `attendance_record` (
			  `date` date NOT NULL default '0000-00-00',
			  `personid` int(11) NOT NULL default '0',
			  `groupid` int(11) NOT NULL default '0',
			  `present` tinyint(1) unsigned NOT NULL default '0',
			  PRIMARY KEY  (`date`,`personid`,`groupid`)
			) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
		";
	}

	function load($date, $congregationid=0, $groupid=0)
	{
		$this->date = $date;
		$this->congregationid = $congregationid;
		$this->groupid = $groupid;
		$db =& $GLOBALS['db'];
		$sql = 'SELECT personid, present FROM attendance_record
				WHERE date = '.$db->quote($date).' 
				AND groupid = '.(int)$this->groupid;
		if ($this->congregationid) {
			$sql .= '
				AND personid IN (SELECT id FROM person WHERE congregationid = '.$db->quote($this->congregationid).')';
		}
		$this->_attendance_records = $db->queryAll($sql, null, null, true);
		check_db_result($this->_attendance_records);
	}


	function save()
	{
		if (empty($this->date)) {
			trigger_error('Cannot save attendance record set with no date', E_USER_WARNING);
			return;
		}
		$db =& $GLOBALS['db'];
		$GLOBALS['system']->doTransaction('begin');
			$this->delete();
			$stmt = $db->prepare('INSERT INTO attendance_record (date, groupid, personid, present) VALUES ('.$db->quote($this->date).', '.(int)$this->groupid.', ?, ?)', Array('integer', 'integer', 'integer'), MDB2_PREPARE_MANIP);
			check_db_result($stmt);
			foreach ($this->_attendance_records as $personid => $present) {
				$res = $stmt->execute(Array($personid, $present));
				check_db_result($res);
			}
		$GLOBALS['system']->doTransaction('commit');
	}


	function delete()
	{
		$db =& $GLOBALS['db'];
		$sql = 'DELETE FROM attendance_record
				WHERE date = '.$db->quote($this->date).'
					AND (groupid = '.$db->quote($this->groupid).')';
		if ($this->congregationid) {
			$sql .= '
					AND (personid IN (SELECT id FROM person WHERE congregationid = '.$db->quote($this->congregationid).') ';
			if (!empty($this->_attendance_records)) {
				$our_personids = array_map(Array($GLOBALS['db'], 'quote'), array_keys($this->_attendance_records));
				$sql .= ' OR personid IN ('.implode(', ', $our_personids).')';
			}
			$sql .= ')';
		}
		$res = $db->query($sql);
		check_db_result($res);
	}



//--        INTERFACE PAINTING AND PROCESSING        --//


	function printSummary()
	{
	}

	function printForm()
	{
		if ($this->congregationid) {
			$order = defined('ATTENDANCE_LIST_ORDER') ? constant('ATTENDANCE_LIST_ORDER') : 'status ASC, last_name ASC, age_bracket ASC, gender DESC';
			$members = $GLOBALS['system']->getDBObjectData('person', Array('congregationid' => $this->congregationid, '!status' => 'archived'), 'AND', $order);
		} else {
			$group =& $GLOBALS['system']->getDBObject('person_group', $this->groupid);
			$members =& $group->getMembers();
		}
		$GLOBALS['system']->includeDBClass('person');
		$dummy = new Person();
		?>
		<p class="smallprint">For greatest speed, press P for present and A for absent.  The cursor will automatically progress to the next person.  To go back, press Shift+Tab</p>
		<table class="standard valign-middle bubble-option-props">
		<?php
		foreach ($members as $personid => $details) {
			$v = array_get($this->_attendance_records, $personid, '?');
			$dummy->populate($personid, $details);
			?>
			<tr>
				<td><?php echo $personid; ?></td>
				<td><?php echo $details['last_name']; ?></td>
				<td><?php echo $details['first_name']; ?></td>
				<td><?php echo $dummy->printFieldValue('status'); ?></td>
				<td>
					<select name="attendances[<?php echo $personid; ?>]" class="attendance">
						<option value="?"<?php echo (($v == '?') ? ' selected="selected"' : ''); ?>></option>
						<option value="1" class="present"<?php echo (($v == '1') ? ' selected="selected"' : ''); ?>>Present</option>
						<option value="0" class="absent"<?php echo (($v == '0') ? ' selected="selected"' : ''); ?>>Absent</option>
					</select>
				</td>
				<td>
					<a class="med-popup" tabindex="-1" href="?view=persons&personid=<?php echo $personid; ?>">View Person</a> &nbsp;
					<a class="med-popup" tabindex="-1" href="?view=_edit_person&personid=<?php echo $personid; ?>">Edit Person</a> &nbsp;
					<a class="med-popup" tabindex="-1" href="?view=_add_note_to_person&personid=<?php echo $personid; ?>">Add Note</a>
				</td>
			</tr>
			<?php
		}
		?>
		</table>
		<?php
	}

	function processForm()
	{
		$this->_attendance_records = Array();
		foreach ($_POST['attendances'] as $personid => $present) {
			if ($present != '?') {
				$this->_attendance_records[$personid] = $present;
			}
		}
	}

	function printStats()
	{
		$freqs = array_count_values($this->_attendance_records);
		$db =& $GLOBALS['db'];
		$sql = 'SELECT status, COUNT(id)
				FROM person
				WHERE id IN
					(SELECT personid 
					FROM attendance_record 
					WHERE date = '.$db->quote($this->date).' 
						AND present = __PRESENT__
						AND groupid = '.$db->quote($this->groupid).'
					)';
		if ($this->congregationid) {
			$sql .= '
				AND congregationid = '.$db->quote($this->congregationid);
		}
		$sql .= '
				GROUP BY status';

		$present_sql = str_replace('__PRESENT__', '1', $sql);
		$present_breakdown = $db->queryAll($present_sql, null, null, true);
		check_db_result($present_breakdown);

		$absent_sql = str_replace('__PRESENT__', '0', $sql);
		$absent_breakdown = $db->queryAll($absent_sql, null, null, true);
		check_db_result($absent_breakdown);

		$GLOBALS['system']->includeDBClass('person');
		$dummy = new Person();

		?>
		<table class="standard">
			<tr>
				<th>Present</th>
				<td>
					<?php echo array_get($freqs, 1, 0); ?> persons
					<table style="margin: 3px">
					<?php
					foreach ($present_breakdown as $status => $number) {
						$dummy->setValue('status', $status);
						?>
						<tr>
							<th><?php $dummy->printFieldValue('status'); ?></th>
							<td><?php echo $number; ?></td>
						</tr>
						<?php
					}
					?>
					</table>
				</td>
			</tr>
			<tr>
				<th>Absent</th>
				<td>
					<?php echo array_get($freqs, 0, 0); ?> persons
					<table style="margin: 3px">
					<?php
					foreach ($absent_breakdown as $status => $number) {
						$dummy->setValue('status', $status);
						?>
						<tr>
							<th><?php $dummy->printFieldValue('status'); ?></th>
							<td><?php echo $number; ?></td>
						</tr>
						<?php
					}
					?>
					</table>
				</td>
			</tr>
		</table>
		<?php
	}

	function getCongregationalAttendanceStats($start_date, $end_date, $congregations=Array())
	{
		$db =& $GLOBALS['db'];

		$sql = '
				SELECT status, AVG(percent_present) as avg_attendance FROM
				(
					SELECT ar.personid, p.status as status, CONCAT(ROUND(SUM(ar.present) * 100 / COUNT(ar.date)), '.$db->quote('%').') as percent_present
					FROM 
						person p 
						JOIN attendance_record ar ON p.id = ar.personid
					WHERE 
						ar.date BETWEEN '.$db->quote($start_date).' AND '.$db->quote($end_date).'
						AND ar.groupid = 0
				';
		if (!empty($congregations)) {
			$int_congs = Array();
			foreach ($congregations as $congid) {
				$int_congs[] = (int)$congid;
			}
			$sql .= '	AND p.congregationid IN ('.implode(',', $int_congs).')';
		}
		$sql .=	'
					GROUP BY ar.personid, p.status
				) indiv
				GROUP BY status';
		$res = $db->queryAll($sql);
		check_db_result($res);

		$stats = Array();
		foreach ($res as $row) {
			$stats[$row['status']] = round($row['avg_attendance']);
		}
		return $stats;
	}

	function getPersonsByAttendance($target_percent, $cutoff_ts, $congregation=0, $operator='<', $groupid=0)
	{
		if ($operator <> '<') $operator = '>';
		$db =& $GLOBALS['db'];
		$sql = 'SELECT ar.personid, p.*, CONCAT(ROUND(SUM(ar.present) * 100 / COUNT(ar.date)), '.$db->quote('%').') as percent_present
				FROM 
					person p 
					JOIN attendance_record ar ON p.id = ar.personid
					JOIN family f ON p.familyid = f.id
				WHERE UNIX_TIMESTAMP(ar.date) >= '.$db->quote((int)$cutoff_ts).'
				AND ar.groupid = '.(int)$groupid.'
				AND p.status <> '.$db->quote('archived');
		if ($congregation) {
			$sql .= '
				AND p.congregationid = '.$db->quote((int)$congregation);
		}
		$order = defined('ATTENDANCE_LIST_ORDER') ? constant('ATTENDANCE_LIST_ORDER') : 'status ASC, last_name ASC, age_bracket ASC, gender DESC';
		$sql .= '
				GROUP BY p.id, p.first_name, p.last_name, p.congregationid
				HAVING percent_present '.$operator.' '.$db->quote((int)$target_percent).'
				ORDER BY '.$order;
		$persons_res = $db->queryAll($sql, null, null, true);
		check_db_result($persons_res);

		if ($GLOBALS['user_system']->havePerm(PERM_VIEWNOTE)) {

			$notes_res = Array();
			if (!empty($persons_res)) {
				$sql = 'SELECT pn.personid, GROUP_CONCAT(an.subject SEPARATOR '.$db->quote(', ').') 
						FROM abstract_note an JOIN person_note pn ON an.id = pn.id
						WHERE an.status = '.$db->quote('pending').'
							AND an.action_date <= NOW()
							AND pn.personid IN ('.implode(',', array_map(Array($db, 'quote'), array_keys($persons_res))).')
						GROUP BY pn.personid';
				$notes_res = $db->queryAll($sql, null, null, true);
				check_db_result($notes_res);
			}

			foreach ($persons_res as $personid => $result) {
				$persons_res[$personid]['outstanding_notes'] = array_get($notes_res, $personid, '');
			}
		}

		return $persons_res;
	}

		


}//end class
