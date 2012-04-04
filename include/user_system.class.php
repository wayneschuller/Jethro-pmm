<?php
require_once dirname(__FILE__).'/general.php';
class User_System
{
	var $_error;
	var $_permission_levels = Array();

	function User_System()
	{
		include 'permission_levels.php';
		$enabled_features = explode(',', strtoupper(ENABLED_FEATURES));
		foreach ($PERM_LEVELS as $i => $detail) {
			list($define_symbol, $desc, $feature_code) = $detail;
			define('PERM_'.$define_symbol, $i);
			if (empty($feature_code) || in_array($feature_code, $enabled_features)) {
				$this->_permission_levels[$i] = $desc;
			}
		}
		if (!empty($_REQUEST['logout'])) {
			// Log out
			$_SESSION['user'] = NULL;
		} else if (empty($_SESSION['user']) && !empty($_POST['username'])) {
			// process the login form
			if (array_get($_SESSION, 'login_key', NULL) != $_POST['login_key']) {
				$this->_error = 'Login Key Incorrect.  Please try again.';
				return;
			}
			$user_details = $this->_findUser($_POST['username'], $_POST['password']);
			if (is_null($user_details)) {
				$this->_error = 'Incorrect username or password';
			} else {
				// Log the user in
				// Recreate session when logging in
				session_regenerate_id();
				$_SESSION = Array();
				$_SESSION['user'] = $user_details;
			}
		}
		if (!empty($_SESSION['user'])) {
			$res = $GLOBALS['db']->query('SET @current_user_id = '.(int)$_SESSION['user']['id']);
			if (PEAR::isError($res)) trigger_error('Failed to set user id in database', E_USER_ERROR);
		}

	}//end constructor


	function setError($s)
	{
		$this->_error = $s;
	}


	function hasUsers()
	{
		$sql = 'SELECT count(*) FROM staff_member';
		$res = $GLOBALS['db']->queryRow($sql);
		if (PEAR::isError($res)) {
			$res = 0;
		}
		return (bool)$res;
	}

	function getCurrentUser($field='')
	{
		if (empty($_SESSION['user'])) {
			return NULL;
		} else {
			if (empty($field)) {
				return $_SESSION['user'];
			} else {
				return array_get($_SESSION['user'], $field, '');
			}
		}

	}//end getCurrentUser()

	function getCurrentRestrictions()
	{
		$res = Array();
		if (!empty($_SESSION['user']['group_restrictions'])) $res['group'] = $_SESSION['user']['group_restrictions'];
		if (!empty($_SESSION['user']['congregation_restrictions'])) $res['congregation'] = $_SESSION['user']['congregation_restrictions'];
		return $res;
	}

	function getPermissionLevels()
	{
		return $this->_permission_levels;
	}

	function havePerm($permission)
	{
		if ($permission == 0) return true;
		if (!empty($GLOBALS['JETHRO_INSTALLING'])) return true;
		if (!array_key_exists($permission, $this->_permission_levels)) return false; // disabled feature
		return (($this->getCurrentUser('permissions') & $permission) == $permission);
	}

	function checkPerm($permission)
	{
		if (!$this->havePerm($permission)) {
			trigger_error('Your user account does not have permission to perform this action');
			exit;
		}
	}

	// Called by the public interface to indicate no login expected
	function setPublic()
	{
		$res = $GLOBALS['db']->query('SET @current_user_id = -1');
		if (PEAR::isError($res)) trigger_error('Failed to set user id in database', E_USER_ERROR);
	}

	function printLogin()
	{
		$_SESSION['login_key'] = $login_key = $this->_generateLoginKey();
		require TEMPLATE_DIR.'/login_form.template.php';

	}//end printLogin()


	function _generateLoginKey()
	{
		$res = '';
		for ($i=0; $i < 256; $i++) {
			$res .= ord(rand(32, 126));
		}
		return $res;

	}//end _generateLoginKey()


	function _findUser($username, $password)
	{
		$db =& $GLOBALS['db'];
		$sql = 'SELECT sm.*, p.*, GROUP_CONCAT(cr.congregationid) as congregation_restrictions, GROUP_CONCAT(gr.groupid) as group_restrictions
				FROM staff_member sm
					JOIN _person p ON sm.id = p.id
					LEFT JOIN account_congregation_restriction cr ON cr.personid = sm.id
					LEFT JOIN account_group_restriction gr ON gr.personid = sm.id
				WHERE sm.username = '.$db->quote($username).'
					AND active = 1
				GROUP BY p.id';
		$row = $db->queryRow($sql);
		check_db_result($row);
		if (!empty($row) && crypt($password, $row['password']) == $row['password']) {
			return $row;
		}
		return NULL;

	}//end _validateUser()


}//end class
?>
