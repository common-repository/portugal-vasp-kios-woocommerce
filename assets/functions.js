//Hide / show
jQuery( document ).on( 'updated_checkout', function( e, data ) {
	//console.log( 'PVKW - Hide pickup points' );
	jQuery( function( $ ) {
		$( '#pvkw' ).hide();
		$( '#pvkw_point_active' ).val('0');
		//Country - we only do this for Portugal
		if ( $( '#ship-to-different-address' ).find( 'input' ).is( ':checked' ) ) {
			var country = $( '#shipping_country' ).val();
		} else {
			var country = $( '#billing_country' ).val();
		}
		if ( country === undefined ) {
			//console.log( 'PVKW - Country fields probably removed from checkout, assume store country '+cppw.shop_country );
			country = pvkw.shop_country;
		}
		//console.log( 'PVKW - '+country );
		if ( country == 'PT' ) {
			//checkout.js : 271
			var shipping_methods = {};
			$( 'select.shipping_method, input[name^="shipping_method"][type="radio"]:checked, input[name^="shipping_method"][type="hidden"]' ).each( function() {
				shipping_methods[ $( this ).data( 'index' ) ] = $( this ).val();
			} );
			//console.log( 'PVKW - DDP activated shipping methods:' );
			//console.log( pvkw.shipping_methods );
			//console.log( 'PVKW - Chosen shipping methods:' );
			//console.log( shipping_methods );
			//Only one shipping method chosen?
			if ( Object.keys( shipping_methods ).length == 1 ) {
				//console.log( 'PVKW - 1 chosen shipping method' );
				var shipping_method = $.trim( shipping_methods[0] );
				if ( $.inArray( shipping_method, pvkw.shipping_methods ) >= 0 ) {
					//console.log( 'PVKW - Show pickup points' );
					$( '#pvkw' ).show();
					$( '#pvkw_point_active' ).val('1');
					if ( $().select2 ) {
						$( '#pvkw_point' ).select2();
					}
				}
			}
		}
	} );
	
} );
//Update chosen point
jQuery( document ).on( 'change', '#pvkw_point', function() {
	jQuery( function( $ ) {
		$( '.pvkw-points-fragment-point-details' ).addClass( 'updating' );
		var data = {
			pvkw_point: $( '#pvkw_point' ).val()
		};
		$.ajax( {
			type:		'POST',
			url:		wc_checkout_params.wc_ajax_url.toString().replace( '%%endpoint%%', 'pvkw_point_details' ),
			data:		data,
			success:	function( data ) {
				//Fragments
				if ( data && data.fragments ) {
					$.each( data.fragments, function ( key, value ) {
						$( key ).replaceWith( value );
						$( key ).unblock();
					} );
				}
			}
		} );
	} );
} );