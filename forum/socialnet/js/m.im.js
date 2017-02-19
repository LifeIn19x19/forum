/**
 * jQuery
 * 
 * @param {Object} $
 */
jQuery(document).ready(function($){

	if ( $.browser.mobile){
		$('#socialnet-im').addClass('snImDockWrapper-mobile');
		
		/*var $divB = $('<div class="ui-widget ui-widget-content ui-corners-all">');
		$('body').append($divB);
		
		$.each($.browser, function(i,v){
			$('<span>'+i+': '+v+'<br /></span>').appendTo($divB);
		});*/
	}
	
	var snImCurCheckTime = $.cookie(socialnet_cfg.cookie.name + 'snImCurCheckTime');
	if (snImCurCheckTime != null) {
		im_cfg.timers.im_counter = snImCurCheckTime;
	}

	im_cfg.title_old = $(document).attr('title');

	$(window).resize(function(){
		snIm_resizeBlocks();
	});
	snIm_resizeBlocks();

	//$.metadataInit();
	
	// NASTAVENI AJAX
	$.ajaxSetup({
	    type : 'post',
	    cache : false,
	    async : true,
	    url : im_cfg.url
	/*
	 * , timeout: 1000
	 */
	});

	$('#socialnet_im .snImButton').textOverflow('...', false);

	// BOTTOM BUTTON CLICK
	$('.snImChatBoxes .snImButton').live('click', function(){
		var cBlock = $(this).next('.snImBlock');
		$(cBlock).slideToggle(100);
		$(this).toggleClass('snImOpener');
		$.cookie(socialnet_cfg.cookie.name + $(cBlock).attr('id'), $(this).hasClass('snImOpener'), socialnet_cfg.cookie);
		$('> .snImTextarea textarea.snImMessage', cBlock).focus();
		$('> .snImUnread', this).hide().html('0');
		$.cookie(socialnet_cfg.cookie.name + $(cBlock).attr('id') + 'Unread', '0', socialnet_cfg.cookie);
		var snMsgs = $(this).next('.snImBlock').children('.snImMsgs');
		$(snMsgs).scrollTop(99999);

		$('.snImChatBoxes .snImButton:not([id=' + $(cBlock).attr('id') + '])').each(function(){
			var oBlock = $(this).next('.snImBlock');
			if ($(oBlock).attr('id') === $(cBlock).attr('id')) {
				return;
			}

			$(oBlock).hide();
			$(this).removeClass('snImOpener');
			$.cookie(socialnet_cfg.cookie.name + $(oBlock).attr('id'), false, socialnet_cfg.cookie);
		});
	}).each(function(){
		// Nastav neprectene
		var cBlock = $(this).next('.snImBlock');
		var unReadCnt = $.cookie(socialnet_cfg.cookie.name + $(cBlock).attr('id') + 'Unread');
		if (unReadCnt !== 0 && unReadCnt !== null) {
			$('> .snImUnread', this).show().html(unReadCnt);
		}
	});

	$('.snImUnread').each(function(){
		$(this).css('display', $(this).html() === '0' ? 'none' : 'inline');
	});

	// Otevreni/zavreni Online listu pri kliknuti na button
	$('#snImOnline .snImButton').click(function(){
		var cBlock = $(this).next('.snImBlock');
		$(cBlock).toggle();
		$(this).toggleClass('snImOpener');
		var hasCls = $(this).hasClass('snImOpener');
		$.cookie(socialnet_cfg.cookie.name + $(cBlock).attr('id'), hasCls, socialnet_cfg.cookie);
		if (hasCls) {
			onlineListLoad();
		}
	});

	// ODHLASENI Z IM - DISABLE IM
	$(".snImLogout").click(function(){
		var parent = $(this).parents('.snImLoginLogout');
		$('.snImLogin', parent).removeClass('selected');
		$(this).addClass('selected');

		$.ajax({
		    data : {
			    mode : 'snImLogout'
		    },
		    success : function(data){
			    if (data.logout === true) {
				    stopTimers();
				    $('#snImOnlineCount span.count').html('(0)');
				    $('#snImOnlineList').html('<span class="snImUserLine">' + im_cfg.youAreOffline + '</span>');
				    $('.snImClose').each(function(){
					    $(this).trigger('click');
				    });
				    var bg = $('#socialnet_im #snImOnline .snImButton').css('background-image');
				    bg = bg.replace(/online\.png/i, 'offline.png');
				    $('#socialnet_im #snImOnline .snImButton').css('background-image', bg);

				    im_cfg.isOnline = false;
			    }
		    }
		})
	});

	// Prihlaseni
	$(".snImLogin").click(function(){
		var parent = $(this).parents('.snImLoginLogout');
		$('.snImLogout', parent).removeClass('selected');
		$(this).addClass('selected');

		$.ajax({
		    data : {
			    mode : 'snImLogin'
		    },
		    success : function(data){
			    if (data.login === true) {
				    $('#snImOnlineList').html('');
				    im_cfg.isOnline = true;
				    startTimers(true);
				    var bg = $('#socialnet_im #snImOnline .snImButton').css('background-image');
				    bg = bg.replace(/offline\.png/i, 'online.png');
				    $('#socialnet_im #snImOnline .snImButton').css('background-image', bg);
				    onlineListLoad(0);
			    }
		    }
		});
	});

	// Zapni / vypni sound
	$(".snImSound").click(function(){
		var sound_attr = $('.snImSound.ui-icon').hasClass('ui-icon-volume-on');
		var $mode = 'snImSound' + (sound_attr ? 'Off' : 'On');
		$.ajax({
		    data : {
			    mode : $mode
		    },
		    success : function(data){
			    if (data == null) {
				    return false;
			    }
			    $('.snImSound.ui-icon').toggleClass('ui-icon-volume-on ui-icon-volume-off');
			    im_cfg.sound = data.sound;
		    }
		});

	});

	// ZAVRIT ONLINE LIST PRI KLIKNUTI MIMO
	$(document).click(function(event){
		if ($('#snImOnlineCount').hasClass('snImOpener')) {
			var s_obj = $('#snImOnline #snImOnlineBlock:visible').parents('#snImOnline').attr('id');
			if (!$(event.target).closest('#' + s_obj).size()) {
				$('#snImOnlineCount').trigger('click');
			}
		}
	});
	$('.snImTitle').live('click', function(){
		var block = $(this).parents('.snImGroup');
		$('> .snImButton', block).removeClass('snImOpener');
		$('> .snImBlock', block).slideUp('fast');

		$.cookie(socialnet_cfg.cookie.name + $('> .snImBlock', block).attr('id'), false, socialnet_cfg.cookie);
	});
	// otevreni chat boxu
	$('.snImChatWith').live('click', function(){
		var uid = $(this).attr('user');

		$('.snImChatBox').each(function(){
			$(this).children('.snImButton').removeClass('snImOpener');
			var snImBlock = $(this).children('.snImBlock');
			snImBlock.hide();
			$.cookie(socialnet_cfg.cookie.name + snImBlock.attr('id'), false, socialnet_cfg.cookie);
		});
		if ($('#snImChatBox' + uid).size() > 0) {
			$('#snImChatBox' + uid + ' .snImButton').addClass('snImOpener');
			$('#snImChatBox' + uid + ' .snImBlock').show();
			$.cookie(socialnet_cfg.cookie.name + 'snImChatBoxBlock' + uid, true, socialnet_cfg.cookie);
			$('#snImChatBox' + uid + ' textarea.snImMessage').focus();
			return;
		}

		makeChatBox(uid, $(this).attr('username'), true);
	});
	// zavreni chat boxu
	$('.snImClose').live('click', function(){
		var but = $(this).parents('.snImChatBox');
		var uid_to = $(but).attr('id').replace('snImChatBox', '');

		$.ajax({
			data : {
			    mode : 'closeChatBox',
			    uid : uid_to
			}
		});
		$.cookie(socialnet_cfg.cookie.name + 'snImChatBoxBlock' + uid_to, null, socialnet_cfg.cookie);
		$.cookie(socialnet_cfg.cookie.name + 'snImChatBoxBlock' + uid_to + 'Unread', -1, socialnet_cfg.cookie);
		$(but).remove();
		snImScrollable(10);
	});

	$('.snImChatBox textarea.snImMessage').TextAreaExpander(16, 64).bind('keyup', function(e){
		messageKeyUp(this, e);
	}).metadataInit('uid');
	
	im_cfg.soundFile = $('#snImMsgArrived a').attr('href');
	im_cfg.soundFlashVars = $('#snImMsgArrived a').attr('title');

	$snImCB = $('#snImChatBoxes');
	im_cfg.curPosit = $.cookie(socialnet_cfg.cookie.name + 'snImCurPosit');
	if (im_cfg.curPosit === null) {
		$.cookie(socialnet_cfg.cookie.name + 'snImCurPosit', 0, socialnet_cfg.cookie);
		im_cfg.curPosit = 0;
	}

	// Vrati time ago pre message
	$('.snImMsg').live('mouseover', function(){
		var cObj = $(this).children('.snImMsgTime');
		// $('.snImMsgTime:visible').hide();
		var mTime = $(this).attr("time");

		$.ajax({
		    data : {
		        mode : 'msg_time',
		        msg_time : mTime
		    },
		    success : function(data){
			    $(cObj).html(data.timeAgo).show();
		    }
		});
	}).live('mouseout', function(){
		var cObj = $(this).children('.snImMsgTime');
		var $this = this;
		$(document).oneTime(1000, $($this).attr('time'), function(){
			$(cObj).hide();
		});
	});
	snImScrollable();

	$('.snImNav.snImPrev').click(function(){
		snImScrollable(1);
	});
	$('.snImNav.snImNext').click(function(){
		snImScrollable(2);
	});
	$(document).focusin(function(){
		startTimers();
	});

	startTimers();

	$('#socialnet_im').removeAttr('style');
	$('.snImChatBoxes .snImButton').each(function(){
		var snMsgs = $(this).next('.snImBlock').children('.snImMsgs');
		$(snMsgs).scrollTop(99999);
	});

	// $('<div id="chkTime"
	// style="font-size:20px;position:fixed;bottom:2px;left:20px;width:150px;height:30px;text-align:center;background-color:#eee;border:3px
	// double #393" />').appendTo('body');
});
/**
 * Vyzvedavani zprav
 * 
 * @param {Integer}
 *            i Pocet volani procedury, generovano z pluginu timers
 */
function coreChat(i){
	jQuery(function($){
		if (!socialnet_cfg.allow_load) {
			return;
		}

		if (!im_cfg.isOnline) {
			stopTimers();
			return;
		}

		$.ajax({
		    data : {
			    mode : 'coreIM'
		    },
		    success : function(data){

			    $('#snImOnlineCount span.count').html('(' + data.onlineCount + ')');
			    if (data.message != null && data.message.length != 0) {
				    startTimers(true);
				    if (im_cfg.sound) {
					    // SOUND
					    // $.dbj_sound.play('#snImMsgArrived a',false);
					    if ($.browser.msie) {
						    $('#snImMsgArrived').html('<object height="1" width="1" type="application/x-shockwave-flash" data="' + im_cfg.soundFile + '"><param name="movie" value="' + im_cfg.soundFile + '"><param name="FlashVars" value="' + im_cfg.soundFlashVars + '"></object>');
					    } else {
						    $('#snImMsgArrived').html('<embed src="' + im_cfg.soundFile + '" width="0" height="0" pluginspage="http://www.macromedia.com/go/getflashplayer" type="application/x-shockwave-flash" FlashVars="' + im_cfg.soundFlashVars + '"></embed>');
					    }
				    }
				    // notify about new message in tab name
				    $.titleAlert(im_cfg.new_message, {
				        requireBlur : true,
				        stopOnFocus : true,
				        duration : 0,
				        interval : 1500
				    });
				    $.each(data.message, function(i,message){
					    if (message.chatBox === false) {
						    makeChatBox(message.uid, message.userName, false);
						    if ($('#snImChatBoxes').children().length > 1) {
							    $('#snImChatBox' + message.uid + ' .snImButton').removeClass('snImOpener');
							    $('#snImChatBox' + message.uid + ' .snImBlock').hide();
							    $.cookie(socialnet_cfg.cookie.name + $('#snImChatBox' + message.uid + ' .snImBlock').attr('id'), false, socialnet_cfg.cookie);
							    $('#snImChatBoxBlock' + message.uid).hide();
						    }
					    } else {
						    var msgs = $('#snImChatBoxBlock' + message.uid + ' .snImMsgs');

						    $(msgs).append(message.message).scrollTop(99999);
					    }
					    if ($('#snImChatBoxBlock' + message.uid).is(':hidden')) {
						    var $snImUnread = $('#snImChatBox' + message.uid + ' .snImUnread');
						    $snImUnread.show();
						    $snImUnread.html(parseInt($snImUnread.html()) + 1);
						    $.cookie(socialnet_cfg.cookie.name + 'snImChatBoxBlock' + message.uid + 'Unread', $snImUnread.html(), socialnet_cfg.cookie);
					    }

				    });
			    }

			    $('#snImOnlineList').html(data.onlineList);
			    $('.snImUserLine').textOverflow('...', false);
		    }
		});

	});
}

function getCaret(el){
	if (el.selectionStart) {
		return el.selectionStart;
	} else if (document.selection) {
		el.focus();

		var r = document.selection.createRange();
		if (r == null) {
			return 0;
		}

		var re = el.createTextRange(), rc = re.duplicate();
		re.moveToBookmark(r.getBookmark());
		rc.setEndPoint('EndToStart', re);

		return rc.text.length;
	}
	return 0;
}

/**
 * Event pri upusteni klavesy pro odeslani zpravy
 * 
 * @param {Object}
 *            obj textarea ktere se to tyka
 * @param {Object}
 *            e informace z klavesnice
 */
function messageKeyUp(obj,e){
	jQuery(function($){
		var code = (e.keyCode ? e.keyCode : e.which);
		// var shift = e.altKey;
		var msgs = $('> .snImMsgs', $(obj).parents('.snImBlock'));
		if ((code === im_cfg.msgSendCode[1] && lPressKey === im_cfg.msgSendCode[0]) || (code === im_cfg.msgSendCode[0] && '*' === im_cfg.msgSendCode[1])) {
			var msg = $(obj).val();
			var getC = getCaret(obj)+($.browser.msie&&$.browser.version<9?1:0);
			if (getC != msg.length) {
				msg = msg.substring(0, getC - 1) + msg.substring(getC);
			}
			msg = msg.replace(/\s*/i, '');
			if (msg !== '') {
				// var uid_to = $.ajax({
				$.ajax({
				    data : {
				        mode : 'sendMessage',
				        uid : $(obj).attr('uid'),
				        message : msg
				    },
				    success : function(data){
					    msgs.append(data.message);
					    msgs.scrollTop(99999);
				    }
				});
			}
			$(obj).val('').css('height', 16);
			stopTimers();
			startTimers(true);
		}

		if ($(obj).attr('h_old') !== $(obj).height()) {
			$(msgs).css({
				height : ($(msgs).height() - ($(obj).height() - $(obj).attr('h_old'))) + 'px'
			}).scrollTop(99999);
		}
		$(obj).attr('h_old', $(obj).height());
		lPressKey = code;

	});
}

/**
 * Vytvoreni chatboxu
 * 
 * @param {Integer}
 *            uid Indetifikator uzivatele
 * @param {String}
 *            userName Jmeno uzivatele
 * @param {Boolean}
 *            bAsync Otevrit asynchrone ci synchrone
 */
function makeChatBox(uid,userName,bAsync){
	jQuery(function($){
		if (im_cfg.isOnline == 0) {
			return;
		}
		if ($('#snImChatBox' + uid).size() > 0) {
			return;
		}
		$.ajax({
		    async : bAsync,
		    data : {
		        mode : 'openChatBox',
		        userTo : uid,
		        usernameTo : userName
		    },
		    success : function(data){
			    $('#snImChatBoxes').append(data.html);
			    $('.snImChatBox textarea.snImMessage').TextAreaExpander(16, 64).bind('keyup', function(e){
				    messageKeyUp(this, e);
			    }).metadataInit('uid');

			    
			    $('#socialnet_im .snImButton').textOverflow('...', false);
			    $('#snImChatBox' + uid + ' textarea.snImMessage').focus();
			    $('#snImChatBox' + uid + ' .snImMsgs').scrollTop(99999);
			    $.cookie(socialnet_cfg.cookie.name + 'snImChatBoxBlock' + uid, true, socialnet_cfg.cookie);
			    snImScrollable(20);
			    if ($.browser.mobile) {
				    scrollIm();
			    }
		    }
		});
	});
}

/**
 * Nacteni online listu
 * 
 * @param {Integer}
 *            i Pocet volani procedury, generovano z pluginu timers
 */
function onlineListLoad(i){
	jQuery(function($){
		if (im_cfg.isOnline == true) {
			$.ajax({
			    data : {
				    mode : 'onlineUsers'
			    },
			    success : function(data){
				    $('#snImOnlineCount span.count').html('(' + data.onlineCount + ')');
				    $('#snImOnlineList').html(data.onlineList);
				    $('.snImUserLine').textOverflow('...', false);
			    }
			});
		}
	});
}

/**
 * Resize bloku, ktere si to zadazi pri zmene okna
 */
function snIm_resizeBlocks(){
	jQuery(function($){
		$('#socialnet_im #snImOnlineList').css('max-height', ($(window).height() - 100 > 50 ? $(window).height() - 100 : 50) + 'px');
	});
}

/**
 * Zastaveni casovacu
 */
function stopTimers(){
	jQuery(function($){
		$(document).stopTime(im_cfg.names.chat);
		// $(document).stopTime(im_cfg.names.onlineList);
	});
}

/**
 * Spusteni casovacu
 * 
 * @param {Boolean}
 *            sh false - dlouhy cas, true - kratky cas
 */
function startTimers(sh){
	jQuery(function($){
		if (sh) {
			im_cfg.timers.im_counter = 1;
		} else {
			im_cfg.timers.im_counter++;
		}
		$.cookie(socialnet_cfg.cookie.name + 'snImCurCheckTime', im_cfg.timers.im_counter - 1, socialnet_cfg.cookie);

		var tiktakChat = im_cfg.timers.im_counter * im_cfg.timers.chat_Open;
		if (tiktakChat > im_cfg.timers.chat_Close) {
			tiktakChat = im_cfg.timers.chat_Close;
		}
		// $('#chkTime').html( 'IM: '+(tiktakChat/1000)+' sec');
		if (!socialnet_cfg.allow_load) {
			return;
		}
		$(document).oneTime(tiktakChat, im_cfg.names.chat, function(i){
			coreChat(i);
			startTimers();
		});
	});
}

/**
 * Posouvani chat boxiku
 * 
 * @param {Integer}
 *            m operace pro scroll 10 - zavreni chat boxiku 20 - vytvoreni chat
 *            boxiku 1 - posun vpravo 2 - posun vlevo 0 - zaciname
 */
function snImScrollable(m){
	jQuery(function($){

		if ($snImCB.children().length > im_cfg.maxBoxes) {
			switch (m) {
			case 10:
				if (im_cfg.curPosit === 0) {
					im_cfg.curPosit--;
				}
				break;
			case 20:
				im_cfg.curPosit++;
				break;
			case 1:
				im_cfg.curPosit--;
				break;
			case 2:
				im_cfg.curPosit++;
				break;
			/*
			 * case 0: default: break;
			 */
			}
			$.cookie(socialnet_cfg.cookie.name + 'snImCurPosit', im_cfg.curPosit, socialnet_cfg.cookie);
			for (i = 0; i < im_cfg.maxBoxes; i++) {
				$snImCB.children(im_cfg.curPosit + i).show();
			}
			$snImCB.children(':lt(' + im_cfg.curPosit + '):visible').hide();
			$snImCB.children(':gt(' + (im_cfg.curPosit + im_cfg.maxBoxes - 1) + '):visible').hide();
		} else {
			$snImCB.children('.snImChatBox').show();
		}

		if ($snImCB.children(':first-child').is(':visible')) {
			$('.snImPrev:visible').hide();
		} else {
			$('.snImPrev:hidden').show();
		}

		if ($snImCB.children(':last-child').is(':visible')) {
			$('.snImNext:visible').hide();
		} else {
			$('.snImNext:hidden').show();
		}
		if ($snImCB.children('.snImChatBox').length === 0) {
			$('.snImNav').hide();
		}
	});
}
