jQuery(document).ready(function($){
/*
	$.each($.browser, function( i, val){
		$("<div>" + i + " : <span>" + val + "</span>")
        .appendTo( document.body );
	});		
*/
	if ( !$.browser.mobile) {
		return;
	}
	
	$('#socialnet_im').toggleClass('snImDockWrapper snImDockWrapper-mobile');
	
	$('.snImMessage').css({border:'1px solid #ccc'});
	$(document).scroll(function(){
		scrollIm();
	});
	$(window).resize(function(){
		scrollIm();
	})
	scrollIm();
	
});


function scrollIm(){jQuery(function($){
	var o_sIm = $('#socialnet_im');
	var imH = parseInt(o_sIm.height());
	var wH = parseInt(window.innerHeight);
	var sT = $(document).scrollTop();
	
	o_sIm.css('top', (wH + sT - imH)+'px');
	

})}