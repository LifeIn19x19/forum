function fms_load(m,s,u){
	jQuery(function($){
		var i_bl = m;
		if (m == 'friends')
			i_bl = 'friend';

		$.ajax({
		    url : fas_cfg.url,
		    data : {
		        mode : m,
		        fmsf : s,
		        usr : u
		    },
		    success : function(data){
			    $('#ucp_' + i_bl + ' .inner').html(data);
			    $('.snFasFriend span').textOverflow('..');
			    eval("var isF = typeof(fms_" + m + "Init) == 'function';");

			    if (isF) {
				    eval('fms_' + m + 'Init();');
			    }
		    }
		});
	})
};

function fms_Uload(m,s,l,u,c,r,p){
	jQuery(function($){
		var data = $.ajax({
		    url : fas_cfg.urlFMS,
		    data : {
		        mode : m,
		        fmsf : s,
		        flim : l,
		        usr : u,
		        chkbx : c,
		        sl : r,
		        pl:p
		    },
		    dataType : 'json',
		    success : function(data){

			    if (!r) {
				    $('#snFMSUsersBlockPagination_' + m).html(data.pagination);
			    }
			    $('#snFMSUsersBlockContent_' + m).html(data.content);
			    eval("var isF = typeof(fms_" + m + "Init) == 'function';");

			    if (isF) {
				    eval('fms_' + m + 'Init();');
			    }
		    }
		});

	})
};

function fms_friendInit(){
	jQuery(function($){
		$('#snFMSUsersBlockContent_friend').parents('form[id^=ucp] .inner').children('fieldset.submit-buttons').children('input').attr('disabled', 'disabled').addClass('disabled');
		fms_groupInit();
	});
}

function fms_approveInit(){
	jQuery(function($){
		$('#snFMSUsersBlockContent_approve').parents('form[id^=ucp] .inner').children('fieldset.submit-buttons').children('input').attr('disabled', 'disabled').addClass('disabled');
	});
}

function fms_cancelInit(){
	jQuery(function($){
		$('#snFMSUsersBlockContent_cancel').parents('form[id^=ucp] .inner').children('fieldset.submit-buttons').children('input').attr('disabled', 'disabled').addClass('disabled');
	});
}

function fms_groupInit(){
	jQuery(function($){
		$('.snFasFriendsBlock.ufg .snFMSUsers > span').draggable({
		    helper : 'clone',
		    appendTo : 'body',
		    revert : 'invalid'
		});
	});
}

function fms_grpschng(s_sub,i_gid,i_uid){
	jQuery(function($){
		$.ajax({
		    type : 'POST',
		    url : fas_cfg.url,
		    dataType : 'json',
		    data : {
		        mode : 'group',
		        sub : s_sub,
		        gid : i_gid,
		        uid : i_uid
		    },
		    success : function(data){
			    if (data.error) {
				    alert(data.text);
			    }
		    }
		});

	});
}

function fms_ldfrnd(toobj){
	jQuery(function($){
		if (toobj.html() != '') {
			return false
		}
		;
		$.ajax({
		    type : 'POST',
		    url : fas_cfg.urlFMS,
		    dataType : 'json',
		    async : true,
		    data : {
		        mode : 'friendgroup',
		        gid : toobj.attr('gid')
		    },
		    success : function(data){
			    toobj.append(data.content);
		    }
		})
	});
}

function fms_chngButtons(obj,chCls){
	jQuery(function($){
		var snFasButtons = $(obj).parents('form[id^=ucp] .inner').children('fieldset.submit-buttons').children('input');
		if ($(obj).parent().children('.snFasFriend.' + chCls).size() != 0) {
			$(snFasButtons).removeAttr('disabled').removeClass('disabled');
		} else {
			$(snFasButtons).attr('disabled', 'disabled').addClass('disabled');
		}

	});
}

jQuery(document).ready(function($){

	if ($('form#ucp #add').size() > 0) {
		$('form#ucp #add').bind('keyup change', function(){
			if ($(this).val() == '') {
				$('form#ucp input[name=submit]').attr('disabled', 'disabled').addClass('disabled');
			} else {
				$('form#ucp input[name=submit]').removeClass('disabled').removeAttr('disabled');
			}
		});
		if ($('form#ucp #add').val() == '') {
			$('form#ucp input[name=submit]').attr('disabled', 'disabled').addClass('disabled');
		} else {
			$('form#ucp input[name=submit]').removeClass('disabled').removeAttr('disabled');
		}
		$('form#ucp input[name=reset]').click(function(){
			$('form#ucp input[name=submit]').attr('disabled', 'disabled').addClass('disabled');
		});
		
		$('form#ucp').mouseover(function(){$('form#ucp #add').trigger('keyup');});
	}

	if ($('form[id=ucp_friend]').size() == 0 && $('form[id=ucp_approve]').size() == 0 && $('form[id=ucp_cancel]').size() == 0) {
		return;
	}

	fms_friendInit();
	$('.snFasFriend').live('click', function(){
		var chCls = 'checked';
		$(this).toggleClass(chCls);
		$(this).children('input[type=checkbox]').attr('checked', $(this).hasClass(chCls));

		fms_chngButtons(this, chCls);

	});
	$('.snFasFriend span').textOverflow('..');

	$('.snFasFriend a').live('click', function(){
		window.location = $(this).attr('href');
		return false;
	});

	$('[id^=ucp_] a.mark').click(function(){
		var $s_block = $(this).attr('class');
		$s_block = $s_block.replace('mark ', '');
		$('#snFMSUsersBlockContent_' + $s_block + ' .snFasFriend').addClass('checked');
		$('#snFMSUsersBlockContent_' + $s_block + ' .snFasFriend input[type=checkbox]').attr('checked', 'checked');
		fms_chngButtons($('#snFMSUsersBlockContent_' + $s_block + ' .snFasFriend'), 'checked');
		return false;
	});
	$('[id^=ucp_] a.unmark').click(function(){
		var $s_block = $(this).attr('class');
		$s_block = $s_block.replace('unmark ', '');

		$('#snFMSUsersBlockContent_' + $s_block + ' .snFasFriend').removeClass('checked');
		$('#snFMSUsersBlockContent_' + $s_block + ' .snFasFriend input[type=checkbox]').removeAttr('checked');
		fms_chngButtons($('#snFMSUsersBlockContent_' + $s_block + ' .snFasFriend'), 'checked');
		return false;
	});
	$('input[type=reset]').live('click', function(){
		$('.snFasFriend').removeClass('checked');
		$('.snFasFriend input[type=checkbox]').removeAttr('checked');
	});
});

jQuery(document).ready(function($){

	if ($('#gacc').size() == 0) {
		return;
	}

	var cfg = ($.hoverintent = {
	    sensitivity : 7,
	    interval : 200
	});
	$.event.special.hoverintent = {
	    setup : function(){
		    $(this).bind("mouseover", jQuery.event.special.hoverintent.handler);
	    },
	    teardown : function(){
		    $(this).unbind("mouseover", jQuery.event.special.hoverintent.handler);
	    },
	    handler : function(event){
		    event.type = "hoverintent";
		    var self = this, args = arguments, target = $(event.target), cX, cY, pX, pY;

		    function track(event){
			    cX = event.pageX;
			    cY = event.pageY;
		    }
		    ;
		    pX = event.pageX;
		    pY = event.pageY;
		    function clear(){
			    target.unbind("mousemove", track).unbind("mouseout", arguments.callee);
			    clearTimeout(timeout);
		    }
		    function handler(){
			    if ((Math.abs(pX - cX) + Math.abs(pY - cY)) < cfg.sensitivity) {
				    clear();
				    jQuery.event.handle.apply(self, args);
			    } else {
				    pX = cX;
				    pY = cY;
				    timeout = setTimeout(handler, cfg.interval);
			    }
		    }
		    var timeout = setTimeout(handler, cfg.interval);
		    target.mousemove(track).mouseout(clear);
		    return true;
	    }
	};

	$('#gacc').accordion({
	    collapsible : false,
	    clearStyle : true,
	    event : "click hoverintent",
	    changestart : function(e,ui){
		    fms_ldfrnd(ui.newContent);
	    }
	});
	fms_ldfrnd($('#gacc').children('div').first());

	$('#gacc > div').droppable({
	    drop : function(event,ui){
		    if ($(this).children('span[title="' + ui.draggable.attr('title') + '"]').size() > 0) {
			    return;
		    }
		    var i_gid = $(this).attr('gid');
		    var i_uid = ui.draggable.attr('uid');
		    var o_cnt = $('h3[gid=' + i_gid + '] span.counter');
		    var i_cnt = parseInt(o_cnt.html());
		    if ( i_cnt == 0) {
		    	$(this).html('');
		    }
		    $(this).append(ui.draggable.clone().css({
		        zIndex : 1500,
		        opacity : 1
		    }));
		    o_cnt.html(i_cnt + 1);
		    fms_grpschng('add', i_gid, i_uid);
	    },
	    activate : function(event,ui){
		    ui.draggable.css({
			    opacity : 0.5
		    });
	    },
	    deactivate : function(event,ui){
		    ui.draggable.css({
			    opacity : 1
		    });
	    }
	}).sortable({
	    helper : 'clone',
	    placeholder : 'snFasFriend move ui-state-highlight',
	    start : function(e,ui){
		    $('.snFasFriend.move.ui-state-highlight').css({
			    height : ui.item.height() + 'px'
		    });
	    },
	    appendTo : 'body',
	    recieve : function(e,ui){
		    sortableIn = 1;
	    },
	    over : function(e,ui){
		    sortableIn = 1;
	    },
	    out : function(e,ui){
		    sortableIn = 0;
	    },
	    beforeStop : function(e,ui){
		    if (sortableIn == 0) {
			    var i_gid = $(this).attr('gid');
			    var i_uid = ui.item.attr('uid');
			    var d_item = ui.item.clone();
			    $('body').append(d_item.css({
			        position : 'absolute',
			        top : ui.position.top,
			        left : ui.position.left
			    }));
			    
			    ui.item.remove();
			    $('body > .snFasFriend').addClass('red').effect('explode', {}, 1000).remove();
			    var o_cnt = $('h3[gid=' + i_gid + '] span.counter');
			    o_cnt.html(parseInt(o_cnt.html()) - 1);
			    fms_grpschng('remove', i_gid, i_uid);
		    }
	    },
	    stop: function(e,ui){
		    var i_gid = $(this).attr('gid');
		    var o_cnt = parseInt($('h3[gid=' + i_gid + '] span.counter').text());
		    if(o_cnt == 0){
		    	$(this).html(fas_cfg.noFriends);
		    }
	    	
	    }
	});

	fms_groupInit();

	$('#gacc .snFmsGroupDelete').click(function(){
		var i_gid = $(this).attr('gid');
		$('#gacc > [gid=' + i_gid + ']').remove();
		fms_grpschng('delete', i_gid, -1);
	});

});

/**
 * PROFILE ADD GROUP
 */
jQuery(document).ready(function($){

	if ($('.snFMSInGroup').size() == 0) {
		return;
	}

	$('.snFMSInGroup').click(function(){
		var o_dui = $(this).children('.ui-icon');
		var i_gid = $(this).attr('gid');
		var i_uid = $(this).attr('uid');
		var s_sub = o_dui.hasClass('ui-icon-check') ? 'remove' : 'add';

		fms_grpschng(s_sub, i_gid, i_uid);
		// console.log('fms_grpschng('+s_sub+','+i_gid+','+i_uid+')');
		o_dui.toggleClass('ui-icon-check ui-icon-no');
	})
})