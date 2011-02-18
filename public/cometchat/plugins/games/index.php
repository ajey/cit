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

include dirname(dirname(dirname(__FILE__)))."/plugins.php";

if (file_exists(dirname(__FILE__)."/lang/".$lang.".php")) {
	include dirname(__FILE__)."/lang/".$lang.".php";
} else {
	include dirname(__FILE__)."/lang/en.php";
}

if ($rtl == 1) {
	$rtl = "_rtl";
} else {
	$rtl = "";
}

if (!file_exists(dirname(__FILE__)."/themes/".$theme."/games".$rtl.".css")) {
	$theme = "default";
}

if (empty($_GET['action'])) {

$toId = $_GET['id'];

echo <<<EOD
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title>{$games_language[1]}</title> 
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/> 
<link type="text/css" rel="stylesheet" media="all" href="themes/{$theme}/games{$rtl}.css" /> 

<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js"></script>
<script>
$(document).ready(function() {
	$("li").click(function() {
		var info = $(this).attr('id').split(',');
		var gameId = info[0];
		var width = info[1];
		location.href = 'index.php?action=request&toId={$toId}&gameId='+gameId+'&gameWidth='+width;
	});
});

</script>

</head>
<body>
<div class="container">
<div class="container_title">{$games_language[2]}</div>

<div class="container_body">

<ul class="games">
	{$games_language[13]}
</ul>
<div style="clear:both"></div>
</div>
</div>
</div>

</body>
</html>
EOD;


} else {

if ($_GET['action'] == 'request') {
	$random_from = md5(getTimeStamp()+$userid+'from');
	$random_to = md5(getTimeStamp()+$_GET['toId']+'to');
	$random_order = $random_from.','.$random_to;
	$toId = $_GET['toId'];

	sendMessageTo($_GET['toId'],$games_language[3]." <a href='javascript:void(0);' onclick=\"javascript:jqcc.ccgames.accept('".$userid."','".$random_from."','".$random_to."','".$random_order."','".$_GET['gameId']."','".$_GET['gameWidth']."');\">".$games_language[4]."</a>".$games_language[5]);

	sendSelfMessage($_GET['toId'],$games_language[6]);

echo <<<EOD
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title>{$games_language[7]}</title> 
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/> 
<link type="text/css" rel="stylesheet" media="all" href="themes/{$theme}/games{$rtl}.css" /> 

<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js"></script>
<script>
$(document).ready(function() {
	$("li").click(function() {
		var info = $(this).attr('id').split(',');
		var gameId = info[0];
		var width = info[1];
		location.href = 'index.php?action=request&toId={$toId}&gameId='+gameId+'&gameWidth='+width;
	});
});

</script>

</head>
<body onload="setTimeout('window.close()',2000);">

<div  class="container">
<div  class="container_title">{$games_language[8]}</div>

<div  class="container_body">

<div class="games">{$games_language[9]}</div>

<div style="clear:both"></div>
</div>
</div>
</div>

</body>
</html>
EOD;

}

if ($_GET['action'] == 'accept') {
	sendMessageTo($_POST['to'],$games_language[10]." <a href='javascript:void(0);' onclick=\"javascript:jqcc.ccgames.accept_fid('".$userid."','".$_POST['tid']."','".$_POST['fid']."','".$_POST['rid']."','".$_POST['gameId']."','".$_POST['gameWidth']."');\">".$games_language[11]."</a>");
}

if ($_GET['action'] == 'play') {

	$fid = $_GET['fid'];
	$tid = $_GET['tid'];
	$rid = $_GET['rid'];
	$gameid = $_GET['gameId'];
	$auth = md5($fid.$rid.'100'.$gameid.'fdd4605ba06214842e3caee695bd2787');
	$rid = urlencode($rid);

	global $userid;
	global $db;
	global $language;

	$sql = ("select ".DB_USERTABLE_NAME." as name from ".TABLE_PREFIX.DB_USERTABLE." where ".DB_USERTABLE_USERID." = '".mysql_real_escape_string($userid)."'");
	$query = mysql_query($sql);
	$user = mysql_fetch_array($query);
	$name = urlencode($user['name']);

	if (function_exists('processName')) {
		$name = processName($name);
	}

	echo <<<EOD
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/> 
<title>{$games_language[12]}</title>
<script language="javascript">AC_FL_RunContent = 0;</script>
<script src="js/AC_RunActiveContent.js" language="javascript"></script>
<style>
html, body, div, span, applet, object, iframe,
h1, h2, h3, h4, h5, h6, p, blockquote, pre,
a, abbr, acronym, address, big, cite, code,
del, dfn, em, font, img, ins, kbd, q, s, samp,
small, strike, strong, sub, sup, tt, var,
dl, dt, dd, ol, ul, li,
fieldset, form, label, legend,
table, caption, tbody, tfoot, thead, tr, th, td {
	margin: 0;
	padding: 0;
	border: 0;
	outline: 0;
	font-weight: inherit;
	font-style: inherit;
	font-size: 100%;
	font-family: inherit;
	vertical-align: baseline;
    text-align: center;
}

body{ overflow-x:hidden;overflow-y:hidden; }

</style>
</head>
<body bgcolor="#fff"> 

<iframe src="http://games.cometchat.com/channel_auth.asp?channel_id=27377&uid={$fid}&nick_name={$name}&method_type=matching&matching_uids={$rid}&matching_stake=100&matching_game_id={$gameid}&auth_sig={$auth}" height="710" width="1000" scrolling="no"></iframe>
</body>
</html>
EOD;
}

}