<?php
class View__Add_User_Account extends View
{
	var $_sm;
	var $_sm_fields = Array('username', 'password', 'active', 'permissions', 'restrictions');

	static function getMenuPermissionLevel()
	{
		return PERM_SYSADMIN;
	}

	function processView()
	{
		$GLOBALS['system']->includeDBClass('staff_member');
		$this->_sm = new Staff_Member();

		if (!empty($_REQUEST['new_sm_submitted'])) {
			if (empty($_REQUEST['personid'])) {
				trigger_error('You must choose a person record to create the user account for');
				return;
			}
			$person =& $GLOBALS['system']->getDBObject('person', $_REQUEST['personid']);
			$this->_sm->processForm('', $this->_sm_fields);
			if ($this->_sm->createFromChild($person)) {
				add_message('User account Added');
				redirect('admin__user_accounts');
			} else {
				trigger_error('Failed to create user account');
			}
		}
	}

	function getTitle()
	{
		return 'Add User Account';
	}


	function printView()
	{
		?>
		<form method="post">
			<input type="hidden" name="new_sm_submitted" value="1" />
			<table>
				<tr>
					<th>
						Person record
					</th>
					<td>
						<input type="text" id="new-member-search" onchange="this.className = ''; $('#add-member-button')[0].disabled = true;" onfocus="this.select()" />
						<input type="hidden" id="new-member-id" name="personid" />
						<input type="hidden" id="iframe-url" value="<?php echo BASE_URL; ?>?call=find_person&search=" />
						<input type="hidden" name="back_to" value="groups" />
						<input type="button" onclick="doPersonSearch(this)" id="new-member-search-button" value="Search" />
						<iframe name="new_member_iframe" id="new_member_iframe" src="" style="display: none; height: 150px; width: 95%; border: 1px solid #888; margin: 5px;"></iframe>
						<p class="smallprint">If the user does not yet exist in the system as a person, you must <a href="?view=families__add">add them first</a></p>
					</td>
				</tr>
			<?php
			foreach ($this->_sm_fields as $field) {
				?>
				<tr>
					<th><?php echo $this->_sm->getFieldLabel($field); ?></th>
					<td><?php $this->_sm->printFieldInterface($field);?></td>
				</tr>
				<?php
			}
			?>
			<tr>
				<th>&nbsp;</th>
				<td><input type="submit" value="Create user account" /></td>
			</tr>
		</table>
		</form>
		<?php
	}
}
?>