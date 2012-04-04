<?php
class View_Groups__Manage_Categories extends View
{
	var $_all_categories;

	static function getMenuPermissionLevel()
	{
		return PERM_MANAGEGROUPCATS;
	}

	function processView()
	{
		if (!empty($_POST['delete_category_id'])) {
			$cat = $GLOBALS['system']->getDBObject('person_group_category', (int)$_POST['delete_category_id']);
			$cat->delete();
			add_message('Category deleted');
		}
		$this->_all_categories = $GLOBALS['system']->getDBObjectData('person_group_category', Array(), 'OR', 'name');
	}
	
	function getTitle()
	{
		return 'Person Group Categories';
	}


	function printView()
	{
		?>
		<div class="action-box standard">
			<h3>Actions</h3>
			<a href="?view=_add_group_category">Add a new group category</a>
		</div>
		<p class="smallprint">A person group can be uncategorised, or it can belong to one category.  <br />Each category can stand at the top level, or be a sub-category of another category.</p>
		<div class="next-to-action" style="width: 80ex">
		<h3>Current Group Categories</h3>
		<div style="width: 90%">
		<?php
		$this->_printCategories();
		?>
		</div>
		</div>
		<?php
	}

	function _printCategories($parent=0)
	{
		$this_level = Array();
		foreach ($this->_all_categories as $id => $details) {
			if ($details['parent_category'] == $parent) {
				$this_level[$id] = $details;
			}
		}
		if (!empty($this_level)) {
			?>
			<ul style="width: auto">
				<?php
				foreach ($this_level as $id => $details) {
					?>
					<li>
						<form class="float-right" style="clear: both" method="post" onsubmit="return confirm('Are you sure you want to delete this category?')">
							<input type="hidden" name="delete_category_id" value="<?php echo $id; ?>" />
							<label class="clickable submit">delete</label>
						</form>
						<a class="float-right" href="?view=_edit_group_category&categoryid=<?php echo (int)$id;?>">edit</a>
						
						<a href="?view=groups__list_all#cat<?php echo $id; ?>"><?php echo htmlentities($details['name']); ?></a>
					<?php
					$this->_printCategories($id);
					?>
					</li>
					<?php
				}
				?>
			</ul>
			<?php
		}
	}

}
?>
