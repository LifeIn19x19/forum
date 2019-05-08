
jQuery(document).ready(function($){

	if (confirmBox_cfg.enable) {
		var $dialogHTML = $('<div class="ui-body-dialog"/>');
		$dialogHTML.attr('id', 'dialog').css('display', 'none');
		$dialogHTML.attr('title', 'Title Confirm Box');
		$dialogHTML.html('Content Confirm Box');

		$('body').append($dialogHTML);

		$('#dialog').dialog({
		    width : confirmBox_cfg.width,
		    resizable : confirmBox_cfg.resizable,
		    draggable : confirmBox_cfg.draggable,
		    modal : confirmBox_cfg.modal,
		    show : confirmBox_cfg.show,
		    hide : confirmBox_cfg.show,
		    autoOpen : false,
		    dialogClass : 'snConfirmBox'
		});
	}
});

function snConfirmBox(cbTitle,cbText,callbackConfirm,callbackLoad){
	jQuery(function($){

		if (confirmBox_cfg.enable) {

			
			$('#ui-dialog-title-dialog').html(cbTitle);
			$('#dialog').html(cbText);
			// $('#dialog').children('div').remove();
			// $('#dialog').children('span').remove();

			if (callbackConfirm == null || !$.isFunction(callbackConfirm)) {
				$('#dialog').dialog('option', {
				    open : function(){
					    if (callbackLoad != null && $.isFunction(callbackLoad)) {
						    callbackLoad.apply();
					    }
				    },
				    buttons : [ {
				        text : confirmBox_cfg.button_close,
				        click : function(){
					        $(this).dialog('close');
				        }
				    } ]
				}).dialog('open');

			} else {
				$('#dialog').dialog('option', {
				    open : function(){
					    if (callbackLoad != null && $.isFunction(callbackLoad)) {
						    callbackLoad.apply();
					    }
				    },
				    buttons : [ {
				        text : confirmBox_cfg.button_confirm,
				        click : function(){
					        if ($.isFunction(callbackConfirm)) {
						        callbackConfirm.apply();
					        }
					        $(this).dialog('close');
				        }
				    }, {
				        text : confirmBox_cfg.button_cancel,
				        click : function(){
					        $(this).dialog('close');
				        }
				    } ]
				}).dialog('open');
			}
		} else if (callbackConfirm != null && $.isFunction(callbackConfirm)) {
			callbackConfirm.apply();
		}
	});
}
