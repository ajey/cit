<?php

/*

CometChat
Copyright (c) 2010 Inscripts

CometChat ('the Software') is a copyrighted work of authorship. Inscripts 
retains ownership of the Software and any copies of it, regardless of the 
form in which the copies may exist. This license is not a sale of the 
original Software or any copies.

By installing and using CometChat on your server, you agree to the following
terms and conditions. Such agreement is either on your own behalf or on behalf
of any corporate entity which employs you or which you represent
('Corporate Licensee'). In this Agreement, 'you' includes both the reader
and any Corporate Licensee and 'Inscripts' means Inscripts (I) Private Limited:

CometChat license grants you the right to run one instance (a single installation)
of the Software on one web server and one web site for each license purchased.
Each license may power one instance of the Software on one domain. For each 
installed instance of the Software, a separate license is required. 
The Software is licensed only to you. You may not rent, lease, sublicense, sell,
assign, pledge, transfer or otherwise dispose of the Software in any form, on
a temporary or permanent basis, without the prior written consent of Inscripts. 

The license is effective until terminated. You may terminate it
at any time by uninstalling the Software and destroying any copies in any form. 

The Software source code may be altered (at your risk) 

All Software copyright notices within the scripts must remain unchanged (and visible). 

The Software may not be used for anything that would represent or is associated
with an Intellectual Property violation, including, but not limited to, 
engaging in any activity that infringes or misappropriates the intellectual property
rights of others, including copyrights, trademarks, service marks, trade secrets, 
software piracy, and patents held by individuals, corporations, or other entities. 

If any of the terms of this Agreement are violated, Inscripts reserves the right 
to revoke the Software license at any time. 

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.

*/

if (!defined('CCADMIN')) { echo "NO DICE"; exit; }

$navigation = <<<EOD
	<div id="leftnav">
		<a href="?module=plugins">Live plugins</a>
		<a href="?module=plugins&action=uploadplugin">Upload new plugin</a>
	</div>
EOD;

function index() {
	global $db;
	global $body;	
	global $plugins;
	global $navigation;
	global $lang;

	$aplugins = array();
	
	if ($handle = opendir(dirname(dirname(__FILE__)).'/plugins')) {
		while (false !== ($file = readdir($handle))) {
			if ($file != "." && $file != ".." && file_exists(dirname(dirname(__FILE__)).'/plugins/'.$file.'/code.php')) {
				$aplugins[] = $file;
			}
		}
		closedir($handle);
	}

	$pluginslist = '';

	foreach ($aplugins as $plugin) {
		require dirname(dirname(__FILE__)).'/plugins/'.$plugin.'/code.php';
		$pluginslist .= '<li class="ui-state-default"><img src="../plugins/'.$plugininfo[0].'/icon.png" style="margin:0;margin-right:5px;float:left;"></img><span style="font-size:11px;float:left;margin-top:2px;margin-left:5px;">'.$plugininfo[1].'</span><span style="font-size:11px;float:right;margin-top:2px;margin-right:5px;"><a href="?module=plugins&action=addplugin&data='.$plugininfo[0].'">add</a></span><div style="clear:both"></div></li>';
	}

	$activeplugins = '';
	$no = 0;

	foreach ($plugins as $ti) {

		$title = ucwords($ti);

		if (file_exists(dirname(dirname(__FILE__)).'/plugins/'.$ti.'/lang/'.$lang.'.php')) {
			require dirname(dirname(__FILE__)).'/plugins/'.$ti.'/lang/'.$lang.'.php';
			$title = ${$ti."_language"}[0];
		}

		++$no;
		
		$activeplugins .= '<li class="ui-state-default" id="'.$no.'" d1="'.$ti.'"><img src="../plugins/'.$ti.'/icon.png" style="margin:0;margin-right:5px;float:left;"></img><span style="font-size:11px;float:left;margin-top:2px;margin-left:5px;" id="'.$ti.'_title">'.stripslashes($title).'</span><span style="font-size:11px;float:right;margin-top:2px;margin-right:5px;"><a href="javascript:void(0)" onclick="javascript:plugins_renameplugin(\''.$ti.'\')">rename</a> | <a href="javascript:void(0)" onclick="javascript:plugins_configplugin(\''.$ti.'\')">config</a> | <a href="javascript:void(0)" onclick="javascript:plugins_removeplugin(\''.$no.'\')">remove</a></span><div style="clear:both"></div></li>';
	}


	$body = <<<EOD
	$navigation

	<div id="rightcontent" style="float:left;width:720px;border-left:1px dotted #ccc;padding-left:20px;">
		<h2>Live Plugins</h2>
		<h3>Use your mouse to change the order in which the plugins appear on the bar (left-to-right). You can add available plugins from the right.</h3>

		<div>
			<ul id="modules_liveplugins">
				$activeplugins
			</ul>
			<div id="rightnav" style="margin-top:5px">
				<h1>Available plugins</h1>
				<ul id="modules_availableplugins">
				$pluginslist
				</ul>
			</div>
		</div>

		<div style="clear:both;padding:7.5px;"></div>
		<input type="button" onclick="javascript:plugins_updateorder()" value="Update order" class="button">&nbsp;&nbsp;or <a href="?module=plugins">cancel</a>
	</div>

	<div style="clear:both"></div>

	<script type="text/javascript">
		$(function() {
			$("#modules_liveplugins").sortable({ connectWith: 'ul' });
			$("#modules_liveplugins").disableSelection();
		});
	</script>

EOD;

	template();

}

function updateorder() {

	$icons = '';

	if (!empty($_POST['order'])) {

		$plugindata = '$plugins = array(';

		$plugindata .= $_POST['order'];

		$plugindata = substr($plugindata,0,-1).');';
	
		configeditor('PLUGINS',$plugindata);
	}

	echo "1";

}

function addplugin() {
	global $plugins;

	if (!empty($_GET['data'])) {
	
		$plugindata = '$plugins = array(';

		foreach ($plugins as $plugin) {
			$plugindata .= "'$plugin',";
		}

		$plugindata .= "'{$_GET['data']}',";

		$plugindata = substr($plugindata,0,-1).');';
	
		configeditor('PLUGINS',$plugindata);
	}
	header("Location:?module=plugins");
}

function uploadplugin() {
	global $db;
	global $body;	
	global $trayicon;
	global $navigation;

	$body = <<<EOD
	$navigation
	<form action="?module=plugins&action=uploadpluginprocess" method="post" enctype="multipart/form-data">
	<div id="rightcontent" style="float:left;width:720px;border-left:1px dotted #ccc;padding-left:20px;">
		<h2>Upload new plugin</h2>
		<h3>Have you downloaded a new CometChat plugin? Use our simple installation facility to add the new plugin to your site.</h3>

		<div>
			<div id="centernav">
				<div class="title">Plugin:</div><div class="element"><input type="file" class="inputbox" name="file"></div>
				<div style="clear:both;padding:5px;"></div>
			</div>
			<div id="rightnav">
				<h1>Tips</h1>
				<ul id="modules_availableplugins">
					<li>You can download new plugins from <a href="http://www.cometchat.com">our website</a>.</li>
 				</ul>
			</div>
		</div>

		<div style="clear:both;padding:7.5px;"></div>
		<input type="submit" value="Add plugin" class="button">&nbsp;&nbsp;or <a href="?module=plugins">cancel</a>
	</div>

	<div style="clear:both"></div>

EOD;

	template();

}

function uploadpluginprocess() {
	global $db;
	global $body;	
	global $trayicon;
	global $navigation;
	global $plugins;

	$extension = '';
	$error = '';

	if (!empty($_FILES["file"]["size"])) {
		if ($_FILES["file"]["error"] > 0) {
			$error = "Plugin corrupt. Please try again.";
		} else {
			if (file_exists(dirname(dirname(__FILE__))."/temp/" . $_FILES["file"]["name"])) {
				unlink(dirname(dirname(__FILE__))."/temp/" . $_FILES["file"]["name"]);
			}

			if (!move_uploaded_file($_FILES["file"]["tmp_name"], dirname(dirname(__FILE__))."/temp/" . $_FILES["file"]["name"])) {
				$error = "Unable to copy to temp folder. Please CHMOD temp folder to 777.";
			}
		}
	} else {
		$error = "Plugin not found. Please try again.";
	}
	
	if (!empty($error)) {
		$_SESSION['error'] = $error;
		header("Location: ?module=plugins&action=uploadplugin");
		exit;
	}

	require_once('pclzip.lib.php');

	$filename = $_FILES['file']['name'];

	$archive = new PclZip(dirname(dirname(__FILE__))."/temp/" . $_FILES["file"]["name"]);
	$pluginname = basename($filename, ".zip");

	if (is_dir(dirname(dirname(__FILE__))."/plugins/".$pluginname)) {
		deletedirectory(dirname(dirname(__FILE__))."/plugins/".$pluginname);
	}

	if ($archive->extract(PCLZIP_OPT_PATH, dirname(dirname(__FILE__))."/plugins") == 0) {
		$error = "Unable to unzip archive. Please manually upload the contents of the zip file to plugins folder.";
	}

	if (!empty($error)) {
		$_SESSION['error'] = $error;
		header("Location: ?module=plugins&action=uploadplugin");
		exit;
	}

	unlink(dirname(dirname(__FILE__))."/temp/" . $_FILES["file"]["name"]);

	

	$plugindata = '$plugins = array(';

	foreach ($plugins as $plugin) {
		$plugindata .= "'$plugin',";
	}

	$plugindata .= "'{$filename}',";
	$plugindata = substr($plugindata,0,-1).');';
	
	configeditor('PLUGINS',$plugindata);


	$src = BASE_URL."/plugins/$pluginname/install.php";

	$body = <<<EOD
	$navigation
	<form action="?module=plugins&action=uploadpluginprocess" method="post" enctype="multipart/form-data">
	<div id="rightcontent" style="float:left;width:720px;border-left:1px dotted #ccc;padding-left:20px;">
		<h2>Plugin installation</h2>
		<h3>We are now proceeding to install any configurations that might be necessary.</h3>

		<div>
			<div id="centernav">
				<iframe src="{$src}" width=400 height=300 frameborder=1></iframe>
				<div style="clear:both;padding:5px;"></div>
			</div>
		</div>

	</div>

	<div style="clear:both"></div>

EOD;

	template();
	
}