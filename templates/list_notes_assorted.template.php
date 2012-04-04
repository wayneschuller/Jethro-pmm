<?php
/* @var	$assigning	Whether we are assigning notes using this view */

if ($reassigning) {
	require_once 'db_objects/abstract_note.class.php';
	$fake_note = new Abstract_Note();
	?>
	<form method="post">
	<?php
}
?>
<table class="hoverable standard task-list sortable">
	<thead>
		<tr>
			<th>ID</th>
			<th colspan="2">For</th>
			<th>Subject</th>
			<th>Creator</th>
			<th>Assignee</th>
			<th>Action Date</th>
			<th>&nbsp;</th>
		</tr>
	</thead>
	<tbody>
	<?php
	foreach ($notes as $id => $note) {
		if (!empty($note['familyid'])) {
			$type = 'family';
			$notee_name = $note['family_name'].' Family';
			$view_url = '?view=families&familyid='.$note['familyid'].'#note_'.$id;
		} else {
			$type = 'person';
			$notee_name = $note['person_fn'].' '.$note['person_ln'];
			$view_url = '?view=persons&personid='.$note['personid'].'#note_'.$id;
		}
		?>
		<tr>
			<td><?php echo $id; ?></td>
			<td class="icon"><img src="<?php echo BASE_URL; ?>/resources/<?php echo $type; ?>.gif" /></td>
			<td class="nowrap"><?php echo $notee_name; ?></td>
			<td><?php echo $note['subject']; ?></td>
			<td class="nowrap"><?php echo $note['creator_fn'].' '.$note['creator_ln']; ?></td>
			<td class="nowrap">
				<?php
				if ($reassigning) {
					$fake_note->populate($id, $note);
					if ($fake_note->haveLock() || $fake_note->canAcquireLock()) {
						$fake_note->acquireLock();
						$fake_note->printFieldInterface('assignee', 'note_'.$id.'_');
					} else {
						$fake_note->printFieldValue('assignee');
						echo '<p class="smallprint">This note is locked by another user and cannot be edited at this time.</p>';
					}
				} else {
					echo $note['assignee_fn'].' '.$note['assignee_ln']; 
				}
				?>
			</td>
			<td class="nowrap"><?php echo format_date($note['action_date']); ?></td>
			<td class="nowrap">
				<a href="<?php echo $view_url; ?>">View</a> &nbsp;
				<a href="?view=_edit_note&note_type=<?php echo $type; ?>&noteid=<?php echo $id; ?>&back_to=<?php echo htmlentities($_REQUEST['view']); ?>">Edit/Comment</a>
			</td>
		</tr>
		<?php
	}
	?>
	</tbody>
</table>
<?php
if ($reassigning) {
	?>
	<input type="submit" name="reassignments_submitted" value="Save Assignees" />
	</form>
	<?php
}
?>