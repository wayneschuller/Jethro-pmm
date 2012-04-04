<?php
include_once 'include/db_object.class.php';
class Person_Query extends DB_Object
{
	var $_field_details = Array();
	var $_query_fields = Array('p.status', 'p.congregationid', 'p.age_bracket', 'p.gender', 'f.address_suburb', 'f.address_state', 'f.address_postcode', 'p.creator', 'p.created', 'p.status_last_changed', 'f.created');
	var $_show_fields = Array('p.first_name', 'p.last_name', 'f.family_name', 'p.age_bracket', 'p.gender', NULL, 'p.email', 'p.mobile_tel', 'p.work_tel', 'f.home_tel', NULL, 'f.address_street', 'f.address_suburb', 'f.address_state', 'f.address_postcode', NULL, 'p.creator', 'p.created', 'p.status_last_changed', 'f.created');
	var $_dummy_family = NULL;
	var $_dummy_person = NULL;
	var $_group_chooser_options_cache = NULL;

	function Person_Query($id=0)
	{
		if (!empty($GLOBALS['system'])) {
			$GLOBALS['system']->includeDBClass('person');
			$GLOBALS['system']->includeDBClass('family');
 			$this->_dummy_person = new Person();
			foreach ($this->_dummy_person->fields as $i => $v) {
				unset($this->_dummy_person->fields[$i]['readonly']);
			}
			$this->_dummy_family = new Family();
			foreach ($this->_dummy_family->fields as $i => $v) {
				unset($this->_dummy_family->fields[$i]['readonly']);
			}
			foreach ($this->_dummy_person->fields as $i => $v) {
				if ($v['type'] == 'serialise') {
					continue;
				}
				if ($i == 'familyid') continue;
				if (empty($v['label'])) $v['label'] = $this->_dummy_person->getFieldLabel($i);
				$this->_field_details['p.'.$i] = $v;
			}
			foreach ($this->_dummy_family->fields as $i => $v) {
				if ($v['type'] == 'serialise') {
					continue;
				}
				if (empty($v['label'])) $v['label'] = $this->_dummy_family->getFieldLabel($i);
				if (in_array($i, Array('status', 'created', 'creator'))) {
					$v['label'] = "Family's ".$v['label'];
				}
				$this->_field_details['f.'.$i] = $v;
			}
		}
		return $this->DB_Object($id);
	}

	function getInitSQL()
	{
		return "
			CREATE TABLE `person_query` (
			  `id` int(11) NOT NULL auto_increment,
			  `name` varchar(255) collate latin1_general_ci NOT NULL default '',
			  `creator` int(11) NOT NULL default '0',
			  `created` timestamp NOT NULL default CURRENT_TIMESTAMP,
			  `params` text collate latin1_general_ci NOT NULL,
			  PRIMARY KEY  (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
		";
	}


	function _getFields()
	{
		$default_params = Array(
							'rules'			=> Array('p.status' => Array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', 'contact')),
							'show_fields'	=> Array('p.first_name', 'p.last_name', '', '', 'view_link', 'checkbox'),
							'group_by'		=> '',
							'sort_by'		=> 'p.last_name',
							'include_groups'	=> Array(),
							'exclude_groups'	=> Array(),
						  );
		return Array(
			'name'	=> Array(
									'type'		=> 'text',
									'width'		=> 30,
									'maxlength'	=> 128,
									'allow_empty'	=> false,
									'initial_cap'	=> true,
								   ),
			'created'			=> Array(
									'type'			=> 'datetime',
									'readonly'		=> true,
									'show_in_summary'	=> false,
								   ),
			'creator'			=> Array(
									'type'			=> 'reference',
									'editable'		=> false,
									'references'	=> 'staff_member',
									'show_in_summary'	=> false,
								   ),
			'params'			=> Array(
									'type'			=> 'serialise',
									'editable'		=> false,
									'show_in_summary'	=> false,
									'default'		=> $default_params,

								   ),

		);
	}

	function toString()
	{
		return $this->values['name'];
	}


	function printForm()
	{
		$GLOBALS['system']->includeDBClass('person_group');
		$params = $this->getValue('params');
		?>
		<h3>Find me people...</h3>

		<h4>whose person/family record matches these rules:</h4>
		<table class="standard indent-left">
		<?php
		foreach ($this->_query_fields as $i) {
			$v = $this->_field_details[$i];
			if (in_array($v['type'], Array('select', 'reference', 'datetime', 'text'))
				&& !in_array($i, Array('p.first_name', 'p.last_name', 'f.family_name', 'p.remarks', 'p.email'))) {
				?>
				<tr>
					<td>
						<input type="checkbox" name="enable_rule[]" value="<?php echo $i; ?>" id="enable_rule_<?php echo $i; ?>" class="select-rule-toggle" <?php if (isset($params['rules'][$i])) echo 'checked="checked" '; ?>/>
						<label for="enable_rule_<?php echo $i; ?>">
							<strong><?php echo $v['label']; ?></strong>
							<?php
							if ($v['type'] == 'datetime') {
								echo 'is between...';
							} else {
								echo 'is...';
							}
							?>
						</label>
					</td>
					<td>
						<div class="select-rule-options" <?php if (!isset($params['rules'][$i])) echo 'style="display: none" '; ?>>
							<?php
							$key = str_replace('.', '_', $i);
							if ($v['type'] == 'datetime') {
								$value = array_get($params['rules'], $i, Array('from' => '2000-01-01', 'to' => date('Y-m-d')));
								print_widget('params_'.$key.'_from', Array('type' => 'date'), $value['from']);
								echo ' and ';
								print_widget('params_'.$i.'_to', Array('type' => 'date'), $value['to']);
							} else {
								$v['allow_multiple'] = TRUE;
								print_widget('params_'.$key, $v, array_get($params['rules'], $i, $v['type'] == 'select' ? Array() : ''));
							}
							?>
						</div>
					</td>
				</tr>
				<?php
			}
		}
		if ($GLOBALS['system']->featureEnabled('DATES')) {
			$value = array_get($params['rules'], 'date', Array('anniversary' => 1, 'typeid' => null, 'from' => '2000-01-01', 'to' => date('Y-m-d')));
			?>
			<tr>
				<td>
					<input type="checkbox" name="enable_rule[]" value="date" id="enable_rule_date" class="select-rule-toggle" <?php if (isset($params['rules']['date'])) echo 'checked="checked" '; ?>/>
					<label for="enable_rule_date">
						has a <strong>date field</strong>...
					</label>
				</td>
				<td>
					<div class="select-rule-options nowrap" <?php if (!isset($params['rules']['date'])) echo 'style="display: none" '; ?>>
						<?php
						print_widget('params_date_typeid', Array('type' => 'select', 'options' => Person::getDateTypes()), (string)$value['typeid']);
						echo 'with';
						print_widget('params_date_anniversary', Array('type' => 'select', 'options' => Array('exact value', 'exact value or anniversary')), (string)$value['anniversary']);
						echo 'between<br />';
						print_widget('params_date_from', Array('type' => 'date'), $value['from']);
						echo ' and ';
						print_widget('params_date_to', Array('type' => 'date'), $value['to']);
						?>
					</div>
				</td>
			</tr>
			<?php
		}
		?>
		</table>

		<h4>who <strong>are</strong> in one or more of these groups:</h4>
		<div class="indent-left">

			<?php
			Person_Group::printMultiChooser('include_groupids', array_get($params, 'include_groups', Array()), Array(), TRUE);
			?>

			<input type="checkbox" name="enable_group_join_date" id="enable_group_join_date" value="1"
			<?php if (!empty($params['group_join_date_from'])) echo 'checked="checked"'; ?>
			 
			/>
			<span id="group-join-dates">
			<label for="enable_group_join_date">and joined the group</label> between <?php print_widget('group_join_date_from', Array('type' => 'date'), array_get($params, 'group_join_date_from')); ?>
			and <?php print_widget('group_join_date_to', Array('type' => 'date'), array_get($params, 'group_join_date_to')); ?>
			</span>
			<script>$('#enable_group_join_date').change(function() {$('#group-join-dates input, #group-join-dates select').attr('disabled', !this.checked)}).change();</script>
		</div>


		<h4>who are <strong>not</strong> in any of these groups:</h4>
		<div class="indent-left">
			<?php
			Person_Group::printMultiChooser('exclude_groupids', array_get($params, 'exclude_groups', Array()), Array(), TRUE);
			?>
		</div>

		<h4>who have a person note containing the phrase:</h4>
		<div class="indent-left">
			<input type="text" name="note_phrase" value="<?php echo (isset($params["note_phrase"])?$params["note_phrase"]:""); ?>">
		</div>

		<h3>For each person found, show me...</h3>
		<?php
		$show_fields = array_get($params, 'show_fields', Array());
		?>

		<table class="expandable indent-left">
		<?php
		foreach ($show_fields as $chosen_field) {
			?>
			<tr>
				<td>
					<img src="<?php echo BASE_URL; ?>/resources/expand_up_down_green_small.png" class="icon insert-row-below" style="position: relative; top: 2ex" title="Create a blank entry here" />
				</td>
				<td>
					<?php
					$options = Array(
								''			=> '',
							   );
					foreach ($this->_show_fields as $i => $opt) {
						if (is_null($opt)) {
							$options['--'.$i] = '-----';
						} else {
							$options[$opt] = $this->_field_details[$opt]['label'];
						}
					}
					$options['--A'] = '-----';
					if ($GLOBALS['system']->featureEnabled('DATES')) {
						foreach (Person::getDateTypes() as $typeid => $name) {
							$options['date---'.$typeid] = ucfirst($name).' Date';
						}
					}
					$options['--B'] = '-----';
					if ($GLOBALS['system']->featureEnabled('PHOTOS')) {
						$options['photo'] = 'Photo';
					}
					$options['groups']	= 'Which of the selected groups they are in';
					$options['notes.subjects'] = 'Matching notes';
					$options['all_members'] = 'Names of all their family members';
					$options['adult_members'] = 'Names of their adult family members';
					$options['--C'] = '-----';
					if ($GLOBALS['system']->featureEnabled('PHOTOS')) {
						$options['photo'] = 'Photo';
					}
					$options['view_link'] = 'A link to view their person record';
					$options['edit_link'] = 'A link to edit their person record';
					$options['checkbox'] = 'A checkbox for bulk actions';
					print_widget('show_fields[]', Array('options' => $options, 'type' => 'select', 'disabled-prefix' => '--'), $chosen_field);
					?>
				</td>
				<td>
					<img src="<?php echo BASE_URL; ?>/resources/arrow_up_thin_black.png" class="icon move-row-up" title="Move this item up" />
					<img src="<?php echo BASE_URL; ?>/resources/arrow_down_thin_black.png" class="icon move-row-down" title="Move this item down" />
					<img src="<?php echo BASE_URL; ?>/resources/cross_red.png" class="icon delete-row" title="Delete this item" />
				</td>
			</tr>
			<?php
		}
		?>
		</table>


		<h3>Group the results...</h3>
		<?php
		$gb = array_get($params, 'group_by', '');
		?>
		<div class="indent-left">
			<select name="group_by">
				<option value=""<?php if ($gb == '') echo ' selected="selected"'; ?>>all together</option>
				<option value="groupid"<?php if ($gb == 'groupid') echo ' selected="selected"'; ?>>by group membership</option>
			<?php
			foreach ($this->_query_fields as $i) {
				$v = $this->_field_details[$i];
				if (!in_array($v['type'], Array('select', 'reference'))) continue;
				?>
				<option value="<?php echo $i; ?>"<?php if ($gb == $i) echo ' selected="selected"'; ?>>by <?php echo $v['label']; ?></option>
				<?php
			}
			?>
			</select>
			<p class="smallprint">Note: Result groups that do not contain any persons will not be shown</p>
		</div>

		<h3>Sort the results by...</h3>

		<select name="sort_by" class="indent-left">
		<?php
		$sb = array_get($params, 'sort_by');
		foreach ($this->_show_fields as $name) {
			if (is_null($name)) {
				?>
				<option disabled="disabled">------</option>
				<?php
			} else {
				?>
				<option value="<?php echo $name; ?>"<?php if ($sb == $name) echo ' selected="selected"'; ?>><?php echo htmlentities($this->_field_details[$name]['label']); ?></option>
				<?php
			}
		}
		if ($GLOBALS['system']->featureEnabled('DATES')) {
			?>
			<option disabled="disabled">------</option>
			<?php
			foreach (Person::getDateTypes() as $typeid => $name) {
				?>
				<option value="date---<?php echo $typeid; ?>"<?php if ($sb == 'date---'.$typeid) echo ' selected="selected"'; ?>><?php echo htmlentities($name); ?> date</option>
				<?php
			}
		}

		?>
		</select>

		<?php
		if ($GLOBALS['user_system']->havePerm(PERM_MANAGEREPORTS)) {
			?>
			<h3>I want to save this report...</h3>
			<div class="indent-left">
				<input type="radio" name="save_option" value="new" id="save_option_new" <?php if (empty($this->id)) echo 'checked="checked"'; ?>>
				<label for="save_option_new">
					as a new query called
				</label>
				<input type="text" name="new_query_name" />
				<br />

				<input type="radio" name="save_option" value="replace" id="save_option_replace" <?php if ($this->id && ($this->id != 'TEMP')) echo 'checked="checked"'; ?>>
				<label for="save_option_replace">
					in place of an existing query
				</label>
				<?php print_widget('replace_query_id', Array('type' => 'reference', 'references' => 'person_query'), $this->id); ?>
				<br />

				<input type="radio" name="save_option" value="temp" id="save_option_temp"<?php if (empty($this->id) || $this->id == 'TEMP') echo ' checked="checked"'; ?>>
				<label for="save_option_temp">only temporarily as an ad-hoc report</label>
				<br />
			</div>
			<?php
		}
	}

	function processForm()
	{
		if ($GLOBALS['user_system']->havePerm('PERM_MANAGEREPORTS')) {
			switch ($_POST['save_option']) {
				case 'new':
					$this->populate(0, Array());
					$this->setValue('name', $_POST['new_query_name']);
					break;
				case 'replace':
					$this->load((int)$_POST['replace_query_id']);
					break;
				case 'temp':
					$this->id = 'TEMP';
				break;
			}
		} else {
			$this->id = 'TEMP';
		}

		$params = $this->getValue('params');

		// FIELD RULES
		$rules = Array();
		if (!empty($_POST['enable_rule'])) {
			foreach ($_POST['enable_rule'] as $field) {
				$rules[$field] = $this->_processRuleDetails($field);
			}
		}
		$params['rules'] = $rules;

		// GROUP RULES
		$params['include_groups'] = $this->_removeEmpties($_POST['include_groupids']);
		$params['group_join_date_from'] = empty($_POST['enable_group_join_date']) ? NULL : process_widget('group_join_date_from', Array('type' => 'date'));
		$params['group_join_date_to'] = empty($_POST['enable_group_join_date']) ? NULL : process_widget('group_join_date_to', Array('type' => 'date'));
		$params['exclude_groups'] = $this->_removeEmpties($_POST['exclude_groupids']);

		// NOTE RULES
		$params['note_phrase'] = $_POST['note_phrase'];

		// SHOW FIELDS
		$params['show_fields'] = $this->_removeEmpties($_POST['show_fields']);

		// GROUP BY
		$params['group_by'] = $_POST['group_by'];

		// SORT BY
		$params['sort_by'] = $_POST['sort_by'];
		$this->setValue('params', $params);
	}

	function _processRuleDetails($field)
	{
		$res = Array();
		if ($field == 'date') {
			$res['typeid'] =  (int)$_POST['params_date_typeid'];
			$res['anniversary'] = (bool)$_POST['params_date_anniversary'];
			$res['from'] = process_widget('params_date_from', Array('type' => 'date'));
			$res['to'] = process_widget('params_date_to', Array('type' => 'date'));
		} else {
			switch ($this->_field_details[$field]['type']) {
				case 'datetime':
					$res['from'] = process_widget('params_'.str_replace('.', '_', $field).'_from', Array('type' => 'date'));
					$res['to'] = process_widget('params_'.str_replace('.', '_', $field).'_to', Array('type' => 'date'));
					break;
				case 'select':
				case 'reference':
					$res = $this->_removeEmpties(array_get($_POST, 'params_'.str_replace('.', '_', $field), Array()));
					break;
			}
		}
		return $res;
	}

	function _removeEmpties($ar)
	{
		$res = Array();
		foreach ($ar as $x) {
			if (($x != '')) {
				$res[] = $x;
			}
		}
		return $res;
	}

	function _getGroupAndCategoryRestrictionSQL($submitted_groupids, $from_date=NULL, $to_date=NULL)
	{
		global $db;
		$int_groupids = Array();
		$int_categoryids = Array();

		// sepearate the group IDs from cateogry IDs
		foreach ($submitted_groupids as $groupid) {
			if (substr($groupid, 0, 1) == 'c') {
				$int_categoryids[] = (int)substr($groupid, 1);
			} else {
				$int_groupids[] = (int)$groupid;
			}
		}

		// assemble the SQL clause to restrict group and category IDs
		$groupid_comps = Array();
		if (!empty($int_groupids)) {
			$groupid_comps[] = '(pgm.groupid IN ('.implode(',', $int_groupids).'))';
		}
		if (!empty($int_categoryids)) {
			$groupid_comps[] = '(pg.categoryid IN ('.implode(',', $int_categoryids).') AND pg.is_archived = 0)';
		}

		$res = implode(' OR ', $groupid_comps);


		if (!empty($from_date)) {
			// restrict the join date too
			$res = '('.$res.') AND pgm.created BETWEEN '.$db->quote($from_date).' AND '.$db->quote($to_date);
		}

		return $res;
	}


	function getSQL($select_fields=NULL)
	{
		$db =& $GLOBALS['db'];
		$params = $this->getValue('params');
		if (empty($params)) return null;
		$query = Array();
		$query['from'] = 'person p 
						JOIN family f ON p.familyid = f.id
						';
		$query['order_by'] = $params['sort_by'];
		$query['where'] = Array();

		// BASIC FILTERS
		foreach ($params['rules'] as $field => $values) {
			if ($field == 'date') {
				$query['from'] .= ' JOIN person_date pd ON pd.personid = p.id AND pd.typeid = '.(int)$values['typeid']."\n";
				$between = 'BETWEEN '.$db->quote($values['from']).' AND '.$db->quote($values['to']);
				$w = Array();
				$w[] = '(pd.`date` NOT LIKE "-%" 
						AND pd.`date` '.$between.')';
				if ($values['anniversary']) {
					// Anniversary matches either have no year or a year before the 'to' year
					// AND their month-day fits the range either in the from year or the to year.
					$fromyearbetween = 'CONCAT('.$db->quote(substr($values['from'], 0, 4)).', RIGHT(pd.`date`, 6)) '.$between;
					$toyearbetween = 'CONCAT('.$db->quote(substr($values['to'], 0, 4)).', RIGHT(pd.`date`, 6)) '.$between;
					$w[] = '(pd.`date` LIKE "-%" AND '.$fromyearbetween.')';
					$w[] = '(pd.`date` LIKE "-%" AND '.$toyearbetween.')';
					$w[] = '(pd.`date` NOT LIKE "-%" AND pd.`date` < '.$db->quote($values['to']).' AND '.$fromyearbetween.')';
					$w[] = '(pd.`date` NOT LIKE "-%" AND pd.`date` < '.$db->quote($values['to']).' AND '.$toyearbetween.')';
				}
				$query['where'][] = '('.implode(' OR ', $w).')';

			} else if (isset($values['from'])) {
				if (($this->_field_details[$field]['type'] == 'datetime') && (strlen($values['from']) == 10)) {
					// we're searching on a datetime field using only date values
					// so extend them to prevent boundary errors
					$values['from'] .= ' 00:00';
					$values['to'] .= ' 23:59';
				}
				$query['where'][] = $field.' BETWEEN '.$db->quote($values['from']).' AND '.$db->quote($values['to']);
			} else {
				switch (count($values)) {
					case 0:
						$query['where'][] = $field.' = 0';
					case 1:
						$query['where'][] = $field.' = '.$db->quote(reset($values));
						break;
					default:
						$quoted_vals = Array();
						foreach ($values as $val) {
							$quoted_vals[] = $db->quote($val);
						}
						$query['where'][] = $field.' IN ('.implode(', ', $quoted_vals).')';
				}
			}
		}

		// GROUP MEMBERSHIP FILTERS
		if (!empty($params['include_groups'])) {

			$include_groupids_clause = $this->_getGroupAndCategoryRestrictionSQL($params['include_groups'], $params['group_join_date_from'], $params['group_join_date_to']);
			$group_members_sql = 'SELECT personid 
								FROM person_group_membership pgm 
								JOIN person_group pg ON pgm.groupid = pg.id
								WHERE ('.$include_groupids_clause.')';
			$query['where'][] = 'p.id IN ('.$group_members_sql.')';
		}
		if (!empty($params['exclude_groups'])) {

			$exclude_groupids_clause = $this->_getGroupAndCategoryRestrictionSQL($params['exclude_groups']);
			$query['where'][] = 'p.id NOT IN (
									SELECT personid 
									FROM person_group_membership pgm
									JOIN person_group pg ON pgm.groupid = pg.id
									WHERE ('.$exclude_groupids_clause.')
								)';
		}

		//NOTE FILTERS
		if (!empty($params['note_phrase'])) {
			$note_sql = 'SELECT pn.personid, GROUP_CONCAT(an.Subject) as subjects
						FROM person_note pn
						JOIN abstract_note an ON an.id = pn.id
						WHERE an.details LIKE '.$GLOBALS['db']->quote('%'.$params['note_phrase'].'%').'
						OR an.subject LIKE '.$GLOBALS['db']->quote('%'.$params['note_phrase'].'%').'
						GROUP BY pn.personid';
			$query['from'] .= ' JOIN ('.$note_sql.') notes ON notes.personid = p.id ';
		}

		// GROUPING
		if (empty($params['group_by'])) {
			$grouping_field = '';
		} else if ($params['group_by'] == 'groupid') {
			if (!empty($params['include_groups'])) {
				$grouping_field = 'CONCAT(pg.name, '.$db->quote(' (#').', pg.id, '.$db->quote(')').'), ';
				$query['from'] .= ' JOIN person_group_membership pgm ON p.id = pgm.personid
									JOIN person_group pg ON pg.id = pgm.groupid
									';
				$query['where'][] = $this->_getGroupAndCategoryRestrictionSQL($params['include_groups'], $params['group_join_date_from'], $params['group_join_date_to']);
			} else {
				$grouping_field = '';
			}
			$query['order_by'] = 'pg.name, '.$query['order_by'];
		} else {
			$grouping_field = $params['group_by'].', ';
			if (FALSE !== ($key = array_search($params['group_by'], $params['show_fields']))) {
				unset($params['show_fields'][$key]);
			}
			$query['order_by'] = $grouping_field.$query['order_by'];
		}

		// DISPLAY FIELDS
		if (empty($select_fields)) {
			foreach ($params['show_fields'] as $field) {
				switch ($field) {
					case 'groups':
						if (($params['group_by'] != 'groupid') && !empty($params['include_groups'])) {
							$query['select'][] = 'GROUP_CONCAT(pg.name ORDER BY pg.name SEPARATOR '.$db->quote('<br />').') as person_groups';
							$query['from'] .= ' LEFT JOIN person_group_membership pgm ON p.id = pgm.personid
												JOIN person_group pg ON pg.id = pgm.groupid
												';
							$query['where'][] = $this->_getGroupAndCategoryRestrictionSQL($params['include_groups'], $params['group_join_date_from'], $params['group_join_date_to']);
						}
						break;
					case 'view_link':
					case 'edit_link':
					case 'checkbox':
					case 'photo':
						$query['select'][] = 'p.id as '.$field;
						break;
					case 'all_members':
						$query['from'] .= 'JOIN (
											SELECT familyid, IF (
												GROUP_CONCAT(DISTINCT last_name) = ff.family_name, 
												GROUP_CONCAT(first_name ORDER BY age_bracket, gender DESC SEPARATOR ", "),
												GROUP_CONCAT(CONCAT(first_name, " ", last_name) ORDER BY age_bracket, gender DESC SEPARATOR ", ")
											  ) AS `names`
											FROM person pp
											JOIN family ff ON pp.familyid = ff.id
											WHERE pp.status <> "archived"
											GROUP BY familyid
										   ) all_members ON all_members.familyid = p.familyid
										   ';
						$query['select'][] = 'all_members.names as `All Family Members`';
						break;
					case 'adult_members':
						// For a left join to be efficient we need to 
						// create a temp table with an index rather than
						// just joining a subquery.
						$r1 = $GLOBALS['db']->query('CREATE TEMPORARY TABLE _family_adults'.$this->id.' (
													familyid int(10) not null primary key,
													names varchar(512) not null
													)');
						check_db_result($r1);
						$r2 = $GLOBALS['db']->query('INSERT INTO _family_adults'.$this->id.' (familyid, names)
											SELECT familyid, IF (
												GROUP_CONCAT(DISTINCT last_name) = ff.family_name, 
												GROUP_CONCAT(first_name ORDER BY age_bracket, gender DESC SEPARATOR ", "),
												GROUP_CONCAT(CONCAT(first_name, " ", last_name) ORDER BY age_bracket, gender DESC SEPARATOR ", ")
											  )
											FROM person pp
											JOIN family ff ON pp.familyid = ff.id
											WHERE pp.status <> "archived" AND pp.age_bracket = 0
											GROUP BY familyid');
						check_db_result($r2);
						$query['from'] .= 'LEFT JOIN _family_adults'.$this->id.' ON _family_adults'.$this->id.'.familyid = p.familyid
											';
						$query['select'][] = '_family_adults'.$this->id.'.names as `Adult Family Members`';
						break;
					case 'notes.subjects':
						if (empty($params['note_phrase'])) {
							$query['select'][] = '"" AS subjects';
							break;
						}
						// else deliberate fallthrough...
					default:
						if (substr($field, 0, 7) == 'date---') {
							$types = Person::getDateTypes();
							$dateid = substr($field, 7);
							if (isset($types[$dateid])) {
								$query['from'] .= 'LEFT JOIN person_date pd'.$dateid.' ON pd'.$dateid.'.personid = p.id AND pd'.$dateid.'.typeid = '.$db->quote($dateid)."\n";
								$query['select'][] = 'pd'.$dateid.'.`date` as '.$db->quote('DATE---'.$types[$dateid])."\n";
							}
						} else {
							$query['select'][] = $field.' as '.$db->quote($field);
						}
				}
			}
			$select_fields = $grouping_field.'p.id as ID, '.implode(', ', $query['select']);
		}

		// Order by
		if (substr($query['order_by'], 0, 7) == 'date---') {
			$query['from'] .= 'LEFT JOIN person_date pdorder ON pdorder.personid = p.id AND pdorder.typeid = '.$db->quote(substr($query['order_by'], 7))."\n";
			// we want persons with a full date first, in chronological order.  Then persons with a yearless date, in order.  Then persons with no date.
			$query['order_by'] = 'IF (pdorder.`date` IS NULL, 3, IF (pdorder.`date` LIKE "-%", 2, 1)), pdorder.`date`';
		}

		// Build SQL
		$sql = 'SELECT '.$select_fields.'
				FROM '.$query['from'].'
				';
		if (!empty($query['where'])) {
			$sql .= 'WHERE
					('.implode(")\n\tAND (", $query['where']).')
				';
		}
		$sql .= 'GROUP BY p.id ';
		$sql .= 'ORDER BY '.$query['order_by'].', p.last_name, p.first_name';

		return $sql;
	}


	function getResultCount()
	{
		$db =& $GLOBALS['db'];
		$sql = $this->getSQL();
		if (is_null($sql)) return 0;
		$res = $db->query($sql);
		check_db_result($res);
		return $res->numRows();
	}


	function getResultPersonIDs()
	{
		$db =& $GLOBALS['db'];
		$sql = $this->getSQL('p.id');
		if (is_null($sql)) return Array();
		$res = $db->queryCol($sql);
		check_db_result($res);
		return $res;
	}	


	function printResults($format='html')
	{
		$db =& $GLOBALS['db'];
		$params = $this->getValue('params');

		$sql = $this->getSQL();
		if (is_null($sql)) return;

		if ($format == 'html' && in_array('checkbox', $params['show_fields'])) {
			echo '<form method="post" class="bulk-person-action">';
		}
		
		$grouping_field = $params['group_by'];
		if (empty($grouping_field)) {
			$res = $db->queryAll($sql, null, null, true, true);
			check_db_result($res);
			$this->_printResultSet($res, $format);
		} else {
			$res = $db->queryAll($sql, null, null, true, false, true);
			check_db_result($res);
			$this->_printResultGroups($res, $params, $format);
		}

		if ($res && ($format == 'html') && in_array('checkbox', $params['show_fields'])) {
			echo '<div class="no-print">';
			include 'templates/bulk_actions.template.php';
			echo '</div>';
			echo '</form>';
		}
	}

	function _printResultGroups($res, $params, $format)
	{
		foreach ($res as $i => $v) {
			if ($params['group_by'] != 'groupid') {
					$var = $params['group_by'][0] == 'p' ? '_dummy_person' : '_dummy_family';
					$fieldname = substr($params['group_by'], 2);
					$this->$var->setValue($fieldname, $i);
					$heading = $this->$var->getFormattedValue($fieldname);
			} else {
					$heading = $i;
			}
			$this->_printResultSet($v, $format, $heading);
		}
	}


	function _printResultSet($x, $format, $heading=NULL)
	{
		if ($format == 'csv') {
			$this->_printResultSetCsv($x, $heading);
		} else {
			$this->_printResultSetHtml($x, $heading);
		}
	}

	function _printResultSetCsv($x, $groupingname)
	{
		if (empty($x)) return;
		static $headerprinted = false;
		if (!$headerprinted) {
			foreach (array_keys(reset($x)) as $heading) {
				if (in_array($heading, Array('view_link', 'edit_link', 'checkbox'))) continue;
				echo '"';
				switch($heading) {
					case 'person_groups':
						echo 'Groups';
						break;
					case 'notes.subjects':
						echo 'Notes';
						break;
					default:
						if (isset($this->_field_details[$heading])) {
							echo $this->_field_details[$heading]['label'];
						} else if (substr($heading, 0, 7) == 'DATE---') {
							echo ucfirst(substr($heading, 7));
						} else {
							echo ucfirst($heading);
						}
				}
				echo '",';
			}
			if ($groupingname) echo 'GROUPING';
			echo "\r\n";
			$headerprinted = TRUE;
		}
		foreach ($x as $row) {
			foreach ($row as $label => $val) {
				if (in_array($label, Array('view_link', 'edit_link', 'checkbox'))) continue;
				echo '"';
				if (isset($this->_field_details[$label])) {
					$var = $label[0] == 'p' ? '_dummy_person' : '_dummy_family';
					$fieldname = substr($label, 2);
					echo str_replace('"', '""', $this->$var->getFormattedValue($fieldname, $val));
				} else if (substr($label, 0, 7) == 'DATE---') {
					echo $val ? format_date($val) : '';
				} else {
					echo str_replace('"', '""', $val);
				}
				echo '",';
			}
			if ($groupingname) echo str_replace('"', '""', $groupingname);
			echo "\r\n";
		}
	}

	function _printResultSetHtml($x, $heading)
	{
		if ($heading) {
			echo '<h3>'.$heading.'</h3>';
		}
		if (empty($x)) {
			?>
			<i>No matching persons were found</i>
			<?php
			return;
		}
		?>
		<table class="standard">
			<thead>
				<tr>
				<?php
				foreach (array_keys(reset($x)) as $heading) {
					?>
					<th<?php echo $this->_getColClasses($heading); ?>>
						<?php
						switch($heading) {
							case 'person_groups':
								echo 'Groups';
								break;
							case 'notes.subjects':
								echo 'Notes';
								break;
							case 'edit_link':
							case 'view_link':
								break;
							case 'checkbox':
								echo '<input type="checkbox" class="select-all" title="Select all" />';
								break;
							default:
								if (isset($this->_field_details[$heading])) {
									echo $this->_field_details[$heading]['label'];
								} else if (substr($heading, 0, 7) == 'DATE---') {
									echo ucfirst(substr($heading, 7));
								} else {
									echo ucfirst($heading);
								}
						}
						?>
					</th>
					<?php
				}
				?>
				</tr>
			</thead>
			<tbody>
			<?php
			foreach ($x as $row) {
				?>
				<tr>
				<?php
				foreach ($row as $label => $val) {
					?>
					<td<?php echo $this->_getColClasses($label); ?>>
						<?php
						switch ($label) {
							case 'edit_link':
								?>
								<a class="med-popup no-print" href="?view=_edit_person&personid=<?php echo $row[$label]; ?>">Edit</a>
								<?php
								break;
							case 'view_link':
								?>
								<a class="med-popup no-print" href="?view=persons&personid=<?php echo $row[$label]; ?>">View</a>
								<?php
								break;
							case 'checkbox':
								?>
								<input name="personid[]" type="checkbox" value="<?php echo $row[$label]; ?>" class="no-print" />
								<?php
								break;
							case 'photo':
								?>
								<a class="med-popup" href="?view=persons&personid=<?php echo $row[$label]; ?>">
								<img height="60" src="?call=person_photo&personid=<?php echo $row[$label]; ?>" />
								</a>
								<?php
								break;
							default:
								if (isset($this->_field_details[$label])) {
									$var = $label[0] == 'p' ? '_dummy_person' : '_dummy_family';
									$fieldname = substr($label, 2);
									$this->$var->setValue($fieldname, $val);
									$this->$var->printFieldValue($fieldname);
								} else if (substr($label, 0, 7) == 'DATE---') {
									echo $val ? format_date($val) : '';
								} else {
									echo $val;
								}
						}
						?>
					</td>
					<?php
				}
				?>
				</tr>
				<?php
			}
			?>
			</tbody>
		</table>
		<p><strong><?php echo count($x); ?> persons listed</strong></p>
		<?php
	}


	function validateFields()
	{
		if (!parent::validateFields()) return FALSE;

		return TRUE;
	}


	function save()
	{
		if ($this->id == 'TEMP') {
			$_SESSION['saved_query'] = serialize($this);
			return TRUE;
		} else {
			return parent::save();
		}
	}

	function load($id)
	{
		if ($id == 'TEMP') {
			if (!empty($_SESSION['saved_query'])) {
				$x = unserialize($_SESSION['saved_query']);
				$this->populate($x->id, $x->values);
			}
			return TRUE;
		} else {
			return parent::load($id);
		}
	}

	function _getColClasses($heading)
	{
		$class_list = '';
		if (in_array($heading, Array('edit_link', 'view_link', 'checkbox'))) {
			$class_list[] = 'no-print';
		}
		if ($heading == 'checkbox') {
			$class_list[] = 'selector';
		}
		$classes = empty($class_list) ? '' : ' class="'.implode(' ', $class_list).'"';
		return $classes;
	}


}
?>
