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
	</div>
EOD;

function index() {
	global $body;

	$onlineusers = onlineusers();

	$sql = ("select max(id) totalmessages from cometchat");
	$query = mysql_query($sql); 
	$r = mysql_fetch_array($query);
	$totalmessages = $r['totalmessages'];

	$now = getTimeStamp()-60*60*24;

	$sql = ("select count(id) totalmessages from cometchat where sent >= $now");
	$query = mysql_query($sql); 
	$r = mysql_fetch_array($query);
	$totalmessagest = $r['totalmessages'];

	$detectchangepass = 'Below are quick statistics of your site. Be sure to frequently change your administrator password.';

	if ($_SESSION['cometchat_admin_user'] == 'cometchat' && $_SESSION['cometchat_admin_pass'] == 'cometchat') {
		$detectchangepass = '<span style="color:#ff0000">Warning: Default administrator username/password detected. Please go to settings and change the username and password.</span>';
	}

	if (empty($totalmessages)) {
		$totalmessages = 0;
	}

		$body = <<<EOD
<h2>Welcome</h2>
<h3>$detectchangepass</h3>

<div style="float:left;padding-right:20px;border-right:1px dotted #cccccc;margin-right:20px;">
	<h1 style="font-size: 70px; font-weight: bold;">$onlineusers</h1>
	<span style="font-size: 10px;">USERS CHATTING</span>
</div>

<div style="float:left;padding-right:20px;border-right:1px dotted #cccccc;margin-right:20px;">
	<h1 style="font-size: 70px; font-weight: bold;">$totalmessages</h1>
	<span style="font-size: 10px;">TOTAL MESSAGES</span>
</div>

<div style="float:left;padding-right:20px;border-right:1px dotted #cccccc;margin-right:20px;">
	<h1 style="font-size: 70px; font-weight: bold;">$totalmessagest</h1>
	<span style="font-size: 10px;">MESSAGES SENT IN THE LAST 24 HOURS</span>
</div>


<div style="clear:both"></div>
	
EOD;
	template();
}

function loadexternal() {
	global $getstylesheet;
	if (file_exists(dirname(dirname(__FILE__)).'/'.$_GET['type'].'s/'.$_GET['name'].'/settings.php')) {
		require (dirname(dirname(__FILE__)).'/'.$_GET['type'].'s/'.$_GET['name'].'/settings.php');
	} else {
echo <<<EOD
$getstylesheet
<form action="?module=dashboard&action=loadexternal&type=module&name=twitter&process=true" method="post">
<div id="content">
		<h2>No configuration required</h2>
		<h3>Sorry there are no settings to modify</h3>
		<input type="button" value="Close Window" class="button" onclick="javascript:window.close();">
</div>
</form>
EOD;
	}
}