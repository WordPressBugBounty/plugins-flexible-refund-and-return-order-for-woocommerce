( function ( $ ) {
	"use strict";

	const FormBuilder = {

		refund_form_qty_fields: $( '#fr_refund_table' ),

		init: function () {
			$( '#fb-field-type' ).val( '' );
			$( '#fb-field-name' ).val( '' );
			$( '#fb-field-label' ).val( '' );
		},

		formatMoney: function ( number, decPlaces, decSep, thouSep ) {
			decPlaces = isNaN( decPlaces = Math.abs( decPlaces ) ) ? 2 : decPlaces,
				decSep = typeof decSep === "undefined" ? "." : decSep;
			thouSep = typeof thouSep === "undefined" ? "," : thouSep;
			var sign = number < 0 ? "-" : "";
			var i = String( parseInt( number = Math.abs( Number( number ) || 0 ).toFixed( decPlaces ) ) );
			var j = ( j = i.length ) > 3 ? j % 3 : 0;

			return sign +
				( j ? i.substr( 0, j ) + thouSep : "" ) +
				i.substr( j ).replace( /(\decSep{3})(?=\decSep)/g, "$1" + thouSep ) +
				( decPlaces ? decSep + Math.abs( number - i ).toFixed( decPlaces ).slice( 2 ) : "" );
		},

		number_format: function ( value ) {
			return this.formatMoney( value.toString(), fr_front_i18n.price_decimals, fr_front_i18n.decimal_point, fr_front_i18n.thousand_point );
		},

		roundMoney: function ( value ) {
			const precision = 10 ** fr_front_i18n.price_decimals;

			return Math.round( ( value + Number.EPSILON ) * precision ) / precision;
		},

		calculateRefundTotals: function () {
			let _this = this;
			_this.refund_form_qty_fields.on( 'change', '.item-qty input', function () {
				let qty = $( this ).val();
				let qty_max = parseInt( $( this ).attr( 'max' ) );
				let value = parseFloat( $( this ).attr( 'data-item-price' ) ) * qty;
				if ( $( this ).attr( 'type' ) === 'checkbox' ) {
					if ( $( this ).prop( 'checked' ) ) {
						value = parseFloat( $( this ).attr( 'data-item-price' ) );
					} else {
						value = 0;
					}
				}

				value = _this.roundMoney( value );
				$( this ).closest( 'tr' ).find( '.item-total-refund-qty' ).html( _this.number_format( value ) );

				let total_amount = 0;
				let total_qty = 0;
				let total_amount_wrapper = $( '.refund-total-calc' );
				let total_qty_wrapper = $( '.refund-total-qty' );
				let total_qty_input = $( '#refund-total-qty-input' );
				$( this ).closest( 'table' ).find( '.item-qty input' ).each( function () {
					let qty = $( this ).val();
					let qty_max = parseInt( $( this ).attr( 'max' ) );
					let total_value = parseFloat( $( this ).attr( 'data-item-price' ) ) * qty;
					if ( $( this ).attr( 'type' ) === 'checkbox' ) {
						if ( $( this ).prop( 'checked' ) ) {
							total_value = parseFloat( $( this ).attr( 'data-item-price' ) );
							qty = 1;
						} else {
							total_value = 0;
							qty = 0;
						}
					}


					total_amount += _this.roundMoney( parseFloat( total_value ) );
					total_qty += parseInt( qty );
				} )

				total_amount_wrapper.html( _this.number_format( total_amount ) );
				total_qty_wrapper.html( total_qty );
				total_qty_input.val( total_qty );
			} );

			$( '.woocommerce-table-refund-details .item-qty input' ).trigger( 'change' );
		},

		validateUploadInput: function () {
			$( '.refund-front-form' ).find( 'input[type="file"]' ).each( function ( i, v ) {
				let field = $( v );
				const limit = field.attr( 'data-files-limit' );

				$( this ).on( 'change', () => {
					const fileLimit = parseInt( limit );
					const fileCount = this.files ? this.files.length : 0;
					let notice_wrapper = field.closest( '.field-row' ).find( '.fr-required-field-notice' );

					if ( fileCount > fileLimit ) {
						notice_wrapper.html( `<span class="label-error-required">${fr_front_i18n.files_limit} (limit: ${fileLimit})</span>` );
						this.value = '';
					} else {
						notice_wrapper.html( '' );
					}
				} )
			} );
		},

		hasRequiredFieldValue: function ( field_row ) {
			let has_value = false;
			let fields = field_row.find( 'input[name]:not([type="hidden"]), textarea[name], select[name]' );

			fields.each( function () {
				let field = $( this );
				let type = field.attr( 'type' );
				let value = field.val();

				if ( type === 'radio' || type === 'checkbox' ) {
					has_value = has_value || field.prop( 'checked' );
				} else if ( type === 'file' ) {
					has_value = has_value || Boolean( this.files && this.files.length );
				} else if ( Array.isArray( value ) ) {
					has_value = has_value || value.length > 0;
				} else {
					has_value = has_value || String( value || '' ).trim() !== '';
				}
			} );

			return has_value;
		},

		validateRefundForm: function () {
			let _this = this;
			let required_fields = 'input[name]:not([type="hidden"]), textarea[name], select[name]';

			$( '.refund-front-form' ).on( 'input change', required_fields, function () {
				let field_row = $( this ).closest( '.field-required' );
				if ( field_row.length && _this.hasRequiredFieldValue( field_row ) ) {
					field_row.find( '.fr-required-field-notice' ).html( '' );
				}
			} );

			$( '.refund-front-form' ).submit( function () {
				let is_valid = true;
				let qty_total = 0;
				$( this ).find( '.qty-input' ).each( function ( i, v ) {
					let qty_value = parseInt( $( this ).val() );
					if ( $( this ).attr( 'type' ) === 'checkbox' ) {
						if ( $( this ).prop( 'checked' ) ) {
							qty_value = 1;
						} else {
							qty_value = 0;
						}
					}

					qty_total += parseInt( qty_value );
				} );

				if ( qty_total === 0 ) {
					is_valid = false;
				}

				if ( ! is_valid ) {
					$( '#fr-front-refund-table-errors' ).html( '<ul class="woocommerce-error fr-form-error"><li>' + fr_front_i18n.qty_empty + '</li></ul>' );
				} else {
					$( '#fr-front-refund-table-errors' ).html( '' );
				}

				$( this ).find( '.field-required' ).each( function () {
					let field_row = $( this );
					let notice_wrapper = field_row.find( '.fr-required-field-notice' );
					if ( _this.hasRequiredFieldValue( field_row ) ) {
						notice_wrapper.html( '' );
					} else {
						is_valid = false;
						notice_wrapper.html( '<span class="label-error-required">' + fr_front_i18n.required_field + '</span>' );
					}
				} );

				return is_valid;
			} );
		},

		checkAll: function () {
			let _this = this;

			var button = $( '.check-all-button button' );
			var check_text = button.attr( 'data-text-check' );
			var uncheck_text = button.attr( 'data-text-uncheck' );

			if ( _this.isAllInputChecked() ) {
				button.html( uncheck_text );
				button.addClass( 'uncheck' );
			} else {
				button.html( check_text );
				button.removeClass( 'uncheck' );
			}

			button.on( 'click', function () {
				if ( ! $( this ).hasClass( 'uncheck' ) ) {
					$( this ).html( uncheck_text );
					_this.checkAllInputs( 'max', true );
					$( this ).addClass( 'uncheck' );
				} else {
					$( this ).html( check_text );
					_this.checkAllInputs( 'min', false );
					$( this ).removeClass( 'uncheck' );
				}

				return false;
			} );
		},

		isAllInputChecked: function () {
			let is_checked = true;
			$( '.qty-input', this.refund_form_qty_fields ).each( function ( i, v ) {
				let field = $( v );
				let qty = field.attr( 'max' );
				let type = field.attr( 'type' );
				if ( type === 'number' ) {
					if ( qty !== field.val() ) {
						is_checked = false;
					}
					field.change();
				} else if ( type === 'checkbox' ) {
					if ( ! field.prop( 'checked' ) ) {
						is_checked = false;
					}
				}
			} );

			return is_checked;
		},

		checkAllInputs: function ( attr, prop ) {
			$( '.qty-input', this.refund_form_qty_fields ).each( function ( i, v ) {
				let field = $( v );
				let qty = field.attr( attr );
				let type = field.attr( 'type' );
				if ( type === 'number' ) {
					field.val( qty );
					field.change();
				} else if ( type === 'checkbox' ) {
					field.prop( 'checked', prop );
					field.change();
				}
			} );
		},

		refundCancelButtons: function () {
			let cancel_button = $( '#fr-cancel-request-section a.cr-button' );
			let dismiss_button = $( '#fr-cancel-request-section a.ds-button' );
			let confirm_button = $( '#fr-cancel-request-section a.cf-button' );
			if ( cancel_button.length ) {
				cancel_button.click( function () {
					confirm_button.show();
					dismiss_button.show();
					cancel_button.hide();

					return false;
				} );
			}
			if ( dismiss_button.length ) {
				dismiss_button.click( function () {
					confirm_button.hide();
					dismiss_button.hide();
					cancel_button.show();

					return false;
				} );
			}
		},

		initSelect2: function () {
			$( document ).ready( function () {
				let multiselect = $( '.multiselect' );
				if ( multiselect.length ) {
					multiselect.each( function () {
						let field = $( this );
						if ( ! field.hasClass( 'select2-hidden-accessible' ) ) {
							field.select2( { width: '100%' } );
						}
					} );
				}
			} );
		}
	}

	FormBuilder.init();
	FormBuilder.calculateRefundTotals();
	FormBuilder.validateRefundForm();
	FormBuilder.checkAll();
	FormBuilder.refundCancelButtons();
	FormBuilder.initSelect2();
	FormBuilder.validateUploadInput();

} )( jQuery );
