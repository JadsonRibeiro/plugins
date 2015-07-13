jQuery(document).ready(function(){
	jQuery('#export_button').on('click', function(){
		var product = jQuery('.field_product').val();
		var status = jQuery('.field_status').val();
		// on PHP use $_POST['name_field']

		jQuery.ajax({
			method: "POST",
			url: ajaxurl,
			data: {
				action : "exp_callback", 
				mode : "export",
				field_status: status,
				field_product: product, 
			},
			dataType: "json",
			success: function(data){
				if(data.error == true){
					alert(data.message);
				} else {
					alert(data.message);
					jQuery('#button_download').append(data.rendered_button);
				}
			},
		});
	})

});