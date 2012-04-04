<?php
class View__Mixed_Search extends View
{
	var $_family_data;
	var $_person_data;

	function processView()
	{
		$this->_search_params = Array();

		if (!empty($_REQUEST['tel'])) {
			$this->_family_data = $GLOBALS['system']->getDBObjectData('family', Array('home_tel' => (int)$_REQUEST['tel']));
			$this->_person_data = $GLOBALS['system']->getDBObjectData('person', Array('mobile_tel' => (int)$_REQUEST['tel'], 'work_tel' => (int)$_REQUEST['tel']));
		}
	}

	function getTitle()
	{
		if (empty($this->_family_data)) {
			if (count($this->_person_data) == 1) {
				return 'One Matching Person Found';
			} else {
				return count($this->_person_data).' Matching Persons Found';
			}
		}
		if (empty($this->_person_data)) {
			if (count($this->_family_data) == 1) {
				return 'One Matching Family Found';
			} else {
				return count($this->_family_data).' Matching Families Found';
			}
		}
		$total = count($this->_family_data) + count($this->_person_data);
		if ($total) {
			return $total.' Matches Found';
		} else {
			return 'No Matches Found';
		}
	}

	
	function printView()
	{
		if (empty($this->_family_data) && (count($this->_person_data) == 1)) {
			$person =& $GLOBALS['system']->getDBObject('person', key($this->_person_data));
			$family =& $GLOBALS['system']->getDBObject('family', $person->values['familyid']);
			include dirname(dirname(__FILE__)).'/templates/view_person.template.php';
		} else if (empty($this->_person_data) && (count($this->_family_data) == 1)) {
			$family =& $GLOBALS['system']->getDBObject('family', key($this->_family_data));
			include dirname(dirname(__FILE__)).'/templates/view_family.template.php';
		} else {
			if (!empty($this->_person_data)) {
				?>
				<h3><?php echo count($this->_person_data); ?> Person Record(s):</h3>
				<table class="hoverable standard">
					<?php
					foreach ($this->_person_data as $id => $values) {
						?>
						<tr>
							<td><?php echo $id; ?></td>
							<td><?php echo htmlentities($values['first_name']); ?></td>
							<td><?php echo htmlentities($values['last_name']); ?></td>
							<td>
								<a href="?view=persons&personid=<?php echo $id; ?>">View Person Record</a> &nbsp;
								<a href="?view=_edit_person&personid=<?php echo $id; ?>">Edit Person Record</a>
							</td>
						</tr>
						<?php
					}
					?>
				</table>
				<?php
			}
			if (!empty($this->_family_data)) {
				?>
				<h3><?php echo count($this->_family_data); ?> Family Record(s):</h3>
				<table class="hoverable standard">
					<?php
					foreach ($this->_family_data as $id => $values) {
						?>
						<tr>
							<td><?php echo $id; ?></td>
							<td><?php echo htmlentities($values['family_name']); ?> Family</td>
							<td>
								<a href="?view=families&familyid=<?php echo $id; ?>">View Family Record</a> &nbsp;
								<a href="?view=_edit_family&familyid=<?php echo $id; ?>">Edit Family Record</a>
							</td>
						</tr>
						<?php
					}
					?>
				</table>
				<?php
			}
		}
	}
}
?>