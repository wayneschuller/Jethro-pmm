<?php
$GLOBALS['system']->includeDBClass('person');
$dummy_person = new Person();

if (!isset($special_fields)) {
	$special_fields = Array();
	if(!isset($include_special_fields) || $include_special_fields) {
		if (!empty($persons)) {
			$first_row = reset($persons);
			foreach ($first_row as $i => $v) {
				if (!isset($dummy_person->fields[$i]) && (strtolower($i) != 'id')) {
					$special_fields[] = $i;
				}
			}
		}
	}
}

if (!isset($show_actions)) $show_actions = TRUE;
?>

<form method="post" action="" class="bulk-person-action">
<table class="hoverable standard person-list">
	<thead>
		<tr>
			<th class="narrow">ID</th>
			<th>Name</th>
			<th>Gender</th>
			<th>Age</th>
			<th>Status</th>
		<?php
		foreach ($special_fields as $field) {
			?>
			<th><?php echo ucwords(str_replace('_', ' ', $field)); ?></th>
			<?php
		}
		if ($show_actions) {
			?>
			<th>Actions</th>
			<th class="narrow selector"><input type="checkbox" class="select-all" title="Select all" /></th>
			<?php
		}
		?>
		</tr>
	</thead>
	<tbody>
	<?php
	foreach ($persons as $id => $details) {
		$dummy_person->populate($id, $details);
		$tr_class = ($details['status'] === 'archived') ? ' class="archived"' : '';
		?>
		<tr<?php echo $tr_class; ?>>
			<td><?php echo $id; ?></td>
			<td><?php echo $dummy_person->toString() ?></td>
			<td><?php $dummy_person->printFieldValue('gender'); ?></td>
			<td><?php $dummy_person->printFieldValue('age_bracket'); ?></td>
			<td><?php $dummy_person->printFieldValue('status'); ?></td>
		<?php
		foreach ($special_fields as $field) {
			?>
			<td><?php echo $details[$field]; ?></td>
			<?php
		}
		if ($show_actions) {
			?>
			<td class="narrow">
				<a href="?view=persons&personid=<?php echo $id; ?>">View</a> &nbsp;
			<?php
			if ($GLOBALS['user_system']->havePerm(PERM_EDITPERSON)) {
				?>
				<a href="?view=_edit_person&personid=<?php echo $id; ?>">Edit</a> &nbsp;
				<?php
			}
			if ($GLOBALS['user_system']->havePerm(PERM_EDITNOTE)) {
				?>
				<a href="?view=_add_note_to_person&personid=<?php echo $id; ?>">Add Note</a>
				<?php
			}
			?>
			</td>
			<td class="selector"><input name="personid[]" type="checkbox" value="<?php echo $id; ?>" /></td>
			<?php
		}
		?>
		</tr>
		<?php
	}
	?>
	</tbody>
</table>
<?php
if ($show_actions) {
	include 'templates/bulk_actions.template.php';
}
?>
</form>
