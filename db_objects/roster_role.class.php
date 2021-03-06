<?php
include_once 'include/db_object.class.php';
class roster_role extends db_object
{
	var $_load_permission_level = NULL;
	var $_save_permission_level = PERM_MANAGEROSTERS;
	var $_volunteers = NULL;

	function _getFields()
	{
		
		$fields = Array(
			'congregationid'	=> Array(
									'type'				=> 'reference',
									'references'		=> 'congregation',
									'label'				=> 'Congregation',
									'show_id'			=> FALSE,
									'order_by'			=> 'meeting_time',
									'allow_empty'		=> TRUE,
									'filter'			=> create_function('$x', '$y = $x->getValue("meeting_time"); return !empty($y);'),
								   ),
			'title'		=> Array(
									'type'		=> 'text',
									'width'		=> 80,
									'maxlength'	=> 128,
									'initial_cap'	=> TRUE,
									'allow_empty' => FALSE,
								   ),
			'volunteer_group'		=> Array(
									'type'		=> 'reference',
									'references' => 'person_group',
									'order_by'	=> 'name',
									'allow_empty'	=> true,
									'note'			=> 'If no volunteer group is chosen, any person in the system can be allocated to this role'
								   ),
			'assign_multiple'	=> Array(
									'type'			=> 'select',
									'options'		=> Array(1 => 'Yes', 0 => 'No'),
									'default'		=> 0,
							   ),
			'details'		=> Array(
									'type'		=> 'html',
									'width'		=> 80,
									'height'	=> 4,
									'note' => 'These details will be shown when a public user clicks the role name in the public roster'
								   ),
			'active'		=> Array(
									'type'			=> 'select',
									'options'		=> Array(1 => 'Yes', 0 => 'No'),
									'default'		=> '1',
									'note'			=> 'When a role is no longer to be used, mark it as inactive'
							   ),
		);
		return $fields;
	}

	function printFieldInterface($name, $prefix='')
	{
		if (($name == 'volunteer_group') && (empty($this->id) || $this->haveLock())) {
			$GLOBALS['system']->includeDBClass('person_group');
			$value = array_get($this->values, $name);
			Person_Group::printChooser($prefix.$name, $value, array(), null, '(None)');
		} else {
			parent::printFieldInterface($name, $prefix);
		}
	}

	function _getVolunteers()
	{
		if (is_null($this->_volunteers)) {
			$this->_volunteers = Array();
			if ($this->getValue('volunteer_group')) {
				$group = $GLOBALS['system']->getDBObject('person_group', $this->getValue('volunteer_group'));
				foreach ($group->getMembers() as $id => $details) {
					if ($details['status'] == 'archived') continue;
					$this->_volunteers[$id] = $details['first_name'].' '.$details['last_name'];
				}
			}
		}
		return $this->_volunteers;
	}


	function getInstancesQueryComps($params, $logic, $order)
	{
		$res = parent::getInstancesQueryComps($params, $logic, $order);
		$res['select'][] = 'g.name as volunteer_group_name, c.name as congregation_name';
		$res['from'] = '('.$res['from'].') 
							LEFT OUTER JOIN person_group g ON roster_role.volunteer_group = g.id
							LEFT OUTER JOIN congregation c ON roster_role.congregationid = c.id';
		return $res;
	}

	function _printUnlistedAlloceeOption($personid, $name)
	{
		?>
		<option value="<?php echo (int)$personid; ?>" class="unlisted-allocee" selected="selected" title="This person is no longer in the volunteer group for this role"><?php echo htmlentities($name); ?></option>
		<?php
	}

	function printChooser($date, $currentval=Array(''))
	{
		if ($groupid = $this->getValue('volunteer_group')) {
			$volunteers = $this->_getVolunteers();
			if ($this->getValue('assign_multiple')) {
				if (empty($currentval)) $currentval = Array('');
				?>
				<table class="expandable no-borders no-padding">
				<?php
				foreach ($currentval as $id => $name) {
					?>
					<tr><td>
					<select name="assignees[<?php echo $this->id; ?>][<?php echo $date; ?>][]">
						<option value=""></option>
					<?php
					if (!empty($id) && !isset($volunteers[$id])) $this->_printUnlistedAlloceeOption($id, $name);
					foreach ($volunteers as $vid => $name) {
						?>
						<option value="<?php echo $vid; ?>"<?php if ($vid == $id) echo ' selected="selected"'; ?>><?php echo htmlentities($name); ?></option>
						<?php
					}
					?>
					</select>
					</td></tr>
					<?php
				}
				?>
				</table>
				<?php
			} else {
				?>
				<select name="assignees[<?php echo $this->id; ?>][<?php echo $date; ?>]">
					<option value=""></option>
				<?php
				if (!empty($id) && !isset($volunteers[$id])) $this->_printUnlistedAlloceeOption($id, $name);
				foreach ($volunteers as $id => $name) {
					?>
					<option value="<?php echo $id; ?>"<?php if (isset($currentval[$id])) echo ' selected="selected"'; ?>><?php echo htmlentities($name); ?></option>
					<?php
				}
				?>
				</select>
				<?php
			}
		} else {
			$GLOBALS['system']->includeDBClass('person');
			if ($this->getValue('assign_multiple')) {
				Person::printMultipleFinder('assignees['.$this->id.']['.$date.']', $currentval);
			} else {
				Person::printSingleFinder('assignees['.$this->id.']['.$date.']', $currentval);
			}
		}
	}

}
?>
