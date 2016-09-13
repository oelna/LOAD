<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

date_default_timezone_set('Europe/Berlin');

DEFINE('DS', DIRECTORY_SEPARATOR);
DEFINE('BR', '<br />');


/* USER CONFIG */

$dir = dirname(__FILE__);
$dl_dir = 'downloads';

$uploaded_user = ''; // if you have credentials for uploaded.net, fill them in here (numeric user id, password)
$uploaded_pass = '';
$dropbox_app_id = ''; // if you want to use dropbox saving, learn how to get the app id on https://www.dropbox.com/developers/saver

/* END USER CONFIG */


if(!is_dir($dir.DS.$dl_dir)) {
	mkdir($dir.DS.$dl_dir, 0777);
}

// http://stackoverflow.com/a/23888858/3625228
function nice_filesize($bytes, $dec = 1, $kbsize = 1024) {
    $size   = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
    $factor = floor((strlen($bytes) - 1) / 3);
    if($factor == 0) $dec = 0;

    return '<span class="value">'. sprintf("%.{$dec}f", $bytes / pow($kbsize, $factor)) .'</span><span class="unit">'. @$size[$factor] .'</span>';
}

?><!DOCTYPE html>
<html>
<head>
	<title>LOAD</title>
	
	<meta name="viewport" content="width=device-width, initial-scale=1" />

	<style>
		* { margin: 0; padding: 0; box-sizing: border-box; }

		html {
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "Roboto", "Oxygen", "Ubuntu", "Cantarell", "Fira Sans", "Droid Sans", "Helvetica Neue", sans-serif;
			font-size: 100%;
			line-height: 1.45;
		}

		a { color: #007aff; text-decoration: none; }

		main {
			width: 95%;
			margin: 2em auto;
		}

		section {
			margin: 2em 0;
		}

		#form input[type="url"] {
			width: CALC(100% - 7.5em);
			padding: 0.2em;
			border: 1px solid #ddd;
			font-size: 1.1em;
		}

		#form input[type="url"]:focus {
			outline: 0;
			border-color: #333;
		}

		#form input[type="submit"] {
			width: 7em;
			padding: 0.2em;
			font-size: 1.1em;
		}

		#list table {
			width: 100%;
			border-collapse: collapse;
		}

		#list table td {
			vertical-align: top;
			padding: 0.5em 0;
		}

		#list table tr + tr td {
			border-top: 1px solid #ddd;
		}

		#list table td + td {
			padding-left: 0.5em;
		}

		#list table .size,
		#list table .delete {
			text-align: right;
		}

		#list table .size .unit {
			display: inline-block;
			width: 1.8em;
			text-align: left;
			margin-left: 0.5em;
		}

		.new,
		.delete a {
			display: inline-block;
			font-size: 0.8em;
			padding: 0.1em 0.35em 0em;
			background: #4CD964;
			color: #fff;
			border-radius: 0.2em;
			text-decoration: none;
		}

		.delete a { background: #FF4981; }

		.dropbox a {  }

		.message {
			background: #FFCC00;
			padding: 1em;
			margin-bottom: 1em;
			border-radius: 0.45em;
		}

		.message:empty { display: none; }

		.message .filename {
			color: #FF3B30;
		}

		@media only screen and (min-width: 50em) {
			main {
				width: 70%;
			}
		}

		@media only screen and (min-width: 85em) {
			main {
				width: 60%;
			}
		}
	</style>
</head>
<body>
	<main>
		<section id="form">
			<div class="message"><?php

				// handle downloads
				if(!empty($_POST['url'])) {

					$dl_url = trim($_POST['url']);
					$filename = pathinfo($dl_url, PATHINFO_BASENAME);

					if(preg_match('/ul\.to|uploaded/i', $dl_url) > 0) {
						exec('bash '.$dir.'/uploaded.sh '.$uploaded_user.' '.$uploaded_pass.' '.$dl_url.' '.$dl_dir);
						echo('Downloaded the file from uploaded');
					} else {
						exec('wget -P '.$dir.DS.$dl_dir.' '.$dl_url.' >/dev/null 2>&1');

						if(file_exists($dir.DS.$dl_dir.DS.urldecode($filename))) {
							echo('Downloaded the file <span class="filename">'.urldecode($filename).'</span>. Probably.');
						} else {
							echo('Download of file <span class="filename">'.urldecode($filename).'</span> (probably) failed');
						}
					}
				}

				// handle delete
				if(!empty($_GET['delete'])) {
					$file_to_delete = pathinfo($_GET['delete'], PATHINFO_BASENAME);

					if(file_exists($dir.DS.$dl_dir.DS.$file_to_delete)) {
						unlink($dir.DS.$dl_dir.DS.$file_to_delete);
						echo('Deleted <span class="filename">'.$file_to_delete.'</span>');
					} else {
						echo('File <span class="filename">'.$file_to_delete.'</span> not found');
					}
				}
			?></div>
			<form method="post">
				<input type="url" name="url" placeholder="URL of the file to download" />
				<input type="submit" value="Download" />
			</form>
		</section>

		<section id="list">
			<table>
			<?php
				$downloaded_files = array_diff(scandir($dir.DS.$dl_dir), array('..', '.'));
				$files = array();

				// gather info on all files
				foreach($downloaded_files as $file) {
					$fileabs = $dir.DS.$dl_dir.DS.$file;
					$filetime = filectime($fileabs);
					$filesize = filesize($fileabs);

					$files[] = array(
						'abspath' => $dir.DS.$dl_dir.DS.$file,
						'name' => $file,
						'timestamp' => $filetime,
						'size' => $filesize,
						'nicesize' => nice_filesize($filesize, 1, 1000),
						'new' => ((time()-$filetime) < 7200)
					);
				}

				// sort by date
				usort($files, function($a, $b) {
					return strcmp($a['timestamp'], $b['timestamp']);
				});
				$files = array_reverse($files);

				// output
				foreach($files as $file) {
					$new = ($file['new']) ? ' <span class="new">NEW!</span>' : '';

					echo('<tr>');
					echo('<td class="date"><time datetime="'.date('Y-m-d H:i:s', $file['timestamp']).'">'.date('j.n.y G:i', $file['timestamp']).'</time></td>');
					echo('<td class="url"><a href="'.$dl_dir.'/'.$file['name'].'">'.$file['name'].'</a>'.$new.'</td>');
					if(!empty($dropbox_app_id)) echo('<td class="dropbox"><a class="dropbox-saver" href="'.$dl_dir.'/'.$file['name'].'">'.$file['name'].'</a></td>');
					echo('<td class="size"><span title="'.$file['size'].'">'.$file['nicesize'].'</span></td>');
					echo('<td class="delete"><a href="?delete='.$file['name'].'">Delete</a></td>');
					echo('<tr>');
				}
			?>
			</table>
		</section>
	</main>

	<?php if(!empty($dropbox_app_id)) { echo('<script type="text/javascript" src="https://www.dropbox.com/static/api/2/dropins.js" id="dropboxjs" data-app-key="'.trim($dropbox_app_id).'"></script>'); } ?>
</body>
</html>
