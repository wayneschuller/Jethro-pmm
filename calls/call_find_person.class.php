<?php
class Call_Find_Person extends Call
{
	function run()
	{
		$results = Array();
		if (!empty($_REQUEST['search'])) {
			$name = $_REQUEST['search'];
			$GLOBALS['system']->includeDBClass('person');
			$results = Person::getPersonsByName($name);
		}
		?>
		<html>
			<head>
				<?php include 'templates/head.template.php'; ?>
				<script type="text/javascript">
					function selectResult(tr)
					{
						window.parent.document.getElementById('new-member-id').value = tr.id;
						window.parent.document.getElementById('new-member-search').value = tr.getElementsByTagName('TD')[0].innerHTML + ' (#'+tr.id+')';
						$(window.parent.document.getElementById('new-member-search')).addClass('found');
						window.parent.document.getElementById('new_member_iframe').style.display = 'none';
						window.parent.document.getElementById('add-member-button').disabled = false;
					}
					window.onload = function() {
						var us = document.getElementsByTagName('TR');
						for (var i=0; i < us.length; i++) {
							us[i].onclick = new Function("selectResult(this);");
						}
					};
				</script>
			</head>
			<body style="margin: 10px; padding: 0px" id="body">
				<?php
				if (empty($results)) {
					echo '(No results, try searching again)';
				} else {
					$GLOBALS['system']->includeDBClass('person');
					$dummy = new Person();
					?>
					Click one of the matching rows to continue
					<table class="standard clickable-rows hoverable" style="width: 100%">
					<?php
					foreach ($results as $i => $details) {
						$dummy->populate($i, $details);
						?>
						<tr id="<?php echo $i; ?>">
							<td><?php echo $dummy->toString(); ?></td>
							<td><?php $dummy->printFieldValue('status'); ?></td>
							<td><?php $dummy->printFieldValue('congregationid'); ?></td>
						</tr>
						<?php
					}
					?>
					</table>
					<?php
				}
				?>
			</body>
		</html>
		<?php
	}
}
?>
