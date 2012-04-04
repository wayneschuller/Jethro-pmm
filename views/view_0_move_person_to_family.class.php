<?php
class View__Move_Person_To_Family extends View
{
	var $_person;

	static function getMenuPermissionLevel()
	{
		return PERM_EDITPERSON;
	}

	function processView()
	{
		$this->_person =& $GLOBALS['system']->getDBObject('person', (int)$_REQUEST['personid']);
		if (!empty($_REQUEST['familyid'])) {
			if ($_REQUEST['familyid'] == 'NEW') {
				$family =& $GLOBALS['system']->getDBObject('family', (int)$this->_person->getValue('familyid'));
				$family->id = 0;
				$family->create();
				$this->_person->setValue('familyid', $family->id);
				$this->_person->save();
				add_message('New family created with same details as old family.  You should update the new family\'s details as required');
				redirect('_edit_family', Array('familyid' => $family->id)); // exits
			} else {
				$family =& $GLOBALS['system']->getDBObject('family', (int)$_REQUEST['familyid']);
				if ($family) {
					$old_familyid = $this->_person->getValue('familyid');
					$this->_person->setValue('familyid', (int)$_REQUEST['familyid']);
					if ($this->_person->save()) {
						add_message('Person moved to family "'.$family->toString().'"');

						$remaining_members = $GLOBALS['system']->getDBObjectData('person', Array('familyid' => $old_familyid));
						if (empty($remaining_members)) {
							$old_family =& $GLOBALS['system']->getDBObject('family', $old_familyid);
							// add a note
							$GLOBALS['system']->includeDBClass('family_note');
							$note = new Family_Note();
							$note->setValue('familyid', $old_familyid);
							$note->setValue('subject', 'Archived by System');
							$note->setValue('details', 'The system is archiving this family because its last member ('.$this->_person->toString().' #'.$this->_person->id.') has been moved to another family ('.$family->toString().' #'.$family->id.')');
							$note->create();

							// archive the family record
							$old_family->setValue('status', 'archived');
							$old_family->save();
						}

						redirect('persons', Array('personid' => $this->_person->id)); // exits

					}
				}
			}

		}
	}

	function getTitle()
	{
		return 'Editing '.$this->_person->toString();
	}


	function printView()
	{
		$show_form = true;
		if (!empty($_POST['familyid'])) {
			if (!$this->_person->haveLock()) {
				// lock expired
				if ($this->_person->acquireLock()) {
					// managed to reacquire lock - ask them to try again
					?>
					<div class="failure">Your changes could not be saved because your lock had expired.  Try making your changes again using the form below</div>
					<?php
					$show_form = true;
				} else {
					// could not re-acquire lock
					?>
					<div class="failure">Your changes could not be saved because your lock has expired.  The lock has now been acquired by another user.  Wait some time for them to finish and then <a href="?view=_edit_person&personid=<?php echo $this->_person->id; ?>">try again</a></div>
					<?php
					$show_form = false;
				}
			} else {
				// must have been some other problem
				$show_form = true;
			}
		} else {
			// hasn't been submitted yet
			if (!$this->_person->acquireLock()) {
				?>
				<div class="failure">This person cannot currently be edited because another user has the lock.  Wait some time for them to finish and then <a href="?view=_edit_person&personid=<?php echo $this->_person->id; ?>">try again</a></div>
				<?php
				$show_form = false;
			}
		}
		if ($show_form) {
			?>
			<form method="post">
				<table class="standard">
					<tr>
						<th>Current Family</th>
						<td><?php echo $this->_person->printFieldValue('familyid'); ?></td>
					</tr>
					<tr>
						<th>New Family </th>
						<td>
							<?php
							Family::printSingleFinder('familyid');
							?>
							<p>OR</p>
							<input name="familyid" id="familyid_new" type="checkbox" value="NEW" onclick="$('#familyid_int').attr('disabled', this.checked)" />
							<label for="familyid_new">Create a new family record</label><br />
							<span class="smallprint">Tick this option to create a new family containing only this person.  Initially, the new family will have the same properties as this person's current family, but you will be prompted to update the details as necessary</span>
						</td>
					</tr>
				</table>
				<input type="submit" value="Move Person to New Family" />
				<a href="?view=persons&personid=<?php echo $this->_person->id; ?>"><input type="button" value="Cancel" /></a>
			</form>
			<script type="text/javascript">
				setTimeout('showLockExpiryWarning()', <?php echo (strtotime('+'.LOCK_LENGTH, 0)-60)*1000; ?>);
				setTimeout('showLockExpiredWarning()', <?php echo (strtotime('+'.LOCK_LENGTH, 0))*1000; ?>);
			</script>
			<?php
		}
	}
}
?>