

jQuery(document).ready(function($){

	if ($('#snProfiletabs').size() > 0) {
		var par_URL = $.parseURL(window.location);
		if (par_URL.hash == 'socialnet_us' && par_URL.params.status_id != null) {
			$.cookie(socialnet_cfg.cookie.name + 'snUpProfileTab', 0, socialnet_cfg.cookie);
		}
		up_cfg.url = up_cfg.url.replace( /&amp;/, '&');
		
		$('#snProfiletabs').tabs({
		    cookie : {
		        name : socialnet_cfg.cookie.name + 'snUpProfileTab',
		        path : socialnet_cfg.cookie.path,
		        domain : socialnet_cfg.cookie.domain,
		        secure : socialnet_cfg.cookie.secure
		    },
		    create : function(e,ui){
			    if ($.cookie(socialnet_cfg.cookie.name + 'snUpProfileTab') == 0) {
				    return false
			    }
		    },
		    select : function(e,ui){
		    	if ( ui.index == 0 ){
		    		$.ajax({
		    			type:'POST',
		    			dataType: 'html',
		    			url: up_cfg.url,
		    			data: {mode:'wall',u:par_URL.params.u},
		    			success:function(data){
		    				$('#snProfiletabs-wall').html(data);
		    				$('.snUsInputComment').watermark(us_cfg.watermark,{
						        useNative: false,
						        className: 'snUsWatermarkComment'						        
						    }).TextAreaExpander(22, 100).css({
								height : '22px'
							});
		    				$("#snUsWallInput").watermark(us_cfg.watermark, {
		    				    useNative : false,
		    				    className : 'snUsWatermark'
		    				}).TextAreaExpander(22, 150).css({
		    					height : '22px'
		    				}).bind('focusin keyup input', function(){
		    					if (isValidURL($(this).val())) {
		    						$('input[name=snUsFetchButton]').show();
		    					} else {
		    						$('input[name=snUsFetchButton]').hide();
		    						$('input[name=snUsFetchClear]').trigger('click');
		    					}
		    				});
		    			}
		    		});
		    	}
		    	
			    if ($('#socialnet_profile #middle_column').height() > 550) {
				    var maxim = Math.min($('#socialnet_profile #middle_column').height(), Math.max($('#socialnet_profile #left-column').height(), 550));
			    } else {
				    var maxim = Math.max($('#socialnet_profile #middle_column').height(), $('#socialnet_profile #left-column').height(), 550);
			    }
			    $('#socialnet_profile #middle_column').css('min-height', maxim);

		    },
		    load : function(e,ui){
		    	$('.snUpEditable').editable({
		    		datePicker:{
		    			dateFormat: up_cfg.dateFormat,
		    			monthNames: up_cfg.monthNames,
		    			monthNamesShort: up_cfg.monthNamesShort
		    			},
		    		ajaxOptions:{
		    			url: up_cfg.urlAJAX
		    		}
		    	});
		    }
		}).removeAttr('style');
		$('.ui-tabs-panel').css({
			fontSize : '0.9em'
		});
	}

	$('#socialnet_profile #middle_column').css('min-height', Math.max($('#socialnet_profile #middle_column').height(), $('#socialnet_profile #left-column').height()));

	if ($('.snUpReportUser a').size() > 0 && $('#snProfiletabs').size() > 0) {
		$('.snUpReportUser a').click(function(){
			up_cfg.tabCurrent = $('#snProfiletabs').tabs('option', 'selected');
			if ($('#snProfiletabs ul li a[href$=report_user]').size() > 0) {
				$('#snProfiletabs').tabs('select', up_cfg.tabReportUser);
				return false;
			}
			up_cfg.tabReportUser = $('#snProfiletabs').tabs('length');
			$('#snProfiletabs').tabs('add', $(this).attr('href') + '#snProfiletabs-report_user', $(this).html());
			$('#snProfiletabs a[href$=report_user]').before('<span class="ui-icon ui-icon-close" style="display:inline-block;float:right;margin:8px 8px 0 0;">Remove Tab</span>');
			$('#snProfiletabs').tabs('select', up_cfg.tabReportUser);

			$('#snProfiletabs a[href$=report_user]').prev('.ui-icon.ui-icon-close').css('cursor', 'pointer').click(function(){
				$('#report input[name=cancel]').trigger('click');
			});
			return false;
		});

	}

	$('#report input[name=cancel]').live('click', function(){
		$('#snProfiletabs').tabs('select', up_cfg.tabCurrent).tabs('remove', up_cfg.tabReportUser);
		return false;
	});

	$('.snActionsProfile').children('a').addClass('ui-widget-content ui-corner-top').css({
	    display : 'inline-block',
	    padding : '1px',
	    marginBottom : '4px',
	    borderColor : '#fff'
	});

	$('.snUpAdd2Group').hoverIntent(function(){
		$(this).css({
		    borderColor : '#ccc',
		    borderBottomColor : '#fff'
		});
		$(this).children('.snUpAdd2GroupDetail').toggle().css({
			display : 'block'
		});
	}, function(){
		$(this).css({
			borderColor : '#fff'
		});
		$(this).children('.snUpAdd2GroupDetail').toggle();
	});

});