<?php
class View_Groups__Search extends View
{
	var $_group_data = NULL;

	function getTitle()
	{
		return 'Group Search Results for "'.htmlentities(array_get($_REQUEST, 'name', '')).'"';
	}

	function processView()
	{
		if (!empty($_REQUEST['name'])) {
			$this->_group_data = $GLOBALS['system']->getDBObjectData('person_group', Array('name' => array_get($_REQUEST, 'name', '')), 'OR', 'name');
			if (empty($this->_group_data)) {
				$this->_group_data = $GLOBALS['system']->getDBObjectData('person_group', Array('name' => '%'.array_get($_REQUEST, 'name', '').'%'), 'OR', 'name');
			}
		}
		if (count($this->_group_data) == 1) {
			add_message("One group found");
			redirect('groups', Array('groupid' => key($this->_group_data), 'name' => NULL)); // exits
		}
	}
	
	function printView()
	{
		if (empty($this->_group_data)) {
			echo 'No matching groups found';
			return;
		}
		?>
		<table class="standard hoverable clickable-rows">
			<thead>
				<tr>
					<th>ID</th>
					<th>Name</th>
					<th>Members</th>
				</tr>
			</thead>
			<tbody>
			<?php
			foreach ($this->_group_data as $id => $details) {
				?>
				<tr>
					<td><a href="?view=groups&groupid=<?php echo $id; ?>"><?php echo $id; ?></a></td>
					<td><?php echo $details['name']; ?></td>
					<td><?php echo $details['member_count']; ?></td>
				</tr>
				<?php
			}
			?>
			</tbody>
		</table>
		<?php
	}
}
?>
