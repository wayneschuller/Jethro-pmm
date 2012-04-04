<?php

class View_Persons__Reports extends View
{
	var $_query;
	var $_have_results = FALSE;
	var $_result_counts = Array();

	static function getMenuPermissionLevel()
	{
		return PERM_RUNREPORT;
	}

	function processView()
	{
		$GLOBALS['system']->includeDBClass('person_query');
		if (isset($_REQUEST['queryid'])) {
			$this->_query = new Person_Query($_REQUEST['queryid']);
		}
		if ($this->_query && !empty($_REQUEST['delete'])) {
			$can_delete = FALSE;
			if (($this->_query->getValue('creator') == $GLOBALS['user_system']->getCurrentUser('id')) || $GLOBALS['user_system']->havePerm(PERM_SYSADMIN)) {
				$can_delete = true;
			} else {
				$query_creator =& $GLOBALS['system']->getDBObject('staff_member', $this->_query->getValue('creator'));
				if (!$query_creator->getValue('active')) {
					$can_delete = true;
				}
			}
			if ($can_delete) {
				$this->_query->delete();
				add_message('Query deleted');
				$this->_query = NULL;
			}
		}
		if (!empty($_POST['show_result_count_queryids'])) {
			foreach($_POST['show_result_count_queryids'] as $queryid) {
				$query = new Person_Query($queryid);
				$this->_result_counts[$queryid] = $query->getResultCount();
			}
		}
		if (!empty($_POST['query_submitted'])) {
			$this->_query->processForm();
			if ($this->_query->id) {
				$this->_query->save();
			} else {
				$this->_query->create();
			}
			if (!empty($_REQUEST['return'])) {
				$this->_query = NULL;
			}
		}
	}
	
	function getTitle()
	{
		if ($this->_query) {
			if ($this->_query->id) {
				if (empty($_REQUEST['execute'])) {
					return 'Configure Person Report';
				} else {
					if ($this->_query->getValue('name')) {
						return $this->_query->getValue('name');
					} else {
						return 'Person Report Results';
					}
				}
			} else {
				return 'Configure Person Report';
			}
		} else {
			return 'Person Reports';
		}
	}
	
	function printView()
	{
		if (!empty($_REQUEST['execute'])) {
			$this->_query->printResults();
			?>
			<p style="clear:both"><br />
			<a class="no-print" href="?view=<?php echo htmlentities($_REQUEST['view']); ?>&queryid=<?php echo $this->_query->id; ?>">Return to report configuration</a> &nbsp;
			<a class="no-print" href="?view=<?php echo htmlentities($_REQUEST['view']); ?>">Return to list of reports</a> &nbsp;
			<a class="no-print" href="?call=report_csv&queryid=<?php echo $this->_query->id; ?>">Get CSV</a>
			</p>
			<?php
		} else if (!empty($this->_query)) {
			?>
			<form method="post">
				<input type="hidden" name="query_submitted" value="1" />
				<?php
				$this->_query->printForm();
				?>
				<h3>&nbsp</h3>
				<input type="submit" name="execute" value="Save and view results" />
				<input type="submit" name="return" value="Save and return to report list" />
				<a href="?view=<?php echo htmlentities($_REQUEST['view']); ?>"><input type="button" value="Cancel and return to report list" /></a>

			</form>
			<?php
		} else {
			?>
			<h3>New and Ad-hoc reports</h3>
			<ul>
				<li><a href="?view=<?php echo htmlentities($_REQUEST['view']); ?>&queryid=0">Create a new report from scratch</a>
			<?php
			if (!empty($_SESSION['saved_query'])) {
				?>
				<li><a href="?view=<?php echo htmlentities($_REQUEST['view']); ?>&queryid=TEMP">View the configuration of your most recent ad-hoc report</a></li>
				<li><a href="?view=<?php echo htmlentities($_REQUEST['view']); ?>&queryid=TEMP&execute=1">View the results of your most recent ad-hoc report</a></li>
				<?php
			}
			?>
			</ul>

			<h3>Saved Reports</h3>
			<?php
			$saved_reports = $GLOBALS['system']->getDBObjectData('person_query', Array(), '', 'name');
			if (empty($saved_reports)) {
				?>
				<i>There are not yet any reports saved in the system</i>
				<?php
			} else {
				?>
				<form method="post">
				<table class="standard hoverable">
					<thead>
						<tr>
							<th>ID</th>
							<th>Report Name</th>
							<th>Actions</th>
							<th>Select</th>
						<?php
						if (!empty($this->_result_counts)) {
							?>
							<th>Result Count</th>
							<?php
						}
						?>
						</tr>
					</thead>
					<tbody>
					<?php
					$saved_reports = $GLOBALS['system']->getDBObjectData('person_query', Array(), '', 'name');
					$staff_members = $GLOBALS['system']->getDBObjectData('staff_member');
					$current_user_id = $GLOBALS['user_system']->getCurrentUser('id');
					foreach ($saved_reports as $id => $details) {
						?>
						<tr>
							<td><?php echo (int)$id; ?></td>
							<td><?php echo $details['name']; ?></td>
							<td>
								<a href="?view=<?php echo htmlentities($_REQUEST['view']); ?>&queryid=<?php echo $id; ?>">Configure</a> &nbsp;
								<a href="?view=<?php echo htmlentities($_REQUEST['view']); ?>&queryid=<?php echo $id; ?>&execute=1">View Results</a> &nbsp;
								<a href="?call=email&queryid=<?php echo $id; ?>" class="hidden-frame">Send Email</a>
							<?php
							if ($GLOBALS['user_system']->havePerm(PERM_MANAGEREPORTS)) {
								?>
								&nbsp;
								<a href="?view=<?php echo htmlentities($_REQUEST['view']); ?>&queryid=<?php echo $id; ?>&delete=1" class="confirm-delete-report">Delete</a>
								<?php
							}
							?>
							</td>
							<td><input type="checkbox" name="show_result_count_queryids[]" value="<?php echo (int)$id; ?>" <?php if (isset($this->_result_counts[$id])) echo 'checked="checked"'; ?> /></td>
						<?php
						if (!empty($this->_result_counts)) {
							?>
							<td><b><?php if (isset($this->_result_counts[$id])) echo (int)$this->_result_counts[$id]; ?></b></td>
							<?php
						}
						?>
						</tr>
						<?php
					}
					?>
					</tbody>
				</table>
				<input type="submit" name="show_result_count" value="Show result counts for selected reports (could be slow)" />
				</form>
				<?php
			}
		}
	}


}
?>
