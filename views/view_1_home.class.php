<?php
class View_Home extends View
{
	function getTitle()
	{
		return 'Home';
	}

	function processView()
	{
	}
	
	function printView()
	{
		?>
		<table style="width: 100%">
			<tr>
				<td>
				<?php
				if ($GLOBALS['user_system']->havePerm(PERM_VIEWNOTE)) {
					$note_cats = Array(
									'now'	=> 'Notes for my immediate attention',
									'later'	=> 'Notes for future action',
								 );
					foreach ($note_cats as $code => $title) {
						$user =& $GLOBALS['system']->getDBObject('staff_member', $GLOBALS['user_system']->getCurrentUser('id'));
						$tasks = $user->getTasks($code);
						?>
						<h3><?php echo $title; ?></h3>
						<?php
						if (empty($tasks)) {
							?>
							<em>None</em>
							<?php
						} else {
							?>
							<table class="clickable-rows hoverable standard task-list">
								<thead>
									<tr>
										<th class="narrow">ID</th>
										<th colspan="2">For</th>
										<th style="width: 40%">Subject</th>
										<th class="narrow">Action Date</th>
									</tr>
								</thead>
								<tbody>
									<?php
									foreach ($tasks as $id => $task) {
										$view = ($task['type'] == 'person') ? 'persons' : 'families';
										?>
										<tr>
											<td><a href="?view=<?php echo $view; ?>&<?php echo $task['type']; ?>id=<?php echo $task[$task['type'].'id']; ?>#note_<?php echo $id; ?>"><?php echo $id; ?></a></td>
											<td class="icon narrow"><img src="<?php echo BASE_URL.'/resources/'.$task['type'].'.gif'; ?>" style="margin: 0px; border: 0px" /></td>
											<td class="narrow"><?php echo $task['name']; ?></td>
											<td><?php echo $task['subject']; ?></td>
											<td class="narrow"><?php echo format_date($task['action_date']); ?></td>
										</tr>
										<?php
									}
									?>
								</tbody>
							</table>
							<?php
						}
					}
				}
				?>
				</td>
				<?php
			if ($GLOBALS['user_system']->havePerm(PERM_VIEWROSTER)) {
			?>

				<td style="padding-left: 2ex; width: 40ex">
					<div class="standard">
							<h3>Upcoming role allocations for <?php echo $GLOBALS['user_system']->getCurrentUser('first_name').'&nbsp;'.$GLOBALS['user_system']->getCurrentUser('last_name'); ?></h3>
						<?php
							$GLOBALS['system']->includeDBClass('roster_role_assignment');
							foreach (Roster_Role_Assignment::getUpcomingAssignments($GLOBALS['user_system']->getCurrentUser('id')) as $date => $allocs) {
								 ?>
								 <h5><?php echo date('j M', strtotime($date)); ?></h5>
								 <?php
								 foreach ($allocs as $alloc) {
									  echo $alloc['cong'].' '.$alloc['title'].'<br />';
								 }
							}
							?>
							<div class="right"><a href="./?view=persons&personid=<?php echo $GLOBALS['user_system']->getCurrentUser('id'); ?>#rosters">See all</a></div>
					 </div>
				</td>
			<?php
			}
			?>
				<td style="padding-left: 2ex; width: 40ex">
					<div class="standard">
						<form method="get">
								<h3>Find Person</h3>
								<input type="hidden" name="view" value="persons__search" />
								Name: <input type="text" name="name" />
								<input type="submit" value="Go" />
						</form>
					</div>
					<div class="standard">
						<form method="get">
							<h3>Find Family</h3>
							<input type="hidden" name="view" value="families__search" />
							Name: <input type="text" name="name" />
							<input type="submit" value="Go" />
						</form>
					</div>
					<div class="standard">
						<form method="get">
							<h3>Find Group</h3>
							<input type="hidden" name="view" value="groups__search" />
							Name: <input type="text" name="name" />
							<input type="submit" value="Go" />
						</form>
					</div>
					<div class="standard">
						<form method="get">
							<h3>Look up Phone Number</h3>
							<input type="hidden" name="view" value="_mixed_search" />
							Number: <input type="text" name="tel" size="12" />
							<input type="submit" value="Go" />
						</form>
					</div>
				</td>
			</tr>
		</table>
		<?php
	}
}
?>
