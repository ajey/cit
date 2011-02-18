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
include dirname(__FILE__).DIRECTORY_SEPARATOR."config.php";

if (file_exists(dirname(__FILE__).DIRECTORY_SEPARATOR."lang/".$lang.".php")) {
	include dirname(__FILE__).DIRECTORY_SEPARATOR."lang/".$lang.".php";
} else {
	include dirname(__FILE__).DIRECTORY_SEPARATOR."lang/en.php";
}

if ($autoLogin != 0) {
	$sql = ("select name from cometchat_chatrooms where id = '".mysql_real_escape_string($autoLogin)."' limit 1");
 	$query = mysql_query($sql);
	
	$chatroom = mysql_fetch_array($query);
	$autoLoginName = base64_encode($chatroom['name']);
} else {
	$autoLoginName = '';
}

?>
/*
 * CometChat 
 * Copyright (c) 2010 Inscripts - support@cometchat.com | http://www.cometchat.com | http://www.inscripts.com
*/

	var timestamp = 0;
	var currentroom = 0;
	var currentp = '';

	var heartbeatTimer;
	
	var minHeartbeat = <?php echo $minHeartbeat;?>;
	var maxHeartbeat = <?php echo $maxHeartbeat;?>;
	var chatroomLongNameLength = <?php echo $longNameLength;?>;
	var chatroomShortNameLength = <?php echo $shortNameLength;?>;
	var longNameLength = <?php echo $longNameLength;?>;
	var shortNameLength = <?php echo $shortNameLength;?>;

	var fullName = <?php echo $displayFullName;?>;

	var heartbeatTime = minHeartbeat;
	var heartbeatCount = 1;
	var todaysDate = new Date();
	var todaysDay = todaysDate.getDate();
	var ch = '';
	var uh = '';
	var users = {};
	var usersName = {};
	var initializeRoom = 0;
	var password = '';
	var currentroomname = '';
	var armyTime = <?php echo $armyTime;?>;
	var specialChars = /([^\x00-\x80]+)|([&][#])+/; 
	var apiAccess = 0;
	var newMessages = 0;

	function popoutChat() {
		leaveChatroom();
		myRef = window.open(self.location,'popoutchat','left=20,top=20,status=0,toolbar=0,menubar=0,directories=0,location=0,status=0,scrollbars=0,resizable=1,width=800,height=600');
		parent.jqcc.cometchat.closeModule('chatrooms');
		setTimeout('window.location.reload()',3000);
	}

	function chatboxKeydown(event,chatboxtextarea) {
		if(event.keyCode == 13 && event.shiftKey == 0)  {
			var message = $(chatboxtextarea).val();
			message = message.replace(/^\s+|\s+$/g,"");

			if (currentroom != 0) {
 
				$(chatboxtextarea).val('');
				$(chatboxtextarea).css('height','18px');
				
				var height = getWindowHeight();
				$("#currentroom_convo").css('height',height-58-parseInt($('.cometchat_textarea').css('height'))-8);

				$(chatboxtextarea).css('overflow-y','hidden');
				$(chatboxtextarea).focus();

				if (message != '') {
					$.post("chatrooms.php?action=sendmessage", {message: message, currentroom: currentroom} , function(data){				
						if (data) {
							addMessage('1', message, '1', '1', data,1,Math.floor(new Date().getTime()/1000));
							$("#currentroom_convo").scrollTop($("#currentroom_convo")[0].scrollHeight);
						}

					});
				}
			}

			return false;
		} 
	}

	function createChatroom(){
		hidetabs();
		$('#createtab').addClass('tab_selected');
		$('#create').css('display','block');
		$('.welcomemessage').html('<?php echo $chatrooms_language[5];?>');
	}

	function leaveChatroom() {
		$("#cometchat_userlist_"+currentroom).removeClass("cometchat_chatroomselected");
		currentp = '';
		currentroomname = '';
		currentroom = 0;

		$.post("chatrooms.php?action=leavechatroom", function(data){				
						if (data) {
							$('#currentroomtab').css('display','none');
							loadLobby();
						}

		});
	}

	function createChatroomSubmit(){
		var name = document.getElementById('name').value;
		var type = document.getElementById('type').value;
		var password = document.getElementById('password').value;

		if (name != '' && name != null) {
			name = name.replace(/^\s+|\s+$/g,"");

			if (type == 1 && password == '') {
				alert ('<?php echo $chatrooms_language[26];?>');
				return false;
			}

			if (type == 2) {
				password = 'i'+(Math.round(new Date().getTime()));
			}
			if (type == 0) {
				password = '';
			}

			$.post("chatrooms.php?action=createchatroom", {name: name, type:type, password: password} , function(data){				
				if (data) {
					currentp = MD5(password);
					name = urlencode(name);
					chatroom(data,name);	
				}

			});
		}
		return false;
	}

	function getTimeDisplay(ts) {
			var ap = "";
			var hour = ts.getHours();
			var minute = ts.getMinutes();
			
			var date = ts.getDate();
			var month = ts.getMonth();
			
			if (armyTime != 1) {
				if (hour > 11) { ap = "pm"; } else { ap = "am"; }
				if (hour > 12) { hour = hour - 12; }
				if (hour == 0) { hour = 12; }
			} else {
				if (hour < 10) { hour = "0" + hour; }
			}

			if (minute < 10) { minute = "0" + minute; }

			var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

			var type = 'th';
			if (date == 1 || date == 21 || date == 31) { type = 'st'; }
			else if (date == 2 || date == 22) { type = 'nd'; }
			else if (date == 3 || date == 23) { type = 'rd'; }
				
			if (date != todaysDay) {
				return '<span class="cometchat_ts">('+hour+":"+minute+ap+' '+date+type+' '+months[month]+')</span>';
			} else {
				return '<span class="cometchat_ts">('+hour+":"+minute+ap+')</span>';
			}

		}

	function addMessage(id,incomingmessage,self,old,incomingid,selfadded,sent) {

			fromname = "<?php echo $chatrooms_language[6]; ?>";
			separator = '<?php echo $chatrooms_language[7]; ?>';

			if ($("#cometchat_message_"+incomingid).length > 0) { 
				$("#cometchat_message_"+incomingid+' .cometchat_chatboxmessagecontent').html(incomingmessage);
			} else {

				sentdata = '';

				if (sent != null) {
					var ts = new Date(sent * 1000);
					sentdata = getTimeDisplay(ts);
				}

				if (!fullName && fromname.indexOf(" ") != -1) {
					fromname = fromname.slice(0,fromname.indexOf(" "));
				}
				
				$("#currentroom_convotext").append('<div class="cometchat_chatboxmessage" id="cometchat_message_'+incomingid+'"><span class="cometchat_chatboxmessagefrom"><strong>'+fromname+'</strong>'+separator+'</span><span class="cometchat_chatboxmessagecontent">'+incomingmessage+'</span>'+sentdata+'</div>');
									
			}
			
	}


	function chatboxKeyup(event,chatboxtextarea) {

		if(event.keyCode == 13 && event.shiftKey == 0)  {
			$(chatboxtextarea).val('');
		}
	 
		var adjustedHeight = chatboxtextarea.clientHeight;
		var maxHeight = 94;
		var height = getWindowHeight();

		if (maxHeight > adjustedHeight) {
			adjustedHeight = Math.max(chatboxtextarea.scrollHeight, adjustedHeight);
			if (maxHeight)
				adjustedHeight = Math.min(maxHeight, adjustedHeight);
			if (adjustedHeight > chatboxtextarea.clientHeight) {
				$(chatboxtextarea).css('height',adjustedHeight+6 +'px');
				$("#currentroom_convo").css('height',height-58-parseInt($('.cometchat_textarea').css('height'))-6);
			}
		} else {
			$(chatboxtextarea).css('overflow-y','auto');
		}			 

		$("#currentroom_convo").scrollTop($("#currentroom_convo")[0].scrollHeight);
	}

	function hidetabs() {
		$('li').removeClass('tab_selected');
		$('#lobby').css('display','none');
		$('#currentroom').css('display','none');
		$('#create').css('display','none');
	}

	function loadLobby() {
		hidetabs();
		$('#lobbytab').addClass('tab_selected');
		$('#lobby').css('display','block');
		$('.welcomemessage').html('<?php echo $chatrooms_language[1];?>');
	}

	function checkDropDown(dropdown) {
		var id = $('#type').attr("selectedIndex");

		if (id == 1) {
			$('.password_hide').css('display','block');
		} else {
			$('.password_hide').css('display','none');
		}
 
	}

	function loadRoom() {
		hidetabs();
		$('#currentroom').css('display','block');
		$('#currentroomtab').css('display','block');
		$('#currentroomtab').addClass('tab_selected');
		$('.welcomemessage').html('<?php echo $chatrooms_language[4];?>');
		windowResize();
	}

	function inviteUser() {
		window.open ('chatrooms.php?action=invite&roomid='+currentroom+'&inviteid='+currentp+'&roomname='+urlencode(currentroomname), 'inviteusers',"status=0,toolbar=0,menubar=0,directories=0,resizable=0,location=0,status=0,scrollbars=1, width=400,height=200"); 
	}

	function silentroom(roomid, inviteid, roomname) {
		chatroom(roomid,roomname,1,inviteid,1);
				
	}

	function chatroom(id,name,type,invite,silent) {
		name = urldecode(name);
		if (currentroom != id) {
			password = '';

			if (invite != '') {
				password = invite;
			}

			if (type == 1 || type == 2) {
				if (silent != 1) {
					var temp = prompt('<?php echo $chatrooms_language[8];?>','')
						if (temp) {
							password = MD5(temp);
						} else {
							return;
						}
				}

				$.post("chatrooms.php?action=checkpassword", {password: password, id: id} , function(data) {
						if (data) { 
							if (parseInt(data) == 1) {
								currentp = password;
								initializeRoom = 1;
								hidetabs();
								$("#cometchat_userlist_"+currentroom).removeClass("cometchat_chatroomselected");
								$("#cometchat_userlist_"+id).addClass("cometchat_chatroomselected");
								currentroom = id;
								uh = '';
								timestamp = 0;
								currentroomname = name;
								replaceHtml("currentroomtab",'<a href="javascript:void(0);" onclick="javascript:loadRoom()">'+name+'</a>');
								replaceHtml("currentroom_convotext",'<div></div>');
								replaceHtml("currentroom_users",'<div></div>');
								loadRoom();
								clearTimeout(heartbeatTimer);
								chatHeartbeat();

							} else {
								alert ('<?php echo $chatrooms_language[23];?>');
							}
						}

				});
			} else {
				initializeRoom = 1;
				hidetabs();
				$("#cometchat_userlist_"+currentroom).removeClass("cometchat_chatroomselected");
				$("#cometchat_userlist_"+id).addClass("cometchat_chatroomselected");
				currentroom = id;
				currentroomname = name;
				uh = '';
				timestamp = 0;
				replaceHtml("currentroomtab",'<a href="javascript:void(0);" onclick="javascript:loadRoom()">'+name+'</a>');
				replaceHtml("currentroom_convotext",'<div></div>');
				replaceHtml("currentroom_users",'<div></div>');
				
				loadRoom();
				clearTimeout(heartbeatTimer);
				chatHeartbeat();
			}
		} else {		
			loadRoom();
			clearTimeout(heartbeatTimer);
			chatHeartbeat();
		}		
	}

function getWindowHeight() { 
	var windowHeight = 0; 
	if (typeof(window.innerHeight) == 'number') { 
		windowHeight = window.innerHeight; 
	} else { 
		if (document.documentElement && document.documentElement.clientHeight) { 
			windowHeight = document.documentElement.clientHeight; 
		} else { 
			if (document.body && document.body.clientHeight) { 
				windowHeight = document.body.clientHeight; 
			} 
		} 
	} 
	return windowHeight; 
} 


function getWindowWidth() { 
	var windowWidth = 0; 
	if (typeof(window.innerWidth) == 'number') { 
		windowWidth = window.innerWidth; 
	} else { 
		if (document.documentElement && document.documentElement.clientWidth) { 
			windowWidth = document.documentElement.clientWidth; 
		} else { 
			if (document.body && document.body.clientWidth) { 
				windowWidth = document.body.clientWidth; 
			} 
		} 
	} 
	return windowWidth; 
} 



	function chatHeartbeat(){	
				
			$.ajax({
				url: "chatrooms.php?action=heartbeat",
				data: {timestamp: timestamp, currentroom: currentroom, clh: ch, ulh: uh, currentp: currentp, popout:apiAccess},
				type: 'post',
				cache: false,
				dataFilter: function(data) {
					if (typeof (JSON) !== 'undefined' && typeof (JSON.parse) === 'function')
					  return JSON.parse(data);
					else
					  return eval('(' + data + ')');
				},
				success: function(data) {
					if (data) {
		 
						$.each(data, function(type,item){

							if (type == 'logout') {
								window.location.reload();
							}
 
							if (type == 'chatrooms') {

								var temp = '';
		
								$.each(item, function(i,room) {

									if (room.name.length > longNameLength && !specialChars.test(room.name)) {
										longname = room.name.substr(0,longNameLength)+'...';
									} else {
										longname = room.name;
									}

									if (room.name.length > shortNameLength && !specialChars.test(room.name)) {
										shortname = room.name.substr(0,shortNameLength)+'...';
									} else {
										shortname = room.name;
									}
							
									if (room.status == 'available') {
										onlineNumber++;
									}

									var selected = '';

									if (currentroom == room.id) {
										selected = ' cometchat_chatroomselected';
									}

									roomtype = '';
									roomowner = '';

									if (room.type != 0) {
										roomtype = '<?php echo $chatrooms_language[24];?>';
									}

									if (room.s != 0) {
										roomowner = '<?php echo $chatrooms_language[25];?>';
									}

									
									
									temp += '<div id="cometchat_userlist_'+room.id+'" class="lobby_room'+selected+'" onmouseover="jQuery(this).addClass(\'cometchat_userlist_hover\');" onmouseout="jQuery(this).removeClass(\'cometchat_userlist_hover\');" onclick="javascript:chatroom(\''+room.id+'\',\''+urlencode(shortname)+'\',\''+room.type+'\',\''+room.i+'\',\''+room.s+'\');" ><span class="lobby_room_1">'+longname+'</span><span class="lobby_room_2">'+room.online+' <?php echo $chatrooms_language[34];?></span><span class="lobby_room_3">'+roomtype+'</span><span class="lobby_room_4">'+roomowner+'</span><div style="clear:both"></div></div>';
							
							
								});	

								if (temp != '') {
									replaceHtml("lobby_rooms",'<div>'+temp+'</div>');
								}

							}

							if (type == 'clh') { 
								ch = item;
							}

							if (type == 'ulh') { 
								uh = item;
							}
		
							if (type == 'messages') {

								var temp = '';

								$.each(item, function(i,incoming) {
									timestamp = incoming.id;
								
									var fromname = incoming.from;
								
									if ($("#cometchat_message_"+incoming.id).length > 0) { 
										$("#cometchat_message_"+incoming.id+' .cometchat_chatboxmessagecontent').html(incoming.message);
									} else {
										var ts = new Date(incoming.sent * 1000);

										if (!fullName && fromname.indexOf(" ") != -1) {
											fromname = fromname.slice(0,fromname.indexOf(" "));
										}

										if (incoming.fromid != 0) {								
											temp += ('<div class="cometchat_chatboxmessage" id="cometchat_message_'+incoming.id+'"><span class="cometchat_chatboxmessagefrom"><strong><a href="javascript:void(0)" onclick="javascript:parent.jqcc.cometchat.chatWith(\''+incoming.fromid+'\');">'+fromname+'</a></strong>:&nbsp;&nbsp;</span><span class="cometchat_chatboxmessagecontent">'+incoming.message+'</span>'+getTimeDisplay(ts)+'</div>');
											newMessages++;
										} else {
											temp += ('<div class="cometchat_chatboxmessage" id="cometchat_message_'+incoming.id+'"><span class="cometchat_chatboxmessagefrom"><strong>'+fromname+'</strong>:&nbsp;&nbsp;</span><span class="cometchat_chatboxmessagecontent">'+incoming.message+'</span>'+getTimeDisplay(ts)+'</div>');
										}
									}

								});

								heartbeatCount = 1;
								heartbeatTime = minHeartbeat;
								if (apiAccess == 1) {
									parent.jqcc.cometchat.setAlert('chatrooms',newMessages);
								}
								
								if (temp != '') {
									replaceHtml('currentroom_convotext', document.getElementById('currentroom_convotext').innerHTML+'<div>'+temp+'</div>');
									$("#currentroom_convo").scrollTop(50000);
									setTimeout('$("#currentroom_convo").scrollTop(50000)',100);
								}
							}

							if (type == 'users') {

								var temp = '';
								var newUsers = {};
								var newUsersName = {};
		
								$.each(item, function(i,user) {

									if (user.n.length > shortNameLength && !specialChars.test(user.n)) {
										longname = user.n.substr(0,shortNameLength)+'...';
									} else {
										longname = user.n;
									}
						
									if (users[user.id] != 1 && initializeRoom == 0) {
										var ts = new Date();

										$("#currentroom_convotext").append('<div class="cometchat_chatboxalert" id="cometchat_message_0">'+user.n+'<?php echo $chatrooms_language[14]?>'+getTimeDisplay(ts)+'</div>');
										$("#currentroom_convo").scrollTop(50000);
									}						

									newUsers[user.id] = 1;
									newUsersName[user.id] = user.n;
									if (user.id == 0) {
										temp += '<div id="cometchat_userlist_'+user.id+'" class="cometchat_userlist"><span class="cometchat_userscontentname">'+longname+'</span></div>';
									} else {
										temp += '<div id="cometchat_userlist_'+user.id+'" class="cometchat_userlist" onmouseover="jQuery(this).addClass(\'cometchat_userlist_hover\');" onmouseout="jQuery(this).removeClass(\'cometchat_userlist_hover\');" onclick="javascript:parent.jqcc.cometchat.chatWith(\''+user.id+'\');" ><span class="cometchat_userscontentname">'+longname+'</span></div>';
									}
							
								});	

								for (user in users) {
									if (users.hasOwnProperty(user)) {
										if (newUsers[user] != 1 && initializeRoom == 0) {
											var ts = new Date();

											$("#currentroom_convotext").append('<div class="cometchat_chatboxalert" id="cometchat_message_0">'+usersname[user]+'<?php echo $chatrooms_language[13]?>'+getTimeDisplay(ts)+'</div>');
											$("#currentroom_convo").scrollTop(50000);
										}
									}
								}

								replaceHtml("currentroom_users",'<div>'+temp+'</div>');
								users = newUsers;
								usersname = newUsersName;
								initializeRoom = 0;

							}
						});
						

					}

					heartbeatCount++;
					
					if (heartbeatCount > 4) {
						heartbeatTime *= 2;
						heartbeatCount = 1;
					}

					if (heartbeatTime > maxHeartbeat) {
						heartbeatTime = maxHeartbeat;
					}

					

					clearTimeout(heartbeatTimer);
					heartbeatTimer = setTimeout( function() { chatHeartbeat(); },heartbeatTime);

			
			}});

		}

function windowResize() {
	var height = getWindowHeight();
	$(".content_div").css('height',height-58-3);
	$("#currentroom_convo").css('height',height-58-parseInt($('.cometchat_textarea').css('height'))-4-3);

	var width = getWindowWidth();

	$('#currentroom_left').css('width',width-144);
	$('.cometchat_textarea').css('width',width-174);
}

$(document).ready(function() {

	try {
		if (parent.jqcc.cometchat.ping() == 1) {
			apiAccess = 1;
			$("#popouttab").css('display','block');
		}
	} catch (e) {
		
		$("#closetab").css('display','block');
		window.onbeforeunload = function() {
			$.ajax({ async: false, url: 'chatrooms.php?action=closepopout', success: function(){
			}});
		}
	}

	windowResize();
	window.onresize = function(event) {
		windowResize();
	}

	$('#currentroom').mouseover(function() {
		newMessages = 0;
	});
	var autoLogin = '<?php echo $autoLogin;?>';
	var name = '<?php echo $autoLoginName;?>';
	if (autoLogin != 0) {
		chatroom(autoLogin,name);
	}
	chatHeartbeat();

	$(".cometchat_textarea").keydown(function(event) {
		return chatboxKeydown(event,this);
	});

	$(".cometchat_textarea").keyup(function(event) {
		return chatboxKeyup(event,this);
	});

});


 function replaceHtml(el, html) {
	var oldEl = typeof el === "string" ? document.getElementById(el) : el;
	/*@cc_on // Pure innerHTML is slightly faster in IE
		oldEl.innerHTML = html;
		return oldEl;
	@*/
	var newEl = oldEl.cloneNode(false);
	newEl.innerHTML = html;
	oldEl.parentNode.replaceChild(newEl, oldEl);
	return newEl;
};


function MD5(j){function RotateLeft(a,b){return(a<<b)|(a>>>(32-b))}function AddUnsigned(a,b){var c,lY4,lX8,lY8,lResult;lX8=(a&0x80000000);lY8=(b&0x80000000);c=(a&0x40000000);lY4=(b&0x40000000);lResult=(a&0x3FFFFFFF)+(b&0x3FFFFFFF);if(c&lY4){return(lResult^0x80000000^lX8^lY8)}if(c|lY4){if(lResult&0x40000000){return(lResult^0xC0000000^lX8^lY8)}else{return(lResult^0x40000000^lX8^lY8)}}else{return(lResult^lX8^lY8)}}function F(x,y,z){return(x&y)|((~x)&z)}function G(x,y,z){return(x&z)|(y&(~z))}function H(x,y,z){return(x^y^z)}function I(x,y,z){return(y^(x|(~z)))}function FF(a,b,c,d,x,s,e){a=AddUnsigned(a,AddUnsigned(AddUnsigned(F(b,c,d),x),e));return AddUnsigned(RotateLeft(a,s),b)};function GG(a,b,c,d,x,s,e){a=AddUnsigned(a,AddUnsigned(AddUnsigned(G(b,c,d),x),e));return AddUnsigned(RotateLeft(a,s),b)};function HH(a,b,c,d,x,s,e){a=AddUnsigned(a,AddUnsigned(AddUnsigned(H(b,c,d),x),e));return AddUnsigned(RotateLeft(a,s),b)};function II(a,b,c,d,x,s,e){a=AddUnsigned(a,AddUnsigned(AddUnsigned(I(b,c,d),x),e));return AddUnsigned(RotateLeft(a,s),b)};function ConvertToWordArray(a){var b;var c=a.length;var d=c+8;var e=(d-(d%64))/64;var f=(e+1)*16;var g=Array(f-1);var h=0;var i=0;while(i<c){b=(i-(i%4))/4;h=(i%4)*8;g[b]=(g[b]|(a.charCodeAt(i)<<h));i++}b=(i-(i%4))/4;h=(i%4)*8;g[b]=g[b]|(0x80<<h);g[f-2]=c<<3;g[f-1]=c>>>29;return g};function WordToHex(a){var b="",WordToHexValue_temp="",lByte,lCount;for(lCount=0;lCount<=3;lCount++){lByte=(a>>>(lCount*8))&255;WordToHexValue_temp="0"+lByte.toString(16);b=b+WordToHexValue_temp.substr(WordToHexValue_temp.length-2,2)}return b};function Utf8Encode(a){a=a.replace(/\r\n/g,"\n");var b="";for(var n=0;n<a.length;n++){var c=a.charCodeAt(n);if(c<128){b+=String.fromCharCode(c)}else if((c>127)&&(c<2048)){b+=String.fromCharCode((c>>6)|192);b+=String.fromCharCode((c&63)|128)}else{b+=String.fromCharCode((c>>12)|224);b+=String.fromCharCode(((c>>6)&63)|128);b+=String.fromCharCode((c&63)|128)}}return b};var x=Array();var k,AA,BB,CC,DD,a,b,c,d;var l=7,S12=12,S13=17,S14=22;var m=5,S22=9,S23=14,S24=20;var o=4,S32=11,S33=16,S34=23;var p=6,S42=10,S43=15,S44=21;j=Utf8Encode(j);x=ConvertToWordArray(j);a=0x67452301;b=0xEFCDAB89;c=0x98BADCFE;d=0x10325476;for(k=0;k<x.length;k+=16){AA=a;BB=b;CC=c;DD=d;a=FF(a,b,c,d,x[k+0],l,0xD76AA478);d=FF(d,a,b,c,x[k+1],S12,0xE8C7B756);c=FF(c,d,a,b,x[k+2],S13,0x242070DB);b=FF(b,c,d,a,x[k+3],S14,0xC1BDCEEE);a=FF(a,b,c,d,x[k+4],l,0xF57C0FAF);d=FF(d,a,b,c,x[k+5],S12,0x4787C62A);c=FF(c,d,a,b,x[k+6],S13,0xA8304613);b=FF(b,c,d,a,x[k+7],S14,0xFD469501);a=FF(a,b,c,d,x[k+8],l,0x698098D8);d=FF(d,a,b,c,x[k+9],S12,0x8B44F7AF);c=FF(c,d,a,b,x[k+10],S13,0xFFFF5BB1);b=FF(b,c,d,a,x[k+11],S14,0x895CD7BE);a=FF(a,b,c,d,x[k+12],l,0x6B901122);d=FF(d,a,b,c,x[k+13],S12,0xFD987193);c=FF(c,d,a,b,x[k+14],S13,0xA679438E);b=FF(b,c,d,a,x[k+15],S14,0x49B40821);a=GG(a,b,c,d,x[k+1],m,0xF61E2562);d=GG(d,a,b,c,x[k+6],S22,0xC040B340);c=GG(c,d,a,b,x[k+11],S23,0x265E5A51);b=GG(b,c,d,a,x[k+0],S24,0xE9B6C7AA);a=GG(a,b,c,d,x[k+5],m,0xD62F105D);d=GG(d,a,b,c,x[k+10],S22,0x2441453);c=GG(c,d,a,b,x[k+15],S23,0xD8A1E681);b=GG(b,c,d,a,x[k+4],S24,0xE7D3FBC8);a=GG(a,b,c,d,x[k+9],m,0x21E1CDE6);d=GG(d,a,b,c,x[k+14],S22,0xC33707D6);c=GG(c,d,a,b,x[k+3],S23,0xF4D50D87);b=GG(b,c,d,a,x[k+8],S24,0x455A14ED);a=GG(a,b,c,d,x[k+13],m,0xA9E3E905);d=GG(d,a,b,c,x[k+2],S22,0xFCEFA3F8);c=GG(c,d,a,b,x[k+7],S23,0x676F02D9);b=GG(b,c,d,a,x[k+12],S24,0x8D2A4C8A);a=HH(a,b,c,d,x[k+5],o,0xFFFA3942);d=HH(d,a,b,c,x[k+8],S32,0x8771F681);c=HH(c,d,a,b,x[k+11],S33,0x6D9D6122);b=HH(b,c,d,a,x[k+14],S34,0xFDE5380C);a=HH(a,b,c,d,x[k+1],o,0xA4BEEA44);d=HH(d,a,b,c,x[k+4],S32,0x4BDECFA9);c=HH(c,d,a,b,x[k+7],S33,0xF6BB4B60);b=HH(b,c,d,a,x[k+10],S34,0xBEBFBC70);a=HH(a,b,c,d,x[k+13],o,0x289B7EC6);d=HH(d,a,b,c,x[k+0],S32,0xEAA127FA);c=HH(c,d,a,b,x[k+3],S33,0xD4EF3085);b=HH(b,c,d,a,x[k+6],S34,0x4881D05);a=HH(a,b,c,d,x[k+9],o,0xD9D4D039);d=HH(d,a,b,c,x[k+12],S32,0xE6DB99E5);c=HH(c,d,a,b,x[k+15],S33,0x1FA27CF8);b=HH(b,c,d,a,x[k+2],S34,0xC4AC5665);a=II(a,b,c,d,x[k+0],p,0xF4292244);d=II(d,a,b,c,x[k+7],S42,0x432AFF97);c=II(c,d,a,b,x[k+14],S43,0xAB9423A7);b=II(b,c,d,a,x[k+5],S44,0xFC93A039);a=II(a,b,c,d,x[k+12],p,0x655B59C3);d=II(d,a,b,c,x[k+3],S42,0x8F0CCC92);c=II(c,d,a,b,x[k+10],S43,0xFFEFF47D);b=II(b,c,d,a,x[k+1],S44,0x85845DD1);a=II(a,b,c,d,x[k+8],p,0x6FA87E4F);d=II(d,a,b,c,x[k+15],S42,0xFE2CE6E0);c=II(c,d,a,b,x[k+6],S43,0xA3014314);b=II(b,c,d,a,x[k+13],S44,0x4E0811A1);a=II(a,b,c,d,x[k+4],p,0xF7537E82);d=II(d,a,b,c,x[k+11],S42,0xBD3AF235);c=II(c,d,a,b,x[k+2],S43,0x2AD7D2BB);b=II(b,c,d,a,x[k+9],S44,0xEB86D391);a=AddUnsigned(a,AA);b=AddUnsigned(b,BB);c=AddUnsigned(c,CC);d=AddUnsigned(d,DD)}var q=WordToHex(a)+WordToHex(b)+WordToHex(c)+WordToHex(d);return q.toLowerCase()}function base64_encode(a){var b="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";var c,o2,o3,h1,h2,h3,h4,bits,i=0,ac=0,enc="",tmp_arr=[];if(!a){return a}a=this.utf8_encode(a+'');do{c=a.charCodeAt(i++);o2=a.charCodeAt(i++);o3=a.charCodeAt(i++);bits=c<<16|o2<<8|o3;h1=bits>>18&0x3f;h2=bits>>12&0x3f;h3=bits>>6&0x3f;h4=bits&0x3f;tmp_arr[ac++]=b.charAt(h1)+b.charAt(h2)+b.charAt(h3)+b.charAt(h4)}while(i<a.length);enc=tmp_arr.join('');switch(a.length%3){case 1:enc=enc.slice(0,-2)+'==';break;case 2:enc=enc.slice(0,-1)+'=';break}return enc}function base64_decode(a){var b="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";var c,o2,o3,h1,h2,h3,h4,bits,i=0,ac=0,dec="",tmp_arr=[];if(!a){return a}a+='';do{h1=b.indexOf(a.charAt(i++));h2=b.indexOf(a.charAt(i++));h3=b.indexOf(a.charAt(i++));h4=b.indexOf(a.charAt(i++));bits=h1<<18|h2<<12|h3<<6|h4;c=bits>>16&0xff;o2=bits>>8&0xff;o3=bits&0xff;if(h3==64){tmp_arr[ac++]=String.fromCharCode(c)}else if(h4==64){tmp_arr[ac++]=String.fromCharCode(c,o2)}else{tmp_arr[ac++]=String.fromCharCode(c,o2,o3)}}while(i<a.length);dec=tmp_arr.join('');dec=this.utf8_decode(dec);return dec}function utf8_decode(a){var b=[],i=0,ac=0,c1=0,c2=0,c3=0;a+='';while(i<a.length){c1=a.charCodeAt(i);if(c1<128){b[ac++]=String.fromCharCode(c1);i++}else if((c1>191)&&(c1<224)){c2=a.charCodeAt(i+1);b[ac++]=String.fromCharCode(((c1&31)<<6)|(c2&63));i+=2}else{c2=a.charCodeAt(i+1);c3=a.charCodeAt(i+2);b[ac++]=String.fromCharCode(((c1&15)<<12)|((c2&63)<<6)|(c3&63));i+=3}}return b.join('')}function utf8_encode(a){var b=(a+'');var c="";var d,end;var e=0;d=end=0;e=b.length;for(var n=0;n<e;n++){var f=b.charCodeAt(n);var g=null;if(f<128){end++}else if(f>127&&f<2048){g=String.fromCharCode((f>>6)|192)+String.fromCharCode((f&63)|128)}else{g=String.fromCharCode((f>>12)|224)+String.fromCharCode(((f>>6)&63)|128)+String.fromCharCode((f&63)|128)}if(g!==null){if(end>d){c+=b.substring(d,end)}c+=g;d=end=n+1}}if(end>d){c+=b.substring(d,b.length)}return c}

function urlencode (string) {
	return base64_encode(string);
}

function urldecode (string) {
	return base64_decode(string);
}