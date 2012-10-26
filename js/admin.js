jQuery(document).ready(function(){
	
	jQuery( "#clear" ).live("click", function(){
		reset = confirm("Are you sure you want to clear your settings and return to defaults?");
		if(!reset)
			return false;
	});
	
	jQuery( "#logout" ).live("click", function(){
		reset = confirm("Are you sure you want to logout of Instagram?");
		if(!reset)
			return false;
	});
	
	jQuery("input[id^='clear-']").live("change", function(){
		radionid = jQuery(this).attr("id").replace("clear-", "no-");
		if(jQuery(this).attr("checked") !== "checked")
			{
				jQuery("#"+radionid).eq(0).attr("checked", "checked");
				jQuery(this).parent().next().children("div").eq(0).children("ul").children(".active").removeClass("active");
				jQuery(this).parent().next().slideUp();
			}
		else
			{
				jQuery("#"+radionid).eq(0).attr("checked", "");
				jQuery(this).parent().next().slideDown();
			}
	});
	// Edit prompt
	jQuery(function(){
		var changed = false;
		
		jQuery('input, textarea, select, checkbox').change(function(){
			changed = true;
		});
		
		jQuery('.nav-tab-wrapper a').click(function(){
			if (changed) {
				window.onbeforeunload = function() {
				    return "The changes you made will be lost if you navigate away from this page.";
				}
			} else {
				window.onbeforeunload = '';
			}
		});
		
		jQuery('.submit input').click(function(){
			window.onbeforeunload = '';
		});
	});

});
