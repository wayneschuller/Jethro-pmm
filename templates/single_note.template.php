<?php
$dummy->populate($id, $entry);
$type = (!empty($entry['familyid']) ? 'family' : 'person');
?>
<table class="notes-history-entry <?php echo $type; ?>-note" id="note_<?php echo $id; ?>">
	<tr>
		<td class="icon" title="<?php echo ucfirst($type); ?> note">
		</td>
		<td>
			<div class="content">
				<h4><?php echo $entry['subject']; ?></h4>
				<p><?php echo nl2br($entry['details']); ?></p>
				<p class="author">
					Added by 
					<?php echo $entry['creator_fn'].' '.$entry['creator_ln'].' (#'.$entry['creator'].')'; ?>
					<?php echo format_datetime($entry['created']); ?>
				</p>
			</div>
			<?php
			if (!empty($entry['comments'])) {
				?>
				<h5>Comments:</h5>
				<ul class="comments">
				<?php
				foreach ($entry['comments'] as $comment) {
					?>
					<li>
						<div class="content">
							<?php echo nl2br($comment['contents']); ?>
							<p class="author">
								Added by 
								<?php echo $comment['creator_fn'].' '.$comment['creator_ln'].' (#'.$entry['creator'].')'; ?>
								<?php echo format_datetime($comment['created']); ?>
							</p>
						</div>
					</li>
					<?php
				}
				?>
				</ul>
				<?php
			}
			if (!empty($show_form)) {
				?>
				<h5>Add Update:</h5>
				<ul class="comments">
				<li>
					<div class="content">
						<?php $dummy->printUpdateForm(); ?>
					</div>
				</li>
				</ul>
				<?php
			}
			?>
		</td>
	<?php
	if (empty($show_form)) {
		?>
		<td class="status">
			<p>
			<?php $dummy->printStatusSummary(); ?>
			</p>
			<?php
			if ($entry['status'] == 'pending') {
				echo '<p>Assigned&nbsp;to<br /> '.$entry['assignee_fn'].'&nbsp;'.$entry['assignee_ln'].' (#'.$entry['assignee'].')</p>';
				
			}
			if (!empty($show_edit_link) && $GLOBALS['user_system']->havePerm(PERM_EDITNOTE)) {
				?>
				<p><a href="?view=_edit_note&note_type=<?php echo $type; ?>&noteid=<?php echo $id; ?>">Edit Note</a></p>
				<?php
			}
			?>
		</td>
		<?php
	}
	?>
	</tr>
</table>
