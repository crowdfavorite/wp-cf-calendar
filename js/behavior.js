;(function($) {
	$(function() {
		$('.cfcal-list li').click(function() {
			var _this = $(this);
			var wheight = Math.floor($(window).height()*0.8);
			
			$.post("index.php", {
				cf_action:"cfcal_post_popup",
				post_id:_this.attr("id").replace("cf-posts-",""),
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
		});

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

		// Show/Hide JS Functionality
		$("a[rel$='cfcal-showhide']").click(function() {
			_this = $(this);
			
			var this_class = _this.attr('class');
			var class_split = this_class.split('-');
			
			if (class_split[1] == 'hide') {
				$('.cfcal-'+_this.attr('class').replace('cfcal-hide-','')).hide();
				$('.'+_this.attr('class').replace('cfcal-hide-','cfcal-show-')).show();
			}
			else {
				$('.cfcal-'+this_class.replace('cfcal-show-','')).show();
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
			
			// Send the window to the new location
			window.location.href = url_main+page+month+year+other;
		})
	});
})(jQuery);
