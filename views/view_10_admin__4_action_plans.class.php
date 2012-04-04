<?php

class View_Admin__Action_Plans extends View
{
	var $_plan;

	static function getMenuPermissionLevel()
	{
		return PERM_SYSADMIN;
	}

	function processView()
	{
		$GLOBALS['system']->includeDBClass('action_plan');
		if (isset($_REQUEST['planid'])) {
			$this->_plan = new Action_Plan((int)$_REQUEST['planid']);
		}
		if ($this->_plan && !empty($_REQUEST['delete'])) {
			if ($this->_plan->acquireLock()) {
				$this->_plan->delete();
				add_message('Action plan deleted');
				$this->_plan->releaseLock();
				$this->_plan  = NULL;
			} else {
				add_message('The plan could not be deleted because another user currently holds the lock.  Wait for them to finish editing then try again.', 'failure');
			}
		} else if (!empty($_POST['plan_submitted'])) {
			$this->_plan->processForm();
			if ($this->_plan->id) {
				$this->_plan->save();
				$this->_plan->releaseLock();
				add_message("Action plan updated");
			} else {
				$this->_plan->create();
				add_message("Action plan created");
			}
			$this->_plan = NULL;
		} else if ($this->_plan && $this->_plan->id) {
			if (!$this->_plan->acquireLock()) {
				add_message("This plan cannot be edited because another user holds the lock.  Please wait for them to finish editing and try again.", 'failure');
			}
		}
	}
	
	function getTitle()
	{
		if ($this->_plan) {
			if ($this->_plan->id) {
				return 'Edit action plan: '.$this->_plan->getValue('name');
			} else {
				return 'Add action plan';
			}
		} else {
			return 'Action plans';
		}
	}
	
	function printView()
	{
		if (!empty($this->_plan)) {
			if (empty($this->_plan->id) || $this->_plan->haveLock()) {
				?>
				<form method="post">
					<input type="hidden" name="plan_submitted" value="1" />
					<?php
					$this->_plan->printForm();
					?>
					<input type="submit" value="Save" />
					<a href="<?php echo build_url(Array('planid' => NULL)); ?>"><input type="button" value="Cancel" /></a>
				</form>
				<?php
			} else {
				$this->_plan->printSummary();
			}
		} else {
			?>
			<div class="standard float-right">
				<h3>Actions</h3>
				<a href="<?php echo build_url(Array('planid' => 0, 'delete' => NULL)); ?>">Add new plan</a>
			</div>
			<?php
			$saved_plans = $GLOBALS['system']->getDBObjectData('action_plan', Array(), '', 'name');
			if (empty($saved_plans)) {
				?>
				<i>There are not yet any action plans saved in the system</i>
				<?php
			} else {
				?>
				<table class="standard hoverable">
					<thead>
						<tr>
							<th>ID</th>
							<th>Name</th>
							<th>Last modified</th>
							<th>&nbsp;</th>
						</tr>
					</thead>
					<tbody>
					<?php
					$dummy_plan = new Action_Plan();
					foreach ($saved_plans as $id => $details) {
						$dummy_plan->populate($id, $details);
						?>
						<tr>
							<td><?php echo (int)$id; ?></td>
							<td class="nowrap"><?php echo htmlentities($details['name']); ?></td>
							<td class="nowrap"><?php $dummy_plan->printFieldValue('modified'); ?> by <?php echo $dummy_plan->printFieldValue('modifier'); ?></td>
							<td class="nowrap">
								<a href="?view=<?php echo htmlentities($_REQUEST['view']); ?>&planid=<?php echo $id; ?>">Edit</a> &nbsp;
								<a href="?view=<?php echo htmlentities($_REQUEST['view']); ?>&planid=<?php echo $id; ?>&delete=1" class="confirm-title" title="Delete this action plan">Delete</a>
							</td>
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
}
?>
