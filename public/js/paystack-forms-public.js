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
		 console.log(php_vars);
			$('.paystack-form').on('submit', function(e) {
				var self = $( this );
				var $form = $(this);

			 var loaderContainer = $( '<span/>', {
					 'class': 'loader-image-container'
			 }).insertAfter( self );
			 var adminurl = $form.attr('url');
			 var loader = $( '<img/>', {
					 src: adminurl+'/images/loading.gif',
					 'class': 'loader-image'
			 }).appendTo( loaderContainer );
				e.preventDefault();

				$.post($form.attr('action'), $form.serialize(), function(data) {
					 loaderContainer.remove();
					 if (data.result == 'success'){
				         var handler = PaystackPop.setup({
              key: 'pk_test_ed34ec15c8e95e2cacdb5460ce9862f5f48e40fe',
              email: data.email,
              amount: data.total,
              ref: data.code,
              callback: function(response){
                  // $.ajax({
                  //     method:'post',
                  //     url: '/registry/confirm_online_payment',
                  //     data: {
                  //         '_token':$('meta[name="csrf_token"]').attr('content'),
                  //         'code':response.trxref
                  //     }
                  // }).success(function(data){
                  //     if (data.result == 'success'){
                  //         $scope.cart.items = [];
                  //         window.location.href = data.link;
                  //     }
                  // });
									alert('Paid');
              },
              onClose: function(){

               }
            });
            handler.openIframe();

				}
					// alert('This is data returned from the server ' + data);

				}, 'json');
			});

		});
		// $.ajax({
    //           method:'post',
    //           url: purl,
    //           data: {
    //               '_token':$('meta[name="csrf_token"]').attr('content'),
    //               items: $scope.cart.items,
    //               'fullname':$('#fullname').val(),
    //               'registry_id':$scope.registry_id,
    //               'mobile':$('#mobile').val(),
    //               'email':$('#email').val(),
    //               'note':$('#note').val(),
    //               'method':'online',
    //               'amount':total,
    //               'pamount':producttotal,
    //           }
    //       }).success(function(data){
    //           if (data.result == 'online'){
    //             var handler = PaystackPop.setup({
    //               key: 'pk_test_ed34ec15c8e95e2cacdb5460ce9862f5f48e40fe',
    //               email: data.email,
    //               amount: data.total*100,
    //               ref: data.code,
    //               callback: function(response){
    //                   $.ajax({
    //                       method:'post',
    //                       url: '/registry/confirm_online_payment',
    //                       data: {
    //                           '_token':$('meta[name="csrf_token"]').attr('content'),
    //                           'code':response.trxref
    //                       }
    //                   }).success(function(data){
    //                       if (data.result == 'success'){
    //                           $scope.cart.items = [];
    //                           window.location.href = data.link;
    //                       }
    //                   });
    //               },
    //               onClose: function(){
		//
    //                }
    //             });
    //             handler.openIframe();
    //           }
    //           if (data.result == 'valerror'){
    //               var errors = '';
    //               for(datos in data.details){
    //                   errors += data.details[datos] + '<br>';
    //               }
    //               $('.errordetails').html(errors);
    //               $('#valerror1').show();
    //               $('html,body').animate({ scrollTop: $('#formarea').offset().top - 110 }, 500);
    //           }
    //           if (data.result == 'success'){
    //               $scope.cart.items = [];
    //               $http({
    //                   url: url+"sms/"+data.mobile+"/"+data.code,
    //                   method: "POST"
    //               });
    //               window.location.href = data.link;
    //           }
    //       });
})( jQuery );
