<?php
require_once 'db_objects/staff_member.class.php';
require_once 'db_objects/family.class.php';
require_once 'db_objects/congregation.class.php';

class Installer
{
	var $initial_person_fields = Array('first_name', 'last_name', 'gender', 'username', 'password', 'email');
	var $person = NULL;
	var $family = NULL;
	var $congregations = Array();

	function run()
	{
		$sql = 'SELECT count(*) FROM PERSON';
		$res = $GLOBALS['db']->queryOne($sql);
		if (!PEAR::isError($res)) {
			trigger_error('System has already been installed, installer is aborting');
			exit();
		}
		include 'templates/installer.template.php';
	}


	function printBody()
	{
		require_once dirname(__FILE__).'/system_controller.class.php';
		$GLOBALS['system'] = new System_Controller();
		set_error_handler(Array($GLOBALS['system'], '_handleError'));

		if ($this->readyToInstall() && $this->initInitialEntities()) {
			$GLOBALS['JETHRO_INSTALLING'] = 1;
			$this->initDB();
			$this->createInitialEntities();
			unset($GLOBALS['JETHRO_INSTALLING']);

			$this->printConfirmation();
		} else {
			$this->printForm();
		}
	}

	function readyToInstall()
	{
		if (empty($_POST)) {
			return FALSE;
		}

		foreach ($this->initial_person_fields as $field) {
			if (isset($_POST['install_'.$field]) && empty($_POST['install_'.$field])) {
				trigger_error('You must enter a value for '.$field.' to proceed');
				return FALSE;
			}
		}

		// if we get to here, all person details were supplied
		if (empty($_POST['congregation_name'])) {
			trigger_error('You must enter at least one congregation name to proceed');
			return FALSE;
		}
		$cong_found = FALSE;
		foreach ($_POST['congregation_name'] as $cname) {
			if (!empty($cname)) {
				$cong_found = TRUE;
				break;
			}
		}
		if (!$cong_found) {
			trigger_error('You must enter at least one congregation name to proceed');
			return FALSE;
		}

		return TRUE;
	}



	function initDB()
	{
		$dh = opendir(dirname(dirname(__FILE__)).'/db_objects');
		while (FALSE !== ($filename = readdir($dh))) {
			if (($filename[0] == '.') || is_dir($filename)) continue;
			$filenames[] = $filename;
		}
		sort($filenames);
		foreach ($filenames as $filename) {
			$classname = str_replace('.class.php', '', $filename);
			require_once dirname(dirname(__FILE__)).'/db_objects/'.$filename;
			$data_obj = new $classname;
			if (method_exists($data_obj, 'getInitSQL')) {
				$sql = $data_obj->getInitSQL();
				if (!empty($sql)) {
					if (!is_array($sql)) $sql = Array($sql);
					foreach ($sql as $s) {
						$r = $GLOBALS['db']->query($s);
						check_db_result($r);
					}
				}
			}
		}

		$sql = Array(
			"CREATE TABLE `db_object_lock` (
			  `objectid` int(11) NOT NULL default '0',
			  `userid` int(11) NOT NULL default '0',
			  `lock_type` VARCHAR( 16 ) NOT NULL,
			  `object_type` varchar(255) collate latin1_general_ci NOT NULL default '',
			  `expires` datetime NOT NULL default '0000-00-00 00:00:00',
			  KEY `objectid` (`objectid`),
			  KEY `userid` (`userid`),
			  KEY `object_type` (`object_type`)
			) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;",

			"CREATE FUNCTION getCurrentUserID() RETURNS INTEGER NO SQL RETURN @current_user_id;",

			"CREATE TABLE account_group_restriction (
			   personid INTEGER NOT NULL,
			   groupid INTEGER NOT NULL,
			   PRIMARY KEY (personid, groupid),
			   CONSTRAINT account_group_restriction_personid FOREIGN KEY (personid) REFERENCES staff_member(id),
			   CONSTRAINT account_group_restriction_groupid FOREIGN KEY (groupid) REFERENCES _person_group(id)
			) engine=innodb;",

			"CREATE TABLE account_congregation_restriction (
			   personid INTEGER NOT NULL,
			   congregationid INTEGER NOT NULL,
			   PRIMARY KEY (personid, congregationid),
			   CONSTRAINT account_congregation_restriction_personid FOREIGN KEY (personid) REFERENCES staff_member(id),
			   CONSTRAINT account_group_restriction_congregationid FOREIGN KEY (congregationid) REFERENCES congregation(id)
			) engine=innodb;",

			"CREATE VIEW person AS
			SELECT * from _person p
			WHERE
				getCurrentUserID() IS NOT NULL
				AND (
					(`p`.`id` = `getCurrentUserID`())
					OR (`getCurrentUserID`() = -(1))
					OR (
						(
						(not(exists(select 1 AS `Not_used` from `account_congregation_restriction` `cr` where (`cr`.`personid` = `getCurrentUserID`()))))
						OR `p`.`congregationid` in (select `cr`.`congregationid` AS `congregationid` from `account_congregation_restriction` `cr` where (`cr`.`personid` = `getCurrentUserID`()))
						)
						AND
						(
						(not(exists(select 1 AS `Not_used` from `account_group_restriction` `gr` where (`gr`.`personid` = `getCurrentUserID`()))))
						OR `p`.`id` in (select `m`.`personid` AS `personid` from (`person_group_membership` `m` join `account_group_restriction` `gr` on((`m`.`groupid` = `gr`.`groupid`))) where (`gr`.`personid` = `getCurrentUserID`()))
						)
					)
				);",

			"CREATE VIEW person_group AS
			SELECT * from _person_group g
			WHERE
			  getCurrentUserID() IS NOT NULL
			  AND
			  (NOT EXISTS (SELECT * FROM account_group_restriction gr WHERE gr.personid  = getCurrentUserID())
				   OR g.id IN (SELECT groupid FROM account_group_restriction gr WHERE gr.personid = getCurrentUserID()))"
		);
		foreach ($sql as $s) {
			$r = $GLOBALS['db']->query($s);
			check_db_result($r);
		}
	}


	function initInitialEntities()
	{
		foreach ($_POST['congregation_name'] as $cname) {
			if (empty($cname)) continue;
			$c = new Congregation();
			$c->setValue('name', $cname);
			$c->setValue('long_name', $cname);
			$this->congregations[] = $c;
			if (!$c->validateFields()) return FALSE;
		}
		
		$this->user = new Staff_Member();
		foreach ($this->initial_person_fields as $field) {
			$this->user->processFieldInterface($field, 'install_');
		}
		$this->user->setValue('status', 0);
		$this->user->setValue('permissions', PERM_SYSADMIN);
		if (!$this->user->validateFields()) return FALSE;

		$this->family = new Family();
		$this->family->setValue('family_name', $this->user->getValue('last_name'));
		$this->family->setValue('creator', 0);
		if (!$this->family->validateFields()) return FALSE;

		return TRUE;
	}

	function createInitialEntities()
	{
		$cong_ids = Array();
		foreach ($this->congregations as $cong) {
			if (!$cong->create()) {
				$this->unInstall();
				return;
			}
			$cong_ids[] = $cong->id;
		}
		
		if (!$this->family->create()) {
			$this->unInstall();
			return;
		}

		$this->user->setValue('familyid', $this->family->id);
		$this->user->setValue('congregationid', reset($cong_ids));
		if (!$this->user->create()) {
			$this->unInstall();
			return;
		}
		

		$this->user->setValue('creator', $this->user->id);
		$this->user->save();

		$this->family->setValue('creator', $this->user->id);
		$this->family->save();
	}

	function unInstall()
	{
		$sql = 'SHOW TABLES';
		$tablenames = $GLOBALS['db']->queryCol($sql);
		foreach ($tablenames as $tablename) {
			$GLOBALS['db']->query('DROP TABLE '.$tablename);
		}
		echo '<p>Installation Rolled Back</p>';
		exit();
	}
		


	function printForm()
	{
		?>
		<h2>Welcome</h2>
		<p>Welcome to the Jethro installer.  The installation process will set up your MySql database so that it's ready to run Jethro.  First we need to collect some details to get things started.</p>
		
		<form method="post">
			<h3>Initial User Account</h3>
			<p>Please enter the details of the first user you want to add to the system.  This is the user as which you will initially log in.  After you have logged in you can edit the rest of the details for this person.</p>

			<table>
			<?php
			$sm = new Staff_Member();
			foreach ($this->initial_person_fields as $fieldname) {
				?>
				<tr>
					<th><?php echo $sm->getFieldLabel($fieldname); ?></th>
					<td><?php $sm->printFieldInterface($fieldname, 'install_'); ?></td>
				</tr>
				<?php
			}
			?>
			</table>

			<h3>Congregations</h3>
			<p>Please enter the names of the congregations your church has.  These can be edited later under admin &gtp; congregations.</p>
			<table class="expandable">
				<tr>
					<td>
						<input type="text" name="congregation_name[]" />
					</td>
				</tr>
			</table>
			<p class="smallprint">(List expands as you type)</p>

			<h3>Continue...</h3>
			<input type="submit" value="Set up the database" />
		</form>
		<?php
	}

	function printConfirmation()
	{
		dump_messages();
		?>
		<h2>Installation Complete!</h2>

		You can now:
		<ul>
			<li><a target="_blank" href="<?php echo BASE_URL; ?>/readme.html">View the readme file</a> for further information</li>
			<li><a target="_blank" href="<?php echo BASE_URL; ?>">Log in to the system</a> to start work</li>
		</ul>
		<?php
	}
}
?>
