<?php
require_once 'include/bible_ref.class.php';
class View_Services__Display_Service_Program extends View
{
	var $_start_date = NULL;
	var $_end_date = NULL; 
	var $_congregations = Array();
	var $_grouped_services = Array();
	var $_dummy_service = NULL;

	static function getMenuPermissionLevel()
	{
		return PERM_VIEWSERVICE;
	}

	function processView()
	{
		// Get the congregations and make sure they're in order
		if (!empty($_REQUEST['congregations'])) {
			$this->_congregations = array_keys($GLOBALS['system']->getDBObjectData('congregation', Array('!meeting_time' => ''), 'OR', 'meeting_time'));
			foreach ($this->_congregations as $i => $v) {
				if (!in_array($v, $_REQUEST['congregations'])) {
					unset($this->_congregations[$i]);
				}
			}
			$this->_congregations = array_values($this->_congregations); // re-index
		}
		$this->_start_date = process_widget('start_date', Array('type' => 'date'), date('Y-m-d'));
		$this->_end_date = process_widget('end_date', Array('type' => 'date'), date('Y-m-d'));
	}

	function getTitle()
	{
		return 'Display Service Program';

	}


	function printView()
	{
		$this->init();
		$this->_printParamsForm();
		$this->_printServiceProgram();
	}

	function init()
	{
		if (is_null($this->_start_date)) $this->_start_date = date('Y-m-d');
		if (is_null($this->_end_date)) $this->_end_date = date('Y-m-d', strtotime('+'.ROSTER_WEEKS_DEFAULT.' weeks'));
		$GLOBALS['system']->includeDBClass('service');
		$this->_dummy_service = new Service();


		$this->_grouped_services = Array();
		if ($this->_congregations && $this->_start_date && $this->_end_date) {
			// Get the relevant services and group by date
			$params = Array(
						'congregationid' => $this->_congregations,
						'>date'			 => date('Y-m-d', strtotime($this->_start_date.'-1 day')),
						'<date'			 => date('Y-m-d', strtotime($this->_end_date.'+1 day')),
					  );
			$services = $GLOBALS['system']->getDBObjectData('service', $params, 'AND', 'date');
			foreach ($services as $id => $details) {
				$details['id'] = $id;
				$this->_grouped_services[$details['date']][$details['congregationid']] = $details;
			}
		}

	}

	function _printServiceProgram()
	{
		if (empty($this->_congregations)) return;
		?>
		<table class="standard roster">
			<thead>
				<tr>
					<th width="1%">Date</th>
				<?php
				foreach ($this->_congregations as $congid) {
					$cong = $GLOBALS['system']->getDBObject('congregation', (int)$congid);
					?>
					<th width="<?php echo floor(100 / count($this->_congregations)); ?>%"><?php echo htmlentities($cong->getValue('name')); ?></th>
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
				foreach ($this->_grouped_services as $date => $services) {
					?>
					<tr<?php if ($date == date('Y-m-d', strtotime('Sunday'))) echo ' class="tblib-hover"'; ?>>
						<td class="nowrap center narrow"><strong><?php echo date('j M y', strtotime($date)); ?></strong></td>
					<?php
					foreach ($this->_congregations as $i => $congid) {
						?>
						<td>
							<?php $this->_printServiceCell($congid, $date, array_get($services, $congid, Array())); ?>
						</td>
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
	}

	function _formatBible($raw)
	{
		if ($raw) {
			$br = new Bible_Ref($raw);
			return $br->getLinkedShortString();
		}
		return '';
	}

	function _printParamsForm()
	{
		$congs = $GLOBALS['system']->getDBObjectData('congregation', Array('!meeting_time' => ''), 'OR', 'meeting_time');
		if (empty($congs)) {
			print_message("To edit services you must first go to admin > congregations and set the 'code name' for the relevant congregations", 'failure');
			return;
		}
		?>
		<form method="get" class="standard">
		<input type="hidden" name="view" value="<?php echo htmlentities($_REQUEST['view']); ?>" />
		<table>
			<tr>
				<td rowspan="3" class="nowrap" style="padding-right: 2ex">
					<b>For congregations</b><br />
					<?php
					
					foreach ($congs as $id => $details) {
						?>
						<input type="checkbox" name="congregations[]" 
						<?php
						if (in_array($id, $this->_congregations)) echo 'checked="checked" ';
						?>
						value="<?php echo $id; ?>" id="congregations_<?php echo $id; ?>" /><label for="congregations_<?php echo $id; ?>"><?php echo htmlentities($details['name']); ?></label><br />
						<?php
					}
					?>
				</td>
				<td><b>from</b></td>
				<td class="nowrap"><?php print_widget('start_date', Array('type' => 'date'), $this->_start_date); ?></td>
			</tr>
			<tr>
				<td><b>to</b></td>
				<td class="nowrap"><?php print_widget('end_date', Array('type' => 'date'), $this->_end_date); ?></td>
			</tr>
			<tr>
				<td></td>
				<td style="vertical-align: bottom; text-align: right"><input type="submit" value="Go" /></td>
			</tr>
		</table>
		</form>
		<p class="smallprint no-print"> <?php $this->printQuickNavLinks(); ?></p>
		<?php
	}

	function printQuickNavLinks()
	{
		if (empty($this->_congregations)) return;
		if ($GLOBALS['user_system']->havePerm(PERM_BULKSERVICE)) {
			?>
			<a href="<?php echo build_url(Array('view' => 'services__edit_service_program')); ?>">Edit this service program</a>
			<?php
		}
	}

	function _printServiceCell($congid, $date, $data)
	{
		if (empty($data)) return;
		$this->_dummy_service->populate($data['id'], $data);
		$this->_dummy_service->printFieldValue('summary');

	}
}
?>
