jQuery(document).ready(function($) {

    //var checkout_form = jQuery('form.checkout');

    
    jQuery('#wc-woonmi-cc-form').css('display', 'none');
    
	var checkout_form = jQuery("form.checkout, form#order_review, form#add_payment_method");

    function submit_error( error_message ) {
        $( '.woocommerce-NoticeGroup-checkout, .woocommerce-error, .woocommerce-message' ).remove();
        checkout_form.prepend( '<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">' + error_message + '</div>' ); // eslint-disable-line max-len
        checkout_form.removeClass( 'processing' ).unblock();
        checkout_form.find( '.input-text, select, input:checkbox' ).trigger( 'validate' ).trigger( 'blur' );
        scroll_to_notices();
        $( document.body ).trigger( 'checkout_error' , [ error_message ] );
        

    }

    function scroll_to_notices() {
        var scrollElement           = $( '.woocommerce-NoticeGroup-updateOrderReview, .woocommerce-NoticeGroup-checkout' );

        if ( ! scrollElement.length ) {
            scrollElement = $( 'form.checkout' );
        }
        $.scroll_to_notices( scrollElement );
    }


    function trigger_nmi_checkout() {

        console.log('Doing Checkout');

        if( jQuery('#nmi-ccnumber').val() == '' || jQuery('#nmi-ccexp').val() == '' || jQuery('#nmi-cvv').val() == '' ){

            console.log('Empty Fields');
            jQuery.unblockUI();
        }

        if( nmi_info.inline_card ){


            CollectJS.configure({
                "variant" : "inline",
                "styleSniffer" : "true",
                //"paymentSelector" : "#place_order",
                "fields": {
                    "ccnumber": {
                        "selector": "#nmi-ccnumber",
                        "title": "Card Number",
                        "placeholder": "0000 0000 0000 0000",
                    },
                    "ccexp": {
                        "selector": "#nmi-ccexp",
                        "title": "Card Expiration",
                        "placeholder": "00 / 00"
                    },
                    "cvv": {
                        "display": "required",
                        "selector": "#nmi-cvv",
                        "title": "CVV Code",
                        "placeholder": "***"
                    },
                },
                //"variant": "inline",
                'validationCallback' : function(field, status, message) {
                    if (status) {
                        var message = field + " is OK: " + message;
                        //nmi_error[field] = '';
                    } else {
                        if( field == 'ccnumber' ){
                            field = 'Card Number'
                        }
                        if( field == 'ccexp' ){
                            field = 'Expiration'
                        }
                        if( field == 'cvv' ){
                            field = 'CVV Code'
                        }
                        var message = field + " " + message;
                        submit_error( '<div class="woocommerce-error" role="alert">Message: '+message +'</div>' );
                        jQuery.unblockUI();
                        CollectJS.closePaymentRequest();
                        jQuery('#wc-woonmi-cc-form').css('display', 'block');
                    }
                    console.log(message);
                },
                'callback' : function(response) {

                    if( !nmi_info.inline_card ){
    
                        jQuery.blockUI(
                            { 
                            message: 'Please wait while your order is being processed.',
                                css: {
                                padding:        0, 
                                margin:         0, 
                                //width:          '360px',
                                //height:          '400px',
                                textAlign:      'center', 
                                color:          '#000', 
                                border:         '3px solid #aaa', 
                                backgroundColor:'#fff', 
                                cursor:         'wait',
                                top:             '20%',
                            },
                            }
                        );
    
                    }

                    jQuery('#wc-woonmi-cc-form').css('display', 'none');

                    if( nmi_info.public_key != '' ){

                        const options = {
                            paymentToken: response.token,
                            currency: nmi_info.currency_code,
                            amount: parseFloat(jQuery('.order-total bdi').text().substring(1)),
                            email: jQuery('#billing_email').val(),
                            phone: jQuery('#billing_phone').val(),
                            city: jQuery('#billing_city').val(),
                            //state: jQuery('#billing_state').val(),
                            address1: jQuery('#billing_address_1').val(),
                            country: jQuery('#billing_country').val(),
                            firstName: jQuery('#billing_first_name').val(),
                            lastName: jQuery('#billing_last_name').val(),
                            postalCode: jQuery('#billing_postcode').val()
                        };

                        const gateway = Gateway.create(nmi_info.public_key);
                        const threeDS = gateway.get3DSecure();


                        const threeDSecureInterface = threeDS.createUI(options);


                        if( nmi_info.inline_3ds ){
                            
                            threeDSecureInterface.start('#nmi-3ds');
                            threeDSecureInterface.on('challenge', function(e) {
                                console.log('Challenged inline');
                                jQuery('#wc-woonmi-cc-form').css('display', 'none');
                                jQuery.unblockUI();
                            });

                        }else{
                    
                            jQuery.blockUI(
                                { 
                                message: '<div id="nmi-threeDSMountPoint">Waiting for 3DS Challenge.</div>',
                                    css: {
                                    padding:        0, 
                                    margin:         0, 
                                    //width:          '360px',
                                    //height:          '400px',
                                    textAlign:      'center', 
                                    color:          '#000', 
                                    border:         '3px solid #aaa', 
                                    backgroundColor:'#fff', 
                                    cursor:         'wait',
                                    top:             '20%',
                                },
                                }
                            );

                            threeDSecureInterface.start('#nmi-threeDSMountPoint');
                            threeDSecureInterface.on('challenge', function(e) {
                                console.log('Challenged');
                            });


                        }


                        threeDSecureInterface.on('_debug', function(e) {
                            console.log('debug');
                            jQuery('#wc-woonmi-cc-form').css('display', 'block');

                            jQuery('#place_order'). attr('disabled', false);

                            submit_error( '<div class="woocommerce-error" role="alert">Message: '+e.message +'</div>' );

                            threeDSecureInterface.unmount();
                            jQuery('.woonmi_payment_token').remove();
                            jQuery('.woonmi_payment_3ds').remove();
                            jQuery.unblockUI();
                            jQuery('#wc-woonmi-cc-form').css('display', 'block');


                        });


                        threeDSecureInterface.on('complete', function(e) {
                            
                            console.log('3DS Complete');

                            checkout_form.append("<input type='text' class='woonmi_payment_token' name='woonmi_payment_token' value='" + JSON.stringify(response) + "'/>");
                            checkout_form.append("<input type='text' class='woonmi_payment_3ds' name='woonmi_payment_3ds' value='" + JSON.stringify(e) + "'/>");
                            
                            threeDSecureInterface.unmount();
                            checkout_form.submit();
                            jQuery('.woonmi_payment_token').remove();
                            jQuery('.woonmi_payment_3ds').remove();
                            jQuery.unblockUI();
                            jQuery('#wc-woonmi-cc-form').css('display', 'block');

                        });

                        threeDSecureInterface.on('failure', function(e) {
                            console.log('failure');

                            jQuery('#wc-woonmi-cc-form').css('display', 'block');

                            jQuery('#place_order'). attr('disabled', false);

                            submit_error( '<div class="woocommerce-error" role="alert">Message: '+e.message +'</div>' );

                            threeDSecureInterface.unmount();
                            jQuery('.woonmi_payment_token').remove();
                            jQuery('.woonmi_payment_3ds').remove();
                            jQuery.unblockUI();
                            jQuery('#wc-woonmi-cc-form').css('display', 'block');
                        });




                        gateway.on('error', function (e) {
                            console.log('Error');
                            threeDSecureInterface.unmount();
                            jQuery('.woonmi_payment_token').remove();
                            jQuery('.woonmi_payment_3ds').remove();
                            jQuery.unblockUI();
                            jQuery('#wc-woonmi-cc-form').css('display', 'block');
                        });



                    }else{



                        ////////////////////////////////////////////////////////////////////////////////////

                            
                        console.log('Normal Complete');
                
                        console.log(response);

                        checkout_form.append("<input type='text' class='woonmi_payment_token' name='woonmi_payment_token' value='" + JSON.stringify(response) + "'/>");
                        checkout_form.submit();
                        jQuery('.woonmi_payment_token').remove();
                        jQuery('.woonmi_payment_3ds').remove();
                        jQuery.unblockUI();
                        jQuery('#wc-woonmi-cc-form').css('display', 'block');




                        ////////////////////////////////////////////////////////////////////////////////////

                    }
                }
            });


        }else{


            CollectJS.configure({
                //"variant" : "inline",
                "styleSniffer" : "true",
                //"paymentSelector" : "#place_order",
                "fields": {
                    "ccnumber": {
                        "selector": "#nmi-ccnumber",
                        "title": "Card Number",
                        "placeholder": "0000 0000 0000 0000",
                    },
                    "ccexp": {
                        "selector": "#nmi-ccexp",
                        "title": "Card Expiration",
                        "placeholder": "00 / 00"
                    },
                    "cvv": {
                        "display": "required",
                        "selector": "#nmi-cvv",
                        "title": "CVV Code",
                        "placeholder": "***"
                    },
                    "customCss" : {
                        
                        'box-sizing': 'border-box',
                        //'width': '100%',
                        'margin': '0',
                        'outline': '0',
                        'line-height': 'normal',

                        'font-size': '1.5em',
                        'padding': '8px',
                        'background-repeat': 'no-repeat',



                    },
                },
                //"variant": "inline",
                'validationCallback' : function(field, status, message) {
                    if (status) {
                        var message = field + " is OK: " + message;
                        //nmi_error[field] = '';
                    } else {
                        if( field == 'ccnumber' ){
                            field = 'Card Number'
                        }
                        if( field == 'ccexp' ){
                            field = 'Expiration'
                        }
                        if( field == 'cvv' ){
                            field = 'CVV Code'
                        }
                        var message = field + " " + message;
                        submit_error( '<div class="woocommerce-error" role="alert">Message: '+message +'</div>' );
                        jQuery.unblockUI();
                        CollectJS.closePaymentRequest();
                        jQuery('#wc-woonmi-cc-form').css('display', 'block');
                    }
                    console.log(message);
                },
                'callback' : function(response) {

                    if( !nmi_info.inline_card ){
    
                        jQuery.blockUI(
                            { 
                            message: 'Please wait while your order is being processed.',
                                css: {
                                padding:        0, 
                                margin:         0, 
                                //width:          '360px',
                                //height:          '400px',
                                textAlign:      'center', 
                                color:          '#000', 
                                border:         '3px solid #aaa', 
                                backgroundColor:'#fff', 
                                cursor:         'wait',
                                top:             '20%',
                            },
                            }
                        );
    
                    }

                    jQuery('#wc-woonmi-cc-form').css('display', 'none');

                    if( nmi_info.public_key != '' ){

                        const options = {
                            paymentToken: response.token,
                            currency: nmi_info.currency_code,
                            amount: parseFloat(jQuery('.order-total bdi').text().substring(1)),
                            email: jQuery('#billing_email').val(),
                            phone: jQuery('#billing_phone').val(),
                            city: jQuery('#billing_city').val(),
                            //state: jQuery('#billing_state').val(),
                            address1: jQuery('#billing_address_1').val(),
                            country: jQuery('#billing_country').val(),
                            firstName: jQuery('#billing_first_name').val(),
                            lastName: jQuery('#billing_last_name').val(),
                            postalCode: jQuery('#billing_postcode').val()
                        };

                        const gateway = Gateway.create(nmi_info.public_key);
                        const threeDS = gateway.get3DSecure();


                        const threeDSecureInterface = threeDS.createUI(options);


                        if( nmi_info.inline_3ds ){
                            
                            threeDSecureInterface.start('#nmi-3ds');
                            threeDSecureInterface.on('challenge', function(e) {
                                console.log('Challenged inline');
                                jQuery('#wc-woonmi-cc-form').css('display', 'none');
                                jQuery.unblockUI();
                            });

                        }else{
                    
                            jQuery.blockUI(
                                { 
                                message: '<div id="nmi-threeDSMountPoint">Waiting for 3DS Challenge.</div>',
                                    css: {
                                    padding:        0, 
                                    margin:         0, 
                                    //width:          '360px',
                                    //height:          '400px',
                                    textAlign:      'center', 
                                    color:          '#000', 
                                    border:         '3px solid #aaa', 
                                    backgroundColor:'#fff', 
                                    cursor:         'wait',
                                    top:             '20%',
                                },
                                }
                            );

                            threeDSecureInterface.start('#nmi-threeDSMountPoint');
                            threeDSecureInterface.on('challenge', function(e) {
                                console.log('Challenged');
                            });


                        }


                        threeDSecureInterface.on('_debug', function(e) {
                            console.log('debug');
                            jQuery('#wc-woonmi-cc-form').css('display', 'block');

                            jQuery('#place_order'). attr('disabled', false);

                            submit_error( '<div class="woocommerce-error" role="alert">Message: '+e.message +'</div>' );

                            threeDSecureInterface.unmount();
                            jQuery('.woonmi_payment_token').remove();
                            jQuery('.woonmi_payment_3ds').remove();
                            jQuery.unblockUI();
                            jQuery('#wc-woonmi-cc-form').css('display', 'block');


                        });


                        threeDSecureInterface.on('complete', function(e) {
                            
                            console.log('3DS Complete');

                            checkout_form.append("<input type='text' class='woonmi_payment_token' name='woonmi_payment_token' value='" + JSON.stringify(response) + "'/>");
                            checkout_form.append("<input type='text' class='woonmi_payment_3ds' name='woonmi_payment_3ds' value='" + JSON.stringify(e) + "'/>");
                            
                            threeDSecureInterface.unmount();
                            checkout_form.submit();
                            jQuery('.woonmi_payment_token').remove();
                            jQuery('.woonmi_payment_3ds').remove();
                            jQuery.unblockUI();
                            jQuery('#wc-woonmi-cc-form').css('display', 'block');

                        });

                        threeDSecureInterface.on('failure', function(e) {
                            console.log('failure');

                            jQuery('#wc-woonmi-cc-form').css('display', 'block');

                            jQuery('#place_order'). attr('disabled', false);

                            submit_error( '<div class="woocommerce-error" role="alert">Message: '+e.message +'</div>' );

                            threeDSecureInterface.unmount();
                            jQuery('.woonmi_payment_token').remove();
                            jQuery('.woonmi_payment_3ds').remove();
                            jQuery.unblockUI();
                            jQuery('#wc-woonmi-cc-form').css('display', 'block');
                        });




                        gateway.on('error', function (e) {
                            console.log('Error');
                            threeDSecureInterface.unmount();
                            jQuery('.woonmi_payment_token').remove();
                            jQuery('.woonmi_payment_3ds').remove();
                            jQuery.unblockUI();
                            jQuery('#wc-woonmi-cc-form').css('display', 'block');
                        });



                    }else{



                        ////////////////////////////////////////////////////////////////////////////////////

                            
                        console.log('Normal Complete');
                
                        console.log(response);

                        checkout_form.append("<input type='text' class='woonmi_payment_token' name='woonmi_payment_token' value='" + JSON.stringify(response) + "'/>");
                        checkout_form.submit();
                        jQuery('.woonmi_payment_token').remove();
                        jQuery('.woonmi_payment_3ds').remove();
                        jQuery.unblockUI();
                        jQuery('#wc-woonmi-cc-form').css('display', 'block');




                        ////////////////////////////////////////////////////////////////////////////////////

                    }
                }
            });



        }
            
    }


    jQuery( document.body ).on( 'updated_checkout', function() {
        console.log('updated_checkout');
        trigger_nmi_checkout(nmi_info);
        jQuery('#wc-woonmi-cc-form').css('display', 'block');

    } );

    jQuery( document.body ).on( 'payment_method_selected', function() {
        console.log('payment_method_selected');
        trigger_nmi_checkout(nmi_info);
        jQuery('#wc-woonmi-cc-form').css('display', 'block');
    } );

    jQuery( 'form.woocommerce-checkout' ).on( 'click change', 'input[name="wc-woonmi-payment-token"]', function() {
        if ( jQuery( '#payment_method_woonmi' ).is( ':checked' ) && ( ! jQuery( 'input[name="wc-woonmi-payment-token"]:checked' ).length || 'new' === jQuery( 'input[name="wc-woonmi-payment-token"]:checked' ).val() ) && ! jQuery( '#nmi-card-number-element' ).children().length ) {
            console.log('wc-woonmi-payment-token-new');
            trigger_nmi_checkout(nmi_info);
            jQuery('#wc-woonmi-cc-form').css('display', 'block');
        }
    } );



	jQuery('form.checkout').on('checkout_place_order_woonmi', function( e ) { 
        console.log('checkout_place_order_woonmi');
		return woonmi_process_card();
	});	

	jQuery('form#order_review').submit(function(){
        console.log('order_review');
		return woonmi_process_card();
	});

	jQuery("form.checkout, form#order_review").on('change', '.woonmi-creditcard, .woonmi-cvv, .woonmi-expdatemonth, .woonmi-expdateyear, .woonmi-account, .woonmi-bankcode, .woonmi-accname', function( e ) {
		jQuery('.woocommerce_error, .woocommerce_message, .woonmi_payment_token, .woonmi_payment_3ds').remove();
	});


	function woonmi_process_card() {


		if ( jQuery('#payment_method_woonmi').is(':checked') &&  jQuery( 'input.woonmi_payment_token' ).length == 0 &&  jQuery( 'input.woonmi_payment_3ds' ).length == 0 ) {

            console.log('submit');

            if( !isNaN(jQuery("input[name='wc-woonmi-payment-token']:checked").val()) ){

                console.log('Vault Card Checkout');
                                    
                return true;

            }else{

                console.log('New Card Checkout');

                if( nmi_info.inline_card ){

                    jQuery.blockUI(
                        { 
                        message: 'Please wait while your order is being processed.',
                            css: {
                            padding:        0, 
                            margin:         0, 
                            //width:          '360px',
                            //height:          '400px',
                            textAlign:      'center', 
                            color:          '#000', 
                            border:         '3px solid #aaa', 
                            backgroundColor:'#fff', 
                            cursor:         'wait',
                            top:             '20%',
                        },
                        }
                    );

                }
                    
        
                CollectJS.startPaymentRequest();
                return false;
            }
                    
		}
		return true;
	};

});

