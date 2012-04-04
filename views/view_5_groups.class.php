<?php
class View_Groups extends View
{
	var $_group = NULL;

	function getTitle()
	{
		if ($this->_group) {
			return 'Viewing Group: '.$this->_group->getValue('name');
		}
	}

	function processView()
	{
		if (!empty($_REQUEST['groupid'])) {
			$this->_group =& $GLOBALS['system']->getDBObject('person_group', (int)$_REQUEST['groupid']);
		}
		if (!empty($_REQUEST['person_groupid'])) {
			$this->_group =& $GLOBALS['system']->getDBObject('person_group', (int)$_REQUEST['person_groupid']);
		}
	}

	function printView()
	{
		if ($this->_group) {
			?>
			<div class="action-box">

				<div class="standard">
					<h3>Add Members by Name Search</h3>
					<form method="post" action="?view=_edit_group&action=add_member&groupid=<?php echo $this->_group->id; ?>">
						<?php
						$GLOBALS['system']->includeDBClass('person');
						Person::printMultipleFinder('personid');
						?>
						<input type="submit" value="Add Members" id="add-member-button" />
					</form>
				</div>

				<div class="standard">
					<h3>Other Actions</h3>
					<p><a href="?view=_edit_group&groupid=<?php echo $this->_group->id; ?>">Edit this group's details</a></p>
					<p><form method="post" action="?view=_edit_group&groupid=<?php echo $this->_group->id; ?>" onsubmit="return confirm('Are you sure you want to delete this group?')">
						<input type="hidden" name="action" value="delete" />
						<label class="clickable submit">Delete this group</label>
					</form></p>

					<p>Email members<br />
					<a href="?call=email&groupid=<?php echo $this->_group->id; ?>&method=public" onclick="return confirm('Sending a public email will allow group members to see each other\'s email addresses.  Are you sure?');">publicly</a> or
					<a href="?call=email&groupid=<?php echo $this->_group->id; ?>">via BCC</a> </p>

				</div>

			</div>

			<div class="next-to-action">

			<h3>Group Details</h3>

			<table class="standard">
				<tr>
					<th class="narrow">Category:</th>
					<td><?php $this->_group->printFieldValue('categoryid'); ?>&nbsp;&nbsp;</td>
					<th class="narrow">Status:</th>
					<td class="narrow"><?php $this->_group->printFieldValue('is_archived'); ?></td>
				</tr>
			</table>

			<?php
			$persons = $this->_group->getMembers();
			?>
			<h3>Group Members (<?php echo count($persons); ?>)</h3>
			<?php
			if (!empty($persons)) {
				include_once 'templates/person_list.template.php';
			} else {
				?>
				<p><em>This group does not currently have any members</em></p>
				<?php
			}
			?>

			</div>

			<?php
		}
	}
}
?>
