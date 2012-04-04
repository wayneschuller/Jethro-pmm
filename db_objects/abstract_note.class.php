<?php
include_once 'include/db_object.class.php';
class Abstract_Note extends DB_Object
{
	var $_load_permission_level = PERM_VIEWNOTE;
	var $_save_permission_level = PERM_EDITNOTE;

	function _getFields()
	{
		$fields = Array(
			'subject'		=> Array(
								'type'		=> 'text',
								'width'		=> 40,
								'maxlength'	=> 256,
								'initial_cap'	=> true,
								'allow_empty'	=> false,
							   ),
			'details'		=> Array(
								'type'		=> 'text',
								'width'		=> 50,
								'height'	=> 5,
								'initial_cap'	=> true,
							   ),
			'status'		=> Array(
								'type'		=> 'select',
								'options'	=> Array(
												'no_action'	=> 'No Action Required',
												'pending'	=> 'Requires Action',
												'failed'	=> 'Failed',
												'complete'	=> 'Complete',
											   ),
								'default'	=> 'no_action',
								'class'		=> 'note-status',
								'allow_empty'	=> false,
								'label'		=> 'Status&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', // cheat to help spacing when hiding/showing other fields
							   ),
			'status_last_changed' => Array(
									'type'				=> 'datetime',
									'show_in_summary'	=> false,
									'allow_empty'		=> TRUE,
									'editable'			=> false,
									'default'			=> NULL,	
								   ),
			'assignee'		=> Array(
								'type'			=> 'reference',
								'references'	=> 'staff_member',
								'default'		=> $GLOBALS['user_system']->getCurrentUser('id'),
								'note'			=> 'Choose the user responsible for acting on this note',
								'allow_empty'	=> true,
								'filter'		=> create_function('$x', 'return $x->getValue("active") && (($x->getValue("permissions") & PERM_EDITNOTE) == PERM_EDITNOTE);'),
							   ),
			'assignee_last_changed' => Array(
									'type'				=> 'datetime',
									'show_in_summary'	=> false,
									'allow_empty'		=> TRUE,
									'editable'			=> false,
									'default'			=> NULL,	
								   ),
			'action_date'	=> Array(
								'type'			=> 'date',
								'note'			=> 'This note will appear in the assignee\'s "to-do" list from this date onwards',
								'allow_empty'	=> false,
								'default'		=> date('Y-m-d'),
							   ),
			'creator'		=> Array(
								'type'			=> 'int',
								'editable'		=> false,
								'references'	=> 'person',
							   ),
			'created'		=> Array(
								'type'			=> 'timestamp',
								'readonly'		=> true,
							   ),
			'history'		=> Array(
								'type'			=> 'serialise',
								'editable'		=> false,
								'show_in_summary'	=> false,
							   ),
		);
		return $fields;
	}


	function toString()
	{
		$creator =& $GLOBALS['system']->getDBObject('person', $this->values['creator']);
		return $this->values['subject'].' ('.$creator->toString().', '.format_date( strtotime($this->values['created'])).')';
	}

	function printFieldInterface($name, $prefix='')
	{
		if ($this->id && in_array($name, Array('subject', 'details'))) {
			if ($GLOBALS['user_system']->getCurrentUser('id') != $this->values['creator']) {
				$this->printFieldValue($name, $prefix);
				return;
			}
		}
		if ($name == 'status') echo '<div class="note-status">';
		parent::printFieldInterface($name, $prefix);
		if ($name == 'status') echo '</div>';
		if ($name == 'action_date') {
			?>
			<span class="nowrap smallprint">
			<button style="font-size: 90%" type="button" onclick="setDateField('<?php echo $prefix; ?>action_date', '<?php echo date('Y-m-d', strtotime('+1 week')); ?>')">1 week from now</button>
			<button style="font-size: 90%" type="button" onclick="setDateField('<?php echo $prefix; ?>action_date', '<?php echo date('Y-m-d', strtotime('+1 month')); ?>')">1 month from now</button>
			</span >
			<?php
		}
	}

	function printFieldValue($name, $value=NULL)
	{
		if (is_null($value)) $value = $this->values[$name];
		if (in_array($name, Array('assignee', 'action_date'))) {
			if ($value == 'no_action') {
				echo 'N/A';
				return;
			}
		}
		if ($name == 'subject') {
			echo '<strong>'.$value.'</strong>';
			return;
		}
		return parent::printFieldValue($name, $value);
	}

	function getInstancesQueryComps($params, $logic, $order)
	{
		$res = parent::getInstancesQueryComps($params, $logic, $order);
		$res['from'] = '('.$res['from'].') LEFT OUTER JOIN person creator ON abstract_note.creator = creator.id';
		$res['from'] = '('.$res['from'].') LEFT OUTER JOIN person assignee ON abstract_note.assignee = assignee.id';
		$res['select'][] = 'creator.first_name as creator_fn';
		$res['select'][] = 'creator.last_name as creator_ln';
		$res['select'][] = 'assignee.first_name as assignee_fn';
		$res['select'][] = 'assignee.last_name as assignee_ln';
		return $res;

	}



	function getInstancesData($params, $logic='OR', $order)
	{
		$res = parent::getInstancesData($params, $logic, $order);

		// Get the comments to go with them
		if (!empty($res)) {
			$sql = 'SELECT c.noteid, c.*, p.first_name as creator_fn, p.last_name as creator_ln 
					FROM note_comment c JOIN person p on c.creator = p.id
					WHERE noteid IN ('.implode(', ', array_keys($res)).')
					ORDER BY noteid, created';
			$db =& $GLOBALS['db'];
			$comments = $db->queryAll($sql, null, null, true, false, true);
			check_db_result($comments);
			foreach ($res as $i => $v) {
				$res[$i]['comments'] = array_get($comments, $i, Array());
			}
		}

		return $res;

	}

	function printStatusSummary()
	{
		if ($this->values['status'] == 'pending') {
			if ($this->values['action_date'] <= date('Y-m-d')) {
				echo '<strong>';
				$this->printFieldValue('status');
				echo '</strong>';
			} else {
				echo 'Scheduled for action on '.str_replace(' ', '&nbsp;', format_date($this->values['action_date']));
			}
		} else {
			$this->printFieldValue('status');
		}
	}

	function delete()
	{
		parent::delete();
		$db =& $GLOBALS['db'];
		$sql = 'DELETE FROM note_comment WHERE noteid = '.$db->quote($this->id);
		$res = $db->query($sql);
		check_db_result($res);
	}


	function printUpdateForm()
	{
		?>
		<form method="post" id="update-note">
		<input type="hidden" name="update_note_submitted" value="1" />
		<table>
			<tr>
				<th>Comment</th>
				<td>
					<?php
					$GLOBALS['system']->includeDBClass('note_comment');
					$comment = new Note_Comment();
					$comment->printFieldInterface('contents');
					?>
				</td>
			</tr>
			<tr>
				<th>Status</th>
				<td><?php $this->printFieldInterface('status'); ?></td>
				</td>
			</tr>
			<tr>
				<th>Assignee</th>
				<td><?php $this->printFieldInterface('assignee'); ?></td>
			</tr>
			<tr>
				<th>Action Date</th>
				<td><?php echo $this->printFieldInterface('action_date'); ?>
				<div class="field-note"><?php echo htmlentities($this->fields['action_date']['note']); ?></div></td>
			</tr>
			<tr>
				<th></th>
				<td><input type="Submit" value="Submit" />
			</tr>
		</table>
		</form>
		<script type="text/javascript">
			setTimeout('showLockExpiryWarning()', <?php echo (strtotime('+'.LOCK_LENGTH, 0)-60)*1000; ?>);
			setTimeout('showLockExpiredWarning()', <?php echo (strtotime('+'.LOCK_LENGTH, 0))*1000; ?>);
			/*$(window).load(function() { setTimeout("$('[name=contents]').focus()", 100); });*/
		</script>
		<?php
	}




}
?>