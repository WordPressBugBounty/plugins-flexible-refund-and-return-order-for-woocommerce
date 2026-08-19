( function ( $ ) {
	"use strict";

	const metaBoxSelector = "#shop_order_fr_meta_box";

	const FR_Ajax = {

		switchRequests: function () {
			$( metaBoxSelector ).on( "click", ".fr-request-selector .button", function ( event ) {
				event.preventDefault();

				const $button = $( this );
				const $metaBox = $button.closest( ".fr-request-meta-box" );
				const requestId = $button.attr( "data-request-id" );
				const isExpanded = $button.attr( "aria-expanded" ) === "true";

				$metaBox.find( ".fr-request-selector .button" )
					.removeClass( "button-primary" )
					.attr( "aria-expanded", "false" );
				$metaBox.find( ".fr-request-content" ).prop( "hidden", true );

				if ( isExpanded ) {
					FR_Ajax.updateRequestUrl( $metaBox.attr( "data-collapsed-url" ) );
					return;
				}

				$button.addClass( "button-primary" ).attr( "aria-expanded", "true" );
				$metaBox
					.find( '.fr-request-content[data-request-id="' + requestId + '"]' )
					.prop( "hidden", false );
				FR_Ajax.updateRequestUrl( $button.attr( "href" ) );
			} );
		},

		updateRequestUrl: function ( url ) {
			if ( window.history && window.history.replaceState ) {
				window.history.replaceState( null, document.title, url );
			}
			if ( typeof fr_meta_box !== "undefined" ) {
				fr_meta_box.redirect_url = url;
			}
		},

		sendRequest: function () {
			$( metaBoxSelector ).on( "click", ".fr-refund-button", function () {
				const $request = $( this ).closest( ".fr-request-content" );
				const $scope = $request.length ? $request : $( metaBoxSelector );
				const $spinner = $scope.find( ".fr-refund-order-meta-box-actions .spinner" );
				const $note = $scope.find( ".fr-refund-request-note, #fr_refund_request_note" ).first();
				const $status = $scope.find( ".fr-refund-request-status, #fr_refund_request_status" ).first();
				const status = String( $status.val() || "" );
				const orderId = $( "#fr_refund_order_id" ).val() || woocommerce_admin_meta_boxes[ "post_id" ];

				if ( status.length < 5 ) {
					alert( "Select status!" );
					return false;
				}

				$spinner.css( "visibility", "visible" );
				$.ajax( {
					type: "POST",
					url: ajaxurl + "?action=fr_refund_request",
					data: {
						note: $note.val(),
						status: status,
						order_ID: orderId,
						request_id: $scope.find( ".fr-refund-request-id, #fr_refund_request_id" ).first().val() || 0,
						nonce: fr_meta_box.nonce,
						form: $scope.find( ".qty-input" ).serialize()
					},
					success: function ( response ) {
						if ( response.success === true ) {
							$spinner.css( "visibility", "hidden" );
							$note.val( "" );
							$status.val( "" );
							location.replace( fr_meta_box.redirect_url );
						} else {
							alert( response.data.error_details );
							$spinner.css( "visibility", "hidden" );
						}
					},
					async: true
				} );

				return false;
			} );
		},

		formatMoney: function ( number, decPlaces, decSep, thouSep ) {
			decPlaces = isNaN( decPlaces = Math.abs( decPlaces ) ) ? 2 : decPlaces;
			decSep = typeof decSep === "undefined" ? "." : decSep;
			thouSep = typeof thouSep === "undefined" ? "," : thouSep;
			const sign = number < 0 ? "-" : "";
			const integer = String( parseInt( number = Math.abs( Number( number ) || 0 ).toFixed( decPlaces ) ) );
			const separatorPosition = integer.length > 3 ? integer.length % 3 : 0;

			return sign +
				( separatorPosition ? integer.substr( 0, separatorPosition ) + thouSep : "" ) +
				integer.substr( separatorPosition ).replace( /(\decSep{3})(?=\decSep)/g, "$1" + thouSep ) +
				( decPlaces ? decSep + Math.abs( number - integer ).toFixed( decPlaces ).slice( 2 ) : "" );
		},

		number_format: function ( value ) {
			return this.formatMoney( value.toString(), fr_meta_box.price_decimals, fr_meta_box.decimal_point, fr_meta_box.thousand_point );
		},

		roundMoney: function ( value ) {
			const precision = 10 ** fr_meta_box.price_decimals;

			return Math.round( ( value + Number.EPSILON ) * precision ) / precision;
		},

		calculateRefundTotals: function () {
			$( metaBoxSelector ).on( "change", ".fr-refund-table .item-qty input", function () {
				const $input = $( this );
				const $table = $input.closest( ".fr-refund-table" );
				const $request = $input.closest( ".fr-request-content" );
				const $scope = $request.length ? $request : $( metaBoxSelector );
				let qty = $input.val();
				let value = parseFloat( $input.attr( "data-item-price" ) ) * qty;

				if ( $input.attr( "type" ) === "checkbox" ) {
					value = $input.prop( "checked" ) ? parseFloat( $input.attr( "data-item-price" ) ) : 0;
				}

				value = FR_Ajax.roundMoney( value );
				$input.closest( "tr" ).find( ".item-total-refund-qty" ).html( FR_Ajax.number_format( value ) );
				$input.closest( "tr" ).find( ".item-refund-total" ).html( FR_Ajax.number_format( value ) );

				let totalAmount = 0;
				let totalQty = 0;
				$table.find( ".item-qty input" ).each( function () {
					let itemQty = $( this ).val();
					let totalValue = parseFloat( $( this ).attr( "data-item-price" ) ) * itemQty;

					if ( $( this ).attr( "type" ) === "checkbox" ) {
						if ( $( this ).prop( "checked" ) ) {
							totalValue = parseFloat( $( this ).attr( "data-item-price" ) );
							itemQty = 1;
						} else {
							totalValue = 0;
							itemQty = 0;
						}
					}

					totalAmount += FR_Ajax.roundMoney( parseFloat( totalValue ) );
					totalQty += parseInt( itemQty );
				} );

				const roundedTotalAmount = FR_Ajax.roundMoney( totalAmount );
				$scope.find( ".refund-total-calc" ).html( FR_Ajax.number_format( roundedTotalAmount ) );
				$scope.find( ".refund-total-qty" ).html( totalQty );
				$scope.find( "#refund-total-qty-input" ).val( totalQty );
			} );

			$( metaBoxSelector ).find( ".fr-refund-table .item-qty input" ).trigger( "change" );
		}
	};

	$( document ).ready( function () {
		$( metaBoxSelector ).prependTo( "#normal-sortables" );
		FR_Ajax.switchRequests();
		FR_Ajax.sendRequest();
		FR_Ajax.calculateRefundTotals();
	} );

} )( jQuery );
