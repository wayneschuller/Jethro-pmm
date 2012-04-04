<?php
include_once 'db_objects/action_plan.class.php';
class View_Families__Add extends View
{
	var $_family;

	static function getMenuPermissionLevel()
	{
		if ($GLOBALS['user_system']->getCurrentRestrictions()) {
			return -1; // users with group or cong restrictions can't add families or persons
		} else {
			return PERM_EDITPERSON;
		}
	}

	function processView()
	{
		$GLOBALS['system']->includeDBClass('family');
		$this->_family = new Family();

		if (array_get($_REQUEST, 'new_family_submitted')) {

			// some initial checks
			$i = 0;
			$found_member = FALSE;
			while (isset($_POST['members_'.$i.'_first_name'])) {
				if (!empty($_POST['members_'.$i.'_first_name'])) {
					$found_member = TRUE;
				}
				$i++;
			}
			if (!$found_member) {
				add_message('New family must have at least one member', 'failure');
				return FALSE;
			}
			if ($GLOBALS['user_system']->havePerm(PERM_EDITNOTE)) {
				if (REQUIRE_INITIAL_NOTE && empty($_POST['initial_note_subject'])) {
					add_message("A subject must be supplied for the initial family note", 'failure');
					return FALSE;
				}
			}

			$GLOBALS['system']->doTransaction('begin');

			// Create the family record itself
			$this->_family->processForm();
			$success = $this->_family->create();
			
			if ($success) {
				// Add members
				$i = 0;
				$members = Array();
				$GLOBALS['system']->includeDBClass('person');
				while (isset($_POST['members_'.$i.'_first_name'])) {
					if (!empty($_POST['members_'.$i.'_first_name'])) {
						$member = new Person();
						$member->setValue('familyid', $this->_family->id);
						$member->processForm('members_'.$i.'_');
						if (!$member->create()) {
							$success = FALSE;
							break;
						}
						$members[] =& $member;
					}
					$i++;
				}
			}

			if ($success) {
				if ($GLOBALS['user_system']->havePerm(PERM_EDITNOTE)) {
					if (REQUIRE_INITIAL_NOTE || !empty($_POST['initial_note_subject'])) {
						// Add note
						if (count($members) > 1) {
							$GLOBALS['system']->includeDBClass('family_note');
							$note = new Family_Note();
							$note->setValue('familyid', $this->_family->id);
						} else {
							$GLOBALS['system']->includeDBClass('person_note');
							$note = new Person_Note();
							$note->setValue('personid', $members[0]->id);
						}
						$note->processForm('initial_note_');
						$success = $note->create();
					}
				}

				if (!empty($_POST['execute_plan'])) {
					foreach ($_POST['execute_plan'] as $planid) {
						$plan = $GLOBALS['system']->getDBObject('action_plan', $planid);
						$plan->execute('family', $this->_family->id, process_widget('plan_reference_date', Array('type' => 'date')));
					}
				}
			}

			// Before committing, check for duplicates
			if (empty($_REQUEST['override_dup_check'])) {
				$this->_similar_families = $this->_family->findSimilarFamilies();
				if (!empty($this->_similar_families)) {
					$GLOBALS['system']->doTransaction('rollback');
					return;
				}
			}

			if ($success) {
				$GLOBALS['system']->doTransaction('commit');
				add_message('Family Created');
				redirect('families', Array('familyid' => $this->_family->id));
			} else {
				$GLOBALS['system']->doTransaction('rollback');
				$this->_family->id = 0;
				add_message('Error during family creation, family not created', 'failure');
			}
		}
	}
	
	function getTitle()
	{
		return 'Add Family';
	}


	function printView()
	{
		if (!empty($this->_similar_families)) {
			$msg = count($this->_similar_families) > 1 
				? 'Several families already exist that are similar to the one you are creating' 
				: 'A family similar to the one you are creating already exists';
			?>
			<p><b>Warning: <?php echo $msg; ?>.</b></p>
			<?php 
			foreach ($this->_similar_families as $family) {
				?>
				<h4>Family #<?php echo $family->id; ?></h4>
				<?php
				$family->printSummary();
			}
			?>

			<form method="post" class="inline">
			<?php print_hidden_fields($_POST); ?>
			<input type="submit" name="override_dup_check" value="Create new family anyway" />
			</form>

			<form method="get" class="inline">
			<input type="submit" value="Cancel family creation" />
			</form>
			<?php
			return;
		}

		// Else the normal form
		$GLOBALS['system']->includeDBClass('person');
		$person = new Person();
		?>
		<form method="post" id="add-family">
			<input type="hidden" name="new_family_submitted" value="1" />

			<div>
			<h3>Family Details</h3>
			<?php
			$this->_family->printForm();
			?>
			</div>

			<div>
			<h3>Family Member Details</h3>
			<table class="expandable">
				<thead>
					<tr>
						<th>First Name</th>
						<th>Last Name</th>
						<th>Gender</th>
						<th>Age</th>
						<th>Status</th>
						<th>Cong.</th>
						<th>Mobile Tel</th>
						<th>Email</th>
					</tr>
				<thead>
				<tbody>
					<tr>
						<td><?php $person->printFieldInterface('first_name', 'members_0_'); ?></td>
						<td class="last_name preserve-value"><?php $person->printFieldInterface('last_name', 'members_0_'); ?></td>
						<td><?php $person->printFieldInterface('gender', 'members_0_'); ?></td>
						<td><?php $person->printFieldInterface('age_bracket', 'members_0_'); ?></td>
						<td class="person-status preserve-value"><?php $person->printFieldInterface('status', 'members_0_'); ?></td>
						<td class="congregation"><?php $person->printFieldInterface('congregationid', 'members_0_'); ?></td>
						<td><?php $person->printFieldInterface('mobile_tel', 'members_0_'); ?></td>
						<td><?php $person->printFieldInterface('email', 'members_0_'); ?></td>
					</tr>
				</tbody>
			</table>
			</div>
		<?php
		if ($GLOBALS['user_system']->havePerm(PERM_EDITNOTE)) {
			?>
			<div <?php echo REQUIRE_INITIAL_NOTE ? '' : 'class="optional"'; ?>>
			<h3>Initial Note</h3>
			<?php
				$GLOBALS['system']->includeDBClass('family_note');
				$note = new Family_Note();
				$note->printForm('initial_note_');
			?>
			</div>
			<?php
		}

		if ($plan_chooser = Action_Plan::getMultiChooser('execute_plan', 'create_family')) {
			?>
			<h3>Action plans</h3>
			<p>Execute the following action plans for the new family: </p>
			<?php echo $plan_chooser; ?>
			<p>Reference date for plans: <?php print_widget('plan_reference_date', Array('type' => 'date'), NULL); ?></p>
			<?php
		}
		?>
		<h3>Create</h3>
			<div class="align-right">
				<input type="submit" value="Create Family" />
			</div>
		</form>
		<?php
	}
}
?>
