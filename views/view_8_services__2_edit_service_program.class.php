<?php
require_once str_replace('2_edit', '1_display', __FILE__);
class View_Services__Edit_Service_Program extends View_Services__Display_Service_Program
{
	var $_failed_congs = Array();
	var $_saved = false;

	static function getMenuPermissionLevel()
	{
		return PERM_BULKSERVICE;
	}

	function processView()
	{
		parent::processView();

		if (empty($_REQUEST['program_submitted'])) {
			foreach ($this->_congregations as $id) {
				$cong = $GLOBALS['system']->getDBObject('congregation', $id);
				if (!($cong->canAcquireLock('services') && $cong->acquireLock('services'))) {
					$this->_failed_congs[] = $id;
					unset($this->_congregations[$id]);
				}
			}
		} else {
			foreach ($this->_congregations as $id) {
				$cong = $GLOBALS['system']->getDBObject('congregation', $id);
				if (!$cong->haveLock('services') && !($cong->canAcquireLock('services') && $cong->acquireLock('services'))) {
					$this->_failed_congs[] = $id;
					unset($this->_congregations[$id]);
				}
			}

			$this->init();

			// Update and/or create services on existing dates
			$dummy =& new Service();
			foreach ($this->_grouped_services as $date => $date_services) {
				foreach ($this->_congregations as $congid) {
					if (isset($date_services[$congid])) {
						// update the existing service
						$dummy->populate($date_services[$congid]['id'], $date_services[$congid]);
						if ($dummy->acquireLock()) {
							$this->_processServiceCell($congid, $date, $dummy);
							$dummy->save();
							$dummy->releaseLock();
						} else {
							trigger_error("Could not acquire lock on individual service for $congid on $date - didn't save");
						}
					} else if (!empty($_POST['topic_title'][$congid][$date]) || !empty($_POST['format_title'][$congid][$date]) || !empty($_POST['bible_ref0'][$congid][$date])) {
						// create a new service
						$service = new Service();
						$service->setValue('date', $date);
						$service->setValue('congregationid', $congid);
						$this->_processServiceCell($congid, $date, $service);
						$service->create();
					}
				}
			}

			// Add services on new dates
			$i = 0;
			while (isset($_POST['new_service_date_d'][$i])) {
				foreach ($this->_congregations as $congid) {
					if (!empty($_POST['topic_title'][$congid]['new_'.$i]) || !empty($_POST['format_title'][$congid]['new_'.$i]) || !empty($_POST['bible_refs'][$congid]['new_'.$i][0]) ||
					!empty($_POST['bible_refs'][$congid]['new_'.$i][1])) {
						// we need to create a service here
						$service = new Service();
						$service->setValue('date', process_widget('new_service_date['.$i.']', Array('type' => 'date')));
						$service->setValue('congregationid', $congid);
						$this->_processServiceCell($congid, 'new_'.$i, $service);
						$service->create();
					}
				}
				$i++;
			}

			// Process the "delete" commands if necessary
			if (!empty($_POST['delete_single'])) {
				$service = $GLOBALS['system']->getDBOBject('service', (int)$_POST['delete_single']);
				if ($service) {
					$service->delete();
					if (!empty($_POST['shift_after_delete'])) {
						Service::shiftServices(Array($service->getValue('congregationid')), $service->getValue('date'), '-7');
					}
				}
			}
			if (!empty($_POST['delete_all_date'])) {
				$services = $GLOBALS['system']->getDBObjectData('service', Array('date' => $_POST['delete_all_date'], 'congregationid' => $this->_congregations), 'AND');
				$dummy = new Service();
				foreach ($services as $id => $details) {
					$dummy->populate($id, $details);
					$dummy->delete();
				}
				if (!empty($_POST['shift_after_delete'])) {
					Service::shiftServices($this->_congregations, $_POST['delete_all_date'], '-7');
				}
			}

			// Process the "insert" commands if necessary
			if (!empty($_POST['insert_all_date'])) {
				Service::shiftServices($this->_congregations, $_POST['insert_all_date'], '7');
			}
			if (!empty($_POST['insert_single_date'])) {
				foreach ($_POST['insert_single_date'] as $congid => $date) {
					Service::shiftServices(Array($congid), $date, '7');
				}
			}

			foreach ($this->_congregations as $id) {
				$cong = $GLOBALS['system']->getDBObject('congregation', $id);
				$cong->releaseLock('services');
			}

			add_message("Services saved");
			redirect('services__display_service_program');
		}
	}

	function printView()
	{
		if (!empty($this->_failed_congs)) {
			if (empty($_REQUEST['program_submitted'])) {
				if (empty($this->_congregations)) {
					print_message('Services for the selected congregations cannot be edited currently because another user has the lock.  Wait for them to finish and then try again', 'failure');
				} else {
					print_message('Another user is currently editing services for congregation(s)   #'.implode(', #', $this->_failed_congs).'.  To edit services for those congregations, wait for the other user to finish and then try again');
				}
			} else {
				print_message('ERROR: Could not save details for congregations "'.implode(', ', $this->_failed_congs).'" because the lock had expired and could not be re-acquired', 'failure');
			}
		}
		if ($this->_saved) {
			print_message('Services saved', 'success');
		}
		parent::printView();
	}

	function getTitle()
	{
		return 'Edit Service Program';

	}

	function printQuickNavLinks()
	{
		?>
		<a href="<?php echo build_url(Array('view' => 'services__display_service_program')); ?>">Show the read-only version</a>
		<?php
	}

	function _printServiceProgram()
	{
		if ($this->_saved) return; // just saved, have released locks
		if (empty($_REQUEST['congregations'])) return;

		?>
		<script>
			$(window).load(function() {

				setTimeout('showLockExpiryWarning()', <?php echo (strtotime('+'.LOCK_LENGTH, 0)-60)*1000; ?>);
				setTimeout('showLockExpiredWarning()', <?php echo (strtotime('+'.LOCK_LENGTH, 0))*1000; ?>);

				$('.confirm-shift').click(function() {
					$('#'+this.name).val(this.value);
					$('#shift-confirm-popup').show();
					return false;
				});
				$('.confirm-delete').click(function() {
					return confirm("Really delete service?");
				});

				$('.notes-icon').click(function() {
					$(this).parents('tr:first').next('tr:first').toggle();
				});
				$('.copy-left').click(function() {
					var targetCell = $(this).parents('td:first').prev('td:first').prev('td:first');
					var sourceCell = $(this).parents('td:first').next('td:first');
					copyServiceDetails(sourceCell, targetCell);
				});
				$('.copy-right').click(function() {
					var targetCell = $(this).parents('td:first').next('td:first').next('td:first');
					var sourceCell = $(this).parents('td:first').prev('td:first');
					copyServiceDetails(sourceCell, targetCell);
				});
				function copyServiceDetails(sourceCell, targetCell)
				{
					// copy by transplanting the whole table and re-naming the inputs
					var topicTitlePrefix = 'topic_title';
					var targetCellFieldnameSuffix = targetCell.find('input[name^='+topicTitlePrefix+']:first').attr('name').substr(topicTitlePrefix.length);
					var sourceCellFieldnameSuffix = sourceCell.find('input[name^='+topicTitlePrefix+']:first').attr('name').substr(topicTitlePrefix.length);
					var targetTable = targetCell.find('table.service-details');
					var replacementTable = sourceCell.find('table.service-details').clone(true);
					replacementTable.find('input, textarea').each(function() {
						if (this.name) {
							this.name = this.name.replace(sourceCellFieldnameSuffix, targetCellFieldnameSuffix);
						}
					});

					targetTable.after(replacementTable);
					targetTable.remove();
				}
			});
			function cancelShiftConfirmPopup()
			{
				$('#shift-confirm-popup').hide();
				$('#delete_all_date').val('');
			}
		</script>

		<form method="post" class="warn-unsaved">
		<input type="hidden" name="program_submitted" value="1" />
		<!-- the following hidden fields preserve the value of an image input whose click
		     is intercepted by a confirm-shift popup -->
		<input type="hidden" name="delete_single" value="" id="delete_single" />
		<input type="hidden" name="delete_all_date" value="" id="delete_all_date" />

		<table class="standard" style="width: 1%">
			<thead>
				<tr>
					<th width="1%">Date</th>
				<?php
				foreach ($this->_congregations as $congid) {
					$cong = $GLOBALS['system']->getDBObject('congregation', (int)$congid);
					?>
					<th colspan="3"><?php echo htmlentities($cong->getValue('name')); ?></th>
					<?php
				}
				?>
				</tr>
			</thead>
			<tbody>
			<?php
			// Print rows for existing services
			if (empty($this->_grouped_services)) {
				$last_date = date('Y-m-d', strtotime($this->_start_date.' -8 days'));
			} else {
				$last_date = key(array_reverse($this->_grouped_services));
			}
			$last_cong = count($this->_congregations) -1;
			$this_sunday = date('Y-m-d', strtotime('Sunday'));
			$last_date = key($this->_grouped_services);
			$new_service_i = 0;
			foreach ($this->_grouped_services as $date => $services) {
				// first, print a blank one if necessary
				$last_date_plus_week = date('Y-m-d', strtotime($last_date.' +1 week'));
				while ($last_date_plus_week < $date) {
					// it's been more than a week since the last service
					// so print a blank one in between
					$this->_printNewServiceRow($new_service_i++, $last_date_plus_week);
					$last_date_plus_week = date('Y-m-d', strtotime($last_date_plus_week.' +1 week'));
				}

				// Now print the service we actually have
				$class_clause = ($date == $this_sunday) ? 'class="tblib-hover"' : '';
				?>
				<tr class="no-padding">
					<td class="center"><input type="image" class="confirm-title" name="insert_all_date" value="<?php echo $date; ?>" src="<?php echo BASE_URL; ?>/resources/expand_up_down_green_small.png" title="Create a blank week here for ALL CONGREGATIONS by moving all the following services down" /></td>
				<?php
				foreach ($this->_congregations as $congid) {
					?>
					<td colspan="3" class="center"><input type="image" class="confirm-title" name="insert_single_date[<?php echo $congid; ?>]" value="<?php echo $date; ?>" src="<?php echo BASE_URL; ?>/resources/expand_up_down_green_small.png" title="Create a blank week here for this congregation by moving all the following services down" /></td>
					<?php
				}
				?>
				</tr>
				<tr <?php echo $class_clause; ?>>
					<td class="nowrap center narrow"><strong><?php echo date('j M y', strtotime($date)); ?></strong><br />
					<input type="image" name="delete_all_date" value="<?php echo $date; ?>" src="<?php echo BASE_URL; ?>/resources/cross_red.png" class="confirm-shift" title="Delete all services on this date" /></td>
				<?php
				foreach ($this->_congregations as $i => $congid) {
					?>
					<td class="narrow no-right-border no-padding middle">
						<?php if ($i != 0) echo '<img src="'.BASE_URL.'/resources/arrow_left_heavy_blue.png" class="clickable copy-left" title="Click to copy this service\'s details to the previous congregation" />'; ?>
					</td>
					<td class="narrow no-side-borders">
						<?php $this->_printServiceCell($congid, $date, array_get($services, $congid, Array())); ?>
					</td>
					<td class="narrow no-left-border no-padding right middle" width="80">
						<?php
						if (isset($services[$congid])) {
							?>
							<div style="position: absolute; margin-top: 20px;">
							<input type="image" name="delete_single" value="<?php echo $services[$congid]['id']; ?>" src="<?php echo BASE_URL; ?>/resources/cross_red.png" class="confirm-shift" title="Delete this service" />
							</div>
							<?php
						}
						if ($i != $last_cong) {
							echo '<img src="'.BASE_URL.'/resources/arrow_right_heavy_blue.png" class="clickable copy-right" title="Click to copy this service\'s details to the next congregation" />'; 
						} else {
							// yuck - so old school - but the only thing that works
							echo '<img src="'.BASE_URL.'/resources/arrow_right_heavy_blue.png" style="visibility: hidden" />'; 
						}
						?>
					</td>
					<?php
				}
				?>
				</tr>
				<?php
				$last_date = $date;
			}

			// Print rows for new services
			$running_date = date('Y-m-d', strtotime('Sunday', strtotime($last_date.' +1 week')));
			while ($running_date < $this->_end_date) {
				$this->_printNewServiceRow($new_service_i, $running_date);
				$running_date = date('Y-m-d', strtotime($running_date.' +1 week'));
				$new_service_i++;
			}
			?>
			</tbody>
		</table>
		<input type="submit" value="Save" accesskey="s" />

		<div id="shift-confirm-popup" class="standard popup-box center">
			<p><b>After deleting, would you like to move the following services up a week to close the gap?</b></p>
			<input type="submit" name="shift_after_delete" value="Yes" />
			<input type="submit" value="No" />
			<input type="button" value="Cancel" onclick="cancelShiftConfirmPopup()" />
		</div>
		</form>
		<?php
	}


	function _printNewServiceRow($i, $running_date)
	{
			?>
			<tr class="nowrap">
				<td colspan="1"><?php print_widget('new_service_date['.$i.']', Array('type' => 'date', 'month_format' => 'M'), $running_date); ?></td>
			<?php
			$j = 0;
			foreach ($this->_congregations as $congid) {
				?>
				<td class="narrow no-right-border no-padding left middle">
					<?php if ($j != 0) echo '<img src="'.BASE_URL.'/resources/arrow_left_heavy_blue.png" class="clickable copy-left" title="Click to copy this service\'s details to the previous congregation" />'; ?>
				</td>
				<td class="no-side-borders">
					<?php $this->_printServiceCell($congid, 'new_'.$i, Array()); ?>
				</td>
				<td class="narrow no-left-border no-padding right middle">
					<?php if ($j != count($this->_congregations) -1) echo '<img src="'.BASE_URL.'/resources/arrow_right_heavy_blue.png" class="clickable copy-right" title="Click to copy this service\'s details to the next congregation" />'; ?>
				</td>
				<?php
				$j++;
			}
			?>
			</tr>
			<?php
		}


	function _printServiceCell($congid, $date, $data)
	{
		$notes_icon_src = empty($data['notes']) ? 'resources/notes_icon_none.png' : 'resources/notes_icon_some.png';
		?>
		<table class="compact service-details">
			<tr>
				<th>Topic</th>
				<td>
					<input type="text" name="topic_title[<?php echo $congid; ?>][<?php echo $date; ?>]" size="36" value="<?php echo htmlentities(array_get($data, 'topic_title')); ?>" />
				</td>
			</tr>
			<tr>
				<th>Texts</th>
				<td>
					<table class="expandable underline-rows hoverable">
					<?php
					$readings = array_get($data, 'readings');
					if (empty($readings)) {
						$readings = Array(Array('to_read' => 1));
					}
					foreach ($readings as $reading) {
						?>
						<tr>
							<td>
								<input type="text" name="bible_refs[<?php echo $congid; ?>][<?php echo $date; ?>][]" style="width: 95%" class="bible-ref" value="<?php echo htmlentities($this->_formatBible(array_get($reading, 'bible_ref', ''))); ?>" />
							</td>
							<td class="middle">
								
								<span title="to be read" class="preserve-value">
									R
									<?php
									/*  because checkboxes themselves don't get don't get submitted if not checked, and 
									we need the "to_read" etc fields to match up with the actual bible refs,
									we use a hidden field for submission, and rely on JS in tb_lib to adjust
									the hidden field when the checkbox is clicked */
									?>
									<input type="checkbox"  style="margin: 0 2px 0 0px" class="toggle-next-hidden" />
									<input type="hidden" name="bible_to_read[<?php echo $congid; ?>][<?php echo $date; ?>][]" value="<?php echo (int)array_get($reading, 'to_read'); ?>" />
								</span>

								<span title="to be preached on">
									P
									<input type="checkbox" style="margin: 0 2px 0 0px" class="toggle-next-hidden bible-to-preach" />
									<input type="hidden" name="bible_to_preach[<?php echo $congid; ?>][<?php echo $date; ?>][]" value="<?php echo (int)array_get($reading, 'to_preach'); ?>" />
								</span>
								
								<img src="<?php echo BASE_URL; ?>/resources/arrow_up_thin_black.png" class="icon move-row-up" title="Move up" />
								<img src="<?php echo BASE_URL; ?>/resources/arrow_down_thin_black.png" class="icon move-row-down" title="Move down" />
								&nbsp;
							</td>
						</tr>
						<?php
					}
					?>
					</table>
				</td>
			</tr>
			<tr>
				<th>Format</th>
				<td>
					<input type="text" name="format_title[<?php echo $congid; ?>][<?php echo $date; ?>]" style="width: 85%" value="<?php echo array_get($data, 'format_title'); ?>" />
					<img class="notes-icon clickable" src="<?php echo $notes_icon_src; ?>" style="height: 12px; width: 12px; ;margin: 2px; vertical-align: bottom" title="Show notes" />
				</td>
			</tr>
			<tr class="hidden">
				<th>Notes</th>
				<td><textarea style="width: 100%"name="notes[<?php echo $congid; ?>][<?php echo $date; ?>]"><?php echo htmlentities(array_get($data, 'notes')); ?></textarea></td>
			</tr>
		</table>
		<?php
	}

	// we want read-only bible refs within editing boxes
	function _formatBible($raw)
	{
		if ($raw) {
			$br = new Bible_Ref($raw);
			return $br->toShortString();
		}
		return '';
	}


	function _processServiceCell($congid, $date, $service)
	{
		if (!isset($_POST['topic_title'][$congid][$date])) return;
		$service->setValue('topic_title', $_POST['topic_title'][$congid][$date]);
		$service->setValue('format_title', $_POST['format_title'][$congid][$date]);
		$service->setValue('notes', $_POST['notes'][$congid][$date]);
		$service->clearReadings();

		foreach ($_POST['bible_refs'][$congid][$date] as $i => $bible_ref) {
			if (!empty($bible_ref)) {
				$to_read = $_POST['bible_to_read'][$congid][$date][$i];
				$to_preach = $_POST['bible_to_preach'][$congid][$date][$i];
				$service->addReading($bible_ref, $to_read, $to_preach);
			}
		}
	}
}
?>
