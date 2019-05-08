
jQuery(document).ready(function($) {
	
	if ( mp_cfg.blockOnlineUsers) {
		if ( socialnet_cfg.allow_load){
			$(document).everyTime(mp_cfg.tikTakOnline,mp_cfg.nameOnline,function(i) {
				mp_onlineListLoad(i);
			});
		}
	}

	// Show and hide the text "What is on your mind" && Resize
	// textarea
	if ($('#snMpInput').size() > 0) {
		$("#snMpInput").watermark(mp_cfg.watermark,{
		    useNative: false,
		    className: 'snUsWatermark'
		}).css({
			height: 22
		}).TextAreaExpander(22,70).bind('focusin keyup input', function() {
			//$('#snMpShareStatus').css('height','54px');
			$('#snMpShareStatus .snMpButtonOver').show();
			if ( $.isValidURL($(this).val())){
				$('input[name=snUsFetchButton]').show();
				$('#snMpShareStatus').removeAttr('style');
			} else {
				$('input[name=snUsFetchButton]').hide();
				$('input[name=snUsFetchClear]').trigger('click');
			}
		});
	}

	// Hide share button if clicked outside
	$(document).click(function(event) {
		if ($('#snMpShareStatus input[name=snMpButton]').size() > 0) {
			if ($('#snMpShareStatus .snMpButtonOver').is(':visible')) {
				if (!$(event.target).closest('#snMpShareStatus').size() && !$(event.currentTarget.activeElement).closest('#snMpInput').size() ) {
					$("#snMpShareStatus .snMpButtonOver").hide();
					/*$('#snMpShareStatus').css('height','29px');*/
					$('input[name=snUsFetchClear]').trigger('click');
					$('input[name=snUsFetchButton]').hide();
				}
			}
		}
	});
	
	$('.snUsFetchData .desc').css({
		height: 22
	}).TextAreaExpander(22,70);
	
	// Share status on mainpage
	if ($('#snMpShareStatus input[name=snMpButton]').size() > 0) {
		$("#snMpShareStatus input[name=snMpButton]").click(function() {
			var status_text = $("#snMpInput").val();

			status_text = status_text.replace(/^\s+|\s+$/g,'');

			if (status_text == '' || status_text == mp_cfg.watermark) {
				snConfirmBox(mp_cfg.emptyStatus,mp_cfg.emptyStatus);
			} else {
				var bPage = $.isValidURL($('#snMpInput').val())&&($('.snUsFetchData .title').html()!='')?1:0;
				var bImage = $('#snUsNoImg').is(':checked');
				var bVideo = $('#snUsNoVideo').is(':checked');

				$.ajax({
				    type: "POST",
				    url: mp_cfg.status_url,
				    cache: false,
				    data: {
				        smode: 'status_share_wall',
				        status: status_text,
				        isPage: bPage,
				        page: { 
				        	title: $('.snUsFetchData .title').html(),
				        	url: $('.snUsFetchData .url a').attr('href'),
				        	desc: $('.snUsFetchData .desc').val(),
				        	image: bImage?'':$('.snUsFetchImgs img:visible').attr('src'),
							imageH: bImage?'':$('.snUsFetchImgs img:visible').attr('imgH'),
							imageW: bImage?'':$('.snUsFetchImgs img:visible').attr('imgW'),
							video:  bVideo?'':$('.snUsFetchVideo').html(),
							videoI: bVideo?'':$('.snUsFetchVideoInfo').html(),
							videoP: bVideo?'':$('.snUsFetchVideoProvider').html()
				        }
				    },
				    success: function(data) {
					    $('#snMpInput').val('').height(22).watermark(mp_cfg.watermark,{
					        useNative: false,
					        className: 'snUsWatermark'
					    });
					    $('#snMpShareStatus').css('height','29px');
					    $('#snMpShareStatus .snMpButtonOver').hide();
					    $('input[name=snUsFetchClear]').trigger('click');
					    $('input[name=snUsFetchButton]').hide();
					    
					    $(data).hide().insertAfter('.snMpLoadNewsOver').slideDown('slow');
					    // $( '#snMpShareStatus').after( data);
					    $(".snUsInputComment").watermark(us_cfg.watermarkComment,{
					        useNative: false,
					        className: 'snUsWatermarkComment'
					    }).css({
						    height: 22
					    }).TextAreaExpander(22,100);
				    }
				});
			}
		});
	}

	$('.snMpGetMore').click(function() {
		var o_loader = $(this).next('.snMpStatusLoader');
		$(o_loader).show();
		var o_prev = $(this).parents('.snMpMoreBottom');
		var i_obj = $(o_prev).prev('div[id^=snMpEntry]');
		var i_lEntry = $(i_obj).attr('id').replace(/^snMpEntry/,'');

		$.ajax({
		    url: mp_cfg.url,
		    data: {
		        mode: 'snMpOlderEntries',
		        lEntryID: i_lEntry
		    },
		    error: function() {
			    $(o_loader).hide();
		    },
		    success: function(data) {
			    $(o_prev).before(data.content);
			    $(".snUsInputComment").watermark(us_cfg.watermarkComment,{
			        useNative: false,
			        className: 'snUsWatermarkComment'
			    }).css({
				    height: 22
			    }).TextAreaExpander(22,100);
			    $('div[id^=snMpEntry]:hidden').slideDown('slow');
			    $(o_loader).hide();
			    if (data.more === false) {
				    $(o_prev).remove();
			    }
		    }
		});
	});

	if ( $.browser.opera){
		$(".snUsWatermark").css({height:18,paddingTop:5});
		$(".snUsWatermarkComment").css({height:18,paddingTop:5});
	}

	$('.snMpLoadNews').click(function() {
		mp_loadNews();
	});

	$(document).focusin(function() {
		mp_loadNews();
	});

});

/**
 * Nacti nove zaznamy
 */
function mp_loadNews() {
	jQuery(function($) {
		if (mp_cfg.loadingNews) {
			return;
		}
		mp_cfg.loadingNews = true;
		var o_next = $('.snMpLoadNewsOver');
		var o_lEntry = $('#middle_column').find('div[id^=snMpEntry]:first');
		if ( $(o_lEntry).size()==0) return;
		var i_lEntry = $(o_lEntry).attr('id').replace(/^snMpEntry/,'');

		$.ajax({
		    url: mp_cfg.url,
		    data: {
		        mode: 'snMpNewestEntries',
		        lEntryID: i_lEntry
		    },
		    success: function(data) {
			    if ($(data.content).size() > 0) {
				    $(o_next).after(data.content);
				    $(".snUsInputComment").watermark(us_cfg.watermarkComment,{
				        useNative: false,
				        className: 'snUsWatermarkComment'
				    }).css({
					    height: 22
				    }).TextAreaExpander(22,100);
				    $(o_next).parent('div').children('div[id^=snMpEntry]:hidden').fadeIn('slow');
				    $(o_next).parent('div').children('div:not([id^=snMpEntry])[id^=snUs]').fadeOut('fast').remove();
			    }
			    mp_cfg.loadingNews = false;
		    }
		});
	});
}

/**
 * Nacti online usery
 */
function mp_onlineListLoad(i) {
	jQuery(function($) {
		$.ajax({
		    type: 'post',
		    cache: false,
		    async: true,
		    url: mp_cfg.url,
		    timeout: 1000,

		    data: {
			    mode: 'onlineUsers'
		    },
		    success: function(data) {

			    if (data.onlineCount == 0) {
				    var dU = $('<div />');
				    $(dU).addClass('snImUserLine').attr('user',0).append(mp_cfg.noOnlineUser);
				    $(dU).textOverflow('...',false);
				    $('#socialnet_mainpage .mpImOnlineUsers').html(dU);
			    } else {
				    $('#socialnet_mainpage .mpImOnlineUsers').html('');
				    $.each(data.onlineUsers,function(user_id, user) {
				    	var o_onlineUser = $('#socialnet_mainpage .mpImOnlineUsers div[user=' + user.user_id + ']'); 
					    if (o_onlineUser.size() == 0) {
						    var dU = $('<div />');
						    $(dU).addClass('snImUserLine').addClass((data.user_online==1)?'snImChatWith':'').attr({
						        user: user.user_id,
						        username: user.userClean
						    }).css('background-image','url("' + user.online + '")');
						    var spAvatar = $(user.avatar);

						    $(dU).append(spAvatar).append(user.userName);
						    $(dU).textOverflow('...',false);
						    $('#socialnet_mainpage .mpImOnlineUsers').append(dU);
					    } else {
						    o_onlineUser.css('background-image','url("' + user.online + '")');
						    if ( user.im_online == 1 && !o_onlineUser.hasClass('snImChatWith'))
						    {
						    	o_onlineUser.removeClass('snImChatWith');
						    }
						    if ( user.im_online == 0 && o_onlineUser.hasClass('snImChatWith'));
						    {
						    	o_onlineUser.addClass('snImChatWith');
						    }
					    }
				    });
			    }
		    }
		});

	});
}
