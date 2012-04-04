<?php
function array_get($array, $index, $alt=NULL)
{
	if (array_key_exists($index, $array)) {
		return $array[$index];
	} else {
		return $alt;
	}
}

function stripslashes_array(&$array, $strip_keys=false) {
	if(is_string($array)) return stripslashes($array);
	$keys_to_replace = Array();
	foreach($array as $key => $value) {
		if (is_string($value)) {
			$array[$key] = stripslashes($value);
		} elseif (is_array($value)) {
			stripslashes_array($array[$key], $strip_keys);
		}

		if ($strip_keys && $key != ($stripped_key = stripslashes($key))) {
			$keys_to_replace[$key] = $stripped_key;
		}
	}
	# now replace any of the keys that needed strip slashing
	foreach($keys_to_replace as $from => $to) {
		$array[$to]   = &$array[$from];
		unset($array[$from]);
	}
	return $array;
}

function strip_all_slashes() {
	if (get_magic_quotes_gpc()) {
		stripslashes_array($_GET, true);
		stripslashes_array($_POST, true);
		stripslashes_array($_COOKIE, true);
		stripslashes_array($_REQUEST, true);
	}
}

function bam($x)
{
	echo '<pre>';
	print_r($x);
	echo '</pre>';
}


function check_db_result(&$res)
{
	if (PEAR::isError($res)) {
		print_r($res->userinfo);
		trigger_error("Database Error: ".print_r($res->userinfo, 1), E_USER_ERROR);
		exit();
	}
}

function format_datetime($d)
{
	if (!is_int($d)) {
		if (0 === strpos($d, '0000-00-00')) return '';
		$d = strtotime($d);
	}
	if ($d == -1) return '';
	if (empty($d)) return ''; // 1 Jan 1970 is not treated as a valid date
	return date('j M Y g:ia', $d); 
}

function format_date($d)
{
	$yearless = is_string($d) && ($d[0] == '-');
	if (!is_int($d)) {
		$d = strtotime($d);
	}
	$format = $yearless ? 'j M' : 'j M Y';
	return date($format, $d);
}

function nbsp($x)
{
	return str_replace(' ', '&nbsp;', $x);
}

function redirect($view, $params=Array(), $hash='')
{
	$params['view'] = $view;
	$url = build_url($params);
	if ($hash) $url .= '#'.$hash;
	session_write_close();
	header('Location: '.urldecode(html_entity_decode($url)));
	exit;
}

function add_message($msg, $class='success', $html=FALSE)
{
	$_SESSION['messages'][] = Array('message' => $msg, 'class' => $class, 'html' => $html);
}

function dump_messages()
{
	if (!empty($_SESSION['messages'])) {
		foreach ($_SESSION['messages'] as $msg) print_message($msg['message'], $msg['class'], $msg['html']);
		unset($_SESSION['messages']);
	}
}

function print_message($msg, $class='success', $html=FALSE)
{
	?>
	<div class="message <?php echo $class; ?>"><?php echo $html ? $msg : htmlentities($msg); ?></div>
	<?php
}



function print_widget($name, $params, $value)
{
	$classes = array_get($params, 'class', '');
	if (!array_get($params, 'allow_empty', 1)) {
		$classes .= ' compulsory';
	}
	switch ($params['type']) {
		case 'phone':
			$lengths = get_valid_phone_number_lengths($params['formats']);
			$width = max(get_phone_format_lengths($params['formats']));
			?>
			<input name="<?php echo $name; ?>" type="text" width="<?php echo $width; ?>" value="<?php echo format_phone_number($value, $params['formats']); ?>" class="phone-number" validlengths="<?php echo implode(',', $lengths); ?>" />
			<?php
			break;
		case 'bibleref':
			require_once 'bible_ref.class.php';
			$br = new bible_ref($value);
			$value = $br->toShortString();
			$params['class'] = 'bible-ref';
			// fall through
		case 'text':
			$maxlength_exp = empty($params['maxlength']) ? '' : 'maxlength="'.$params['maxlength'].'"';
			if (array_get($params, 'height', 1) > 1) {
				$cols_exp = empty($params['width']) ? '' : 'cols="'.$params['width'].'"';
				?>
				<textarea name="<?php echo $name; ?>" rows="<?php echo $params['height']; ?>" <?php echo $cols_exp; ?> class="<?php echo trim($classes); ?>" <?php echo $maxlength_exp; ?>><?php echo htmlentities($value); ?></textarea>
				<?php
			} else {
				$width_exp = empty($params['width']) ? '' : 'size="'.$params['width'].'"';
				$regex_exp = empty($params['regex']) ? '' : 'regex="'.trim($params['regex'], '/ ').'"';
				?>
				<input type="text" name="<?php echo $name; ?>" value="<?php echo $value; ?>" class="<?php echo trim($classes); ?>" <?php echo implode(' ', Array($maxlength_exp, $width_exp, $regex_exp)); ?> />
				<?php
			}
			break;
		case 'html':
			?>
			<textarea class="ckeditor" name="<?php echo $name; ?>"><?php echo $value; ?></textarea>
			<?php
			break;
		case 'int':
			$classes .= ' int-box';
			$width_exp = '';
			if (!empty($params['width'])) {
				$classes .= ' exact-width';
				$width_exp = 'size="'.$params['width'].'" maxlength="'.$params['width'].'"';
			} else {
				$width_exp = 'size="3"';
			}
			?>
			<input type="text" name="<?php echo $name; ?>" value="<?php echo $value; ?>" class="<?php echo trim($classes); ?>" <?php echo $width_exp; ?> />
			<?php
			break;
		case 'select':
			$multiple_exp = '';
			if (array_get($params, 'allow_multiple')) {
				$multiple_exp = ' multiple="multiple"';
				if (substr($name, -2) != '[]') $name .= '[]';
			}
			$our_val = is_array($value) ? $value : (($value === '') ? Array() : Array($value));
			?>
			<select name="<?php echo $name; ?>" class="<?php echo $classes;?>"<?php echo $multiple_exp; ?>>
				<?php
				foreach ($params['options'] as $k => $v) {
					$selected_exp = in_array("$k", $our_val, true) ? ' selected="selected"' : '';
					$disabled_exp = (!empty($params['disabled_prefix']) && (strpos($k, $params['disabled_prefix']) === 0)) ? ' disabled="disabled" ' : '';
					?>
					<option value="<?php echo $k; ?>"<?php echo $selected_exp.$disabled_exp; ?>><?php echo htmlentities($v); ?></option>
					<?php
				}
				?>
			</select>
			<?php
			break;
		case 'date':
			$day_year_classes = trim($classes.' int-box');
			if (FALSE === strpos($name, '[')) {
				$name_template = $name.'%s';
			} else {
				$name_template = substr($name, 0, strpos($name, '[')).'%s'.substr($name, strpos($name, '['));
			}
			$months = Array();
			if (array_get($params, 'allow_empty', false)) $months[''] = '';
			for ($i = 1; $i < 13; $i++) $months[$i] = date(array_get($params, 'month_format', 'F'), strtotime("2007-$i-01")); 
			if (empty($value)) $value = date('Y-m-d'); // blank dates not allowed
			list($year_val, $month_val, $day_val) = explode('-', substr($value, 0, 10));
			?>
			<span class="nowrap">
			<input type="text" name="<?php printf($name_template, '_d'); ?>" class="day-box <?php echo $day_year_classes; ?>" size="2" maxlength="2" value="<?php echo $day_val; ?>" /><select name="<?php printf($name_template, '_m'); ?>" class="<?php echo $classes; ?>">
				<?php
				foreach ($months as $i => $month_name) {
					$selected = (($i) == $month_val) ? ' selected="selected"' : '';
					?>
					<option value="<?php echo $i; ?>"<?php echo $selected; ?>><?php echo $month_name; ?></option>
					<?php
				}
				?>
			</select><input type="text" name="<?php printf($name_template, '_y'); ?>" class="year-box <?php echo $day_year_classes; ?>" size="4" maxlength="4" value="<?php echo $year_val; ?>" />
			</span>
			<?php
			break;
		case 'reference':
			if (!empty($params['references'])) {
				$where = Array();
				if (!empty($params['filter']) && is_array($params['filter'])) $where = $params['filter'];
				$where_logic = array_get($params, 'filter_logic', 'AND');
				$options = $GLOBALS['system']->getDBObjectData($params['references'], $where, $where_logic, array_get($params, 'order_by'));
				$dummy = new $params['references']();
				$our_val = is_array($value) ? $value : (empty($value) ? Array() : Array($value));
				if (!empty($params['filter']) && is_callable($params['filter'])) {
					foreach ($options as $i => $o) {
						$dummy->populate($i, $o);
						if (!in_array($i, $our_val) && !$params['filter']($dummy)) {
							unset($options[$i]);
						}
					}
				}
				$multiple_exp = '';
				if (array_get($params, 'allow_multiple')) {
					$ssize = min(5, count($options));
					$multiple_exp = ' multiple="multiple" size="'.$ssize.'"';
					if (substr($name, -2) != '[]') $name .= '[]';
				}
				?>
				<select name="<?php echo $name; ?>" class="<?php echo $classes;?>"<?php echo $multiple_exp; ?>>
					<?php
					if (array_get($params, 'allow_empty') && !array_get($params, 'allow_multiple')) {
						?>
						<option value=""><?php echo array_get($params, 'empty_text', '(None)'); ?></option>
						<?php
					}
					foreach ($options as $k => $v) {
						$dummy->populate($k, $v);
						$selected_exp = in_array($k, $our_val) ? ' selected="selected"' : '';
						?>
						<option value="<?php echo $k; ?>"<?php echo $selected_exp; ?>><?php echo htmlentities($dummy->toString()); ?></option>
						<?php
					}
					?>
				</select>
				<?php
			}
			break;
		case 'bitmask':
			$value = (int)$value;
			?>
			<div class="bitmask-boxes">
			<?php
			$percol = false;
			if (!empty($params['cols']) && (int)$params['cols'] > 1) {
				$percol = ceil(count($params['options']) / $params['cols']);
				?>
				<table>
					<tr>
						<td class="nowrap">
				<?php
			}
			$i = 0;
			foreach ($params['options'] as $k => $v) {
				$checked_exp = (($value & (int)$k) == $k) ? 'checked="checked"' : '';
				?>
				<input type="checkbox" name="<?php echo htmlentities($name); ?>[]" value="<?php echo htmlentities($k); ?>" id="<?php echo htmlentities($name.'_'.$k); ?>" <?php echo $checked_exp; ?>>
				<label for="<?php echo htmlentities($name.'_'.$k); ?>"><?php echo nbsp(htmlentities($v)); ?></label>
				<?php
				if ($percol && (++$i % $percol == 0)) {
					?>
					</td>
					<td>
					<?php
				} else {
					?>
					<br />
					<?php
				}
			}
			if ($percol) {
				?>
						</td class="nowrap">
					</tr>
				</table>
				<?php
			}
			?>
			</div>
			<?php
			break;
	}
}


function process_widget($name, $params)
{
	$value = null;
	switch ($params['type']) {
		case 'phone':
			if (array_get($params, 'allow_empty', TRUE) && empty($_REQUEST[$name])) {
				$value = '';
			} else {
				if (!is_valid_phone_number($_REQUEST[$name], $params['formats'])) {
					trigger_error('The phone number "'.$_REQUEST[$name].'" is not valid and has not been set', E_USER_NOTICE);
					$value = NULL;
				} else {
					$value = clean_phone_number($_REQUEST[$name]);
				}
			}
			break;
		case 'date':
			if (isset($_REQUEST[$name])) {
				// might have an ISO8601 date
				if (preg_match('/^(\d\d\d\d-\d\d-\d\d)$/', $_REQUEST[$name])) {
					return $_REQUEST[$name];
				}
			}
			if (FALSE === strpos($name, '[')) {
				$subindex = NULL;
			} else {
				$subindex = substr($name, strpos($name, '[')+1, strpos($name, ']')-strpos($name, '[')-1);
				$name = substr($name, 0, strpos($name, '['));
			}
			if (!isset($_REQUEST[$name.'_d'])) return NULL;
			if (!is_null($subindex) && !isset($_REQUEST[$name.'_d'][$subindex])) return NULL;
			foreach (Array('y', 'm', 'd') as $comp) {
				$comp_vals[$comp] = array_get($_REQUEST, $name.'_'.$comp, 0);
				if (!is_null($subindex)) $comp_vals[$comp] = $comp_vals[$comp][$subindex];
			}
			$value = sprintf('%04d-%02d-%02d', $comp_vals['y'], $comp_vals['m'], $comp_vals['d']);
			if ($value == '0000-00-00') return NULL;
			if ($value == '0000-01-00') return NULL;

			if (array_get($params, 'allow_blank_year') && !((int)$comp_vals['y'])) {
				$value = substr($value, 4);
				if (date('-m-d', strtotime('2000'.$value)) != $value) {
					trigger_error('The date "'.$value.'" is not valid and has not been set', E_USER_NOTICE);
					$value = NULL;
				}
			} else {
				if (date('Y-m-d', strtotime($value)) != $value) {
					trigger_error('The date "'.$value.'" is not valid and has not been set', E_USER_NOTICE);
					$value = NULL;
				}
			}
			break;
		case 'bibleref':
			if (!empty($_REQUEST[$name])) {
				require_once 'bible_ref.class.php';
				$br = new bible_ref($_REQUEST[$name]);
				if ($br->book) $value = $br->toCode();
			}
			break;
		case 'bitmask':
			// value is the bitwise-or of all submitted values
			$value = 0;
			if (isset($_REQUEST[$name])) {
				if (isset($_REQUEST[$name])) {
					foreach ($_REQUEST[$name] as $i) {
						$value = $value | (int)$i;
					}
				}
			}
			break;
		case 'html':
			if (isset($_REQUEST[$name])) {
				require_once 'htmLawed.php';
				$value = htmLawed($_REQUEST[$name], array('safe'=>1));
			}
			break;
		default:
			$value = array_get($_REQUEST, $name);
			if (!empty($params['regex']) && !empty($value) && !preg_match($params['regex'].'i', $value)) {
				trigger_error($value.' is not a valid value for '.array_get($params, 'label', ucfirst($name)));
				$value = NULL;
			}
			break;
	}
	return $value;
}

function build_url($params)
{
	if (array_get($params, '*', 1) == NULL) {
		$vars = Array();
	} else {
		$vars = $_GET;
	}
	foreach ($params as $i => $v) {
		if (is_null($v)) {
			unset($vars[$i]);
		} else {
			$vars[$i] = $v;
		}
	}
	$protocol = (REQUIRE_HTTPS || !empty($_REQUEST['HTTPS'])) ? 'https://' : 'http://';
	$ubits = parse_url(BASE_URL);
	$path = (0 === strpos($_SERVER['PHP_SELF'], $ubits['path'])) ? $_SERVER['PHP_SELF'] : $ubits['path'];
	return $protocol.str_replace('index.php', '', $ubits['host'].$path).'?'.http_build_query($vars);
}

function speed_log()
{
	$bt = debug_backtrace();
	if (!isset($GLOBALS['first_log_time'])) {
		$GLOBALS['first_log_time'] = getmicrotime();
		error_log("SPEED_LOG: 0 - ".$bt[0]['file'].':'.$bt[0]['line']);
	} else {
		$diff = getmicrotime() - $GLOBALS['last_log_time'];
		$total = getmicrotime() - $GLOBALS['first_log_time'];
		error_log("SPEED_LOG: Diff=$diff  Total=$total  ".$bt[0]['file'].':'.$bt[0]['line']);
	}
	$GLOBALS['last_log_time'] = getmicrotime();
}

function getmicrotime(){ 
  list($usec, $sec) = explode(" ",microtime()); 
     return (int)((float)$usec + (float)$sec) * 1000; 
  } 

function print_hidden_fields($arr, $prefix='', $suffix='')
{
	foreach ($arr as $id => $val) {
		if (is_array($val)) {
			print_hidden_fields($val, $id.'[', ']');
		} else {
			echo '<input type="hidden" name="'.htmlentities($prefix.$id.$suffix).'" value="'.htmlentities($val).'" />'."\n";
		}
	}
}

function get_csv_row($x)
{
	foreach ($x as $i => &$v) {
		$v = '"'.str_replace('"', '""', $v).'"';
	}
	return implode(',', $x)."\r\n";
}

function get_valid_phone_number_lengths($formats)
{
	foreach (explode("\n", $formats) as $format) {
		$lengths[substr_count($format, 'X')] = $format;
	}
	return array_keys($lengths);
}

function get_phone_format_lengths($formats)
{
	foreach (explode("\n", $formats) as $format) {
		$lengths[] = strlen($format);
	}
	return array_unique($lengths);
}
	

function is_valid_phone_number($x, $formats)
{
	if (preg_match('/[A-Za-z]/', $x)) return false; // no letters allowed
	$x = preg_replace('/[^0-9]/', '', $x); // strip punctuation
	foreach (explode("\n", $formats) as $format) {
		$lengths[substr_count($format, 'X')] = $format;
	}
	return isset($lengths[strlen($x)]);
}

function clean_phone_number($x)
{
	return preg_replace('/[^0-9]/', '', $x); // Numbers only
}

function format_phone_number($x, $formats)
{
	$x = preg_replace('/[^0-9]/', '', $x); // strip punctuation
	foreach (explode("\n", $formats) as $format) {
		$lengths[substr_count($format, 'X')] = $format;
	}
	$format = array_get($lengths, strlen($x));
	if ($format) {
		$res = '';
		$j = 0;
		for ($i=0; $i<strlen($format);$i++) {
			if ($format[$i] == 'X') {
				$res .= $x[$j];
				$j++;
			} else {
				$res .= $format[$i];
			}
		}
		return $res;
	} else {

		return $x;
	}
}
?>
