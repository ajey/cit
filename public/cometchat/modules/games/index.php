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

include dirname(dirname(dirname(__FILE__))).DIRECTORY_SEPARATOR."modules.php";

if ($rtl == 1) {
	$rtl = "_rtl";
} else {
	$rtl = "";
}

if (!file_exists(dirname(__FILE__)."/themes/".$theme."/games".$rtl.".css")) {
	$theme = "default";
}

echo <<<EOD
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<meta http-equiv="cache-control" content="no-cache">
<meta http-equiv="pragma" content="no-cache">
<meta http-equiv="expires" content="-1">
<meta http-equiv="content-type" content="text/html; charset=UTF-8"/> 
<link type="text/css" rel="stylesheet" media="all" href="themes/{$theme}/games{$rtl}.css" /> 
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js" type="text/javascript"></script>
<script>

var gamessource = {};
var gamesheight = {};
var gameswidth = {};
var categorygames = {};

$(document).ready(function() {
	$.getJSON('contents.php?get=categories', function(data) {
		if (data == '0') {
			$("body").html('<iframe width="100%" height="294" frameborder="0" scrolling="no" allowtransparency="true" vspace="0" hspace="0" marginheight="0" marginwidth="0" src="http://www.heyzap.com/embed?embed_key=2b9c74ca22&special_height=300" style="margin-top:3px;" id="heyzap_iframe"></iframe>');
		} else {
			var categoriesinfo = '';
			for (x = 0;x<data.categories.length;x++) {	
				if (data.categories[x].name != 'multiplayer' && data.categories[x].name != 'lifestyle') {				
					categorygames[data.categories[x].name] = data.categories[x].num_games;
					categoriesinfo += '<li id=\''+data.categories[x].name+'\' onclick="javascript:getCategory(\''+data.categories[x].name+'\')">'+data.categories[x].display_name+'</li>';
				}
			}

			$('#categories').html(categoriesinfo);
			
			getCategory('featured');
		}
	});
}); 

function getCategory(catname,page){
	if (page == null) {
		page = 1;
	} else {
		page = parseInt(page);
	}
	
	$('#categories li').removeClass('catselected');
	$('#'+catname).addClass('catselected');

	$('#games').html('');
	$('#loader').css('display','block');

	$.getJSON('contents.php?get='+catname+'&page='+page, function(data) {
		
		$('#loader').css('display','none');
		for (x = 0;x<data.games.length;x++) {	
			var name = data.games[x].display_name;
			var thumbnail = data.games[x].thumb_100x100;
			var width = data.games[x].width;
			var height = data.games[x].height;
			var source = data.games[x].embed_code;

			gamessource[x] = source;
			gamesheight[x] = height;
			gameswidth[x] = width;

			$('#games').append('<div class="gamelist" onclick="javascript:loadGame(\''+x+'\')"><img src="'+thumbnail+'"><br/>'+name+'</div>');
		}

		if (categorygames[catname] > (parseInt(page)+1)*20) {
			$('#games').append('<div class="gamelist" onclick="javascript:getCategory(\''+(catname)+'\',\''+parseInt(page+1)+'\')"><br/><br/><br/>Play more games</div>');
		}
	});
}

function loadGame(id) {
	w = window.open ('', 'singleplayergame',"status=0,toolbar=0,menubar=0,directories=0,resizable=0,location=0,status=0,scrollbars=0, width="+gameswidth[id]+",height="+gamesheight[id]); 
	w.document.write('<html><style>html, body {padding:0;margin:0;overflow:hidden;}</style>');
	w.document.write(gamessource[id]);
}


</script>

</head>
<body>
<div style="width:100%;margin:0 auto;margin-top: 0px;">

<div id="container">
<div id="categories"></div>
<div id="games"></div>
<div style="clear:both"></div>
</div>
</div>
<div id="loader"></div>
</body>
</html>
EOD;
?>