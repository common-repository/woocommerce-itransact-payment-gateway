jQuery( document ).ready( function() {
	showHideOptions(jQuery( "#woocommerce_wc_itransact_payment_method" ).val());
});

jQuery(document).on('change','#woocommerce_wc_itransact_payment_method',function() {
	showHideOptions(jQuery( this ).val());
});

function showHideOptions(opt)
{
	if(opt=="api")
	{
		jQuery(".api_options").parents('tr').show();
		jQuery(".redirect_options").parents('tr').hide();
	}	
	else
	{
		jQuery(".redirect_options").parents('tr').show();
		jQuery(".api_options").parents('tr').hide();
	}
}