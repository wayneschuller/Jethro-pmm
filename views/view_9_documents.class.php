<?php
class View_Documents extends View
{
	var $_rootpath = DOCUMENTS_ROOT_PATH;
	var $_realdir = NULL;
	var $_editfile = NULL;
	var $_messages = Array();
	
	function getTitle()
	{
		return NULL;
	}

	function _addMessage($msg) {
		$this->_messages[] = $msg;
	}

	function _dumpMessages() {
		static $i = 0;
		foreach ($this->_messages as $msg) {
			?>
			<div id="msg-<?php echo $i; ?>" class="success document-message hidden" ><?php echo htmlentities($msg); ?></div>
			<script>$(window).ready(function() { $("#msg-<?php echo $i; ?>").fadeIn('fast'); setTimeout('$("#msg-<?php echo $i; ?>").fadeOut("slow");', 3000); })</script>
			<?php
			$i++;
		}
	}

	function processView()
	{
		if (empty($this->_rootpath)) $this->_rootpath = JETHRO_ROOT.'/files';
		if (!is_dir($this->_rootpath)) {
			trigger_error("Documents root path ".$this->_rootpath.' does not exist, please check your config file', E_USER_ERROR); // exits
		}
		$this->_realdir = $this->_rootpath;
		$this->_messages = Array();
		if (!empty($_REQUEST['dir'])) {
			$this->_realdir = $this->_validateDirPath($_REQUEST['dir']);
		}

		if ($GLOBALS['user_system']->havePerm(PERM_EDITDOC)) {
			if (!empty($_POST['deletefolder'])) {
				if (rmdir($this->_realdir)) {
					$this->_addMessage('Folder "'.basename($this->_realdir).'" deleted');
					$this->_realdir = dirname($this->_realdir);
				}
			}
			if (!empty($_POST['renamefolder'])) {
				if ($newname = $this->_validateDirName($_POST['renamefolder'])) {
					$newdir = dirname($this->_realdir).'/'.$newname;
					if (rename($this->_realdir, $newdir)) {
						$this->_addMessage('Folder "'.basename($this->_realdir).'" renamed to "'.$newname.'"');
						$this->_realdir = $newdir;
					}
				}
			}
			if (!empty($_POST['newfolder'])) {
				if ($newname = $this->_validateDirName($_POST['newfolder'])) {
					$newdir = $this->_realdir.'/'.$newname;
					if (is_dir($newdir) || mkdir($newdir)) {
						chmod($newdir, fileperms(DOCUMENTS_ROOT_PATH));
						$this->_addMessage('Folder "'.$newname.'" created');
						$this->_realdir = $newdir;
					}
				}
			}
			if (!empty($_FILES['newfile'])) {
				foreach ($_FILES['newfile']['error'] as $key => $error) {
					if ($error == UPLOAD_ERR_OK) {
						$tmp_name = $_FILES["newfile"]["tmp_name"][$key];
						if ($name = $this->_validateFileName($_FILES["newfile"]["name"][$key])) {
							if (move_uploaded_file($tmp_name, $this->_realdir.'/'.$name)) {
								chmod($this->_realdir.'/'.$name, fileperms(DOCUMENTS_ROOT_PATH));
								$this->_addMessage('File "'.$name.'" saved');
							}
						}
					}
				}
			}
			if (!empty($_FILES['replacefile'])) {
				foreach ($_FILES['replacefile']['error'] as $origname => $error) {
					if ($error == UPLOAD_ERR_OK) {
						$tmp_name = $_FILES["replacefile"]["tmp_name"][$origname];
						if (file_exists($this->_realdir.'/'.$origname)) {
							if (move_uploaded_file($tmp_name, $this->_realdir.'/'.$origname)) {
								chmod($this->_realdir.'/'.$origname, fileperms(DOCUMENTS_ROOT_PATH));
								$this->_addMessage('File "'.$origname.'" replaced');
							}
						}
					}
				}
			}
			if (!empty($_POST['deletefile'])) {
				foreach ($_POST['deletefile'] as $delname) {
					if ($delname = $this->_validateFileName($delname)) {
						if (unlink($this->_realdir.'/'.$delname)) {
							$this->_addMessage('File "'.$delname.'" deleted');
						}
					}
				}
			}
			if (!empty($_POST['renamefile'])) {
				foreach ($_POST['renamefile'] as $origname => $newname) {
					if (($newname = $this->_validateFileName($newname)) && ($origname = $this->_validateFileName($origname))) {
						if (rename($this->_realdir.'/'.$origname, $this->_realdir.'/'.$newname)) {
							$this->_addMessage("$origname renamed to $newname");
						}
					}
				}
			}
			if (!empty($_POST['movefile'])) {
				foreach ($_POST['movefile'] as $filename => $newdir) {
					if (($filename = $this->_validateFileName($filename)) && ($fulldir = $this->_validateDirPath($newdir))) {
						if (rename($this->_realdir.'/'.$filename, $fulldir.'/'.$filename)) {
							$this->_addMessage("\"$filename\" moved to folder \"$newdir\"");
						}
					}
				}
			}
			if (!empty($_REQUEST['editfile'])) {
				if ($_REQUEST['editfile'] == '_new_') {
					$this->_editfile = '_new_';
				} else {
					$this->_editfile = $this->_validateFileName($_REQUEST['editfile']);
				}
			}
			if (!empty($_POST['savefile'])) {
				if ($filename = $this->_validateFileName($_POST['savefile'])) {
					if (!$this->_isHTML($filename)) {
						trigger_error($this->_getExtension($filename)." - Only HTML files can be saved", E_USER_ERROR); // exits
					}
					if (!empty($_POST['isnew']) && file_exists($this->_realdir.'/'.$filename)) {
						trigger_error("$filename already exists in this folder.  Please choose another name.");
						$this->_editfile = $filename;
					} else {
						if (file_put_contents($this->_realdir.'/'.$filename, process_widget('contents', array('type' => 'html')))) {
							chmod($this->_realdir.'/'.$filename, fileperms(DOCUMENTS_ROOT_PATH));
							$this->_addMessage("\"$filename\" saved");
						}
					}
				}
			}
		}
	}

	function printIframeContents()
	{
		if (!empty($this->_editfile)) {
			$this->printEditor();
		} else {
			$this->printFolderContents();
		}
	}

	function _getExtension($filename)
	{
		return strtolower(substr($filename, strrpos($filename, '.')+1));
	}

	function _isHTML($filename)
	{
		return in_array($this->_getExtension($filename), Array('html', 'htm'));
	}

	function _isImage($filename)
	{
		return in_array($this->_getExtension($filename), Array('png', 'jpg', 'jpeg', 'gif'));
	}

	function _isPDF($filename) {
		return $this->_getExtension($filename) == 'pdf';
	}

	// If $filename is an acceptable filename (extension not prohibited, no leading dot, no slashes)
	// returns it intact; else triggers an error and returns blank string
	function _validateFileName($filename) {
		$ext = $this->_getExtension($filename);
		if (in_array($ext, Array('php', 'php3', 'php4', 'inc', 'act'))) {
			trigger_error('File extension "'.$ext.'" is not allowed');
			return '';
		}
		if ($filename[0] == '.') {
			trigger_error('Files beginning with dot are not allowed');
			return '';
		}
		if (FALSE !== strpos($filename, '/') || FALSE !== strpos($filename, '\\')) {
			trigger_error('Files containing slashes are not allowed');
			return '';
		}
		return $filename;
	}

	// If $name is a valid dir name, returns a cleaned version of it (spaces to underscores)
	// Else triggers an error and returns empty string
	function _validateDirName($name) {
		$name = str_replace(' ', '_', $name);
		if (!preg_match('/[-_A-Za-z0-9&]+/', $name)) {
			trigger_error("Invalid folder name");
			return '';
		}
		return $name;
	}

	// Checks $path doesn't contain invalid parameters and converts it to a full filename path
	function _validateDirPath($path)
	{
		$bits = explode('/', $path);
		if (in_array('.', $bits) || in_array('..', $bits)) {
			trigger_error('Dot or double-dot not allowed in directory parameter', E_USER_ERROR); //exits
		}
		$res = $this->_rootpath.implode('/', $bits);
		if (!is_dir($res)) {
			trigger_error("Specified folder does not exist", E_USER_ERROR); // exits
		}
		return $res;
	}

	// Given a fullly qualified path, returns the portion that we should show to the user
	// eg /var/www/jethro/files/foo/bar becomes /foo/bar
	function getPrintedDir($dir=NULL)
	{
		if (is_null($dir)) $dir = $this->_realdir;
		return str_replace($this->_rootpath, '', $dir);
	}

	function printView()
	{
		$id = ($this->getPrintedDir() == '') ? ' id="current-folder"' : '';
		?>
		<table border="0" style="width: 100%">
			<tr>
				<td class="tree" id="folder-tree" style="width: 20%; padding: 0px">
					<div class="standard" style="height: 100%; padding: 0px 10px; overflow: auto">
						<div style="margin: 5px 0px">
						<a href="<?php echo build_url(Array('dir'=>NULL)); ?>"<?php echo $id; ?>>[Top Level]</a>
						<?php $this->_printFolderTree(); ?>
						</div>
					</div>
				</td>
				<td class="contents" id="iframe-container" style="padding: 0px">
					<?php $this->_dumpMessages(); ?>
					<iframe  frameborder="0" src="<?php echo build_url(Array('view'=>NULL,'call'=>'documents', 'dir'=>$this->getPrintedDir())); ?>" name="contents" border="0" style="width: 100%; height: 100%;"></iframe>
				</td>
			</tr>
		</table>
		<script>
			$(document).ready(function() {
				$('#iframe-container, #folder-tree').height($('body').height() - $('#header').height() - 30);
			});
		</script>
		<?php
	}

	function _printFolderOptions($dir=NULL, $indent='')
	{
		if (is_null($dir)) $dir = $this->_rootpath;
		$di = new DirectoryIterator($dir);
		if (!$di->valid()) return; // nothing to list
		$currentprinted = $this->getPrintedDir();
		foreach ($di as $fileinfo) {
			if ($fileinfo->isDir() && !$fileinfo->isDot()) {
				$printed_dir = $this->getPrintedDir($fileinfo->getPath().'/'.$fileinfo->getFilename());
				$sel = ($printed_dir == $currentprinted) ? ' selected="seelected"' : '';
				?>
				<option value="<?php echo htmlentities($printed_dir); ?>"<?php echo $sel; ?>><?php echo nbsp(htmlentities($indent.$fileinfo->getFilename())); ?></option>
				<?php
				if (strlen($indent) < 3) {
					// going too far down into the tree is too slow, limit ourselves to depth 4
					$this->_printFolderOptions($dir.'/'.$fileinfo->getFilename(), $indent.'   ');
				}
			}
		}
	}

	function _printFolderTree($dir=NULL)
	{
		if (is_null($dir)) $dir = $this->_rootpath;
		$di = new DirectoryIterator($dir);
		if (!$di->valid()) return; // nothing to list
		
		?>
		<ul>
		<?php
		$currentprinted = $this->getPrintedDir();
		$dirlist = Array();
		foreach ($di as $fileinfo) {
			if ($fileinfo->isDir() && !$fileinfo->isDot() && substr($fileinfo->getFilename(), 0, 1) != '.') {
				$dirlist[] = $fileinfo->getPath().'/'.$fileinfo->getFilename();
			}
		}
		natsort($dirlist);
		foreach ($dirlist as $dirpath) {
				$printed_dir = $this->getPrintedDir($dirpath);
				$id = ($printed_dir == $currentprinted) ? ' id="current-folder"' : '';
				?>
				<li>
					<a href="<?php echo build_url(Array('dir'=>$printed_dir)); ?>" <?php echo $id; ?>><?php echo htmlentities(basename($dirpath)); ?></a>
					<?php
					if (0 === strpos($currentprinted, $printed_dir)) {
						$this->_printFolderTree($dirpath);
					}
					?>
				</li>
				<?php
		}
		?>
		</ul>
		<?php
	}

	function printEditor()
	{
		?>
		<form method="post" action="<?php echo build_url(Array('editfile' => NULL)); ?>">
		<?php
		if ($this->_editfile == '_new_') {
			$i = 1;
			while (file_exists($this->_realdir.'/newfile'.$i.'.html')) $i++;
			?>
			<p><b>Filename: </b><input name="savefile" class="select-basename" type="text" value="newfile<?php echo $i; ?>.html" /></p>
			<input type="hidden" name="isnew" value="1" />
			<?php
			$content = '';
		} else {
			?>
			<input type="hidden" name="savefile" value="<?php echo $this->_editfile; ?>" />
			<h3><?php echo htmlentities($this->_editfile); ?></h3>
			<?php
			$content = file_get_contents($this->_realdir.'/'.$this->_editfile);
		}
		?>
		<textarea class="ckeditor" style="height: 90%" name="contents"><?php echo htmlentities($content); ?></textarea>
		<p class="right"><input type="submit" value="Save" /></p>
		</form>
		<?php
	}

	function printFolderContents()
	{
		$di = new DirectoryIterator($this->_realdir);
		$dirlist = $dirinfo = Array();
		$filelist = $fileinfo = Array();
		foreach ($di as $file) {
			if ($file->isDir() && !$file->isDot() && substr($file->getFilename(), 0, 1) != '.') {
				$dirlist[] = $file->getFilename();
				$dirinfo[$file->getFilename()] = array('size' => $file->getSize(), 'mtime' => $file->getMTime());
			}
			if ($file->isFile() && !$file->isDot() && substr($file->getFilename(), 0, 1) != '.') {
				$filelist[] = $file->getFilename();
				$fileinfo[$file->getFilename()] = array('size' => $file->getSize(), 'mtime' => $file->getMTime());
			}
		}
		natsort($dirlist);
		natsort($filelist);

		if ($GLOBALS['user_system']->havePerm(PERM_EDITDOC)) {
			$parentaction = build_url(Array('call'=>NULL,'view'=>'documents','dir'=>$this->getPrintedDir()));
			$this->_dumpMessages();
			?>
			<div class="float-right document-icons">
			<?php
			if ($this->getPrintedDir()) {
				if (empty($dirlist) && empty($filelist)) {
					?>
					<form method="post" target="_parent" action="<?php echo $parentaction ?>">
						<input type="hidden" name="deletefolder" value="1" />
						<input type="image" title="Delete this folder" class="confirm-title" src="resources/folder_delete.png" />
					</form>
					<?php
				}
				?>
				<img title="Rename this folder" id="rename-folder" src="resources/folder_edit.png"/>
				<?php
			}
			?>
				<img title="Add new sub-folder" id="add-folder" src="resources/folder_add.png" />

				<a href="<?php echo build_url(Array('editfile' => '_new_')); ?>"><img title="Edit new HTML document" src="resources/document_new.png" /></a>

				<img title="Upload new file" id="upload-file" src="resources/document_import.png" />
			</div>
			<div class="modal" id="rename-folder-modal">
			<form method="post" target="_parent" action="<?php echo $parentaction ?>">
				<p><b>Rename this folder:</b></p>
				<p><input type="text" name="renamefolder" value="<?php echo htmlentities(basename($this->getPrintedDir())); ?>" />
				<input type="submit" value="Go" />
				<input type="button" value="Cancel" class="close" /></p>
			</form>
			</div>

			<div class="modal" id="add-folder-modal">
			<form method="post" target="_parent" action="<?php echo $parentaction ?>">
			<p><b>Create new subfolder:</b></p>
			<p><input type="text" name="newfolder" />
			<input type="submit" value="Go" />
			<input type="button" value="Cancel" class="close" /></p>
			</form>
			</div>
			
			<div class="modal" id="upload-file-modal">
			<form  method="post" enctype="multipart/form-data">
			<p><b>Upload new file:</b></p>
			<p><input type="file" name="newfile[]" />
			<input type="button" value="Cancel" class="close" /></p>
			<p class="upload-progress hidden">Uploading...<br /><img src="resources/progress.gif" /></p>
			</form>
			</div>

			<div class="modal" id="replace-file-modal">
			<form  method="post" enctype="multipart/form-data">
			<p><b>Replace <span id="replaced-filename"></span> with:</b></p>
			<p><input type="file" id="replace-file" name="replacefile[X]" />
			<input type="button" value="Cancel" class="close" /></p>
			<p class="upload-progress hidden">Uploading...<br /><img src="resources/progress.gif" /></p>
			</form>
			</div>
			
			<div class="modal nowrap" id="rename-file-modal">
			<form method="post">
				<p><b>Rename file:</b></p>
				<p><input type="text" class="select-basename" id="rename-file" name="renamefile[X]" value="" style="width: 65%" />
				<input type="submit" value="Go" />
				<input type="button" value="Cancel" class="close" /></p>
			</form>
			</div>

			<div class="modal nowrap" id="move-file-modal">
			<form method="post">
				<p><b>Move <span id="moving-filename"></span> <br />to a different folder: </b></p>
				<p><select id="move-file" name="movefile[X]" style="width: 70%">
					<option value="/">[Top level]</option>
					<?php $this->_printFolderOptions(); ?>
				</select>
				<input type="submit" value="Go" />
				<input type="button" value="Cancel" class="close" /></p>
			</form>
			</div>
			<?php
		}
		?>
		<h2 style="margin-top: 0">
			<?php 
			$title = $this->getPrintedDir(); 
			if (empty($title)) $title = '[Top Level]'; 
			echo 'Documents: '.$title;
			?>
		</h2>
		<?php
		if (empty($filelist) && empty($dirlist)) {
			?>
			<p><i>There are no files in this folder</i></p>
			<?php
			return;
		} else {
			?>
			<table class="standard hoverable clear">
				<thead>
					<tr>
						<th>Filename</th>
						<th>Size</th>
						<th>Last Modified</th>
					<?php
					if ($GLOBALS['user_system']->havePerm(PERM_EDITDOC)) {
						?>
						<th>Actions</th>
						<?php
					}
					?>
					</tr>
				</thead>
				<tbody>
			<?php
			foreach ($dirlist as $dirname) {
				?>
				<tr>
					<td class="filename middle">
						<a href="<?php echo build_url(array('call'=>null, 'view' => 'documents', 'dir' => $this->getPrintedDir().'/'.$dirname)); ?>" target="_parent">
						<img src="resources/folder.png" style="margin-right: 5px" /><?php echo htmlentities($dirname); ?></a>
					</td>
					<td>&nbsp;</td>
					<td><?php echo format_datetime($dirinfo[$dirname]['mtime']); ?></td>
					<td>&nbsp;</td>
				</tr>
				<?php
			}
			$i = 0;
			foreach ($filelist as $filename) {
				?>
				<tr>
					<td class="filename"><a href="<?php echo $this->_getFileURL($filename); ?>"><?php echo htmlentities($filename); ?></a></td>
					<td><?php echo $this->_getFriendlySize($fileinfo[$filename]['size']); ?></td>
					<td><?php echo format_datetime($fileinfo[$filename]['mtime']); ?></td>
				<?php
				if ($GLOBALS['user_system']->havePerm(PERM_EDITDOC)) {
					?>
					<td>
						<span class="clickable replace-file">Replace</span> &nbsp;
						<span class="clickable rename-file">Rename</span> &nbsp;
						<span class="clickable move-file">Move</span> &nbsp;
						<form method="post" style="display: inline">
							<input type="hidden" name="deletefile[]" value="<?php echo htmlentities($filename);?>" ?>
							<label class="clickable submit confirm-title" title="Delete this file">Delete</label>
						</form>&nbsp;
					<?php
					if ($this->_isHTML($filename)) {
						?>
						<a href="<?php echo build_url(array('editfile' => $filename)); ?>">Edit</a> &nbsp;
						<?php
					}
					?>
					</td>
					<?php
				}
				?>
				</tr>
				<?php
			}
			?>
				</tbody>
			</table>
			<?php
		}
	}

	function _getFriendlySize($size)
	{
		$units = 'B';
		if ($size > 1024) {
			$size = floor($size / 1024);
			$units = 'kB';
		}
		if ($size > 1024) {
			$size = number_format($size / 1024, 1);
			$units = 'MB';
		}
		return $size.$units;
	}

	function _getFileURL($filename)
	{
		return build_url(array('call'=>'documents', 'getfile'=>$filename));
	}

	static function getMenuPermissionLevel()
	{
		return PERM_VIEWDOC;
	}

	function serveFile()
	{
		if (($filename = $this->_validateFileName($_REQUEST['getfile'])) && file_exists($this->_realdir.'/'.$filename)) {
			$mime = function_exists('mime_content_type') ? mime_content_type($this->_realdir.'/'.$filename) : '';
			if ($this->_isImage($filename)) {
				if (empty($_REQUEST['bin'])) {
					?>
					<html>
						<head>
							<title><?php echo htmlentities($filename); ?></title>
						</head>
						<body>
							<img src="<?php echo build_url(Array('bin'=>1)); ?>" style="max-width: 100%" />
						</body>
					</html>
					<?php
				} else {
					if (empty($mime)) $mime = 'image/'.$this->_getExtension($filename);
					header('Content-type: '.$mime);
					readfile($this->_realdir.'/'.$filename);
				}
			} else if ($this->_isHTML($filename)) {
				// No extra headers needed for HTML docs
				readfile($this->_realdir.'/'.$filename);
			} else if ($this->_isPDF($filename)) {
				// PDFs can often be displayed inline
				if (empty($mime)) $mime = 'application/pdf';
				header('Content-type: '.$mime);
				readfile($this->_realdir.'/'.$filename);
			} else {
				// download
				if (empty($mime)) $mime = $this->_guessContentType($filename);
				header("Pragma: public"); // required
				header("Expires: 0");
				header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
				header("Cache-Control: private",false); // required for certain browsers
				header("Content-Transfer-Encoding: binary");
				header('Content-type: '.$mime);
				header('Content-Disposition: attachment; filename="'.$filename.'"');
				readfile($this->_realdir.'/'.$filename);
			}
		}
	}

	function _guessContentType($filename)
	{
		switch ($this->_getExtension($filename)) {
			case "pdf": $ctype="application/pdf"; break;
			case "exe": $ctype="application/octet-stream"; break;
			case "zip": $ctype="application/zip"; break;
			case "doc":
			case "docx": $ctype="application/msword"; break;
			case "xls": $ctype="application/vnd.ms-excel"; break;
			case "ppt": $ctype="application/vnd.ms-powerpoint"; break;
			case "gif": $ctype="image/gif"; break;
			case "png": $ctype="image/png"; break;
			case "jpeg":
			case "jpg": $ctype="image/jpg"; break;
			default: $ctype="application/force-download";
		}
		return $ctype;
	}
}
?>
