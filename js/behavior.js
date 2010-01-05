;(function($) {
	$(function() {
		$('.cfcal-list li.cfcal-js-open').click(function(evt) {
			evt.stopPropagation();
			evt.preventDefault();
			cfcal_open_post($(this));
		}).find('.cfcal-day-edit-link').click(function(){
			window.location = $(this).attr('href');
			return false;
		});
		
		cfcal_open_post = function(_this) {
			$.closeDOMWindow();
			
			var wheight = Math.floor($(window).height()*0.8);
			
			$.post("index.php", {
				cf_action:"cfcal_item_popup",
				post_id:_this.attr("id").split('-')[1],
				cfcal_wheight:wheight-20
			}, function(r) {
				res = eval("("+r+")");
				if (res.success != false) {
					cfcal_popup(res.html, null, wheight);
				}
				else {
					cfcal_popup_error(res.html, res.message);
				}
			});
			
			return false;
		};

		cfcal_open_day = function(month, day, year) {
			var wheight = Math.floor($(window).height()*0.8);
			
			$.post("index.php", {
				cf_action:"cfcal_day_popup",
				cfcal_month:month,
				cfcal_day:day,
				cfcal_year:year,
				cfcal_wheight:wheight-20
			}, function(r) {
				res = eval("("+r+")");
				if (res.success != false) {
					cfcal_popup(res.html, null, wheight);
				}
				else {
					cfcal_popup_error(res.html, res.message);
				}
			});
			
			return false;
		};

		cfcal_plus = function(month, day, year, num_items, _this) {
			var wheight = num_items*40;

			$.post("index.php", {
				cf_action:"cfcal_plus",
				cfcal_month:month,
				cfcal_day:day,
				cfcal_year:year,
				cfcal_wheight:wheight
			}, function(r) {
				res = eval("("+r+")");
				if (res.success != false) {
					var t_html = "<div id=\"disposible-wapper\">"+res.html+"</div>";
					var w = 200;
					var h = wheight;
					var top = Math.floor(_this.offset().top);
					top += 13;
					var left = Math.floor(_this.offset().left);
					
					var opts = {
						windowSourceID:t_html,
						borderSize:0,
						windowBGColor:"transparent",
						windowPadding: 0,
						width:w,
						height:h,
						overlay:1,
						overlayOpacity:"30",
						positionLeft:left,
						positionTop:top,
						positionType:'absolute' // centered, anchored, absolute, fixed
					};
					$.openDOMWindow(opts);
					$('#DOMWindow').css('overflow','visible');

					// fix the height on browsers that don't honor the max-height css directive
					var _contentdiv = $('#DOMWindow .cfcal-popup-content');
					if (_contentdiv.height() > wheight-20) {
						_contentdiv.css({'height':(wheight-20) + 'px'});
					} 

					$(".cfcal-popup-plus-close a").click(function(){
						$.closeDOMWindow();
						return false;
					});
					return true;
				}
				else {
					cfcal_popup_error(res.html, res.message);
				}
			});
			
			return false;
		};
		
		// Show/Hide JS Functionality
		$("a[rel$='cfcal-showhide']").click(function() {
			_this = $(this);
			
			var this_class = _this.attr('class');
			var class_split = this_class.split('-');
			
			if (class_split[1] == 'hide') {
				$('.cfcal-'+_this.attr('class').replace('cfcal-hide-','')).slideUp();
				$('.'+_this.attr('class').replace('cfcal-hide-','cfcal-show-')).show();
			}
			else {
				$('.cfcal-'+this_class.replace('cfcal-show-','')).slideDown();
				$('.'+this_class.replace('cfcal-show-','cfcal-hide-')).show();
			}
			
			_this.hide();
			return false;
		});
		
		// Month Select Box Functionality
		$(".cfcal-month-navigation").change(function() {
			_this = $(this);
			if (_this.val() == '0') { return false; }
			var date_val = _this.val().split('-');
			var url_main = window.location.href.split('?')[0];
			var url_get = window.location.search.split('&');
			var page = '';
			var month = '';
			var year = '';
			var other = '';

			$.each(url_get, function(i, val) {
				if (val[0] == '?') {
					page = val;
				}
				else {
					var breakdown = val.split('=');
					if (breakdown[0] == 'month') {
						month = '&month='+date_val[1];
					}
					else if (breakdown[0] == 'year') {
						year = '&year='+date_val[0];
					}
					else {
						other += '&'+val;
					}
				}
			});
			
			// If the month and year have not been set (they were emtpy to being with), then set them now
			if (month == '') {
				month = '&month='+date_val[1];
			}
			if (year == '') {
				year = '&year='+date_val[0];
			}
			
			// Send the window to the new location
			window.location.href = url_main+page+month+year+other;
		})
	});
})(jQuery);
