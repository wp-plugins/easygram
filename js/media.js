jQuery(document).ready(function(){
	jQuery("#easygram-form").live("submit", function(){
		jQuery(".loading").fadeIn();
		jQuery(".ml-submit em").text("Attaching your images to this post, please be patient.");
	});
	// Edit prompt
	jQuery(function(){
		var changed = false;
		
		jQuery('input, textarea, select, checkbox').change(function(){
			changed = true;
		});
		
		jQuery('.tablenav-pages a').click(function(){
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
