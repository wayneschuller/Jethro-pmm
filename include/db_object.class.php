<?php

require_once 'MDB2/Date.php';
class db_object
{

	var $id = NULL;
	var $fields = Array();
	var $values = Array();

	var $_old_values = Array();
	var $_held_locks = Array();
	var $_acquirable_locks = Array();

	var $_tmp = Array();

	var $_load_permission_level = 0;
	var $_save_permission_level = 0;


//--        CREATING, LOADING AND SAVING        --//

	function db_object($id=0)
	{
		if (!$GLOBALS['user_system']->havePerm($this->_load_permission_level)) {
			trigger_error('Current user has insufficient permission level to load a '.get_class($this).' object', E_USER_ERROR);
		}

		$this->fields = Array();
		$parent_class = get_parent_class($this);
		while ($parent_class != 'db_object') {
			$new_fields = call_user_func(Array($parent_class, '_getFields'));
			foreach ($new_fields as $i => $v) {
				$new_fields[$i]['table_name'] = strtolower($parent_class);
			}
			$this->fields += $new_fields;
			$parent_class = get_parent_class($parent_class);
		}
		$own_fields = $this->_getFields();
		foreach ($own_fields as $i => $v) {
			$own_fields[$i]['table_name'] = strtolower(get_class($this));
		}
		$this->fields = $own_fields + $this->fields;
		if ($id) {
			$this->load($id);
		} else {
			$this->loadDefaults();
		}
	}


	function getInitSQL($table_name=NULL)
	{
		return $this->_getInitSQL($table_name);
	}

	/* This helper allows grandchild classes access to the default getInitSQL function */
	function _getInitSQL($table_name=NULL)
	{
		if (is_null($table_name)) $table_name = strtolower(get_class($this));
		$indexes = '';
		foreach ($this->_getUniqueKeys() as $name => $fields) {
			$indexes .= ',
				UNIQUE KEY `'.$name.'` ('.implode(', ', $fields).')';
		}
		foreach ($this->_getIndexes() as $name => $fields) {
			$indexes .= '
				INDEX `'.$name.'` ('.implode(', ', $fields).')';
		}

		$res = "
			CREATE TABLE `".$table_name."` (
			  `id` int(11) NOT NULL auto_increment,
				";
		foreach ($this->_getFields() as $name => $details) {
			$type = 'varchar(255)';
			$default = array_get($details, 'default', '');
			$null_exp = array_get($details, 'allow_empty', 0) ? 'NULL' : 'NOT NULL';
			switch ($details['type']) {
				case 'date':
					$type = 'date';
					break;
				case 'datetime':
					$type = 'datetime';
					break;
				case 'timestamp':
					$type = 'timestamp';
					$default = 'CURRENT_TIMESTAMP';
					break;
				case 'text':
					if (array_get($details, 'height', 1) != 1) {
						$type = 'text';
					} else {
						$type = 'varchar(255)';
					}
					break;
				case 'bibleref':
					$type = 'char(19)';
					break;
				case 'int':
					if (!is_null($len = array_get($details, 'fixed_length'))) {
						$type = 'varchar('.$len.')';
					} else {
						$type = 'int(11)';
					}
					$default = array_get($details, 'default', 0);
					break;
				case 'reference':
					$type = 'int(11)';
					$default = array_get($details, 'default', 0);
					break;
				case 'serialise':
					$type = 'text';
					break;
			}

			switch ($default) {
				case 'CURRENT_TIMESTAMP':
				case 'NULL':
				case '0':
					break;
				default:
					$default = $GLOBALS['db']->quote($default);
					break;
			}

			$res .= "`".$name."` ".$type." ".$null_exp." default ".$default.",
				";
		}
		$res .= "PRIMARY KEY (`id`)".$indexes."
			) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;";
		return $res;
	}

	function _getIndexes()
	{
		return Array();
	}

	function _getUniqueKeys()
	{
		return Array();
	}

	function create()
	{
		if (!$GLOBALS['user_system']->havePerm($this->_save_permission_level)) {
			trigger_error('Current user has insufficient permission level to create a '.get_class($this).' object', E_USER_ERROR);
		}

		$GLOBALS['system']->setFriendlyErrors(TRUE);
		if (!$this->readyToCreate()) {
			return FALSE;
		}
		$GLOBALS['system']->setFriendlyErrors(FALSE);
		if (isset($this->fields['creator']) && empty($this->values['creator'])) {
			$userid = $GLOBALS['user_system']->getCurrentUser('id');
			if (!is_null($userid)) {
				$this->values['creator'] = $userid;
			}
		}
		if (isset($this->fields['history'])) {
			$this->values['history'] = Array(time() => 'Created');
		}

		$parent_class =  strtolower(get_parent_class($this));
		if ($parent_class != 'db_object') {
			$parent_obj = new $parent_class();
			$parent_obj->populate(0, $this->values);
			if (!$parent_obj->create()) {
				return FALSE;
			}
			$this->id = $parent_obj->id;
		}

		return $this->_createFinal();
	}

	function _createFinal()
	{
		$db =& $GLOBALS['db'];
		$flds = Array();
		$vals = Array();
		$our_fields = $this->_getFields();
		foreach ($our_fields as $name => $details) {
			if (array_get($details, 'readonly')) continue;
			$flds[] = $name;
			$v = array_get($this->values, $name, '');
			if ($details['type'] == 'serialise') {
				$vals[] = $db->quote(serialize($v));
			} else {
				$vals[] = $db->quote($v);
			}
		}

		if ($this->id) {
			// if this class doesn't extend db_object directly then ID is not an auto-increment field
			// so we save it like the rest
			array_unshift($flds, 'id');
			array_unshift($vals, $db->quote((int)$this->id));
		}

		$sql = 'INSERT INTO '.strtolower(get_class($this)).' ('.implode(', ', $flds).')
				 VALUES ('.implode(', ', $vals).')';
		$res = $db->query($sql);
		check_db_result($res);
		if (empty($this->id)) $this->id = $db->lastInsertId();
		$this->_old_values = Array();
		return TRUE;
	}


	function createFromChild(&$child)
	{
		if (!$GLOBALS['user_system']->havePerm($this->_save_permission_level)) {
			trigger_error('Current user has insufficient permission level to create a '.get_class($this).' object', E_USER_ERROR);
		}
		$this->populate($child->id, $child->values);
		return $this->_createFinal();
	}


	function _getTableNames()
	{
		$res = strtolower(get_class($this));
		$parent_class = strtolower(get_parent_class($this));
		while ($parent_class != 'db_object') {
			$res  = '('.$res.' JOIN '.$parent_class.' on '.$res.'.id = '.$parent_class.'.id)';
			$parent_class = strtolower(get_parent_class($parent_class));
		}
		return $res;
	}

	/**
	* Get the fields for this class only
	*
	* (Fields for parent classes are automatically added when instanting objects of this class
	*
	* @return array
	* @access protected
	*/
	function _getFields()
	{
		return Array();
	}


	function load($id)
	{
		$db =& $GLOBALS['db'];
		$sql = 'SELECT *
				FROM '.strtolower($this->_getTableNames()).'
				WHERE '.strtolower(get_class($this)).'.id = '.$db->quote($id);
		$res = $db->queryRow($sql);
		check_db_result($res);
		if (!empty($res)) {
			$this->id = $res['id'];
			unset($res['id']);
			$this->values = $res;
		}
		foreach ($this->fields as $name => $details) {
			if (($details['type'] == 'serialise') && isset($this->values[$name])) {
				$this->values[$name] = unserialize($this->values[$name]);
			}
		}
	}


	function loadDefaults()
	{
		foreach ($this->fields as $id => $details) {
			$this->values[$id] = array_get($details, 'default', '');
		}
	}


	function save()
	{
		if (!$GLOBALS['user_system']->havePerm($this->_save_permission_level)) {
			trigger_error('Current user has insufficient permission level to save a '.get_class($this).' object', E_USER_ERROR);
		}
		$GLOBALS['system']->setFriendlyErrors(TRUE);
		if (!$this->validateFields()) {
			return FALSE;
		}
		$GLOBALS['system']->setFriendlyErrors(FALSE);

		if (!$this->haveLock() && !$this->acquireLock()) {
			trigger_error('Cannot save values for '.get_class($this).' #'.$this->id.' because someone else has the lock', E_USER_NOTICE);
			return FALSE;
		}

		if (empty($this->_old_values)) return TRUE;

		// Set the history
		if (isset($this->fields['history'])) {
			$changes = Array();
			foreach ($this->_old_values as $name => $old_val) {
				if ($name == 'history') continue;
				if ($name == 'password') continue;
				$changes[] = $this->getFieldLabel($name).' changed from "'.htmlentities($this->getFormattedValue($name, $old_val)).'" to "'.htmlentities($this->getFormattedValue($name)).'"';
			}
			$user = $GLOBALS['user_system']->getCurrentUser();
			$this->values['history'][time()] = 'Updated by '.$user['first_name'].' '.$user['last_name'].' (#'.$user['id'].")\n".implode("\n", $changes);
			$this->_old_values['history'] = 1;
		}

		// Set any last-changed fields
		foreach ($this->_old_values as $i => $v) {
			if (array_key_exists($i.'_last_changed', $this->fields)) {
				$this->values[$i.'_last_changed'] = date('c');
				$this->_old_values[$i.'_last_changed'] = 1;
			}
		}

		$parent_class = strtolower(get_parent_class($this));
		if ($parent_class != 'db_object') {
			$parent_obj = new $parent_class($this->id);
			$parent_obj->populate($this->id, $this->values);
			if (!$parent_obj->save()) {
				return FALSE;
			}
		}
		
		// Update the DB
		$db =& $GLOBALS['db'];
		$sets = Array();
		$our_fields = $this->_getFields();
		foreach ($this->_old_values as $i => $v) {
			if (!isset($our_fields[$i])) continue;
			if (array_get($this->fields[$i], 'readonly')) continue;
			$new_val = $this->values[$i];
			if ($this->fields[$i]['type'] == 'serialise') {
				$new_val = serialize($new_val);
			}
			$sets[] = ''.$i.' = '.$db->quote($new_val).'';
		}
		if (!empty($sets)) {
			$sql = 'UPDATE '.strtolower(get_class($this)).'
					SET '.implode("\n, ", $sets).'
					WHERE id = '.$db->quote($this->id);
			$res = $db->query($sql);
			check_db_result($res);
		}

		$this->_old_values = Array();

		return TRUE;
	}

	function populate($id, $values)
	{
		$this->_old_values = Array();
		$this->id = $id;
		foreach ($this->fields as $fieldname => $details) {
			if (empty($details['readonly']) && isset($values[$fieldname])) {
				$this->setValue($fieldname, $values[$fieldname]);
			}
		}
		$this->_held_locks = Array();
		$this->_acquirable_locks = Array();
	}

	function delete()
	{
		$db =& $GLOBALS['db'];
		$table_name = strtolower(get_class($this));
		while ($table_name != 'db_object') {
			$sql = 'DELETE FROM '.$table_name.' WHERE id='.$db->quote($this->id);
			$res = $db->query($sql);
			check_db_result($res);
			$table_name = strtolower(get_parent_class($this));
		}
	}

	function hasField($fieldname)
	{
		return isset($this->fields[$fieldname]);
	}





//--        GETTING AND SETTING FIELD VALUES        --//


	function setValue($name, $value)
	{
		if (!isset($this->fields[$name])) {
			trigger_error('Cannot set value for field '.htmlentities($name).' - field does not exist', E_USER_WARNING);
			return FALSE;
		}
		if (array_get($this->fields[$name], 'readonly')) {
			trigger_error('Cannot set value for readonly field "'.$name.'"', E_USER_WARNING);
			return;
		}
		if (array_get($this->fields[$name], 'initial_cap')) {
			$value = ucfirst($value);
		}
		if (array_get($this->fields[$name], 'trim')) {
			$value = trim($value, ",;. \t\n\r\0\x0B");
		}
		if ($this->fields[$name]['type'] == 'select') {
			if (!isset($this->fields[$name]['options'][$value])) {
				trigger_error(htmlentities($value).' is not a valid value for field "'.$name.'", and has not been set', E_USER_NOTICE);
				return;
			}
		}
		if (($this->fields[$name]['type'] == 'phone') && ($value != '')) {
			if (!is_valid_phone_number($value, $this->fields[$name]['formats'])) {
				trigger_error(htmlentities($value).' is not a valid phone number for field "'.$name.'", and has not been set', E_USER_NOTICE);
				return;
			}
		}
		if (!empty($this->fields[$name]['maxlength']) && (strlen($value) > $this->fields[$name]['maxlength'])) {
			$value = substr($value, 0, $this->fields[$name]['maxlength']);
		}
		if ($this->fields[$name]['type'] == 'int') {
			if (!array_get($this->fields[$name], 'allow_empty', true) || ($value !== '')) {
				$strval = (string)$value;
				for ($i=0; $i < strlen($strval); $i++) {
					$char = $strval[$i];
					if ((int)$char != $char) {
						trigger_error(htmlentities($value).' is not a valid value for integer field "'.$name.'" and has not been set', E_USER_NOTICE);
						return;
					}
				}
			}
		}
		if (array_key_exists($name, $this->values) && ($this->values[$name] != $value) && !isset($this->_old_values[$name])) {
			$this->_old_values[$name] = $this->values[$name];
		}
		$this->values[$name] = $value;
	}

	function getValue($name)
	{
		return array_get($this->values, $name);
	}

	function validateFields()
	{
		$res = TRUE;
		foreach ($this->fields as $id => $details) {
			$val = array_get($this->values, $id);
			if (!array_get($details, 'allow_empty', true) && (is_null($val) || ($val === ''))) {
				trigger_error($this->getFieldLabel($id).' is a required field for '.get_class($this).' and cannot be left empty', E_USER_NOTICE);
				$res = FALSE;
			}

			if (isset($details['max_length']) && (strlen($val) > $details['max_length'])) {
				trigger_error('The value for '.array_get($details, 'label', $id).' is too long (maximum is '.$details['max_length'].' characters)', E_USER_NOTICE);
				$res = FALSE;
			}

			if (isset($details['fixed_length']) && !empty($val) && (strlen($val) != $details['fixed_length'])) {
				trigger_error('The value for '.array_get($details, 'label', $id).' is not the correct length (must be exactly '.$details['fixed_length'].' characters)', E_USER_NOTICE);
				$res = FALSE;
			}
		}
		return $res;
	}


	function readyToCreate()
	{
		return $this->validateFields();

	}


//--        INTERFACE PAINTING AND PROCESSING        --//


	function printSummary()
	{
		?>
		<table class="standard">
		<?php
		$this->_printSummaryRows();
		?>
		</table>
		<?php
	}

	
	function _printSummaryRows()
	{
		foreach ($this->fields as $name => $details) {
			if (!array_get($details, 'show_in_summary', true)) continue;
			?>
			<tr>
				<th>
					<?php echo array_get($details, 'label', ucwords(str_replace('_', ' ', $name))); ?>
				</th>
				<td>
					<?php $this->printFieldValue($name); ?>
				</td>
			</tr>
			<?php
		}
	}

	/**
	* Get the formatted value of a field
	*
	* This is used for HTML and non-HTML output so HTML should not be added
	* - see printFieldValue below for that.
	*/
	function getFormattedValue($name, $value=null)
	{
		if (!isset($this->fields[$name])) {
			trigger_error('Cannot get value for field '.htmlentities($name).' - field does not exist', E_USER_WARNING);
			return NULL;
		}
		if (is_null($value)) $value = array_get($this->values, $name, NULL);
		$field = $this->fields[$name];
		if (!empty($field['references'])) {
			$obj =& $GLOBALS['system']->getDBObject($field['references'], $value);
			if (!is_null($obj)) {
				if (!array_get($field, 'show_id', true)) {
					return $obj->toString();
				} else {
					return $obj->toString().' (#'.$value.')';
				}
			} else {
				if ($value != 0)  {
					return $value;
				}
			}
			return '';
		}
		switch ($field['type']) {
			case 'select':
				return array_get($field['options'], $value, '(Invalid Value)');
				break;
			case 'datetime':
				if (empty($value) && array_get($field, 'allow_empty')) return '';
				return format_datetime($value);
				break;
			case 'bibleref':
				require_once 'bible_ref.class.php';
				$br = new bible_ref($value);
				return $br->toShortString();
				break;
			case 'phone':
				return format_phone_number($value, $field['formats']);
				break;
			default:
				if (is_array($value)) {
					return '<pre>'.print_r($value, 1).'</pre>';
				} else {
					return $value;
				}
		}

	}


	/**
	* Print the value of a field to the HTML interface
	*
	* Subclasses should add links and other HTML markup by overriding this
	*/
	function printFieldValue($name, $value=null)
	{
		if (!isset($this->fields[$name])) {
			trigger_error('Cannot get value for field '.htmlentities($name).' - field does not exist', E_USER_WARNING);
			return NULL;
		}
		if (is_null($value)) $value = $this->values[$name];
		if (($name == 'history') && !empty($value)) {
			?>
			<table class="history standard">
			<?php
			foreach ($value as $time => $detail) {
				?>
				<tr>
					<th><?php echo format_datetime($time); ?></th>
					<td><?php echo nl2br(htmlentities($detail)); ?></td>
				</tr>
				<?php
			}
			?>
			</table>
			<?php
		} else if ($this->fields[$name]['type'] == 'bitmask') {
			$percol = false;
			if (!empty($this->fields[$name]['cols']) && (int)$this->fields[$name]['cols'] > 1) {
				$percol = ceil(count($this->fields[$name]['options']) / $this->fields[$name]['cols']);
				?>
				<table>
					<tr>
						<td class="nowrap">
				<?php
			}
			$i = 0;
			foreach ($this->fields[$name]['options'] as $k => $v) {
				$checked_exp = (($value & (int)$k) == $k) ? 'checked="checked"' : '';
				?>
				<input type="checkbox" disabled="disabled" name="<?php echo htmlentities($name); ?>[]" value="<?php echo htmlentities($k); ?>" id="<?php echo htmlentities($name.'_'.$k); ?>" <?php echo $checked_exp; ?>>
				<label for="<?php echo htmlentities($name.'_'.$k); ?>"><?php echo nbsp(htmlentities($v)); ?></label>
				<?php
				if ($percol && (++$i % $percol == 0)) {
					?>
					</td>
					<td class="nowrap">
					<?php
				} else {
					?>
					<br />
					<?php
				}
			}
			if ($percol) {
				?>
						</td>
					</tr>
				</table>
				<?php
			}
		} else if (($this->fields[$name]['type'] == 'text') 
					&& (array_get($this->fields[$name], 'height', 1) > 1)) {
			echo nl2br($this->getFormattedValue($name, $value));
		} else {
			echo $this->getFormattedValue($name, $value);
		}
	}


	function printForm($prefix='', $fields=NULL)
	{
		?>
		<table>
		<?php
		foreach ($this->fields as $name => $details) {
			if (!is_null($fields) && !in_array($name, $fields)) continue;
			if (array_get($details, 'readonly')) continue;
			if (!array_get($details, 'editable', true)) continue;
			?>
			<tr>
				<th>
					<?php echo $this->getFieldLabel($name); ?>
				</th>
				<td>
					<?php 
					$this->printFieldInterface($name, $prefix); 
					if (!empty($this->fields[$name]['note'])) {
						echo '<div class="field-note">'.htmlentities($this->fields[$name]['note']).'</div>';
					}
					?>
				</td>
			</tr>
			<?php
		}
		?>
		</table>
		<?php

	}


	function getFieldLabel($id)
	{
		if (empty($id)) return;
		if (!isset($this->fields[$id])) {
			return ucwords($id);
			//trigger_error('No such field '.$id);
			//return;
		}
		return array_get($this->fields[$id], 'label', ucwords(str_replace('_', ' ', $id)));

	}

	function processForm($prefix='', $fields=NULL)
	{
		$GLOBALS['system']->setFriendlyErrors(TRUE);
		foreach ($this->fields as $name => $details) {
			if (!is_null($fields) && !in_array($name, $fields)) continue;
			if (array_get($details, 'readonly')) continue;
			if (!array_get($details, 'editable', true)) continue;
			$this->processFieldInterface($name, $prefix);
		}
		$GLOBALS['system']->setFriendlyErrors(FALSE);
	}

	function printFieldInterface($name, $prefix='')
	{
		$value = array_get($this->values, $name);
		if ($this->id && !$this->haveLock()) {
			echo $value;
		} else {
			print_widget($prefix.$name, $this->fields[$name], $value);
		}
	}

	function processFieldInterface($name, $prefix='')
	{
		if (!$this->id || $this->haveLock()) {
			$value = process_widget($prefix.$name, $this->fields[$name]);
			if (!is_null($value)) $this->setValue($name, $value);
		}

	}


//--        PERMISSIONS AND LOCKING        --//

	function haveLock($type='')
	{
		if (!empty($GLOBALS['JETHRO_INSTALLING'])) return TRUE;
		if (!isset($this->_held_locks[$type])) {
			$db =& $GLOBALS['db'];
			$sql = 'SELECT COUNT(*)
					FROM  db_object_lock
					WHERE object_type = '.$db->quote(strtolower(get_class($this))).'
						AND objectid = '.$db->quote($this->id).'
						AND lock_type = '.$db->quote($type).'
						AND userid = '.$GLOBALS['user_system']->getCurrentUser('id').'
						AND expires > '.$db->quote(MDB2_Date::unix2Mdbstamp(time()));
			$this->_held_locks[$type] = $db->queryOne($sql);
			check_db_result($this->_held_locks[$type]);
		}
		return $this->_held_locks[$type];
	}

	function canAcquireLock($type='')
	{
		if (!empty($GLOBALS['JETHRO_INSTALLING'])) return TRUE;
		if (!isset($this->_acquirable_locks[$type])) {
			$db =& $GLOBALS['db'];
			$sql = 'SELECT userid
					FROM  db_object_lock
					WHERE object_type = '.$db->quote(strtolower(get_class($this))).'
						AND lock_type = '.$db->quote($type).'
						AND objectid = '.$db->quote($this->id).'
						AND expires > '.$db->quote(MDB2_Date::unix2Mdbstamp(time()));
			$res = $db->queryOne($sql);
			check_db_result($res);
			if ($res == $GLOBALS['user_system']->getCurrentUser('id')) {
				$this->_acquirable_locks[$type] = TRUE; // already got it, what the heck
				$this->_held_locks[$type] = TRUE;
			} else {
				$this->_acquirable_locks[$type] = empty($res); // if nobody else has it, we can get it
			}
		}
		return $this->_acquirable_locks[$type];
	}

	function acquireLock($type='')
	{
		if ($this->haveLock($type)) return TRUE;
		if (!$this->canAcquireLock()) return FALSE;
		$db =& $GLOBALS['db'];
		$sql = 'INSERT INTO db_object_lock (objectid, object_type, lock_type, userid, expires)
				VALUES (
					'.$db->quote($this->id).',
					'.$db->quote(strtolower(get_class($this))).',
					'.$db->quote($type).',
					'.$db->quote($GLOBALS['user_system']->getCurrentUser('id')).',
					'.$db->quote(MDB2_Date::unix2Mdbstamp(strtotime('+'.LOCK_LENGTH))).')';
		$res = $db->query($sql);
		check_db_result($res);
		$this->_held_locks[$type] = TRUE;
		$this->_acquirable_locks[$type] = TRUE;

		if (rand(LOCK_CLEANUP_PROBABLILITY, 100) == 100) {
			$sql = 'DELETE FROM db_object_lock
					WHERE expires < '.$db->quote(MDB2_Date::unix2Mdbstamp(time()));
			$res = $db->query($sql);
			check_db_result($res);
		}

		return TRUE;
	}


	function releaseLock($type='')
	{
		$db =& $GLOBALS['db'];
		$sql = 'DELETE FROM db_object_lock
				WHERE userid = '.$db->quote($GLOBALS['user_system']->getCurrentUser('id')).'
					AND objectid = '.$db->quote($this->id).'
					AND lock_type = '.$db->quote($type).'
					AND object_type = '.$db->quote(strtolower(get_class($this)));
		$res = $db->query($sql);
		check_db_result($res);
		$this->_held_locks[$type] = FALSE;
		$this->_acquirable_locks[$type] = NULL;
	}


//--        GLOBAL        --//


	function getInstancesQueryComps($params, $logic, $order)
	{
		$db =& $GLOBALS['db'];
		if ($logic != 'OR') $logic = 'AND';
		$res = Array();
		$res['select'] = Array(strtolower(get_class($this)).'.id');
		foreach ($this->fields as $fieldname => $details) {
			if ($details['type'] == 'serialise') continue;
			$fieldname = $details['table_name'].'.'.$fieldname;
			$res['select'][] = $fieldname;
		}
		$res['from'] = $this->_getTableNames();
		$wheres = Array();
		foreach ($params as $field => $val) {
			$operator = is_array($val) ? 'IN' : ((FALSE === strpos($val, '%')) && (FALSE === strpos($val, '?'))) ? '=' : 'LIKE';
				$prefix = '';
				$suffix = '';
			if ($field[0] == '!') {
				$prefix = 'NOT (';
				$field = substr($field, 1);
				$suffix = ')';
			} else if ($field[0] == '<') {
				$operator = '<';
				$field = substr($field, 1);
			} else if ($field[0] == '>') {
				$operator = '>';
				$field = substr($field, 1);
			} else if ($field[0] == '-') {
				$operator = 'BETWEEN';
				$field = substr($field, 1);
			} else if ($field[0] == '(') {
				$operator = 'IN';
				$field = substr($field, 1);
			}
			$raw_field = $field;
			if ($field == 'id') {
				$field = strtolower(get_class($this)).'.'.$field;
			} else if (isset($this->fields[$field])) {
				$field = $this->fields[$field]['table_name'].'.'.$field;
			}
			if (isset($this->fields[$raw_field]) && $this->fields[$raw_field]['type'] == 'text') {
				$field = 'LOWER('.$field.')';
			}
			if ($operator == 'IN') {
				if (is_array($val)) {
					$val = implode(',', array_map(Array($GLOBALS['db'], 'quote'), $val));
				}
				$val = '('.$val.')'; // If val wasn't an array we dont quote it coz it's a subquery
				$wheres[] = '('.$prefix.$field.' '.$operator.' '.$val.$suffix.')';
			} else if ((is_array($val) && !empty($val))) {
				if ($operator == 'BETWEEN') {
					$field_details = array_get($this->fields, $field);
					if ($field_details && ($field_details['type'] == 'datetime') && (strlen($val[0]) == 10)) {
						// we're searching on a datetime field using date values
						// so extend them to prevent boundary errors
						$val[0] .= ' 00:00';
						$val[1] .= '23:59';
					}
					$wheres[] = '('.$field.' '.$operator.' '.$db->quote($val[0]).' AND '.$db->quote($val[1]).')';
				} else {
					$sub_wheres = Array();
					foreach ($val as $v) {
						$operator = ((FALSE === strpos($v, '%')) && (FALSE === strpos($v, '?'))) ? '=' : 'LIKE';
						if (isset($this->fields[$raw_field]) && $this->fields[$raw_field]['type'] == 'text') {
							$v = strtolower($v);
						}
						$sub_wheres[] = '('.$field.' '.$operator.' '.$db->quote($v).')';
					}
					$wheres[] = '('.$prefix.implode(' OR ', $sub_wheres).$suffix.')';
				}
			} else {
				if (isset($this->fields[$raw_field]) && $this->fields[$raw_field]['type'] == 'text') {
					$val = strtolower($val);
				}
				$wheres[] = '('.$prefix.$field.' '.$operator.' '.$db->quote($val).$suffix.')';
			}
		}

		$res['where'] = implode("\n\t".$logic.' ', $wheres);

		if (!empty($order)) {
			if (isset($this->fields[$order])) {
				$res['order_by'] = $this->fields[$order]['table_name'].'.'.$order;
			} else {
				$res['order_by'] = $order; // good luck...
			}
		}
		return $res;

	}

	function getInstancesData($params, $logic='OR', $order='')
	{
		$db =& $GLOBALS['db'];
		$query_bits = $this->getInstancesQueryComps($params, $logic, $order);
		$sql = 'SELECT '.implode(', ', $query_bits['select']).'
				FROM '.$query_bits['from'];
		if (!empty($query_bits['where'])) {
				$sql .= '
					WHERE '.$query_bits['where'];
		}
		if (!empty($query_bits['group_by'])) {
			$sql .= '
					GROUP BY '.$query_bits['group_by'];
		}
		if (!empty($query_bits['order_by'])) {
			$sql .= '
					ORDER BY '.$query_bits['order_by'];
		}
		$res = $db->queryAll($sql, null, null, true);
		check_db_result($res);
		return $res;

	}//end getInstances()

	function toString()
	{
		if (array_key_exists('name', $this->fields)) {
			return $this->getValue('name');
		} else if (array_key_exists('title', $this->fields)) {
			return $this->getvalue('title');
		} else {
			return get_class($this).' #'.$this->id;
		}
	}

	function findMatchingValue($field, $val)
	{
		$val = strtolower($val);
		if ($this->fields[$field]['type'] != 'select') return null;
		foreach ($this->fields[$field]['options'] as $k => $v) {
			if ($val == strtolower($k) || $val == strtolower($v)) return $k;
		}
		return null;
	}

	function fromCsvRow($row)
	{
		foreach ($this->fields as $fieldname => $field) {
			if (isset($row[$fieldname])) {
				$val = $row[$fieldname];
				if ($field['type'] == 'select') {
					if ($val) {
						$newval = $this->findMatchingValue($fieldname, $val);
						if (is_null($newval)) {
							trigger_error("\"$val\" is not a valid option for $fieldname");
							continue;
						} else {
							$val = $newval;
						}
					} else {
						$val = array_get($field, 'default', key($field['options']));
					}
				}
				$this->setValue($fieldname, $val);
			}
		}
		$this->validateFields();
	}


}//end class
