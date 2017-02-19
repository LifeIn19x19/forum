
jQuery(document).ready(function($){

	// $(window).resize(function(){snUs_windowResize();})
	// snUs_windowResize();

	// Show and hide the text "What is on your mind" && Resize textarea
	if ($('#snUsInput').size() > 0) {
		$("#snUsInput").watermark(us_cfg.watermark, {
		    useNative : false,
		    className : 'snUsWatermark'
		}).TextAreaExpander(22, 70).css({
			height : '22px'
		}).focusin(function(){
			$('.snUsShare .snUsButtonOver').show();
		});
	}
	// if ($('#snUsWallInput').size() > 0) {
	$("#snUsWallInput").watermark(us_cfg.watermark, {
	    useNative : false,
	    className : 'snUsWatermark'
	}).TextAreaExpander(22, 150).css({
		height : '22px'
	}).bind('focusin keyup input', function(){
		if ($.isValidURL($(this).val())) {
			$('input[name=snUsFetchButton]').show();
		} else {
			$('input[name=snUsFetchButton]').hide();
			$('input[name=snUsFetchClear]').trigger('click');
		}
	});
	// }
	// Share status on index
	if ($('.snUsShare input[name=snUsButton]').size() > 0) {
		$(".snUsShare input[name=snUsButton]").click(function(){
			var status_text = $("#snUsInput").val();
			// var now = Math.floor(new Date().valueOf() / 1000);
			status_text = status_text.replace(/^\s+|\s+$/g, '');

			if (status_text == '' || status_text == us_cfg.watermark) {
				snConfirmBox(us_cfg.emptyStatus, us_cfg.emptyStatus);
			} else {
				$.ajax({
				    type : "POST",
				    url : us_cfg.url,
				    cache : false,
				    data : {
				        smode : 'status_share',
				        status : status_text
				    },
				    success : function(data){
					    $('#snUsInput').val('').height(22).watermark(us_cfg.watermark, {
						    useNative : false
					    });
					    $('.snUsShared').fadeIn(1500);
					    $('.snUsShare .snUsButtonOver').fadeOut(3500);
				    }
				});
			}
		});
	}
	// Delete status
	$(".snUsDeleteStatus").live('click', function(){
		var status_id = $(this).attr("name");
		var wallid = $(this).attr('wallid');

		$.ajax({
		    type : 'POST',
		    url : us_cfg.url,
		    dataType : 'json',
		    data : {
		        smode : 'get_status',
		        status : status_id,
		        wall : wallid
		    },
		    success : function(data){
			    snConfirmBox(us_cfg.deleteStatusTitle, us_cfg.deleteStatusText + '<hr />' + data.content, function(){
				    $.ajax({
				        type : "POST",
				        url : us_cfg.url,
				        cache : false,
				        data : {
				            smode : 'status_delete',
				            s_id : status_id
				        },
				        success : function(data){
					        $('#snUsStatus' + status_id).fadeOut('slow').remove();
				        }
				    });
			    });
		    }
		})

	});
	// Delete comment
	$(".snUsDeleteComment").live('click', function(){
		var comment_id = $(this).attr("name");

		snConfirmBox(us_cfg.deleteCommentTitle, us_cfg.deleteCommentText + '<hr />' + $('#snUsStatusComment' + comment_id).children('.comment_text').html(), function(){
			$.ajax({
			    type : "POST",
			    url : us_cfg.url,
			    cache : false,
			    data : {
			        smode : 'comment_delete',
			        c_id : comment_id
			    },
			    success : function(data){
				    $('#snUsStatusComment' + comment_id).fadeOut('slow').remove();
			    }
			});
		});
	});

	$('.snUsFetchData .desc').TextAreaExpander(22, 70).css({
		height : '22px'
	});

	// Share status on Wall
	$('.snUsShare input[name=snUsWallButton]').live('click', function(){
		var status_text = $("#snUsWallInput").val();
		var now = Math.floor(new Date().valueOf() / 1000);
		var wall_id = $(this).attr('wall');

		status_text = status_text.replace(/^\s+|\s+$/g, '');

		if (status_text == '' || status_text == us_cfg.watermark) {
			snConfirmBox(us_cfg.emptyStatus, us_cfg.emptyStatus);
		} else {

			var bPage = $.isValidURL($('#snUsWallInput').val()) && ($('.snUsFetchData .title').html() != '') ? 1 : 0;
			var bImage = $('#snUsNoImg').is(':checked');
			var bVideo = $('#snUsNoVideo').is(':checked');
			$.ajax({
			    type : "POST",
			    url : us_cfg.url,
			    cache : false,
			    data : {
			        smode : 'status_share_wall',
			        status : status_text,
			        wall : wall_id,
			        isPage : bPage,
			        page : {
			            title : $('.snUsFetchData .title').html(),
			            url : $('.snUsFetchData .url a').attr('href'),
			            desc : $('.snUsFetchData .desc').val(),
			            image : bImage ? '' : $('.snUsFetchImgs img:visible').attr('src'),
			            imageH : bImage ? '' : $('.snUsFetchImgs img:visible').attr('imgH'),
			            imageW : bImage ? '' : $('.snUsFetchImgs img:visible').attr('imgW'),
			            video : bVideo ? '' : $('.snUsFetchVideo').html(),
			            videoI : bVideo ? '' : $('.snUsFetchVideoInfo').html(),
			            videoP : bVideo ? '' : $('.snUsFetchVideoProvider').html()
			        }
			    },
			    success : function(data){
				    $('#snUsNoStatus').hide();
				    $(data).hide().prependTo('#socialnet_us_profile').slideDown('slow');
				    $('input[name=snUsFetchClear]').trigger('click');
				    $('input[name=snUsFetchButton]').hide();
				    $('#snUsWallInput').val('').height(22).watermark(us_cfg.watermark, {
				        useNative : false,
				        className : 'snUsWatermark'
				    });
				    $(".snUsInputComment").watermark(us_cfg.watermarkComment, {
				        useNative : false,
				        className : 'snUsWatermarkComment'
				    }).TextAreaExpander(22, 100).css({
					    height : '22px'
				    });
				    $('.snUsStatusBlock .snActions').removeAttr('style');
			    }
			});
		}
	});

	// Show and hide the text "Write a comment" && Resize comment textarea
	if ($('.snUsCommentStatus').size() > 0) {
		$('.snUsCommentStatus').click(function(){
			// $(this).parents('.snUsStatusBlock').children('.snUsShareComment
			// .snUsInputComment').trigger('focusin');
			var o_commArea = $(this).parents('.snUsStatusBlock').children('.snUsShareComment').children('.snUsInputComment');
			o_commArea.focus();
			$('.snUsButtonCommentOver:visible').hide();
			$(o_commArea).next('.snUsButtonCommentOver').show();
		});
	}

	if ($('.snUsInputComment').size() > 0) {
		$(".snUsInputComment").watermark(us_cfg.watermarkComment, {
		    useNative : false,
		    className : 'snUsWatermarkComment'
		}).TextAreaExpander(22, 100).css({
			height : '22px'
		});
	}
	$(".snUsInputComment").live('focusin', function(){
		$('.snUsButtonCommentOver:visible').hide();
		$(this).next('.snUsButtonCommentOver').show();
	});

	// Close comment if clicked outside
	$(document).click(function(event){
		if ($('.snUsButtonCommentOver:visible input[name=snUsButtonComment]').size() > 0) {
			var c_obj = $('.snUsButtonCommentOver:visible input[name=snUsButtonComment]').attr('sid');
			if (c_obj != '' && !$(event.currentTarget.activeElement).closest('.snUsShareComment[name=' + c_obj + '],.snUsCommentStatus').size()) {
				$('.snUsButtonCommentOver:visible').hide();
			}
		}
	});
	// Post comment
	$(".snUsShareComment input[name=snUsButtonComment]").live('click', function(){
		var element = $(this);
		var status_id = element.attr("sid");
		var comment_text = $("#snUsTextarea" + status_id).val();
		var now = Math.floor(new Date().valueOf() / 1000);

		comment_text = comment_text.replace(/^\s+|\s+$/g, '');

		if (comment_text == '' || comment_text == us_cfg.watermarkComment) {
			snConfirmBox(us_cfg.emptyComment, us_cfg.emptyComment);
		} else {
			$.ajax({
			    type : "POST",
			    url : us_cfg.url,
			    cache : false,
			    data : {
			        smode : 'comment_share',
			        comment : comment_text,
			        s_id : status_id
			    },
			    success : function(data){
				    $(element).parents('.snUsShareComment').before(data);
				    $(element).parents('.snUsShareComment').prev('.snUsCommentBox').slideDown();
				    $('#snUsTextarea' + status_id).val('').height(22).watermark(us_cfg.watermarkComment, {
				        useNative : false,
				        className : 'snUsWatermarkComment'
				    });
			    }
			});
		}
		$('.snUsButtonCommentOver:visible').hide();
	});
	// Nacteni dalsich statusu
	if ($('.snUsGetMore').size() > 0) {
		$('.snUsGetMore').removeAttr('href').click(function(){
			var lastStatusID = $(this).parents('div.snUsMore').prev('.snUsStatusBlock').attr('id').replace(/^snUsStatus/i, '');
			var userID = $(this).attr('user');
			var t_obj = $(this);
			$('.snUsStatusLoader').show();
			$.ajax({
			    type : 'POST',
			    cache : false,
			    url : us_cfg.url,
			    data : {
			        smode : 'status_more',
			        lStatusID : lastStatusID,
			        u : userID
			    },
			    beforeSubmit : function(){
				    $('.snUsStatusLoader').show();
			    },
			    error : function(){
				    $('.snUsStatusLoader').hide();
			    },
			    success : function(data){
				    $('.snUsStatusLoader').hide();
				    $(t_obj).parents('.snUsMore').before(data.statuses);
				    if (data.moreStatuses == false) {
					    $('.snUsMore').remove();
				    }
				    $(".snUsInputComment").watermark(us_cfg.watermarkComment, {
				        useNative : false,
				        className : 'snUsWatermarkComment'
				    }).TextAreaExpander(22, 100).css({
					    height : '22px'
				    });
			    }
			});
		});
	}
	// Nacteni dalsich komentaru
	// if ($( '.snUsGetMoreComments').size() > 0) {
	$('.snUsGetMoreComments').removeAttr('href').live('click', function(){
		var o_loader = $(this).next('.snUsCommentsLoader');
		o_loader.show();

		var o_g_m_c = this;
		var b_last = $(this).hasClass('before');
		if (b_last) {
			var lastCommentID = $(this).parents('div.snUsMore').prev('.snUsCommentBox').attr('id').replace(/^snUsStatusComment/i, '');
		} else {
			var lastCommentID = $(this).parents('div.snUsMore').next('.snUsCommentBox').attr('id').replace(/^snUsStatusComment/i, '');
		}

		var StatusID = $(this).parents('.snUsStatusBlock').attr('id').replace(/^snUsStatus/i, '');
		var userID = $(this).attr('user');

		$.ajax({
		    type : 'POST',
		    cache : false,
		    url : us_cfg.url,
		    data : {
		        smode : 'comment_more',
		        lCommentID : lastCommentID,
		        s_id : StatusID,
		        u : userID
		    },
		    success : function(data){
			    if (b_last) {
				    $('#snUsStatus' + StatusID + ' .snUsMore').before(data.comments);
			    } else {
				    $('#snUsStatus' + StatusID + ' .snUsMore').after(data.comments);
			    }
			    $('#snUsStatus' + StatusID + ' .snUsCommentBox:hidden').show();
			    if (data.moreComments == false) {
				    $('#snUsStatus' + StatusID + ' .snUsMore').remove();
			    } else {
				    $(o_g_m_c).children('.snUsCommentsCount').html(data.moreComments);
			    }
			    o_loader.hide();
		    }
		});
	});
	// }


		$('input[name=snUsFetchButton]').live('click',function(){
			$('.snUsFetchBlock .loader').show();
			$('.snUsFetchBlock .snUsFetchPreview').hide();
			$('.snUsThumbs').hide();

			if ($('#snUsWallInput').size() > 0) {
				var fetchURL = $('#snUsWallInput').val();
			}
			if ($('#snMpInput').size() > 0) {
				var fetchURL = $('#snMpInput').val();
			}
			$.ajax({
			    type : 'POST',
			    url : us_cfg.urlFetch,
			    data : {
			        action : 'load',
			        url : fetchURL
			    },
			    dataType : 'json',
			    error : function(data){
				    $('input[name=snUsFetchClear]').trigger('click');
				    snConfirmBox('Error', data.responseText);
			    },
			    success : function(data){
					if ( data == null){
					    $('input[name=snUsFetchClear]').trigger('click');
					    snConfirmBox('Error', 'No data returned');
						return;
					}
			    	$('.snUsFetchImgs img').remove();
				    if (data.images.length == 0) {
					    $('.snUsFetchImages').hide();
					    $('.snUsThumbs .snUsThumbsImg').hide();
				    } else {
					    $.each(data.images, function(i,image){
						    $('<img src="' + image.img + '" width="100" id="snUsFetchPreviewImg_' + i + '" imgH="' + image.height + '" imgW="' + image.width + '" ' + (i > 0 ? 'style="display:none;max-height:150px" ' : '') + '/>').appendTo('.snUsFetchImgs');
					    });
					    $('.snUsFetchImages').show();
					    $('.snUsThumbs .snUsThumbsImg').show();
				    }
				    $('.snUsFetchImages .snUsFetchThumb .mPic').html(data.images.length);
				    $('.snUsFetchImages .snUsFetchThumb .cPic').html('1');

				    $('.snUsFetchData .title').html(data.title);
				    $('.snUsFetchData .desc').val(data.desc);
				    $('.snUsFetchData .url a').html(data.url).attr('href', data.url);

				    if (data.video.object.length != 0) {
					    $('.snUsFetchVideo').show();
					    $('.snUsThumbs .snUsThumbsVideo').show();
				    } else {
					    $('.snUsFetchVideo').hide();
					    $('.snUsThumbs .snUsThumbsVideo').hide();

				    }
				    $('.snUsFetchVideo').html(data.video.object);
				    $('.snUsFetchVideoInfo').html(data.video.info);
				    $('.snUsFetchVideoProvider').html(data.video.provider);

				    $('.snUsFetchBlock .loader').hide();
				    $('.snUsFetchPreview').show();
				    $('.snUsThumbs').show();
				    $('input[name=snUsFetchClear]').show();
			    }
			});
		});
		// $('input[name=snUsFetchButton]').trigger('click');
		$('.snUsFetchData .title').html('');
		$('input[name=snUsFetchClear]').live('click',function(){
			$('.snUsFetchBlock .loader').hide();
			$('.snUsFetchImgs').html('');
			$('.snUsFetchData .title').html('');
			$('.snUsFetchData .desc').html('');
			$('.snUsFetchData .url a').html('').attr('href', '');
			$('.snUsFetchVideo').html('');
			$('.snUsFetchVideoInfo').html('');
			$('.snUsFetchVideoProvider').html('');

			$('.snUsFetchBlock .snUsFetchPreview').hide();
			$('.snUsThumbs').hide();
			$(this).hide();
		});

		$('#snUsNoImg').change(function(){
			$('.snUsFetchImages .snUsFetchImgs').toggle();
			$('.snUsFetchImages .snUsFetchImgNav').toggle();
			$('.snUsFetchImages .snUsFetchThumb').toggle();
		});

		$('#snUsNoVideo').change(function(){
			$('.snUsFetchVideo').toggle();
			$('.snUsFetchVideoInfo').toggle();
			$('.snUsFetchVideoProvider').toggle();
		});

		$('.snUsFetchImgsNext').live('click',function(){
			change_pic(+1);
		})
		$('.snUsFetchImgsPrev').live('click',function(){
			change_pic(-1);
		})
		function change_pic(dir){
			var c_pic = parseInt($('.snUsFetchImgs img:visible').attr('id').replace(/^snUsFetchPreviewImg_/i, ''));

			if (c_pic + dir < 0) {
				var c_pic = parseInt($('.snUsFetchImgs img').length);
			} else if (c_pic + dir >= parseInt($('.snUsFetchImgs img').length)) {
				var c_pic = -1;
			}
			$('.snUsFetchImgs img:visible').hide();
			$('#snUsFetchPreviewImg_' + (c_pic + dir)).show();
			$('.snUsFetchImages .snUsFetchThumb .cPic').html((c_pic + dir + 1));
		}


	if ($.browser.msie && $.browser.version < "9.0")
		$('.snUsVideoOverlay').removeAttr('style').css({
		    opacity : '0.4',
		    background : '#000',
		    width : '150px',
		    height : '150px',

		    position : 'absolute',
		    marginLeft : '-154px',
		    cursor : 'pointer'
		});

	$('.snUsVideoOverlay').live('click', function(){
		var obj = $(this).prev('div.snUsPageVideo').children('object');
		$(this).parent('.snUsPagePreview').next('.clear').removeAttr('style').show();
		$(this).attr({
		    height : $(obj).attr('oheight'),
		    width : $(obj).attr('owidth')
		});
		$(this).parent('.snUsPageVideo').appendTo('<div class="clear">aaa</div>');

		$(obj).children('embed').attr({
		    height : $(obj).attr('oheight'),
		    width : $(obj).attr('owidth')
		});
		$(obj).attr({
		    height : $(obj).attr('oheight'),
		    width : $(obj).attr('owidth')
		});
		$(this).removeAttr('style');
	});

	// Nacteni dalsich statusu pri scroll na konec stranky
	if ($('.snUsMore').size() > 0) {
		$(window).scroll(function(){
			if ($(window).scrollTop() == $(document).height() - $(window).height()) {

				$(document).oneTime(us_cfg.loadMoreTime, 'checkScrollDown', function(){
					if ($(window).scrollTop() == $(document).height() - $(window).height()) {
						$('.snUsStatusLoader').show();
						$('.snUsGetMore').trigger('click');
					}
				});
			}
		});
	}
});
