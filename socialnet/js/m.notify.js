
jQuery(document).ready( function($){
	$.extend( $.jGrowl.defaults, ntf_cfg.settings);
	
	
	//$.jGrowl('Testing notification will start in few moments');

	if ( !socialnet_cfg.allow_load){return;}
	sn_ntf_check(0);
	$(document).everyTime( ntf_cfg.check_time, ntf_cfg.timer_name, function(i){sn_ntf_check(i);});
});

function sn_ntf_check(i){
	jQuery(function($){
	if ( i>100){
		$(document).stopTime(ntf_cfg.timer_name);
	}
	$.ajax({
		type: 'POST',
		url: ntf_cfg.url,
		dataType: 'json',
		success: function( data){
			if ( $('#snNtfCube') != null){
				sn_ntf_cubes('#snNtfCube','#snNtfCube', data.cnt);
			}
			if ( $('#snNtfNotify') != null){
				sn_ntf_cubes('#snNtfNotify', '#snNtfNotify a', data.cnt);
			}
			
			$.each( data.message, function(i, ntf){
				$.jGrowl(ntf);
			});
		}
	});
	
	});
}

function sn_ntf_cubes( s_obj, s_obj2, s_count){
	jQuery(function($){
		if ( s_count == 0){
			$(s_obj).hide();
		} else {
			$(s_obj).show();
			$(s_obj2).html(s_count+'');
		}
	});
}