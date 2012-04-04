<?php
$GLOBALS['system']->includeDBClass('person_group');
$GLOBALS['system']->includeDBClass('action_plan');
$groups = Person_Group::getGroups($person->id);
$photoclass = ($GLOBALS['system']->featureEnabled('PHOTOS')) ? 'photo-container' : '';
$plan_chooser = Action_Plan::getMultiChooser('planid', Array());
if ($plan_chooser) {
	?>
	<div id="plan-chooser" class="modal">
	<form method="post" action="?view=_execute_plans&personid[]=<?php echo (int)$person->id; ?>">
		<h4>Execute Action Plan for <?php echo htmlentities($person->toString()); ?></h4>
		<p><?php echo $plan_chooser; ?></p>
		<p>Reference date for plans: <?php print_widget('plan_reference_date', Array('type' => 'date'), NULL); ?></p>
		<p class="align-right">
		<input type="submit" value="Go" />
		<input type="button" value="Cancel" class="close" />
		</p>
	</form>
	</div>
	<?php
}
?>
<div class="tabber">

	<div class="tabbertab" id="basic">
		<h3>Basic Details</h3>
		<div class="standard matching-flow-box">

				<h4>
				<div class="float-right">
					<a href="?call=envelopes&personid=<?php echo $person->id; ?>" class="envelope-popup">Print Envelope</a>
					<?php
					if ($GLOBALS['user_system']->havePerm(PERM_EDITPERSON)) {
						?>
						| <a href="?view=_edit_person&personid=<?php echo $person->id; ?>">Edit Person</a>
						<?php
					}
					
					if ($plan_chooser) {
						?>
						| <span class="clickable" onclick="$('#plan-chooser').toggle()">Execute Action Plan</span>
						<?php
					}

					?>
				</div>
				
				Person Details
				</h4>

			<?php $person->printSummary(); ?>
		</div>
		
		<div class="standard matching-flow-box">
			<h4>
				<div class="float-right">
					<a href="?view=families&familyid=<?php echo $family->id; ?>">View Family</a>
					<?php
					if ($GLOBALS['user_system']->havePerm(PERM_EDITPERSON)) {
						?>
						| <a href="?view=_edit_family&familyid=<?php echo $family->id; ?>">Edit Family</a>
						<?php
					}
					?>
				</div>
				Family Details
			</h4>
			<div class="full-width-table">
				<?php
				$family->printSummary(); 
				if ($GLOBALS['user_system']->havePerm(PERM_EDITPERSON)) {
					?>
					<div class="align-right">
						<a href="?view=_move_person_to_family&personid=<?php echo $person->id; ?>">Move this person to a different/new family</a><br />
					</div>
					<?php
				}
				?>
			</div>
		</div>
	</div>

	<script>
	function handleMatchingFlowBoxes() {
		var boxes = $('.matching-flow-box');
		boxes.css('float', 'left').css('height', 'auto').css('width', 'auto');
		boxes[0] = $(boxes[0]);
		boxes[1] = $(boxes[1]);
		if (boxes.length > 1) {
			if (boxes[0].offset().top != boxes[1].offset().top) {
				// they have flowed
				//alert('flowed');
				var maxW = Math.max(boxes[0].width(), boxes[1].width());
				boxes[0].css('width', maxW+'px');
				boxes[1].css('width', maxW+'px');
			} else {
				// on the same line
				var narrower, wider;
				if (boxes[0].width()< boxes[1].width()) {
					narrower = boxes[0];
					wider = boxes[1];
				} else {
					narrower = boxes[1];
					wider = boxes[0];
				}
				var available = boxes[0].parent().width() - wider.outerWidth(true) - narrower.outerWidth(true);
				narrower.css('width', Math.min(narrower.outerWidth() + available - 20, wider.width())+'px');
				//alert(available + " " + wider.width() + " " + Math.min(narrower.outerWidth() + available - 20, wider.width()));

				var maxH = Math.max(boxes[0].height(), boxes[1].height());
				boxes[0].css('height', maxH+'px');
				boxes[1].css('height', maxH+'px');

			}
		}
	}
	$(window).resize(handleMatchingFlowBoxes);
	$(document).ready(function() {
		handleMatchingFlowBoxes();
		$('div.tabberlive').each(function() { 
			this.tabber.onTabShow = handleMatchingFlowBoxes; 
		});
	});
	</script>

<?php
if ($GLOBALS['user_system']->havePerm(PERM_VIEWNOTE)) {
	$notes = $person->getNotesHistory();
	?>
	<div class="tabbertab" id="notes">
		<h3>Notes (<?php echo count($notes); ?>)</h3>
		<?php
		if ($GLOBALS['user_system']->havePerm(PERM_EDITNOTE)) {
			?>
			<div class="float-right"><a href="?view=_add_note_to_person&personid=<?php echo $person->id; ?>">Add New Note</a></div>
			<?php
		}
		if (empty($notes)) {
			?>
			<p><i>There are no person or family notes for <?php $person->printFieldValue('name'); ?></i></p>
			<?php
		} else {
			?>
			<h4>Person and Family Notes for <?php $person->printFieldValue('name'); ?></h4>
			<?php
		}
		$show_edit_link = true;
		include 'list_notes.template.php';

		if ($GLOBALS['user_system']->havePerm(PERM_EDITNOTE) && !empty($notes)) {
			?>
			<div class="align-right"><a href="?view=_add_note_to_person&personid=<?php echo $person->id; ?>">Add New Note</a></div>
			<?php
		}
		?>
	</div>
	<?php
}
?>

	<div class="tabbertab" id="groups">
		<h3>Groups (<?php echo count($groups); ?>)</h3>
	<?php
	if ($GLOBALS['user_system']->havePerm(PERM_EDITPERSON)) {
		?>
		<div class="standard action-box" style="overflow: hidden; margin-left: 2ex">
			<h4>Add this person to a group</h4>
			<form method="post">
				<input type="hidden" name="view" value="_edit_group" />
				<input type="hidden" name="personid" value="<?php echo $person->id; ?>" />
				<input type="hidden" name="action" value="add_member" />
				<input type="hidden" name="back_to" value="persons" />
				<?php
				$GLOBALS['system']->includeDBClass('person_group');
				if (Person_Group::printChooser('groupid', 0, array_keys($groups))) {
					?>
					<input type="submit" value="Go" accesskey="s" />
					<?php
				}
				?>
			</form>
		</div>
		<?php
	}
	if (empty($groups)) {
		?>
		<p><i><?php $person->printFieldValue('name'); ?> is not a member of any groups</i></p>
		<?php
	} else {
		?>
		<h4><?php echo $person->printFieldValue('name'); ?> is a member of:</h4>
		<table class="standard hoverable">
			<thead>
				<tr>
					<th>ID</th>
					<th>Group Name</th>
					<th>Joined Group</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
			<?php
			foreach ($groups as $id => $details) {
				$trclass = $details['is_archived'] ? ' class="archived"' : '';
				?>
				<tr<?php echo $trclass; ?>>
					<td><?php echo $id; ?></td>
					<td><a href="?view=groups&groupid=<?php echo $id; ?>"><?php echo $details['name']; ?></a></td>
					<td><?php echo format_datetime($details['created']); ?></td>
					<td><form class="inline" method="post" action="?view=_edit_group&action=remove_member&groupid=<?php echo $id; ?>&back_to=persons" onsubmit="return confirm('Are you sure you want to remove this person from the group \'<?php echo $details['name']; ?>\'?');"><input type="hidden" name="personid" value="<?php echo $person->id; ?>"><label class="clickable submit">Remove</label></form></td>
				</tr>
				<?php
			}
			?>
			</tbody>
		</table>
		<?php
	}
	?>
	</div>

	<div class="tabbertab" id="history">
		<h3>History</h3>
		<p>Person Record created on <?php $person->printFieldValue('created'); ?> by <?php $person->printFieldValue('creator'); ?></p>
		<?php $person->printFieldValue('history'); ?>
	</div>

<?php
if ($GLOBALS['user_system']->havePerm(PERM_VIEWATTENDANCE)) {
	?>
	<div class="tabbertab" id="attendance">
		<h3>Attendance</h3>
		<?php $person->printRecentAttendance(12); ?>
	</div>
	<?php
}

if ($GLOBALS['user_system']->havePerm(PERM_VIEWROSTER)) {
	?>
	<div class="tabbertab" id="rosters">
		<h3>Roster Allocations</h3>
		<h4>Upcoming role allocations for <?php $person->printFieldValue('name'); ?></h4>
		<?php
		$GLOBALS['system']->includeDBClass('roster_role_assignment');
		$assignments = Roster_Role_Assignment::getUpcomingAssignments($person->id, NULL);
		if (empty($assignments)) {
			echo '<i>None</i>';
		} else {
			foreach ($assignments as $date => $allocs) {
				?>
				<h5><?php echo date('j M', strtotime($date)); ?></h5>
				<?php
				foreach ($allocs as $alloc) {
					echo htmlentities($alloc['cong'].' '.$alloc['title']).'<br />';
				}
			}
		}
		?>
	</div>
	<?php
}
?>
</div>
