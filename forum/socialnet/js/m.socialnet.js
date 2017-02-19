// jQuery.noConflict(); - We are prepared for noConflict mode

var socialnet_cfg = {
    allow_load : false,
    cookie : {
        name : '',
        path : '',
        domain : '',
        secure : '0'
    },
    regional: 'cs',
    rtl: false
}

var confirmBox_cfg = {
    enable : false,
    resizable : false,
    draggable : false,
    width : '60%',
    modal : false,
    show : 'fade',
    button_confirm : '',
    button_cancel : '',
    button_close : '',
    postMinChar: 0
};

var us_cfg = {
	loadMoreTime: 2000,		
    watermark: '',
    emptyStatus: '',
    watermarkComment: '',
    emptyComment: '',
    deleteStatusTitle: '',
    deleteStatusText: '',
    deleteCommentTitle: '',
    deleteCommentText: '',
    url: '',
    urlFetch: ''
}

var im_cfg = {
    root_path : '',
    sound : false,
    soundFile : '',
    soundFlashVars : '',
    onlineListTitle : '',
    youAreOffline : '',
    new_message : '',
    title_old : '',
    title_new : '',
    url : '',
    isOnline : true,
    timers : {
        chat_Open : 1000,
        chat_Close : 60000,
        im_counter : 1
    },
    names : {
        chat : 'snIM_Chat_Timer',
        onlineList : 'snIM_OnlineList_Timer',
        me : '',
        online : '{ SN_IM_CHAT }'
    },
    msgSendCode : [ 13, '*' ],
    maxBoxes : 4,
    curPosit : 0
};

// Posledni stisknuta klavesa
var lPressKey = -1;

var up_cfg = {
    url : '',
    urlAJAX: '',
    tabReportUser : 0,
    tabCurrent : 0,
    spinner: '<em>Loading&#8230;</em>',
    dateFormat: 'd. MM yy',
	monthNames: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
	monthNamesShort: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
}

var ntf_cfg = {
	check_time : 6000,
	timer_name : 'notify_ticker',
	url : '',
	settings: {
		position: 'bottom-left',
		glue: 'before',
		closer: false,
		life: 10000
	}
};

var mp_cfg = {
    url: '',
    status_url: '',
    tikTakOnline: 30000,
    watermark: '',
    watermarkComment: '',
    emptyStatus: '',
    emptyComment: '',
    noOnlineUser: '',
    deleteStatusTitle: '',
    deleteStatusText: '',
    deleteCommentTitle: '',
    deleteCommentText: '',
    nameOnline: 'mp_onlineTicker',
    loadingNews: false,
    blockOnlineUsers: false    
}

var fas_cfg = {
    url : '',
    urlFMS : '',
    noFriends: '{ FAS FRIENDGROUP NO TOTAL }'
}




function snUs_windowResize() {
	jQuery(function($) {
		$('#socialnet_us').css({
			left: (($(document).width() - $('#socialnet_us').width()) / 2) + 'px'
		});

	});
}  

(function($){
	$.parseURL = function(url) {
	    var a =  document.createElement('a');
	    a.href = url;
	    return {
	        source: url,
	        protocol: a.protocol.replace(':',''),
	        host: a.hostname,
	        port: a.port,
	        query: a.search,
	        params: (function(){
	            var ret = {},
	                seg = a.search.replace(/^\?/,'').split('&'),
	                len = seg.length, i = 0, s;
	            for (;i<len;i++) {
	                if (!seg[i]) { continue; }
	                s = seg[i].split('=');
	                ret[s[0]] = s[1];
	            }
	            return ret;
	        })(),
	        file: (a.pathname.match(/\/([^\/?#]+)$/i) || [,''])[1],
	        hash: a.hash.replace('#',''),
	        path: a.pathname.replace(/^([^\/])/,'/$1'),
	        relative: (a.href.match(/tps?:\/\/[^\/]+(.+)/) || [,''])[1],
	        segments: a.pathname.replace(/^\//,'').split('/')
	    };
	}
	
	$.isValidURL = function (url) {
		//var RegExp = /(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/;
		//var RegExp = /^(([\w]+:)?\/\/)?(([\d\w]|%[a-fA-f\d]{2,2})+(:([\d\w]|%[a-fA-f\d]{2,2})+)?@)?([\d\w][-\d\w]{0,253}[\d\w]\.)+[\w]{2,4}(:[\d]+)?(\/([-+_~.\d\w]|%[a-fA-f\d]{2,2})*)*(\?(&?([-+_~.\d\w]|%[a-fA-f\d]{2,2})=?)*)?(#([-+_~.\d\w]|%[a-fA-f\d]{2,2})*)?$/;
		//var RegExp = /^(https|http|ftp)?:\/\/(?:[a-z\-]+\.)+[a-z]{2,6}(?:\/[^\/#?]+)+\.?(?:.*)$/;
		var RegExp = /^(https?|ftp):\/\/(((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&amp;'\(\)\*\+,;=]|:)*@)?(((\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5]))|((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?)(:\d*)?)(\/((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&amp;'\(\)\*\+,;=]|:|@)+(\/(([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&amp;'\(\)\*\+,;=]|:|@)*)*)?)?(\?((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&amp;'\(\)\*\+,;=]|:|@)|[\uE000-\uF8FF]|\/|\?)*)?(\#((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&amp;'\(\)\*\+,;=]|:|@)|\/|\?)*)?$/i;
		if(RegExp.test(url)) {
			return true;
		} else {
			return false;
		}
	}
	
	$.equalsArray = function (obj1, obj2) {
		return $.param(obj1) === $.param(obj2);
	}
	
	
	$.metadataInit = function(){
		if ( $.metadata){
			//IM
			if ( $('.snImChatBox textarea.snImMessage').size()>0){
				$('.snImChatBox textarea.snImMessage').each(function(){
					$(this).attr('uid', $(this).metadata().uid);
				});
				
			}
		}
	}
	
	$.fn.metadataInit = function (param){
		$(this).each(function(){
			$(this).attr(param, eval('\$(this).metadata().'+param+';'));
		})
	}
	
})(jQuery);


jQuery(document).ready(function($){
	$.metadata.setType("class");
	socialnet_cfg.rtl = $('body').hasClass('rtl');
	socialnet_cfg.cookie.name = socialnet_cfg.cookie.name + '_';
	if (!socialnet_cfg.cookie.domain.match(/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/) && !socialnet_cfg.cookie.domain.match(/^\./)) {
		socialnet_cfg.cookie.domain = '.' + socialnet_cfg.cookie.domain;
	}

	if ($('ul.snMenu').size() > 0) {
		$('ul.snMenu').menuNew().mouseleave(function(){
			$(this).children('li').children('ul').fadeOut(300);
			$(this).children('li').children('a.ui-state-active').removeClass('ui-state-active');
		});
	}

	$('input.ui-button').bind('mouseover mouseout', function(){
		$(this).toggleClass('ui-state-hover');
	});
});

