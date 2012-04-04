<table class="hoverable standard">
	<tr>
		<th>ID</th>
		<th>Family Name</th>
		<th>Family Members</th>
		<th>Actions</th>
	</tr>
<?php
foreach ($families as $id => $details) {
	$tr_class = ($details['status'] == 'archived') ? ' class="archived"' : '';
	?>
	<tr<?php echo $tr_class; ?>>
		<td><?php echo $id; ?></td>
		<td><?php echo $details['family_name']; ?></td>
		<td><?php echo $details['members']; ?></td>
		<td class="narrow">
			<a href="?view=families&familyid=<?php echo $id; ?>">View</a> &nbsp;
		<?php
		if ($GLOBALS['user_system']->havePerm(PERM_EDITPERSON)) {
			?>
			<a href="?view=_edit_family&familyid=<?php echo $id; ?>">Edit</a> &nbsp;
			<?php
		}
		if ($GLOBALS['user_system']->havePerm(PERM_EDITNOTE)) {
			?>
			<a href="?view=_add_note_to_family&familyid=<?php echo $id; ?>">Add Note</a></td>
			<?php
		}
		?>
		</td>
	</tr>
	<?php
}
?>
</table>
