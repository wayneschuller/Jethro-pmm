<?php
class View_Admin__Date_Types extends View
{
	static function getMenuPermissionLevel()
	{
		$features = explode(',', ENABLED_FEATURES);
		if (in_array('DATES', $features)) {
			return PERM_SYSADMIN;
		} else {
			return -1;
		}
	}

	function getTitle()
	{
		return 'Configure Date Types';
	}

	function processView()
	{
		if (!empty($_POST['datetypename'])) {
			$to_add = $to_delete = $to_update = Array();
			foreach ($_POST['datetypename'] as $i => $name) {
				if (!empty($_POST['datetypeid'][$i])) {
					if ($name != '') {
						$to_update[$_POST['datetypeid'][$i]] = $name;
					} else {
						$to_delete[] = (int)$_POST['datetypeid'][$i];
					}
				} else if ($name != '') {
					$to_add[] = $name;
				}
			}
			foreach ($to_add as $name) {
				$SQL = 'INSERT INTO date_type (name)
						VALUES ('.$GLOBALS['db']->quote($name).')';
				$res = $GLOBALS['db']->query($SQL);
				check_db_result($res);
			}
			foreach ($to_update as $id => $name) {
				$SQL = 'UPDATE date_type
						SET name = '.$GLOBALS['db']->quote($name).'
						WHERE id = '.(int)$id;
				$res = $GLOBALS['db']->query($SQL);
				check_db_result($res);
			}
			if (!empty($to_delete)) {
				// aleady cast as ints above
				$SQL = 'DELETE FROM date_type
						WHERE id IN ('.implode(',', $to_delete).')';
				$res = $GLOBALS['db']->query($SQL);
				check_db_result($res);
			}
		}
	}

	function printView()
	{
		$GLOBALS['system']->includeDBClass('person');
		$types = Person::getDateTypes();
		if (empty($types)) {
			?>
			<p><i>No date types have been set up in the system yet.</i></p>
			<?php
			$types = Array('' => '');
		}
		?>
		<form method="post">
		<table class="expandable">
			<thead>
			</thead>
			<tbody>
			<?php
			$i = 0;
			foreach ($types as $id => $name) {
				?>
				<tr>
					<td>
						<input type="hidden" name="datetypeid[_<?php echo $i; ?>_]" value="<?php echo $id; ?>"/>
						<input name="datetypename[_<?php echo $i; ?>_]" value="<?php echo htmlentities($name); ?>" />
					</td>
				</tr>
				<?php
				$i++;
			}
			?>
			</tbody>
		</table>
		<input type="submit" value="Save" />
		</form>
		<?php
	}
}
?>
