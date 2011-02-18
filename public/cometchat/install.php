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

include (dirname(__FILE__)).DIRECTORY_SEPARATOR.'cometchat_init.php';

$files = array('config.php','modules/','plugins/','temp/','lang/','themes/','plugins/handwrite/uploads/','plugins/filetransfer/uploads/');
$extra = '';

$unwritable = '';
foreach ($files as $file) {
	if (iswritable(dirname(__FILE__).'/'.$file)) {
	} else {
		$unwritable .= '<br/>'.$file;
	}
}

if (!empty($unwritable)) {
	$extra = "<br/><br/><strong>Please CHMOD the following files<br/>and folders to 777:</strong><br/>$unwritable";
}

function iswritable($path) {

	if ($path{strlen($path)-1}=='/')
		return iswritable($path.uniqid(mt_rand()).'.tmp');

	if (file_exists($path)) {
		if (!($f = @fopen($path, 'r+')))
			return false;
		fclose($f);
		return true;
	}

	if (!($f = @fopen($path, 'w')))
		return false;
	fclose($f);
	unlink($path);
	return true;
} 

$body = '';
$path = '';

$rollback = 0;
$errors = '';

	$content = <<<EOD
RENAME TABLE `cometchat` to `cometchat_messages_old`;

DROP TABLE IF EXISTS `cometchat`;
CREATE TABLE  `cometchat` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `from` int(10) unsigned NOT NULL,
  `to` int(10) unsigned NOT NULL,
  `message` text NOT NULL,
  `sent` int(10) unsigned NOT NULL default '0',
  `read` tinyint(1) unsigned NOT NULL default '0',
  `direction` tinyint(1) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `to` (`to`),
  KEY `from` (`from`),
  KEY `direction` (`direction`),
  KEY `read` (`read`),
  KEY `sent` (`sent`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `cometchat_announcements`;
CREATE TABLE  `cometchat_announcements` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `announcement` text NOT NULL,
  `time` int(10) unsigned NOT NULL,
  `to` int(10) signed NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `to` (`to`),
  KEY `time` (`time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `cometchat_chatroommessages`;
CREATE TABLE  `cometchat_chatroommessages` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `userid` int(10) unsigned NOT NULL,
  `chatroomid` int(10) unsigned NOT NULL,
  `message` text NOT NULL,
  `sent` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `userid` (`userid`),
  KEY `chatroomid` (`chatroomid`),
  KEY `sent` (`sent`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `cometchat_chatrooms`;
CREATE TABLE  `cometchat_chatrooms` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(255) NOT NULL,
  `lastactivity` int(10) unsigned NOT NULL,
  `createdby` int(10) unsigned NOT NULL,
  `password` varchar(255) NOT NULL,
  `type` tinyint(1) unsigned NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `lastactivity` (`lastactivity`),
  KEY `createdby` (`createdby`),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `cometchat_chatrooms_users`;
CREATE TABLE  `cometchat_chatrooms_users` (
  `userid` int(10) unsigned NOT NULL,
  `chatroomid` int(10) unsigned NOT NULL,
  `lastactivity` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`userid`),
  KEY `chatroomid` (`chatroomid`),
  KEY `lastactivity` (`lastactivity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `cometchat_status`;
CREATE TABLE  `cometchat_status` (
  `userid` int(10) unsigned NOT NULL,
  `message` text,
  `status` enum('available','away','busy','invisible','offline') default NULL,
  `typingto` int(10) unsigned default NULL,
  `typingtime` int(10) unsigned default NULL,
  PRIMARY KEY  (`userid`),
  KEY `typingto` (`typingto`),
  KEY `typingtime` (`typingtime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `cometchat_videochatsessions`;
CREATE TABLE  `cometchat_videochatsessions` (
  `username` varchar(255) NOT NULL,
  `identity` varchar(255) NOT NULL,
  `timestamp` int(10) unsigned default 0,
  PRIMARY KEY  (`username`),
  KEY `username` (`username`),
  KEY `identity` (`identity`),
  KEY `timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

EOD;
 
if (defined('ADD_LAST_ACTIVITY') && ADD_LAST_ACTIVITY == 1) {
	$usertable = TABLE_PREFIX.DB_USERTABLE;
	$lastactivity = DB_USERTABLE_LASTACTIVITY;
	$content .= <<<EOD
ALTER TABLE `$usertable` ADD COLUMN $lastactivity INT default 0;
EOD;
}

	$q = preg_split('/;[\r\n]+/',$content);

	foreach ($q as $query) {
		if (strlen($query) > 4) {
		$result = mysql_query($query);
			if (!$result) {
				$rollback = 1;
				$errors .= mysql_error()."<br/>\n";
			}
		}
	}

	
	
	$baseurl = '/cometchat/';

	if (!empty($_SERVER['DOCUMENT_ROOT']) && !empty($_SERVER['SCRIPT_FILENAME'])) {
		$baseurl = preg_replace('/install.php/i','',str_replace($_SERVER['DOCUMENT_ROOT'],'',$_SERVER['SCRIPT_FILENAME']));
	}

	$baseurl = str_replace('\\','/',$baseurl);

	if ($baseurl[0] != '/') {
		$baseurl = '/'.$baseurl;
	}

	if ($baseurl[strlen($baseurl)-1] != '/') {
		$baseurl = $baseurl.'/';
	}
	
	$file = 'config.php';
	$content = @file_get_contents($file);

	if ($content != '') {

		$myvar = "define('BASE_URL','{$baseurl}');";

		$content = str_replace("define('BASE_URL','cometchat/');",$myvar, $content);

		$f = @fopen($file,'w');
		if($f) {
		  @fwrite($f, $content);
		  @fclose($f);
		} else {
		  $extra .= "<br/><br/><strong>Unable to edit config.php.</strong> Find the BASE_URL line in config.php and replace it with:<br/><br/>define('BASE_URL','{$baseurl}');";
		}
	}

	$codeA = '<link type="text/css" href="'.$baseurl.'cometchatcss.php" rel="stylesheet" charset="utf-8">'."\r\n".'<script type="text/javascript" src="'.$baseurl.'cometchatjs.php" charset="utf-8"></script>';

	$codeB = '<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>'."\r\n".'<script>jqcc=jQuery.noConflict(true);</script>';

	if (defined('INCLUDE_JQUERY') && INCLUDE_JQUERY == 1) {
		$body = "<strong>Installation complete.</strong> Did you expect something more complicated?<br/><br/>Add the following immediately after <strong>&lt;head&gt;</strong> tag in your site template:<br/><br/><textarea readonly id=\"code\" onclick=\"copycode()\"  class=\"textarea\" name=\"code\">$codeA</textarea>$extra";
	} else {
		$body = "<strong>Installation complete.</strong> Did you expect something more complicated?<br/><br/>Add the following immediately after <strong>&lt;head&gt;</strong> tag in your site template:<br/><br/><textarea readonly id=\"codeJ\" onclick=\"copycodeJ()\"  class=\"textarea\" name=\"codeJ\">$codeB</textarea><br/><br/>Add the following immediately before <strong>&lt;/body&gt;</strong> tag in your site template:<br/><br/><textarea readonly id=\"code\" onclick=\"copycode()\"  class=\"textarea\" name=\"code\">$codeA</textarea>$extra";
	}


?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
 
	<head>
		
		<title>CometChat Installation</title>
	
		<style type="text/css">
			html,body {
				background: #f9f9f9;
				overflow: hidden;
			}
			#container { display: table; width: 100%; height: 100%; }
			#position { display: table-cell; vertical-align: middle; }
			#content { }
			#box { padding:0px; width:362px; margin:0 auto; }
			#boxtop { background: url(images/install_top.png); width: 362px; height: 64px;}
			#boxrepeat { 
				font-family: "Lucida Grande",Verdana,Arial,"Bitstream Vera Sans",sans-serif;
				font-size: 11px;
				color: #333333;
				background: url(images/install_repeat.png);
				width: 332px;
				padding: 15px; 
			}
			#boxbottom { background: url(images/install_bottom.png); width: 362px; height: 36px;}

			.textarea {
				font-family: "Lucida Grande",Verdana,Arial,"Bitstream Vera Sans",sans-serif;
				font-size: 10px;
				color: #333333;
				width: 332px;
				border: 1px solid #ccc;
				padding: 2px;
				height: 80px;
				overflow:hidden
			}
	
		</style>

		<script>
			function copycode() {
				var tempval= document.getElementById('code');
				tempval.focus()
				tempval.select()
			}

			function copycodeJ() {
				var tempval= document.getElementById('codeJ');
				tempval.focus()
				tempval.select()
			}
		</script>
	
		<!--[if IE]>
	
		<style type="text/css">
	
			#container { position: relative; }
			#position { position: absolute; top: 50%; }
			#content { position: relative; width:100%; top: -50%; }
			#box { position:relative; left:50%; margin-left:-181px; }
	
		</style>
	
		<![endif]-->
	
	</head>
	
	<body>
	  
		<div id="container">
			<div id="position">
				<div id="content">
					<div id="box">
						<div id="boxtop"></div>
						<div id="boxrepeat"><?php echo $body;?></div>
						<div id="boxbottom"></div>						
					</div>
				</div>
			</div>
		</div>
		<!-- License void if removed -->
		<img src="http://www.cometchat.com/registerdomain.php?key=<?php echo $licensekey;?>" width="1" height="1"/>
	</body>
	
</html>