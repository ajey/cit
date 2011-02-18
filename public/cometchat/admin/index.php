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

include_once dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR."config.php";
include_once dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR."cometchat_shared.php";
include_once dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR."php4functions.php";

$menuoptions = array("Dashboard","Announcements","Chatrooms","Spy","Modules","Plugins","Themes","Language","Settings","Logs","Logout");

if (!session_id()) {
	session_name('CCADMIN');
	session_start();
} 

if(get_magic_quotes_runtime()) { 
    set_magic_quotes_runtime(false); 
}

include_once dirname(__FILE__)."/shared.php";

function stripSlashesDeep($value) {
	$value = is_array($value) ? array_map('stripSlashesDeep', $value) : stripslashes($value);
	return $value;
}

if (get_magic_quotes_gpc() || (defined('FORCE_MAGIC_QUOTES') && FORCE_MAGIC_QUOTES == 1)) {
	$_GET = stripSlashesDeep($_GET);
	$_POST = stripSlashesDeep($_POST);
	$_COOKIE = stripSlashesDeep($_COOKIE);
}

if (empty($_SESSION['timedifference'])) {
	$_SESSION['timedifference'] = 0;
}

$dbh = mysql_connect(DB_SERVER.':'.DB_PORT,DB_USERNAME,DB_PASSWORD);
if (!$dbh) {
	echo "<h3>Unable to connect to database. Please check details in configuration file.</h3>";
	exit();
}

mysql_selectdb(DB_NAME,$dbh);
mysql_query("SET NAMES utf8");
mysql_query("SET CHARACTER SET utf8");
mysql_query("SET COLLATION_CONNECTION = 'utf8_general_ci'");  

$usertable = TABLE_PREFIX.DB_USERTABLE;
$usertable_username = DB_USERTABLE_NAME;
$usertable_userid = DB_USERTABLE_USERID;

$body = '';

if (!empty($_POST['username'])) { $_SESSION['cometchat_admin_user'] = $_POST['username']; }
if (!empty($_POST['password'])) { $_SESSION['cometchat_admin_pass'] = $_POST['password']; }

authenticate();

$module = "dashboard";
$action = "index";

if (!empty($_GET['module'])) {
	if (file_exists(dirname(__FILE__).'/'.$_GET['module'].'.m.php')) {
		$module = $_GET['module'];
	}
}

define ('CCADMIN',true);

if (!file_exists(dirname(__FILE__).'/'.$module.'.m.php')) {
	$_SESSION['error'] = 'Oops. This module does not exist.';
	$module = 'dashboard';
}

require (dirname(__FILE__).'/'.$module.'.m.php');


if (!empty($_GET['action'])) {
	if (function_exists($_GET['action'])) {
		$action = $_GET['action'];
	}
}

call_user_func($action);

function onlineusers() {
	global $db;

	$sql = ("select count(distinct(cometchat.from)) users from cometchat where ('".getTimeStamp()."'-cometchat.sent)<300");

	$query = mysql_query($sql); 
	$chat = mysql_fetch_array($query);

	return $chat['users'];
}

function authenticate() {
	if (empty ($_SESSION['cometchat_admin_user']) || empty ($_SESSION['cometchat_admin_pass']) || !($_SESSION['cometchat_admin_user'] == ADMIN_USER && $_SESSION['cometchat_admin_pass'] == ADMIN_PASS)) {
		global $body;
		$body = <<<EOD
			<form method="post" action="?module=dashboard">
			<div class="chatbar"><div style="float:left">Please login with your username and password</div><div style="clear:both"></div></div>
<div class="chat chatnoline">Username: <input type="text" name="username" class="inputbox"></div>
<div class="chat chatnoline">Password: <input type="password" name="password" class="inputbox"></div>
<div class="chat chatnoline"><input type="submit" value="Login" class="button"></div>
			</form>
EOD;
		template();
	}
}

function template() {

	global $body;
	global $menuoptions;
	global $module;

	$tabs = $menuoptions;

	$tabstructure = '';

	foreach ($tabs as $tab) {
		$tabslug = strtolower($tab);
		$tabslug = str_replace(" ","",$tabslug);
	    $tabslug = str_replace("/","",$tabslug);

		$current = '';

		if (!empty($module) && $module == $tabslug) {
			$current = 'class="current"'; 
		}
		
		$tabstructure .= <<<EOD
		  <li $current>
			<a href="?module={$tabslug}">{$tab}</a>
		  </li>
EOD;

	}

	$errorjs = '';

	if (!empty($_SESSION['error'])) {
		$errorjs = <<<EOD
<script>
\$(document).ready(function() {
	\$.fancyalert('{$_SESSION['error']}');
});
</script>
EOD;
		unset($_SESSION['error']);
	}

	echo <<<EOD
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title>CometChat Administration</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">

<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>

<link href="css/admin.css" media="all" rel="stylesheet" type="text/css" />
<script src="js/admin.js"></script>

<link rel="stylesheet" href="css/jquery-ui.css" type="text/css" media="all" />
<script src="js/jquery-ui.min.js" type="text/javascript"></script>
<script src="js/jquery.bgiframe-2.1.1.js" type="text/javascript"></script>
<script src="js/jquery-ui-i18n.min.js" type="text/javascript"></script>
</head>
<body>
<div id="container">
<div style="float:right;padding-bottom:30px;padding-right:20px"><img src="logo.gif"></div>
<div style="clear:both"></div>
<div id="views">
<ol class="tabs">
{$tabstructure}
</ol>
</div>
<div style="clear:both"></div>
<div id="content">
$body
</div>
<div style="text-align:center;padding-top:10px"><a href="http://www.cometchat.com" target="_blank">Powered by CometChat</a></div>
</div>
$errorjs
</body>
</html>
EOD;

exit();
}