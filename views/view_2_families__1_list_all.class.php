<?php
include_once 'include/paginator.class.php';
class View_Families__List_All extends View
{
	var $_family_data;
	var $_paginator;

	function processView()
	{
		$params = Array();
		if (empty($_REQUEST['show_archived'])) {
			$params['!status'] = 'archived';
		}
		if (empty($_SESSION['total_families'])) {
			$_SESSION['total_families'] = $GLOBALS['db']->queryOne('SELECT count(*) from _person');
		}
		if (!empty($_REQUEST['slice_size'])) {
			$this->_paginator = new Paginator((float)$_REQUEST['slice_size'], (int)$_REQUEST['slice_num']);
			$params['-SUBSTRING(family.family_name, 1, 1)'] = $this->_paginator->getCurrentSliceStartEnd();
		} else if ($_SESSION['total_families'] > CHUNK_SIZE) {
			$num_chunks = ceil($_SESSION['total_families'] / CHUNK_SIZE);
			$this->_paginator = new Paginator(26 / $num_chunks, 1);
			$params['-SUBSTRING(family.family_name, 1, 1)'] = $this->_paginator->getCurrentSliceStartEnd();
		}
		$this->_family_data = ($GLOBALS['system']->getDBObjectData('family', $params, 'AND', 'family_name'));
	}

	
	function getTitle()
	{
		$res = 'All Families';
		return $res;

	}

	
	function printView()
	{
		if (empty($_REQUEST['show_archived'])) {
			echo '<p class="float-right"><a href="'.build_url(Array('show_archived' => 1)).'">Include Archived families</a></p>';
		} else {
			echo '<p class="float-right"><a href="'.build_url(Array('show_archived' => NULL)).'">Exclude Archived families</a></p>';
		}

		if ($this->_paginator) {
			echo '<p>';
			$this->_paginator->printPageNav();
			echo '</p>';
		}

		$GLOBALS['system']->includeDBClass('family');
		$families =& $this->_family_data;
		if (empty($families)) {
			if ($this->_paginator) {
				?>
				<p><strong>No families in this range</strong></p>
				<?php
			} else {
				?>
				<p><strong>No families were found</strong></p>
				<a href="<?php echo build_url(Array('show_archived' => 1)); ?>">Include Archived families</a>
				<?php
			}
		} else {
			if ($this->_paginator) {
				echo '<p><strong>'.count($families).' families in this range</strong></p>';
			} else  {
				echo '<p><strong>'.count($families).' families in total</strong></p>';
			}
			include dirname(dirname(__FILE__)).'/templates/family_list.template.php';
		}
	}
}
?>
