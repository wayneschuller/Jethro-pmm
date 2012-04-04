<?php
include_once 'include/db_object.class.php';
class Person_Group extends db_object
{
	var $_save_permission_level = PERM_EDITGROUP;

	function _getFields()
	{
		return Array(
			'name'		=> Array(
									'type'		=> 'text',
									'width'		=> 40,
									'maxlength'	=> 128,
									'allow_empty'	=> FALSE,
									'initial_cap'	=> TRUE,
								   ),
			'categoryid'	=> Array(
									'type'	=> 'reference',
									'references' => 'person_group_category',
									'label' => 'Category',
									'allow_empty' => TRUE,
									'order_by' => 'name',
								),
			'is_archived'	=> Array(
									'type' => 'select',
									'options'	=> Array('Current', 'Archived'),
									'label' => 'Status',
									'default'	=> 0,
								),
			'can_record_attendance' => Array(
									'type'		=> 'select',
									'options'	=> Array('No', 'Yes'),
									),
		);
	}

	function getInitSQL()
	{
		// Need to create the group-membership table as well as the group table
		return Array(
				parent::getInitSQL('_person_group'),
				"CREATE TABLE `person_group_membership` (
				  `personid` int(11) NOT NULL default '0',
				  `groupid` int(11) NOT NULL default '0',
				  `created` timestamp NOT NULL default CURRENT_TIMESTAMP,
				  PRIMARY KEY  (`personid`,`groupid`),
				  INDEX personid (personid),
				  INDEX groupid (groupid)
				) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;"
		);
	}


	function toString()
	{
		return $this->values['name'];
	}

	function getMembers()
	{
		$db =& $GLOBALS['db'];
		$sql = 'SELECT p.*, c.name as congregation, gm.created as joined_group
				FROM person_group_membership gm 
				JOIN person p ON gm.personid = p.id
				LEFT JOIN congregation c ON c.id = p.congregationid
				WHERE gm.groupid = '.$db->quote((int)$this->id).'
				ORDER BY p.last_name, p.first_name';
		$res = $db->queryAll($sql, null, null, true);
		check_db_result($res);
		foreach ($res as $k => &$v) {
			$v['joined_group'] = format_date($v['joined_group']);
		}
		return $res;
	}

	function addMember($personid)
	{
		$new_member =& $GLOBALS['system']->getDBObject('person', $personid);
		if ($new_member->id) {
			$db =& $GLOBALS['db'];
			$sql = 'INSERT IGNORE INTO person_group_membership (groupid, personid)
					VALUES ('.$db->quote((int)$this->id).', '.$db->quote((int)$personid).')';
			$res = $db->query($sql);
			check_db_result($res);
			return TRUE;
		}
		return FALSE;
	}

	function removeMember($personid)
	{
		$new_member =& $GLOBALS['system']->getDBObject('person', $personid);
		if ($new_member->id) {
			$db =& $GLOBALS['db'];
			$sql = 'DELETE FROM person_group_membership
					WHERE groupid = '.$db->quote((int)$this->id).'
						AND personid = '.$db->quote((int)$personid);
			$res = $db->query($sql);
			check_db_result($res);
			return TRUE;
		}
		return FALSE;
	}


	static function getGroups($personid)
	{
		$db =& $GLOBALS['db'];
		$sql = 'SELECT g.id, g.name, gm.created, g.is_archived, g.categoryid
				FROM person_group_membership gm JOIN person_group g ON gm.groupid = g.id
				WHERE gm.personid = '.$db->quote((int)$personid).'
				ORDER BY g.name';
		$res = $db->queryAll($sql, null, null, true);
		check_db_result($res);
		return $res;
	}

	function printSummary() 
	{
		?>
		<table class="standard">
			<tr>
				<th>Group Name</th>
				<td><?php echo $this->getValue('name'); ?></td>
			</tr>
			<tr>
				<th>Members</th>
				<td>
					<ul>
					<?php
					foreach ($this->getMembers() as $id => $details) {
						?>
						<li><a href="?view=persons&personid=<?php echo $id; ?>"><?php echo $details['first_name'].' '.$details['last_name']; ?></a></li>
						<?php
					}
					?>
					</ul>
				</td>
			</tr>
		</table>
		<?php
	}

		
	function getInstancesQueryComps($params, $logic, $order)
	{
		$res = parent::getInstancesQueryComps($params, $logic, $order);
		$res['from'] = '('.$res['from'].') LEFT OUTER JOIN person_group_membership gm ON gm.groupid = person_group.id';
		$res['select'][] = 'COUNT(gm.personid) as member_count';
		$res['group_by'] = 'person_group.id';
		return $res;

	}

	function delete()
	{
		parent::delete();
		$db =& $GLOBALS['db'];
		$sql = 'DELETE FROM person_group_membership WHERE groupid = '.$db->quote($this->id);
		$res = $db->query($sql);
		check_db_result($res);
	}

	// DISUSED
	function printMailtoLinks()
	{
		$db =& $GLOBALS['db'];
		$sql = 'SELECT p.email
				FROM person p JOIN person_group_membership pgm ON p.id = pgm.personid
				WHERE pgm.groupid = '.$db->quote($this->id).'
					AND p.status <> "archived"
				AND p.email <> '.$db->quote('');
		$emails = $db->queryCol($sql);
		check_db_result($emails);

		$sql = 'SELECT count(p.id)
				FROM person p JOIN person_group_membership pgm ON p.id = pgm.personid
				WHERE pgm.groupid = '.$db->quote($this->id).'
				AND p.email = '.$db->quote('');
		$num_blank_emails = $db->queryOne($sql);
		check_db_result($num_blank_emails);

		$my_email = $GLOBALS['user_system']->getCurrentUser('email');
		if (empty($emails)) return;
		if (count($emails) > EMAIL_CHUNK_SIZE) {
			?>
			Email this group <abbr title="This method of emailing prevents all recipients from seeing each others' email addresses">using BCC</abbr>: <span class="smallprint">(Recommended)</span>
			<?php
			foreach (array_chunk($emails, EMAIL_CHUNK_SIZE) as $i => $chunk) {
				$their_emails = implode(',', $chunk);
				?>
				<br />&nbsp; &bull; <a href="mailto:<?php echo $my_email; ?>?bcc=<?php echo $their_emails; ?>">Batch <?php echo ($i+1); ?></a>
				<?php
			}
			?>
			<br />Email this group <abbr title="Emails sent this way will display all the email addresses to every recipient - use with care">publicly</abbr>:
			<?php
			foreach (array_chunk($emails, EMAIL_CHUNK_SIZE) as $i => $chunk) {
				$their_emails = implode(',', $chunk);
				?>
				<br />&nbsp; &bull; <a href="mailto:<?php echo $their_emails; ?>">Batch <?php echo ($i+1); ?></a>
				<?php
			}
		} else {
			$their_emails = implode(',', $emails);
			?>
			<a href="mailto:<?php echo $my_email; ?>?bcc=<?php echo $their_emails; ?>" title="Emailing using BCC is recommended because it doesn't expose all email addresses to all recipients">Email this group using BCC</a> <span class="smallprint">(recommended)</span><br />
			<a href="mailto:<?php echo $their_emails; ?>" onclick="return confirm('Emailing a group publicly will let all the recipients see each other\'s email addresses - this should be used with care.  Are you sure you want to continue?')">Email this group publicly</a>
			<?php
		}
		if ($num_blank_emails) echo '<p class="smallprint">(NB '.$num_blank_emails.' members have no email address)</p>';
		echo '<p class="smallprint">(Any archived group members will not be emailed)</p>';
	}

	function printFieldValue($fieldname, $value=NULL)
	{
		if (is_null($value)) $value = $this->values[$fieldname];
		switch ($fieldname) {
			case 'categoryid':
				if ($value == 0) {
					echo '<i>(Uncategorised)</i>';
					return;
				}
				// deliberate fall through
			default:
				return parent::printFieldValue($fieldname, $value);
		}
	}

	function printFieldInterface($fieldname, $prefix='')
	{
		if ($fieldname == 'categoryid') {
			$GLOBALS['system']->includeDBClass('person_group_category');
			Person_Group_Category::printChooser($prefix.$fieldname, $this->getValue('categoryid'));
		} else {
			return parent::printFieldInterface($fieldname, $prefix);
		}
	}

	static function printMultiChooser($name, $value, $exclude_groups=Array(), $allow_category_select=FALSE)
	{
		?>
		<table class="expandable">
		<?php
		foreach ($value as $id) {
			?>
			<tr>
				<td>
					<?php Person_Group::printChooser($name.'[]', $id, $exclude_groups, $allow_category_select); ?>
				</td>
			</tr>
			<?php
		}
		?>
			<tr>
				<td>
					<?php Person_Group::printChooser($name.'[]', 0, $exclude_groups, $allow_category_select); ?>
				</td>
			</tr>
		</table>
		<?php
	}


	static function printChooser($fieldname, $value, $exclude_groups=Array(), $allow_category_select=
	FALSE, $empty_text='(Choose)')
	{
		$cats = $GLOBALS['system']->getDBObjectData('person_group_category', Array(), 'OR', 'name');
		$groups = $GLOBALS['system']->getDBObjectData('person_group', Array('is_archived' => 0), 'OR', 'name');
		if (empty($groups)) {
			?><i>There are no groups in the system yet</i><?php
			return FALSE;
		}
		?>
		<select name="<?php echo $fieldname; ?>">
			<option value=""><?php echo htmlentities($empty_text); ?></option>
			<?php
			self::_printChooserOptions($cats, $groups, $value, $allow_category_select);
			if ($allow_category_select) {
				$sel = ($value === 'c0') ? ' selected="selected"' : '';
				?>
				<option value="c0" class="strong"<?php echo $sel; ?>>Uncategorised Groups (ALL)</option>
				<?php 
				self::_printChooserGroupOptions($groups, 0, $value);
			} else {
				?>
				<optgroup label="Uncategorised Groups">
				<?php self::_printChooserGroupOptions($groups, 0, $value); ?>
				</optgroup>
				<?php
			}
			?>
		</select>
		<?php
		
		return TRUE;
	}

	function _printChooserOptions($cats, $groups, $value, $allow_category_select=FALSE, $parentcatid=0, $prefix='')
	{
		foreach ($cats as $cid => $cat) {
			if ($cat['parent_category'] != $parentcatid) continue;
			if ($allow_category_select) {
				$sel = ($value === 'c'.$cid) ? ' selected="selected"' : '';
				?>
				<option value="c<?php echo $cid; ?>" class="strong"<?php echo $sel; ?>><?php echo $prefix.htmlentities($cat['name']); ?> (ALL)</option>
				<?php
				self::_printChooserGroupOptions($groups, $cid, $value, $prefix.'&nbsp;&nbsp;&nbsp;');
				self::_printChooserOptions($cats, $groups, $value, $allow_category_select, $cid, $prefix.'&nbsp;&nbsp;');
			} else {
				?>
				<optgroup label="<?php echo $prefix.htmlentities($cat['name']); ?>">
				<?php
				self::_printChooserGroupOptions($groups, $cid, $value);
				self::_printChooserOptions($cats, $groups, $value, $allow_category_select, $cid, $prefix.'&nbsp;&nbsp;');
				?>
				</optgroup>
				<?php
			}
		}
	}

	function _printChooserGroupOptions($groups, $catid, $value, $prefix='')
	{
		foreach ($groups as $gid => $group) {
			if ($group['categoryid'] != $catid) continue;
			$sel = ($gid == $value) ? ' selected="selected"' : '';
			?>
			<option value="<?php echo (int)$gid; ?>"<?php echo $sel; ?>><?php echo $prefix.htmlentities($group['name']); ?></option>
			<?php
		}
	}





}
