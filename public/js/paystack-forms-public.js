(function( $ ) {
	'use strict';

	/**
	 * All of the code for your public-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */
	 $(document).ready(function($) {

			 $('.pf-number').keydown(function(event) {
		       if (event.keyCode == 46 || event.keyCode == 8 || event.keyCode == 9
		           || event.keyCode == 27 || event.keyCode == 13
		           || (event.keyCode == 65 && event.ctrlKey === true)
		           || (event.keyCode >= 35 && event.keyCode <= 39)){
		               return;
		       }else{
		           if (event.shiftKey || (event.keyCode < 48 || event.keyCode > 57) && (event.keyCode < 96 || event.keyCode > 105 )) {
		               event.preventDefault();
		           }
		       }
		   });
			 	function validateEmail(email) {
				  var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
				  return re.test(email);
				}
		 	$('.paystack-form').on('submit', function(e) {
				var stop = false;
				$(this).find("input, textarea").each(function() {
						$(this).css({ "border-color":"#d1d1d1" });
				});
				var email = $(this).find("#pf-email").val();
				var amount = $(this).find("#pf-amount").val();
				if (Number(amount) > 0) {
				}else{
					$(this).find("#pf-amount").css({ "border-color":"red" });
					stop = true;


				}
				if (!validateEmail(email)) {
			    $(this).find("#pf-email").css({ "border-color":"red" });
					stop = true;
				}
					$(this).find("input, select, textarea").filter("[required]").filter(function() { return this.value == ''; }).each(function() {
              $(this).css({ "border-color":"red" });
							stop = true;
					});
				if (stop) {
					return false;
				}

	 		 	var self = $(this);
				var $form = $(this);
				e.preventDefault();

				$.blockUI({ message: 'Please wait...' });

				var formdata = new FormData(this);

				$.ajax({
			     url: $form.attr('action'),
			     type: "POST",
			     data: formdata,
			     mimeTypes:"multipart/form-data",
			     contentType: false,
			     cache: false,
			     processData: false,
					 dataType:"JSON",
			     success: function(data){
							$.unblockUI();
							if (data.result == 'success'){
								var names = data.name.split(' ');
								var firstName = names[0] || "";
								var lastName = names[1] || "";
								// console.log(firstName+ " - "+lastName);
								var handler = PaystackPop.setup({
				 					key: settings.key,
				 					email: data.email,
				 					amount: data.total,
									firstname: firstName,
	 								lastname: lastName,
				 					ref: data.code,
				 					metadata: {'custom_fields': data.custom_fields},
				 					callback: function(response){
				 						$.blockUI({ message: 'Please wait...' });
				 						$.post($form.attr('action'), {'action':'paystack_confirm_payment','code':response.trxref}, function(newdata) {
				 									data = JSON.parse(newdata);
				 									if (data.result == 'success'){
				 										$('.paystack-form')[0].reset();
				 										$('html,body').animate({ scrollTop: $('.paystack-form').offset().top - 110 }, 500);

				 										self.before('<pre>'+data.message+'</pre>');
				 										$(this).find("input, select, textarea").each(function() {
				 												$(this).css({ "border-color":"#d1d1d1" });
				 										});

				 										$.unblockUI();
				 									}else{
				 										self.before('<pre>'+data.message+'</pre>');
				 										$.unblockUI();
				 									}
				 							});
				 					},
				 					onClose: function(){

				 					 }
				 				});
				 				handler.openIframe();
				 			}else{
									alert(data.message);
							}
						}

			     });
				});

		});

})( jQuery );
