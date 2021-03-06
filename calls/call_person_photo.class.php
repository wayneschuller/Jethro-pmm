<?php
/**
 * JETHRO PMM
 * 
 * 
 *
 * @author Tom Barrett <tom@tombarrett.id.au>
 * @version $Id: call_person_photo.class.php,v 1.1 2012/01/15 10:14:50 tbar0970 Exp $
 * @package jethro-pmm
 */
class Call_Person_Photo extends Call
{
	/**
	 * Execute this call
	 *
	 * @return void
	 * @access public
	 */
	function run()
	{
		$db = $GLOBALS['db'];
		$person = $GLOBALS['system']->getDBObject('person', (int)$_REQUEST['personid']);
		if ($person) {
			$sql = 'SELECT * FROM person_photo WHERE personid = '.(int)$person->id;
			$res = $db->queryRow($sql);
			check_db_result($res);
			if ($res) {
				header('Content-type: image/jpeg'); // FIXME
				echo $res['photodata'];
				return;
			}
		} 
		header('Content-type: image/gif');
		readfile(dirname(dirname(__FILE__)).'/resources/unknown.gif');
	}
}
?>
