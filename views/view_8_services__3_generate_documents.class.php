<?php
require_once 'include/bible_ref.class.php';
require_once 'include/odf_tools.class.php';
class View_Services__Generate_Documents extends View
{
	var $_service_date = '';
	var $_congregations = Array();
	var $_generated_files = Array();
	var $_keywords = Array();
	var $_dirs = NULL;

	static function getMenuPermissionLevel()
	{
		return PERM_SERVICEDOC;
	}

	function processView()
	{
		$GLOBALS['system']->includeDBClass('service');
		$this->_service_date = process_widget('service_date', Array('type' => 'date'));
		$this->_congregations = $GLOBALS['system']->getDBObjectData('congregation', Array('!meeting_time' => ''));
		if (empty($this->_congregations)) {
			add_message("You need to set the 'code name' for some of your congregations before using this feature", 'failure');
			$this->_congregations = NULL; // mark that we neve had any even before processing
			return;
		}
		$this->_replacements = Array();
		$this->_dirs = Array();
		$this->_dirs['populate'] = SERVICE_DOCS_TO_POPULATE_DIRS ? explode('|', SERVICE_DOCS_TO_POPULATE_DIRS) : '';
		$this->_dirs['expand'] = SERVICE_DOCS_TO_EXPAND_DIRS ? explode('|', SERVICE_DOCS_TO_EXPAND_DIRS) : '';
		if (empty($this->_dirs['populate']) && empty($this->_dirs['expand'])) {
			add_message("You need to set a value for SERVICE_DOCS_TO_POPULATE_DIRS or SERVICE_DOCS_TO_EXPAND_DIRS in your system configuration before using this feature", 'failure');
			$this->_dirs = NULL;
			return;
		}

		if ($this->_service_date) {
			switch (array_get($_REQUEST, 'action')) {
				case 'initiate':
					$this->processInitiate();
					break;
				case 'populate':
					$this->processPopulate();
					break;
				case 'expand':
					$this->processExpand();
					break;
			}
		}
	}

	function processInitiate()
	{
		foreach ($this->_dirs['populate'] as $dir) {
			chdir($dir);
			foreach ($this->_congregations as $congid => $cong) {
				$service = Service::findByDateAndCong($this->_service_date, $congid);
				if (empty($service)) {
					add_message('No service found for congregation '.$cong['name'].' on '.$this->_service_date, 'failure');
					unset($this->_congregations[$congid]);
					continue;
				}
				$template_name = '_template_'.$cong['meeting_time'];
				if (file_exists($template_name.'.ott')) {
					$new_name = $this->_service_date.'_'.$cong['meeting_time'].'.odt';
					if (file_exists($new_name)) {
						add_message("$new_name already exists", 'failure');
					} else {
						copy($template_name.'.ott', $new_name);
						chmod($new_name, fileperms($template_name.'.ott'));
						$this->_generated_files[] = basename($dir).' / '.$new_name;
					}
				} else if (file_exists($template_name.'.otp')) {
					$new_name = $this->_service_date.'_'.$cong['meeting_time'].'.odp';
					if (file_exists($new_name)) {
						add_message("$new_name already exists", 'failure');
					} else {
						copy($template_name.'.otp', $new_name);
						chmod($new_name, fileperms($template_name.'.otp'));
						$this->_generated_files[] = basename($dir).' / '.$new_name;
					}
				} else {
					add_message("No template found for ".$cong['meeting_time'].' in '.basename($dir), 'failure');
				}
			}
		}
		$this->_addGeneratedFilesMessage();
	}

	function processPopulate()
	{
		foreach ($this->_dirs['populate'] as $dir) {
			chdir($dir);
			if (empty($_POST['replacements'])) {
				// Get ready to print the replacements form
				foreach ($this->_congregations as $congid => &$congregation) {
					$service = Service::findByDateAndCong($this->_service_date, $congid);
					if (empty($service)) {
						trigger_error('No service found for congregation '.$congregation['name'].' on '.$this->_service_date);
						unset($this->_congregations[$congid]);
						continue;
					}
					$next_service = Service::findByDateAndCOng(date('Y-m-d', strtotime($this->_service_date.' +1 week')), $congid);

					$basename = $this->_service_date.'_'.$congregation['meeting_time'];
					if (is_file($dir.'/'.$basename.'.odt')) {
						$odf_filename = $dir.'/'.$basename.'.odt';
					} else if (is_file($dir.'/'.$basename.'.odp')) {
						$odf_filename = $dir.'/'.$basename.'.odp';
					} else {
						add_message('No file found for '.$basename.' in '.$dir, 'failure');
						continue;
					}
					$odf_content = ODF_Tools::getXML($odf_filename);
					if (empty($odf_content)) continue;

					$congregation['filenames'][] = basename($dir).' / '.$odf_filename;

					$keywords = ODF_Tools::getKeywords($odf_filename);
					foreach ($keywords as $keyword) {
						$keyword = strtoupper($keyword);
						if (isset($_POST['replacements'][$congid][$keyword])) {
							$this->_replacements[$congid][$keyword] = $_POST['replacements'][$congid][$keyword];
						} else {
							if (0 === strpos($keyword, 'NAME_OF_')) {
								$role_title = substr($keyword, strlen('NAME_OF_'));
								$this->_replacements[$congid][$keyword] = $service->getPersonnelByRoleTitle($role_title);
							} else if (0 === strpos($keyword, 'SERVICE_')) {
								$service_field = strtolower(substr($keyword, strlen('SERVICE_')));
								$this->_replacements[$congid][$keyword] = $service->getValue($service_field);
								if ($service_field == 'date') {
									// make a friendly date
									$this->_replacements[$congid][$keyword] = date('j F Y', strtotime($this->_replacements[$congid][$keyword]));
								}
							} else if (0 === strpos($keyword, 'NEXT_SERVICE_')) {
								if (!empty($next_service)) {
									$service_field = strtolower(substr($keyword, strlen('NEXT_SERVICE_')));
									$this->_replacements[$congid][$keyword] = $next_service->getValue($service_field);
									if ($service_field == 'date') {
										// make a short friendly date
										$this->_replacements[$congid][$keyword] = date('j M', strtotime($this->_replacements[$congid][$keyword]));
									}
								} else {
									trigger_error('Next service not found, cant replace '.$keyword);
								}
							} else if (0 === strpos($keyword, 'CONGREGATION_')) {
								$cong_field = strtolower(substr($keyword, strlen('CONGREGATION_')));
								$this->_replacements[$congid][$keyword] = $cong[$cong_field];
							} else {
								$this->_replacements[$congid][$keyword] = '';
							}
						}
					}
				}
			} else {
				// do the replacements
				$this->_replacements = $_POST['replacements'];
				foreach ($this->_congregations as $congid => $details) {
					$basename = $this->_service_date.'_'.$details['meeting_time'];
					if (is_file($dir.'/'.$basename.'.odt')) {
						$odf_filename = $basename.'.odt';
					} else if (is_file($dir.'/'.$basename.'.odp')) {
						$odf_filename = $basename.'.odp';
					} else {
						continue;
					}
					$output_odf_filename = 'POPULATED_'.$odf_filename;

					if (file_exists($dir.'/'.$output_odf_filename)) {
						if (!unlink($output_odf_filename)) {
							trigger_error('Cannot write to '.$output_odf_filename.' - is the file in use?');
							continue;
						}
					}
					copy($dir.'/'.$odf_filename, $dir.'/'.$output_odf_filename);
					ODF_Tools::replaceKeywords($dir.'/'.$output_odf_filename, $this->_replacements[$congid]);
					chmod($dir.'/'.$output_odf_filename, fileperms($dir.'/'.$odf_filename));

					$this->_generated_files[] = basename($dir).' / '.$output_odf_filename;
				}
			}
		}
		$this->_addGeneratedFilesMessage();
	}


	function getTitle()
	{
		switch (array_get($_REQUEST, 'action')) {
			case 'populate':
				return 'Populate keywords in service documents';
			case 'expand':
				return 'Expand service documents';
		}
		return 'Generate service documents';
	}

	function _printPopulateReplacementsForm()
	{
		?>
		Confirm or correct the following field values
		<form method="post">
		<input type="hidden" name="action" value="populate" />
		<input type="hidden" name="service_date" value="<?php echo $this->_service_date; ?>" />
		<table class="standard">
			<tr>
		<?php
		foreach ($this->_congregations as $congid => $congregation) {
			?>
			<th>
				<?php
				echo htmlentities($congregation['name']);
				foreach ($congregation['filenames'] as $file) {
					echo '<br />';
					echo htmlentities($file);
				}
				?>
			</th>
			<?php
		}
		?>
		</tr>
		<tr>
		<?php
		foreach ($this->_congregations as $congid => $congregation) {
			?>
			<td>
				<table>
				<?php
				foreach ($this->_replacements[$congid] as $keyword => $value) {
					?>
					<tr>
					<td><?php echo htmlentities($keyword); ?></td>
					<td><input type="text" name="replacements[<?php echo (int)$congid; ?>][<?php echo htmlentities($keyword); ?>]" value="<?php echo htmlentities($value); ?>" /></td>
					</tr>
					<?php
				}
				?>
				</table>
			</td>
			<?php
		}
		?>
		</tr>
		</table>
		<input type="submit" value="Go" />
		</form>
		<?php
	}

	function _addGeneratedFilesMessage()
	{
		if (!empty($this->_generated_files)) {
			$str = "
			Files created:
			<ul>";
			foreach ($this->_generated_files as $file) {
				$str .= "<li>".htmlentities($file).'</li>';
			}
			$str .= '</ul>';
			add_message($str, 'success', true);
		} else {
			add_message("No files created", 'failure');
		}
	}

	function printView()
	{
		if (is_null($this->_congregations) || is_null($this->_dirs)) return;
				
		switch (array_get($_REQUEST, 'action')) {
			case 'populate':
				if (empty($_POST['replacements']) && !empty($this->_congregations)) {
					$this->_printPopulateReplacementsForm();
					break;
				}

			case 'expand':
				if (empty($_POST['replacements']) && !empty($this->_congregations)) {
					$this->_printExpandReplacementsForm();
					break;
				}

			// deliberate fallthroughs...

			default:
				$default_date = array_get($_REQUEST, 'service_date', date('Y-m-d', strtotime('Sunday')));
				?>
				<form method="post">
				<input type="hidden" name="view" value="<?php echo htmlentities($_GET['view']); ?>" />
				<table>
					<tr>
						<th class="nowrap">Service date</th>
						<td><?php print_widget('service_date', Array('type' => 'date'), $default_date); ?></td>
					</tr>
					<tr>
						<th>Action:</th>
						<td>
							<table>
							<?php
							if (!empty($this->_dirs['populate'])) {
								?>
								<tr>
									<td><input type="radio" name="action" value="initiate" id="action-initiate" /></td>
									<td>
										<label for="action-initiate">
										<b>Initiate</b> - create a file for each service using templates<br />
										<div class="standard smallprint">For each congregation with a code name, this will look in
										<ul><li><?php echo implode('</li><li>', $this->_dirs['populate']); ?></ul>
										for a file named "_template_CODENAME.odt" <br />and make a copy named "<?php echo $default_date; ?>_CODENAME.odt" (using the date specified above).</div>
										</label>
									</td>
								</tr>
								<tr>
									<td><input type="radio" name="action" value="populate" id="action-populate" /></td>
									<td>
										<label for="action-populate">
										<b>Populate</b> - replace keywords in each service's file<br />
										<div class="standard smallprint">For each congregation with a code name, this will look in
										<ul><li><?php echo implode('</li><li>', $this->_dirs['populate']); ?></ul>
										for a file named "<?php echo $default_date; ?>_CODENAME.odt" (using the date specified above), <br />replace %KEYWORDS% within it, <br />and save the results as "POPULATED_<?php echo $default_date; ?>_CODENAME.odt".</div>
										</label>
									</td>
								</tr>
								<?php
							}
							if (!empty($this->_dirs['expand'])) {
								?>
								<tr>
									<td><input type="radio" name="action" value="expand" id="action-expand" /></td>
									<td>
										<label for="action-expand">
										<b>Expand</b> - make a copy for each congregation<br />
										<div class="standard smallprint">For each file FILENAME.ODT  or FILENAME.ODP in 
										<ul><li><?php echo implode('</li><li>', $this->_dirs['expand']); ?></ul>
										this will create a subfolder using the date specified above,<br />
										and make a copy of the file within the subfolder for each congregation - "<?php echo $default_date; ?>/FILENAME_CODENAME.odt, <br />replacing %KEYWORDS% in each copy as appropriate. </div>
										</label>
									</td>
								</tr>
								<?php
							}
							?>
							</table>
						</td>
					</tr>
					<tr>
						<th>&nbsp</th>
						<td><input type="submit" value="Go" /></td>
					</tr>
				</table>
				</form>
				<?php
				break;
		}

	}

	function processExpand()
	{
		if ($this->_service_date) {
			foreach ($this->_dirs['expand'] as $dir) {
				$di = new DirectoryIterator($dir);
				foreach ($di as $fileinfo) {
					if (!$fileinfo->isFile()) continue;
					$pathinfo = pathinfo($fileinfo->getFilename());
					if (in_array($pathinfo['extension'], Array('odt', 'odp'))) {
						$this->_keywords = array_merge($this->_keywords, ODF_Tools::getKeywords($fileinfo->getPathname()));
						if (!empty($_POST['replacements'])) {
							// make copies and replace the keywords
							$this->expandFile($fileinfo->getPathname());
						}
					}
				}
			}
			if (empty($_POST['replacements'])) {
				$this->loadReplacements();
			} else {
				$this->_addGeneratedFilesMessage();
			}
		}
	}

	function loadReplacements()
	{
		foreach ($this->_congregations as $congid => $cong) {
			$service = Service::findByDateAndCong($this->_service_date, $congid);
			if (empty($service)) {
				add_message("Could not find service for ".$cong['name']." on ".$this->_service_date, 'failure');
				unset($this->_congregations[$congid]);
				continue;
			}
			$next_service = Service::findByDateAndCong(date('Y-m-d', strtotime($this->_service_date.' +1 week')), $congid);
			foreach ($this->_keywords as $keyword) {
				$keyword = strtoupper($keyword);
				if (0 === strpos($keyword, 'NAME_OF_')) {
					$role_title = substr($keyword, strlen('NAME_OF_'));
					$this->_replacements[$congid][$keyword] = $service->getPersonnelByRoleTitle($role_title);
				} else if (0 === strpos($keyword, 'SERVICE_')) {
					$service_field = strtolower(substr($keyword, strlen('SERVICE_')));
					$this->_replacements[$congid][$keyword] = $service->getValue($service_field);
					if ($service_field == 'date') {
						// make a friendly date
						$this->_replacements[$congid][$keyword] = date('j F Y', strtotime($this->_replacements[$congid][$keyword]));
					}
				} else if (0 === strpos($keyword, 'NEXT_SERVICE_')) {
					if (!empty($next_service)) {
						$service_field = strtolower(substr($keyword, strlen('NEXT_SERVICE_')));
						$this->_replacements[$congid][$keyword] = $next_service->getValue($service_field);
						if ($service_field == 'date') {
							// make a short friendly date
							$this->_replacements[$congid][$keyword] = date('j M', strtotime($this->_replacements[$congid][$keyword]));
						}
					} else {
						trigger_error('Next service for '.$cong['name'].' not found, cant replace '.$keyword);
					}
				} else if (0 === strpos($keyword, 'CONGREGATION_')) {
					$cong_field = strtolower(substr($keyword, strlen('CONGREGATION_')));
					$this->_replacements[$congid][$keyword] = $cong[$cong_field];
				} else {
					$this->_replacements[$congid][$keyword] = '';
				}
			}
		}
	}

	function expandFile($filename)
	{
		$pathinfo = pathinfo($filename);
		$new_dirname = $pathinfo['dirname'].'/'.$this->_service_date;
		if (!file_exists($new_dirname)) {
			mkdir($new_dirname);
		}
		if (is_writable($new_dirname)) {
			chdir($new_dirname);
			foreach ($this->_congregations as $congid => $cong) {
				$new_filename = substr($pathinfo['basename'], 0, -(strlen($pathinfo['extension'])+1)).'_'.$cong['meeting_time'].'.'.$pathinfo['extension'];
				if (file_exists($new_filename)) {
					if (!unlink($new_filename)) {
						trigger_error("Could not overwrite ".$new_filename.' - file open?');
						continue;
					}
				}
				copy($filename, $new_filename);
				ODF_Tools::replaceKeywords($new_filename, $_POST['replacements'][$congid]);
				chmod($new_filename, fileperms($filename));
				$this->_generated_files[] = basename($pathinfo['dirname']).' / '.basename($new_dirname).' / '.$new_filename;
			}
		}
	}

	function _printExpandReplacementsForm()
	{
			?>
			Confirm or correct the following field values
			<form method="post">
			<input type="hidden" name="action" value="expand" />
			<input type="hidden" name="service_date" value="<?php echo $this->_service_date; ?>" />
			<table class="standard">
				<tr>
			<?php
			foreach ($this->_congregations as $congid => $congregation) {
				?>
				<th>
					<?php
					echo htmlentities($congregation['name'].' ('.$congregation['meeting_time'].')');
					?>
				</th>
				<?php
			}
			?>
			</tr>
			<tr>
			<?php
			foreach ($this->_congregations as $congid => $congregation) {
				?>
				<td>
					<table>
					<?php
					foreach ($this->_replacements[$congid] as $keyword => $value) {
						?>
						<tr>
						<td><?php echo htmlentities($keyword); ?></td>
						<td><input type="text" name="replacements[<?php echo (int)$congid; ?>][<?php echo htmlentities($keyword); ?>]" value="<?php echo htmlentities($value); ?>" /></td>
						</tr>
						<?php
					}
					?>
					</table>
				</td>
				<?php
			}
			?>
			</tr>
			</table>
			<input type="submit" value="Go" />
			</form>
			<?php
	}
	
}

