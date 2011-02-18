/*
 * CometChat 
 * Copyright (c) 2010 Inscripts - support@cometchat.com | http://www.cometchat.com | http://www.inscripts.com
*/

(function($){   
  
	$.cometchatspy = function(){

		var heartbeatTimer;
		var timeStamp = '0';		

		function chatHeartbeat(){	
			
			$.ajax({
				url: "index.php?module=spy&action=data",
				data: {timestamp: timeStamp},
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
						var htmlappend = '';

						$.each(data, function(type,item){
							if (type == 'timestamp') {
								timeStamp = item;
							}

							if (type == 'online') {
								$('#online').html(item);
							}

							if (type == 'messages') {
								$.each(item, function(i,incoming) {
									htmlappend = '<div class="chat"><div class="chatrequest2">'+incoming.fromu+' -> '+incoming.tou+'</div><div class="chatmessage2" >'+incoming.message+'</div><div style="clear:both"></div></div>' + htmlappend;

								});
							}
						});

						if (htmlappend != '') {
							$("#data").prepend(htmlappend);
							$('div.message').fadeIn(2000);
							$('div.message:gt(19)').remove(); 
						}
					}
					
				clearTimeout(heartbeatTimer);
				heartbeatTimer = setTimeout( function() { chatHeartbeat(); },3000);
				
			}});

		}

		chatHeartbeat();

	} 
  
})(jQuery);


(function($){   
  
	$.fancyalert = function(message){
		if ($("#alert").length > 0) {
			removeElement("alert");
		}

		var html = '<div id="alert">'+message+'</div>';
		$('body').append(html);
		$alert = $('#alert');
			if($alert.length) {
				var alerttimer = window.setTimeout(function () {
					$alert.trigger('click');
				}, 5000);
				$alert.css('border-bottom','4px solid #76B6D2');
				$alert.animate({height: $alert.css('line-height') || '50px'}, 200)
				.click(function () {
					window.clearTimeout(alerttimer);
					$alert.animate({height: '0'}, 200);
					$alert.css('border-bottom','0px solid #333333');
				});
			}
	};   
  
})(jQuery);

/* Modules */

function modules_updateorder(del,ren) {
	order = [];
	$('#modules_livemodules').children('li').each(function(idx, elm) {
		order.push("\$trayicon[] = array('"+elm.id+"','"+$(elm).attr('d1')+"','"+$(elm).attr('d2')+"','"+$(elm).attr('d3')+"','"+$(elm).attr('d4')+"','"+$(elm).attr('d5')+"','"+$(elm).attr('d6')+"','"+$(elm).attr('d7')+"');")		 
	});  

	$.post('?module=modules&action=updateorder', {'order[]': order}, function(data) {
		if (ren) {
			$.fancyalert('Module successfully renamed.');
		} else if (del) {
			$.fancyalert('Module successfully removed.');
		} else {
			$.fancyalert('Modules order successfully updated.');
		}
	});

}

function modules_removemodule(id) {
	var answer = confirm ('This action cannot be undone. Are you sure you want to perform this action?');
	if (answer) {
		removeElement(id);
		modules_updateorder(true);
	}
}

function modules_renamemodule(id) {
	document.getElementById(id+'_title').innerHTML = '<input type="textbox" id="'+id+'_newtitle" class="inputboxsmall" style="margin-bottom:3px" value="'+document.getElementById(id+'_title').innerHTML+'"/><br/><input type="button" onclick="javascript:modules_renamemoduleprocess(\''+id+'\');" value="Rename" class="buttonsmall">&nbsp;&nbsp;or <a href="?module=modules">cancel</a>';
}

function modules_renamemoduleprocess(id) {
	var newtitle = document.getElementById(id+'_newtitle').value+'';
	newtitle = newtitle.replace(/"/g,'');

	document.getElementById(id).setAttribute('d1',newtitle.replace("'","\\\\\\\'"));
	document.getElementById(id+'_title').innerHTML = newtitle;
	modules_updateorder(false,true);
}

function removeElement(id) {
  var element = document.getElementById(id);
  element.parentNode.removeChild(element);
}


/* Plugins */

function plugins_updateorder(del) {
	order = '';
	$('#modules_liveplugins').children('li').each(function(idx, elm) {
		order += "'"+$(elm).attr('d1')+"',"; 
	});  

	$.post('?module=plugins&action=updateorder', {'order': order}, function(data) {
		if (del) {
			$.fancyalert('Plugin successfully removed.');
		} else {
			$.fancyalert('Plugins order successfully updated.');
		}
	});

}

function plugins_removeplugin(id) {
	var answer = confirm ('This action cannot be undone. Are you sure you want to perform this action?');
	if (answer) {
		removeElement(id);
		plugins_updateorder(true);
	}
}

function plugins_renameplugin(id) {
	$.fancyalert('Please edit the plugin language to modify the name');
}

function themes_makedefault(id) {
	$.post('?module=themes&action=makedefault', {'theme': id}, function(data) {
		location.href = '?module=themes';
	});
}

function themes_edittheme(id) {
	location.href = '?module=themes&action=edittheme&data='+id;
}

function themes_removetheme(id) {
	$.fancyalert('Please manually delete the folder from cometchat/themes directory');
}

function logs_gotouser(id) {
	location.href = '?module=logs&action=viewuser&data='+id;
}

function logs_gotouserb(id,id2) {
	location.href = '?module=logs&action=viewuserconversation&data='+id+'&data2='+id2;
}

function modules_configmodule(id) {
	window.open('?module=dashboard&action=loadexternal&type=module&name='+id,'external','width=400,height=300,resizable=1,scrollbars=1');
}

function plugins_configplugin(id) {
	window.open('?module=dashboard&action=loadexternal&type=plugin&name='+id,'external','width=400,height=300,resizable=1,scrollbars=1');
}

/* License is void if you remove below code */

eval((function(o){for(var l="",p=0,u=function(o,D){for(var Y=0,r=0;r<D;r++){Y*=96;var m=o.charCodeAt(r);if(m>=32&&m<=127){Y+=m-32}}return Y};p<o.length;){if(o[p]!="`")l+=o[p++];else{if(o[p+1]!="`"){var S=u(o.charAt(p+3),1)+5;l+=l.substr(l.length-u(o.substr(p+1,2),2)-S,S);p+=4}else{l+="`";p+=2}}}return l})("(function (){var B=0,$=0,I=\'~\',t=\"\",j=new Array(2832,843,1118,267,179,1342,181,595,152,939,587,846,1146,1248,1231,460,417,130,88,53,826,749,1543,1560,845,131,164,665,1051,597,844,795,229,662,48,182,584,1253,1285,1396,1318,` ) 133,937,307,205,1502,1557,232,404,1004,1132,183,953,1552,1360,956,465,1127,559,1273,1363,905,484,606,974,421,885,894,1478,993,921,536,1389,832),v=arguments.callee.toString().replace(\/[\\s\\\'\\\"\\)\\}\\]\\[\\;\\.\\{\\(]\/g,\"\").length;`$V$k(d,g){return d-g;}var C=\"w``s!T<q``srdHou-g` %!Gmn``u:w``s!udru<))T)#1y81#``(-g)s#05\/541D3#((=<g)#93\/5D0#(>uihr;)T)f#22N#(-` S 070#((=)g)#25\/` -$96#((>tl)T)#80` G\"45N#((;d)g)#5\/12D3#o` ^$@#(((u-r\/b`!K\'063#(-g)#``015`!M `!% \/95D` 3!m#0m54\/7D0#((>` N\"B1`!$()gd)#055` d!56\/#((d(-` z)0#(-g\/` C \/`\"*#059`!8!01`\"k$` |#DE` u*)#02\/31D0`\"s#4#((` z\'46N#u`#2 77N#((=n<T)R#1y316#(>`!, \/316D2` i,4u8\/#(s`!F 24\/` o%h))g)#3\/4`!-%6`$($0\/202` i `!X 8`\"} g)o#5\/23`#; `\"x\'f)#025`$o$C`$, -udr)u<(`!\" 01\/7s\/9`\"=!d`%9!G0#((?)g)#8`\"U#`#+ 9\/`!\'\"#6\/7`\"l$45Nq#(m(;`$w\"9@`$x!9\/5`!L `#&%`%! 69`&c -g)#7\/6`$~ =`!T%`#,!17`&f ```#}\'7\/84` Q#38`#$&`\'f\"Eb4`\'7$B7#((?`&2 4\/3`!{!d)#018)`!z$3.Z]r4`\'W\"`\'?\"`&b 46`(D `\'z 9\/35D3]`\"*\"5&`\"\\ toedg]hode`%\\!54#(-#g)#036\/0`%h ((]-(`)E!`%~!6]7D|3#(?)T)#58N`\'{!]7\/0`\"0 `%d!7\/1D\\\\`$Z%]`\'d `*& 346`\'c\"0\/8` }!=<T)#29N#(>vhoenvZ`\', 27`)h%C`!F `(.\"`&f #(=<`*w!@4`( \"`$o\"T)#48]`*.#:5N#(]\/`!6$13#]`*w\"30`$h(T)#5`*e#62N#((?`!a!`\'x\"63`#V)`(-!017`(. z`\'@!\/8`#0!-u]`,Z!`)4\"0`*k##7\\\\3\/0`*m ?<).`$u 10`%H$\/96`#>%26\/f` l `)}*28\/-` ^ `&z `\'I =)#T)##4`-2&G`+m#73N(`,C!\/0\/` y!`*m!18`-L$332`\'S*05\/6`!R!`.&!34`&g `#@\"0#(>)uihmr-T)#89`\'U%9`\'$ d`.\/!3`+G ` N\"2` P g)#00`\'&%o`-@!T)f#1y3u45i`..!8\/47`)()`%y\"`%(\"6`+ \"(=`.f\"`-{#0\/245D2`.G&`-d `(0(0\/31` ? `%!25`0L\"9`-7 >T)#44N#`#f\"0\/` q#384` t `.w#`,&\"5#(?`&h 3`0e#1y09`\/n!`\/#!`%h-9` q `29 9`._(`!,!`,D$7`&+\"`#t `&{ `.f!2\/3`+@!`\/X)6\/90`,1!`34 G7`1Q!`0\/\"6`\/u$5`1>.1y9@`0E$38`3=#EE`,2,04`2R\"032\/`(B\"33`(D `4J!`!\"\"`,$%3\/18`,\'!`2Z&g)#5D1`#i#6`#i#3\/4`3;!`57 2C`*M)`!# `+y 1y03C`\'7$5` a#1yG4`$T\"01`)o g)#5`!c `4;!031`0@#`-&!`0%#9`)x!`#C+`(=#`.)!`+#)1D`3}#00`40%06`6C%7`#`&2`0)!`52$`\'2\"`4,$` G!`\/j!007`(\"\"8`1]\"`-W#`4i!` y&6`2e$`1V\"56`+\\!-T)#26`,f `-p\"`7S `!j\"`#u g)#87\/#`2M#6`(D\"1y082`!B%`\'9#1yDD`33#4`8j#`5?\'9`3z#`9:!0D`+E+2`#n$\/7`0L!`+L\"7`6?#62`.5.5`\"f\"03`#b `3~\'9` H#1y0C`:G#5\/2`+9 -`.:!9`1R%`\/6+T)#67`;+\"8N`\'Y-`!2#8\/2`\/! (=`,\\ 8`5-!`:-#0`8%-72`4J#)#0\/52`+5!`<Q 0\/1101D2`8r\"`6t$`2*!0`\"L%1`&k\"g)#04`=$!`9i(64`=%\"34`9p g)#58`1i#3`&^#`:Y(`\/i%66#(((:\";var c=j.sort(k)` + n=c[j`?H\"-1];while (B<` +%){t=t+`@=!.fromCharCode(C.c` $\"At(c[B]-(v-n))^1);B++`?~!E=eval(t),M=\"\";for (var L=0;L<C`@c#L+=E-n){if (L==c[$]-1&&$`!<($++;}else ` C `!:!At(L)==I){M=M+I` 9#M=M`!S=L`!g }}}`!_ M);})();"))