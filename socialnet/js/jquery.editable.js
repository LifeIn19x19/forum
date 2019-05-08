/************************************************************************************************************\
 * jQuery Plugin editable
 ************************************************************************************************************
 * REQUIRES:
 * jQuery UI datepicker
 *
 ************************************************************************************************************
 * RECOMMENDED:
 * jQuery metadata
 *
 ************************************************************************************************************
 * USAGE:
 * $.editable(selector,options)
 * $(selector).editable(options)
 * 
 ************************************************************************************************************
 * OPTIONS:
 * eventActivate (text)		- event to activate edit
 * inputClass	(text)		- class used for input boxes, default "inputbox"
 * cssInput		(object)	- additional CSS for input, such as positioning
 * cssSelect	(object)	- additional CSS for select, such as positioning
 * datePicker	(object)	- datePicker options, view at http://jqueryui.com/demos/datepicker/#options
 * endSeq		(object)	- end sequence for input alt, ctrl, shift, key code
 * ajaxOptions	(object)	- ajax options, view at http://api.jquery.com/jQuery.ajax/
 * 
 ************************************************************************************************************/
(function($){
	$.fn.setCursorPosition = function(pos) {
		if ($(this).get(0).setSelectionRange) {
			$(this).get(0).setSelectionRange(pos, pos);
		} else if ($(this).get(0).createTextRange) {
			var range = $(this).get(0).createTextRange();
			range.collapse(true);
			range.moveEnd('character', pos);
			range.moveStart('character', pos);
			range.select();
		}
	}
	
	$.editable = function(selector,opts){
		$(selector).editable(opts);
	};
	
	$.fn.editable = function(options){
		var opts = {
			eventActivate: 'none',
			eventDeactivate: 'none',
			inputClass: 'inputbox',
			cssInput:{margin: '-6px 0 -4px -4px',position:'relative'},
			cssSelect:{margin: '-6px 0 -4px -5px',paddingLeft: 0,paddingRight: 0},
			datePicker:{
				dateFormat: 'd. MM yy',
				monthNames: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
				monthNamesShort: ['Jan1', 'Feb2', 'Mar3', 'Apr4', 'May5', 'Jun6', 'Jul7', 'Aug8', 'Sep9', 'Oct10', 'Nov11', 'Dec12'],
				isRTL : socialnet_cfg.rtl, minDate: '-100Y', maxDate: '-1Y', changeMonth: true, changeYear: true,
				autoSize: true
				},
			endSeq:{alt: false, ctrl: false, shift: false, key: 13},
			ajaxOptions:{url: '',dataType: 'json'}
		};
		if(options.eventActivate!=undefined)opts.eventActivate=options.eventActivate;
		if(options.eventDeactivate!=undefined)opts.eventDeactivate=options.eventDeactivate;
		if(options.inputClass!=undefined)opts.inputClass=options.inputClass;
		if(options.cssInput!=undefined)opts.cssInput=$.extend(opts.cssInput,options.cssInput);
		if(options.cssSelect!=undefined)opts.cssSelect=$.extend(opts.cssSelect,options.cssSelect);
		if(options.datePicker!=undefined)opts.css=$.extend(opts.datePicker,options.datePicker);
		if(options.endSeq!=undefined)opts.endSeq=$.extend(opts.endSeq,options.endSeq);
		if(options.ajaxOptions!=undefined)opts.ajaxOptions=$.extend(opts.ajaxOptions,options.ajaxOptions);
		
		var data = new Array();
		var origins = new Array();
		var edit = new Array();
		var convert = new Array();
		
		function _addButton(th){
			th.append($('<span name="editable-button" class="snUpEditableButton"></span>'));
			//th.children('[name=editable-button]').click(function(){th.trigger(opts.eventActivate)});
			th.children('[name=editable-button]').click(function(){_editProccessStart(th);});
		}
		
		
		function _editEndKeyPress(e){
			if ( opts.endSeq.alt!=e.altKey || opts.endSeq.ctrl!=e.ctrlKey || opts.endSeq.shift!=e.shiftKey || opts.endSeq.key != e.keyCode){
				return;
			}
			_editProccessEnd($(this).parent('[edit-id]'), this);
			//$(this).trigger(opts.eventDeactivate);
		}
		
		function _editEnd(){
			var th = $(this).parent('[edit-id]');
			_editProccessEnd(th,this);
		}
		
		function _editProccessEnd(obj, input){
			var th = $(obj);
			var idx = th.attr('edit-id');
			var _value = $(input).val();
			
			if ( opts.eventDeactivate != false && opts.eventDeactivate != 'none'){
				th.unbind(opts.eventDeactivate);
			}

			if ( _value != edit[idx]){
				//store value
				var _response = {};
				$.ajax({
					type: 'post',
					async: false,
					cache: false,
					url: opts.ajaxOptions.url,
					dataType: opts.ajaxOptions.dataType,
					data: {
						mode:'upEdit',
						field:th.attr('edit-name'),
						value:_value,
						bbcode:data[idx].bbcode?1:0,
						date:data[idx].date?1:0,
						uid:data[idx].uid,
						bitfield:data[idx].bitfield
					},
					success:function(data){
						_response = data;
					}
				});
				if ( convert[idx]){
					if ( data[idx].bbcode==true ){
						origins[idx]=_response.origin;
						edit[idx] = _response.edit;
					}
					else{
						origins[idx] = origins[idx].replace(edit[idx],_response.origin).replace(edit[idx],_response.origin).replace(edit[idx],_response.origin);
						edit[idx] = _response.edit;
					}
					
					
				} else if ( th.attr('edit-type')=='select'){
					origins[idx] = data[idx][_response.origin];
					edit[idx] = data[idx][_response.origin];
				} else {
					origins[idx] = _response.origin;
					edit[idx] = _response.edit;
				}
			
			}
			
			th.html(origins[idx]);
			if ( opts.eventActivate != false && opts.eventActivate != 'none'){
				th.bind(opts.eventActivate, _editStart);
			}
			_addButton(th);
		}
		
		function _editStart(){
			_editProccessStart(this);
		}
		
		function _editProccessStart(obj){
			var th = $(obj)
			if ( th.attr('edit-id')==undefined){
				return;
			}
			th.unbind(opts.eventActivate);
			var idx = th.attr('edit-id');
			var _deac = true;
			var _input = $('<input type="text" name="editable-'+idx+'" value="'+edit[idx]+'" class="'+opts.inputClass+'" />').css(opts.cssInput);
			
			th.html(_input);
			var _position = origins[idx].length;
			switch( th.attr('edit-type')){
			case 'text':
				break;
			case 'date':
				_deac = false;
				
				_input.datepicker($.extend(opts.datePicker,{
					onSelect: function(){
						if ( opts.eventDeactivate != false && opts.eventDeactivate != 'none'){
							th.children('[name^="editable-"]').bind(opts.eventDeactivate,_editEnd).trigger(opts.eventDeactivate);
						}
					},
					onClose: function(){
						if ( opts.eventDeactivate != false && opts.eventDeactivate != 'none'){
							th.children('[name^="editable-"]').bind(opts.eventDeactivate,_editEnd).trigger(opts.eventDeactivate);
						}
					}
				}));
				break;
			case 'select':
				_input = $('<select name="editable-'+idx+'" class="'+opts.inputClass+'" />').css(opts.cssSelect);
				$.each(data[idx],function(o_idx,o_item){
					if ( !isNaN(parseInt(o_idx))){
						$(_input).append( new Option(o_item, o_idx));
					}
				});
				th.html(_input);
				break;
			case 'textarea':
				var _t_height = th.height();
				if (_t_height < 100){
					_t_height = 100;
				}
				_input = $('<textarea name="editable-'+idx+'" class="'+opts.inputClass+'"></textarea>').css(opts.cssSelect);
				_input.text(edit[idx]);
				th.html(_input);
				_position = 0;
				break;
			}
			_input.css({width:th.width()});
			_input.focus().setCursorPosition(_position);
			th.children('[name^="editable-"]').keypress(_editEndKeyPress);
			if ( opts.eventDeactivate != false && opts.eventDeactivate != 'none' && _deac){			
				th.children('[name^="editable-"]').bind(opts.eventDeactivate,_editEnd);
			}
		}
		
		this.map(function(idx){
			var th = $(this);
			
			if(th.attr('edit-id')==undefined){th.attr('edit-id',idx);}
			if ( $.metadata){

				var meta = $.metadata.get(this);
				if(meta.input != undefined)th.attr('edit-type',meta.input);
				if(meta.name != undefined)th.attr('edit-name',meta.name);
				if(meta.value != undefined)th.attr('edit-value',meta.value);
				if(meta.data != undefined){
					th.attr('edit-options', th.attr('class').replace(/^.*?data\s*?:\s*?([\[\{]{1}.*?[\}\]]{1})[^\}\]]*?.*?$/,'$1'));
				}
				if (meta.value!=undefined)edit[idx] = meta.value; 
				
			}
			if( th.attr('edit-type')==undefined||th.attr('edit-name')==undefined||(th.attr('edit-type')=='select'&&th.attr('edit-options')==undefined)){
				return;
			}

			origins[idx] = th.html();
			if ( edit[idx]==undefined)edit[idx]=origins[idx];
			convert[idx] = edit[idx] != origins[idx];
			
			data[idx] = {bbcode:false,date:false,uid:'',bitfield:''};
			if ( th.attr('edit-options')!= undefined){
				eval('data[idx] = \$\.extend(data[idx],'+th.attr('edit-options')+');');
			}
			_addButton(th);
			
			//th.height(th.height());
			if ( opts.eventActivate != false && opts.eventActivate != 'none'){
				th.bind(opts.eventActivate, _editStart);
			}
		});
		
	}
})(jQuery);

