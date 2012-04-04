<div class="tabber">

	<div class="tabbertab full-width-table">
		<h3>Basic Details</h3>
		<?php
		$links = Array();
		if ($GLOBALS['user_system']->havePerm(PERM_EDITPERSON)) {
			$links[] = '<a href="?view=_edit_family&familyid='.$family->id.'">Edit Details</a>';
			if (count($GLOBALS['user_system']->getCurrentRestrictions()) == 0) {
				// users with group or cong restrictions are not allowed to add persons
				$links[] = '<a href="?view=_add_person_to_family&familyid='.$family->id.'">Add Member</a>';
			}
		}
		if ($family->getPostalAddress() != '') {
			$links[] = '<a href="?call=envelopes&familyid='.$family->id.'" class="envelope-popup">Print Envelope</a>';
		}
		$all_emails = $family->getAllEmailAddrs();
		if (!empty($all_emails)) {
			$links[] = '<a href="mailto: '.implode(', ', $all_emails).'">Email All</a>';
		}
		if (!empty($links)) {
			?>
			<div class="align-right" style="margin: 0px 0.5ex">
				<?php echo implode(' &nbsp; ', $links); ?>
			</div>
			<?php
		}

		$family->printSummary(FALSE);
		?>
	</div>
<?php
if ($GLOBALS['user_system']->havePerm(PERM_VIEWNOTE)) {
	$notes = $GLOBALS['system']->getDBObjectData('family_note', Array('familyid' => $family->id), 'OR', 'created');
	?>
	<div class="tabbertab family-notes">
		<h3>Notes (<?php echo count($notes); ?>)</h3>
		<?php
		$show_edit_link = FALSE;
		if ($GLOBALS['user_system']->havePerm(PERM_EDITNOTE)) {
			?>
			<div class="align-right">
				<?php
				$members = $family->getMemberData();
				if (count($members) > 1) {
					?>
					<a href="?view=_add_note_to_family&familyid=<?php echo $family->id; ?>">Add Family Note</a>
					<?php
				} else {
					?>
					<a href="?view=_add_note_to_person&personid=<?php echo reset(array_keys($members)); ?>">Add Person Note</a>
					<?php
				}
				?>
			</div>
			<?php
			$show_edit_link = TRUE;
		}
		include 'list_notes.template.php';
		?>
	</div>
	<?php
}
?>

	<div class="tabbertab">
		<h3>History</h3>
		<p>Family Record Created on <?php $family->printFieldValue('created'); ?> by <?php $family->printFieldValue('creator'); ?></p>
		<?php $family->printFieldValue('history'); ?>
	</div>

</div>
