<?php
		if (file_exists(dirname(__FILE__).DIRECTORY_SEPARATOR."lang/".$lang.".php")) {
			include dirname(__FILE__).DIRECTORY_SEPARATOR."lang/".$lang.".php";
		} else {
			include dirname(__FILE__).DIRECTORY_SEPARATOR."lang/en.php";
		}
?>

/*
 * CometChat
 * Copyright (c) 2010 Inscripts - support@cometchat.com | http://www.cometchat.com | http://www.inscripts.com
*/

(function($){   
  
	$.ccgames = (function () {

		var title = '<?php echo $games_language[0];?>';
		var lastcall = 0;

        return {

			getTitle: function() {
				return title;	
			},

			init: function (id) {
				baseUrl = $.cometchat.getBaseUrl();
				window.open (baseUrl+'plugins/games/index.php?id='+id, 'games_init',"status=0,toolbar=0,menubar=0,directories=0,resizable=0,location=0,status=0,scrollbars=0, width=440,height=260"); 
			},

			accept: function (id,fid,tid,rid,gameId,gameWidth) {
				baseUrl = $.cometchat.getBaseUrl();
                $.post(baseUrl+'plugins/games/index.php?action=accept', {to: id,fid: fid,tid: tid, rid: rid, gameId: gameId, gameWidth: gameWidth});
				var w = window.open (baseUrl+'plugins/games/index.php?action=play&fid='+fid+'&tid='+tid+'&rid='+rid+'&gameId='+gameId, 'games'+fid+''+tid,"status=0,toolbar=0,menubar=0,directories=0,resizable=0,location=0,status=0,scrollbars=0, width="+(gameWidth-30)+",height=600"); 
				w.focus(); // add popup blocker check
			},

			accept_fid: function (id,fid,tid,rid,gameId,gameWidth) {
				baseUrl = $.cometchat.getBaseUrl();
				var w =window.open (baseUrl+'plugins/games/index.php?action=play&fid='+fid+'&tid='+tid+'&rid='+rid+'&gameId='+gameId, 'games'+fid+''+tid,"status=0,toolbar=0,menubar=0,directories=0,resizable=0,location=0,status=0,scrollbars=0, width="+(gameWidth-30)+",height=600");
				w.focus(); // add popup blocker check
			}

        };
    })();
 
})(jqcc);