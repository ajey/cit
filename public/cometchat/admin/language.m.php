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
		<a href="?module=language">Languages</a>
		<a href="?module=language&action=uploadlanguage">Upload new language</a>
	</div>
EOD;

function index() {
	global $db;
	global $body;	
	global $languages;
	global $navigation;
	global $lang;
	global $rtl;

	$alanguages = array();
	
	if ($handle = opendir(dirname(dirname(__FILE__)).'/lang')) {
		while (false !== ($file = readdir($handle))) {
			if ($file != "." && $file != ".." && file_exists(dirname(dirname(__FILE__)).'/lang/'.$file) && strtolower(extension($file)) == 'php') {
				$alanguages[] = substr($file,0,-4);
			}
		}
		closedir($handle);
	}

	$languages = '';
	$no = 0;

	foreach ($alanguages as $ti) {
		if (strtolower($lang) == strtolower($ti)) {
			$languages .= '<option selected>'.$ti;	
		} else {
			$languages .= '<option>'.$ti;	
		}
	}

	$rtly = "";
	$rtln = "";

	if ($rtl == 1) {
		$rtly = "checked";
	} else {
		$rtln = "checked";
	}


	$body = <<<EOD
	$navigation
	<form action="?module=language&action=updatelanguage" method="post">
	<div id="rightcontent" style="float:left;width:720px;border-left:1px dotted #ccc;padding-left:20px;">
		<h2>Languages</h2>
		<h3>To set the language, select an option from the drop-down menu. If the language direction is right-to-left then set the parameter to yes.</h3>

		<div>
			<div id="centernav">
				<div class="title">Language:</div><div class="element"><select class="inputbox" name="lang">$languages</select></div>
				<div style="clear:both;padding:5px;"></div>
				<div class="title">Right to left:</div><div class="element"><input type="radio" name="rtl" value="1" $rtly>Yes <input type="radio" $rtln name="rtl" value="0" >No</div>
				<div style="clear:both;padding:5px;"></div>
			</div>
			<div id="rightnav">
				<h1>Tips</h1>
				<ul id="modules_availablemodules">
					<li>To create a new language, create a copy of every instance of en.php in the "cometchat" folder to XX.php and then edit the contents</li>
 				</ul>
			</div>
		</div>

		<div style="clear:both;padding:7.5px;"></div>
		<input type="submit" value="Update Language" class="button">&nbsp;&nbsp;or <a href="?module=language">cancel</a>
	</div>

	<div style="clear:both"></div>
	</form>
EOD;

	template();

}

function updatelanguage() {

	$icons = '';

	if (!empty($_POST['lang'])) {
		$data = '$lang = \''.$_POST['lang'].'\';'."\r\n".'$rtl = '.$_POST['rtl'].';';

		configeditor('LANGUAGE',$data,0);
	}

	$_SESSION['error'] = 'Language details updated successfully';

	header("Location:?module=language");

}

function uploadlanguage() {
	global $db;
	global $body;	
	global $trayicon;
	global $navigation;

	$body = <<<EOD
	$navigation
	<form action="?module=language&action=uploadlanguageprocess" method="post" enctype="multipart/form-data">
	<div id="rightcontent" style="float:left;width:720px;border-left:1px dotted #ccc;padding-left:20px;">
		<h2>Upload new language</h2>
		<h3>Have you downloaded a new CometChat language? Use our simple installation facility to add the new language to your site.</h3>

		<div>
			<div id="centernav">
				<div class="title">Language:</div><div class="element"><input type="file" class="inputbox" name="file"></div>
				<div style="clear:both;padding:5px;"></div>
			</div>
			<div id="rightnav">
				<h1>Tips</h1>
				<ul id="modules_availablelanguages">
					<li>You can download new languages from <a href="http://www.cometchat.com">our website</a>.</li>
 				</ul>
			</div>
		</div>

		<div style="clear:both;padding:7.5px;"></div>
		<input type="submit" value="Add language" class="button">&nbsp;&nbsp;or <a href="?module=language">cancel</a>
	</div>

	<div style="clear:both"></div>

EOD;

	template();

}

function uploadlanguageprocess() {
	global $db;
	global $body;	
	global $trayicon;
	global $navigation;
	global $languages;

	$extension = '';
	$error = '';

	if (!empty($_FILES["file"]["size"])) {
		if ($_FILES["file"]["error"] > 0) {
			$error = "Language corrupted. Please try again.";
		} else {
			if (file_exists(dirname(dirname(__FILE__))."/temp/" . $_FILES["file"]["name"])) {
				unlink(dirname(dirname(__FILE__))."/temp/" . $_FILES["file"]["name"]);
			}

			if (!move_uploaded_file($_FILES["file"]["tmp_name"], dirname(dirname(__FILE__))."/temp/" . $_FILES["file"]["name"])) {
				$error = "Unable to copy to temp folder. Please CHMOD temp folder to 777.";
			}
		}
	} else {
		$error = "Language not found. Please try again.";
	}
	
	if (!empty($error)) {
		$_SESSION['error'] = $error;
		header("Location: ?module=language&action=uploadlanguage");
		exit;
	}

	require_once('pclzip.lib.php');

	$filename = $_FILES['file']['name'];

	$archive = new PclZip(dirname(dirname(__FILE__))."/temp/" . $_FILES["file"]["name"]);

	if ($archive->extract(PCLZIP_OPT_PATH, dirname(dirname(__FILE__))) == 0) {
		$error = "Unable to unzip archive. Please manually install the language or create a support ticket.";
	}

	if (!empty($error)) {
		$_SESSION['error'] = $error;
		header("Location: ?module=language&action=uploadlanguage");
		exit;
	}

	unlink(dirname(dirname(__FILE__))."/temp/" . $_FILES["file"]["name"]);
	
	$_SESSION['error'] = 'Language added successfully';
	header("Location: ?module=language");
	exit;
	
}