<?php
class Call_email extends Call
{
	function run() 
	{
		if (!empty($_REQUEST['print_popup'])) {
			$GLOBALS['system']->initErrorHandler();
		}
		$blanks = $archived = Array();

		if (!empty($_REQUEST['queryid'])) {
			$query = $GLOBALS['system']->getDBObject('person_query', (int)$_REQUEST['queryid']);
			$personids = $query->getResultPersonIDs();
			$recips = $GLOBALS['system']->getDBObjectData('person', Array('(id' => $personids, '!email' => '', '!status' => 'archived'), 'AND');
			$blanks = $GLOBALS['system']->getDBObjectData('person', Array('(id' => $personids, 'email' => '', '!status' => 'archived'), 'AND');
			$archived = $GLOBALS['system']->getDBObjectData('person', Array('(id' => $personids, 'status' => 'archived'), 'AND');
		} else if (!empty($_REQUEST['groupid'])) {
			$group = $GLOBALS['system']->getDBObject('person_group', (int)$_REQUEST['groupid']);
			$personids = array_keys($group->getMembers());
			$recips = $GLOBALS['system']->getDBObjectData('person', Array('(id' => $personids, '!email' => '', '!status' => 'archived'), 'AND');
			$blanks = $GLOBALS['system']->getDBObjectData('person', Array('(id' => $personids, 'email' => '', '!status' => 'archived'), 'AND');
			$archived = $GLOBALS['system']->getDBObjectData('person', Array('(id' => $personids, 'status' => 'archived'), 'AND');
		} else if (!empty($_REQUEST['roster_view'])) {
			$view = $GLOBALS['system']->getDBObject('roster_view', (int)$_REQUEST['roster_view']);
			$recips = $view->getAssignees($_REQUEST['start_date'], $_REQUEST['end_date']);
			// TODO: find email-less people here?
		} else {
			switch (array_get($_REQUEST, 'email_type')) {
				case 'family':
					$GLOBALS['system']->includeDBClass('family');
					$families = Family::getFamilyDataByMemberIDs($_POST['personid']);
					$recips = $GLOBALS['system']->getDBObjectData('person', Array('age_bracket' => '0', '(familyid' => array_keys($families), '!email' => '', '!status' => 'archived'), 'AND');
					$blanks =$GLOBALS['system']->getDBObjectData('person', Array('age_bracket' => '0', '(familyid' => array_keys($families), 'email' => '', '!status' => 'archived'), 'AND');
					$archived = $GLOBALS['system']->getDBObjectData('person', Array('age_bracket' => '0', '(familyid' => array_keys($families), 'status' => 'archived'), 'AND');
					break;
				case 'person':
				default:
					$recips = $GLOBALS['system']->getDBObjectData('person', Array('id' => $_POST['personid'], '!email' => '', '!status' => 'archived'), 'AND');
					$blanks = $GLOBALS['system']->getDBObjectData('person', Array('id' => $_POST['personid'], 'email' => '', '!status' => 'archived'), 'AND');
					$archived = $GLOBALS['system']->getDBObjectData('person', Array('id' => $_POST['personid'], 'status' => 'archived'), 'AND');
					$GLOBALS['system']->includeDBClass('person');
					break;
			}
		}
		
		$emails = array();
		foreach ($recips as $recip) {
			$emails[$recip['email']] = 1;
		}
		$my_email = $GLOBALS['user_system']->getCurrentUser('email');
		$public = array_get($_REQUEST, 'method') == 'public';
		if (!$public) unset($emails[$my_email]); // So I don't get it twice ("to" and "BCC")
		$emails = array_keys($emails);
		if (!empty($_REQUEST['print_popup'])) {
			?>
			<html>
				<head>
					<title>Jethro PMM - selected emails</title>
					<?php include 'templates/head.template.php'; ?>
					<script>
						var targetWin = window.opener.parent;
						$(window).load(function() {
							$('table.person-list td a').click(function() {
								if (targetWin) {
									targetWin.document.location.href = this.href;
									return false;
								}
							});
						});
					</script>
				</head>
				<body id="popup">
					<div id="header" style="min-width: 1px"><h1>Send Email</h1></div>
					<div id="body" style="min-width: 1px">
					<?php
					$chunks = array_chunk($emails, EMAIL_CHUNK_SIZE);
					if (count($chunks) == 1) {
						$to = implode(',', $emails);
						if (!$public) $to = $my_email.'?bcc='.$to;
						?>
						<p><a href="mailto:<?php echo $to ?>">Click here to send email</a></p>
						<?php
					} else {
						foreach ($chunks as $i => $chunk) {
							$to = implode(',', $chunk);
							if (!$public) $to = $my_email.'?bcc='.$to;
							?>
							<p><a href="mailto:<?php echo $to; ?>" onclick="this.style.text-decoration='line-through'">Batch <?php echo ($i+1); ?></a></p>
							<?php
						}
					}
					if (!empty($archived)) {
						?>
						<h4>Archived persons</h4>
						<p>?php echo count($archived); ?> of the intended recipients were archived and will not be sent this email.</p>
						<?php
					}
					if (!empty($blanks)) {
						?>
						<h4>Persons with no email address</h4>
						 <?php echo count($blanks); ?> of the intended recipients have no email address in the system:
						<?php
						$persons = $blanks;
						$special_fields = Array('congregation');
						include 'templates/person_list.template.php';
					}
					?>
					</div>
				</body>
			</html>
			<?php
		} else if ((count($emails) > EMAIL_CHUNK_SIZE) || !empty($blanks)) {
			?>
			<html><body>
			<form id="emailpopupform" method="post" action="<?php echo build_url(Array('print_popup'=>1)); ?>" target="emailpopup">
			<?php print_hidden_fields($_POST); ?> 
			</form>
			<script>
			var w = <?php echo empty($blanks) ? '300' : 'Math.round(screen.width * 0.6, 10)'; ?>;
			var h = <?php echo empty($blanks) ? '300' : '450'; ?>;
			var left = Math.round(screen.width - w);
			var top = Math.round((screen.height/2)-(h/2), 10);
			medLinkPopupWindow = window.open('', 'emailpopup', 'height='+h+',width='+w+',top='+top+',left='+left+'resizable=yes,scrollbars=yes');
			if (medLinkPopupWindow) {
				document.getElementById('emailpopupform').submit();
				try { medLinkPopupWindow.focus(); } catch (e) {}
			} else {
				alert('Jethro tried but could not open a popup window - you probably have a popup blocker enabled.  Please disable your popup blocker for this site, reload the page and try again.');
			}
			</script>
			</body></html>
			<?php
		} else if (count($emails) > 0) {
			$to = implode(',', $emails);
			if (!$public) $to = $my_email.'?bcc='.$to;
			header('Location: mailto:'.$to); // a bit more reliable than JS
		} else {
			?>
			<script>alert('None of the selected persons have email addresses in the system');</script>
			<?php
		}
	}
}


?>
