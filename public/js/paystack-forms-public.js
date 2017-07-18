(function( $ ) {
	'use strict';


	 $(document).ready(function($) {

	 		$(function(){
				$(".date-picker").datepicker({
					dateFormat: 'mm/dd/yy',
					prevText: '<i class="fa fa-caret-left"></i>',
					nextText: '<i class="fa fa-caret-right"></i>'
				});
			});

			 var international_card = false;
			 if( $('#pf-vamount').length ){
			 	var amountField = $('#pf-vamount');
		     	
			}else{
				var amountField = $('#pf-amount');
		     
			}
		     var max = 10;
		     amountField.keydown(function(e) {
		         format_validate(max, e);
		     });

		     function format_validate(max, e) {
		         var value = amountField.text();
		         if (e.which != 8 && value.length > max) {
		             e.preventDefault();
		         }
		         // Allow: backspace, delete, tab, escape, enter and .
		         if ($.inArray(e.keyCode, [46, 8, 9, 27, 13, 110, 190]) !== -1 ||
		             // Allow: Ctrl+A
		             (e.keyCode == 65 && e.ctrlKey === true) ||
		             // Allow: Ctrl+C
		             (e.keyCode == 67 && e.ctrlKey === true) ||
		             // Allow: Ctrl+X
		             (e.keyCode == 88 && e.ctrlKey === true) ||
		             // Allow: home, end, left, right
		             (e.keyCode >= 35 && e.keyCode <= 39)) {
		             // let it happen, don't do anything
		             calculateFees();
		             return;
		         }
		         // Ensure that it is a number and stop the keypress
		         if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
		             e.preventDefault();
		         } else {
		             calculateFees();
		         }
		     }


			$.fn.digits = function(){
			    return this.each(function(){
			        $(this).text( $(this).text().replace(/(\d)(?=(\d\d\d)+(?!\d))/g, "$1,") );
			    })
			}
		     function calculateFees(transaction_amount) {
		         setTimeout(function() {
		         	transaction_amount = transaction_amount || parseInt(amountField.val());
		         	if( $('#pf-vamount').length ){
					 	var name = $('#pf-vamount option:selected').attr('data-name');
			 			$('#pf-vname').val(name);
					}
     				 var multiplier = 0.015;
					 var fees = multiplier * transaction_amount;
					 var extrafee = 0;
					 if (fees > 2000) {
						 var fees = 2000;
					 }else{
						 if (transaction_amount > 2500) {fees += 100};
					 }
					 var total = transaction_amount + fees;
								//  console.log(transaction_amount);
		             if (transaction_amount == '' || transaction_amount == 0 || transaction_amount.length == 0 || transaction_amount == null || isNaN (transaction_amount)) {
						 var total = 0;
						 var fees = 0;
					 }
					 $(".pf-txncharge").hide().html("NGN"+fees.toFixed(2)).show().digits();
					 $(".pf-txntotal").hide().html("NGN"+total.toFixed(2)).show().digits();
		         }, 100);
		     }

		     calculateFees();

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
			$('#pf-quantity,#pf-vamount').on('change', function() {
				if( $('#pf-vamount').length ){
				 	var amountField = $('#pf-vamount');
			     	
				}else{
					var amountField = $('#pf-qamount');
			     
				}
			 	var unit = amountField.val();
			 	var quant = $('#pf-quantity').val();
			 	var newvalue = unit * quant;
			 	$('#pf-amount').val(newvalue);
			 	calculateFees(newvalue);
			});
		 	function validateEmail(email) {
			  var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
			  return re.test(email);
			}
		 	$('.paystack-form').on('submit', function(e) {
				var stop = false;

				$("#pf-agreementicon").removeClass('rerror');
					
				$(this).find("input,select, textarea").each(function() {
						$(this).removeClass('rerror');//.css({ "border-color":"#d1d1d1" });
				});
				var email = $(this).find("#pf-email").val();
				var amount = $(this).find("#pf-amount").val();
				if (Number(amount) > 0) {
				}else{
					$(this).find("#pf-amount").addClass('rerror');//  css({ "border-color":"red" });
					$('html,body').animate({ scrollTop: $('.rerror').offset().top - 110 }, 500);
					return false;
				}
				if (!validateEmail(email)) {
			    $(this).find("#pf-email").addClass('rerror');//.css({ "border-color":"red" });
					$('html,body').animate({ scrollTop: $('.rerror').offset().top - 110 }, 500);
					return false;
				}
				$(this).find("input, select, textarea").filter("[required]").filter(function() { return this.value == ''; }).each(function() {
            		$(this).addClass('rerror');
            		$('html,body').animate({ scrollTop: $('.rerror').offset().top - 110 }, 500);
					return false;
				});
				if($('#pf-agreement').length){
					if($("#pf-agreement").is(':checked')){
						stop = false;
					}else{
						$("#pf-agreementicon").addClass('rerror');
						stop = true;
					}
					if (stop) {
						$('html,body').animate({ scrollTop: $('.rerror').offset().top - 110 }, 500);
						return false;

					}
				
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
									var quantity =data.quantity;
									// console.log(firstName+ " - "+lastName);
									if (data.plan == 'none' || data.plan == ''  || data.plan == 'no' ) {
										var handler = PaystackPop.setup({
						 					key: settings.key,
						 					email: data.email,
						 					amount: data.total,
											firstname: firstName,
			 								lastname: lastName,
						 					subaccount:data.subaccount,
						 					bearer:data.txnbearer,
						 					transaction_charge:data.transaction_charge,
                            				ref: data.code,
                            				metadata: {'custom_fields': data.custom_fields},
						 					callback: function(response){
						 						$.blockUI({ message: 'Please wait...' });
						 						$.post($form.attr('action'), {'action':'kkd_pff_paystack_confirm_payment','code':response.trxref,'quantity':quantity}, function(newdata) {
						 									data = JSON.parse(newdata);
						 									if (data.result == 'success2'){
										                       window.location.href = data.link;
										                    }
						 									if (data.result == 'success'){
						 										$('.paystack-form')[0].reset();
						 										$('html,body').animate({ scrollTop: $('.paystack-form').offset().top - 110 }, 500);

						 										self.before('<pre>'+data.message+'</pre>');
						 										$(this).find("input, select, textarea").each(function() {
						 												$(this).css({ "border-color":"#d1d1d1","background-color":"#fff" });
						 										});
																$(".pf-txncharge").hide().html("NGN0").show().digits();
																$(".pf-txntotal").hide().html("NGN0").show().digits();

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

									}else{
										var handler = PaystackPop.setup({
						 					key: settings.key,
						 					email: data.email,
						 					plan: data.plan,
											firstname: firstName,
			 								lastname: lastName,
						 					ref: data.code,
						 					subaccount:data.subaccount,
						 					bearer:data.txnbearer,
                            				transaction_charge:data.transaction_charge,
                            				metadata: {'custom_fields': data.custom_fields},
						 					callback: function(response){
						 						$.blockUI({ message: 'Please wait...' });
						 						$.post($form.attr('action'), {'action':'kkd_pff_paystack_confirm_payment','code':response.trxref}, function(newdata) {
						 									data = JSON.parse(newdata);
						 									if (data.result == 'success2'){
										                       window.location.href = data.link;
										                    }
						 									if (data.result == 'success'){
						 										$('.paystack-form')[0].reset();
						 										$('html,body').animate({ scrollTop: $('.paystack-form').offset().top - 110 }, 500);

						 										self.before('<pre>'+data.message+'</pre>');
						 										$(this).find("input, select, textarea").each(function() {
						 												$(this).css({ "border-color":"#d1d1d1","background-color":"#fff" });
						 										});
																$(".pf-txncharge").hide().html("NGN0").show().digits();
																$(".pf-txntotal").hide().html("NGN0").show().digits();

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
									}


					 				handler.openIframe();
					 			}else{
										alert(data.message);
								}
							}

			    });
			     });
			
			
			$('.retry-form').on('submit', function(e) {
				
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
							var quantity =data.quantity;
							// console.log(firstName+ " - "+lastName);
							if (data.plan == 'none' || data.plan == ''  || data.plan == 'no' ) {
								var handler = PaystackPop.setup({
				 					key: settings.key,
				 					email: data.email,
				 					amount: data.total,
									firstname: firstName,
	 								lastname: lastName,
				 					ref: data.code,
				 					subaccount:data.subaccount,
				 					bearer:data.txnbearer,
                    				transaction_charge:data.transaction_charge,
                    				metadata: {'custom_fields': data.custom_fields},
				 					callback: function(response){
				 						$.blockUI({ message: 'Please wait...' });
				 						$.post($form.attr('action'), {'action':'kkd_pff_paystack_rconfirm_payment','code':response.trxref,'quantity':quantity}, function(newdata) {
				 									data = JSON.parse(newdata);
				 									if (data.result == 'success2'){
								                       window.location.href = data.link;
								                    }
				 									if (data.result == 'success'){
				 										$('.retry-form')[0].reset();
				 										$('html,body').animate({ scrollTop: $('.retry-form').offset().top - 110 }, 500);

				 										self.before('<pre>'+data.message+'</pre>');
				 										$(this).find("input, select, textarea").each(function() {
				 												$(this).css({ "border-color":"#d1d1d1","background-color":"#fff" });
				 										});
														$(".pf-txncharge").hide().html("NGN0").show().digits();
														$(".pf-txntotal").hide().html("NGN0").show().digits();
														$('#submitbtn').remove();
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

							}else{
								var handler = PaystackPop.setup({
				 					key: settings.key,
				 					email: data.email,
				 					plan: data.plan,
									firstname: firstName,
	 								lastname: lastName,
				 					ref: data.code,
				 					subaccount:data.subaccount,
				 					bearer:data.txnbearer,
                    				transaction_charge:data.transaction_charge,
                    				metadata: {'custom_fields': data.custom_fields},
				 					callback: function(response){
				 						$.blockUI({ message: 'Please wait...' });
				 						$.post($form.attr('action'), {'action':'kkd_pff_paystack_rconfirm_payment','code':response.trxref}, function(newdata) {
				 									data = JSON.parse(newdata);
				 									if (data.result == 'success2'){
								                       window.location.href = data.link;
								                    }
				 									if (data.result == 'success'){
				 										$('.retry-form')[0].reset();
				 										$('html,body').animate({ scrollTop: $('.retry-form').offset().top - 110 }, 500);

				 										self.before('<pre>'+data.message+'</pre>');
				 										$(this).find("input, select, textarea").each(function() {
				 												$(this).css({ "border-color":"#d1d1d1","background-color":"#fff" });
				 										});
														$(".pf-txncharge").hide().html("NGN0").show().digits();
														$(".pf-txntotal").hide().html("NGN0").show().digits();
														$('#submitbtn').remove();
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
							}


			 				handler.openIframe();
			 			}else{
								alert(data.message);
						}
					}

			    });
			

			});
});
})( jQuery );
