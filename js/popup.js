// Mini popup framework
;(function($){
	// popup generic function
	cfcal_popup = function(html,width,height) {
		var t_html = "<div id=\"disposible-wapper\">"+html+"</div>";
		var w = width || 500;
		var h = height || 500;
		
		var opts = {
			windowSourceID:t_html,
			borderSize:0,
			windowBGColor:"transparent",
			windowPadding: 0,
			positionType:"centered",
			width:w,
			height:h,
			overlay:1,
			overlayOpacity:"30"
		};
		$.openDOMWindow(opts);
		$('#DOMWindow').css('overflow','visible');
		
		// fix the height on browsers that don't honor the max-height css directive
		var _contentdiv = $('#DOMWindow .cfcal-popup-content');
		if (_contentdiv.height() > height-20) {
			_contentdiv.css({'height':(height-20) + 'px'});
		} 
		
		$(".cfcal-popup-close a").click(function(){
			$.closeDOMWindow();
			return false;
		});
		return true;
	};
	
	// popup generic error function
	cfcal_popup_error = function(html,message) {
		alert("TEMPORARY FAILURE MESSAGE FORMAT: "+message);
		return true;
	};
})(jQuery);