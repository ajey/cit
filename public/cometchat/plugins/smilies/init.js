/*
 * CometChat - Smilies Plugin
 * Copyright (c) 2010 Inscripts - support@cometchat.com | http://www.cometchat.com | http://www.inscripts.com
*/

(function($){   
  
	$.ccsmilies = (function () {

		var title = 'Send an emoticon';

        return {

			getTitle: function() {
				return title;	
			},

			init: function (id) {
				baseUrl = $.cometchat.getBaseUrl();
				window.open (baseUrl+'plugins/smilies/index.php?id='+id, 'smilies',"status=0,toolbar=0,menubar=0,directories=0,resizable=0,location=0,status=0,scrollbars=0, width=220,height=130"); 
			},

			addtext: function (id,text) {

				var string = $('#cometchat_user_'+id+'_popup .cometchat_textarea').val();
				
				if (string.charAt(string.length-1) == ' ') {
					$('#cometchat_user_'+id+'_popup .cometchat_textarea').val($('#cometchat_user_'+id+'_popup .cometchat_textarea').val()+text);
				} else {
					$('#cometchat_user_'+id+'_popup .cometchat_textarea').val($('#cometchat_user_'+id+'_popup .cometchat_textarea').val()+' '+text);
				}
				
				$('#cometchat_user_'+id+'_popup .cometchat_textarea').focus();
				
			}

        };
    })();
 
})(jqcc);