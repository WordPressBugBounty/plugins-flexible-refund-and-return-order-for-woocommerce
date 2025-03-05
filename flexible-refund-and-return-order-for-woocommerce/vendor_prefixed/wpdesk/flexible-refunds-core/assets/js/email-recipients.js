( function ( $ ) {
	const $recipientInput = $( '#woocommerce_fr_email_refund_admin_requested_recipient' );
	if ( ! $recipientInput.length ) {
		return;
	}

	if ( fr_email_recipients.is_super === "false" ) {
		const $addRowButtonFree = $( '<a style="height: 30px; display: inline-flex; align-items: center;text-decoration: none; color: #999;" href="#"><span class="dashicons dashicons-insert"></span></a>' );
		$recipientInput.after( $addRowButtonFree );

		return;
	}

	$recipientInput.hide();

	const $container = $( '<div class="multi-recipients-container"></div>' );
	$recipientInput.after( $container );

	let existing = $recipientInput.val().split( ',' );
	if ( existing.length === 0 || ( existing.length === 1 && ! existing[ 0 ].trim() ) ) {
		existing = [ '' ];
	}

	existing.forEach( ( email, i ) => {
		addRecipientRow( email.trim(), i > 0, i === 0 );
	} );

	function addRecipientRow( value = '', canRemove = true, canAdd = false ) {
		const $row = $( '<div class="recipient-row" style="margin-bottom:5px;"></div>' );
		const $input = $( '<input type="email" class="recipient-email-input" />' ).val( value );
		const $addRowButton = $( '<a class="add_row" style="height: 30px; display: inline-flex; align-items: center;text-decoration: none" href="#"><span class="dashicons dashicons-insert"></span></a>' );
		const $removeRowButton = $( '<a class="remove_row" style="height: 30px; display: inline-flex; align-items: center;text-decoration: none" href="#"><span class="dashicons dashicons-remove"></span></a>' );

		if ( ! canRemove ) {
			$removeRowButton.hide();
		}
		if ( ! canAdd ) {
			$addRowButton.hide();
		}

		$row.append( $input ).append( ' ' ).append( $addRowButton ).append( ' ' ).append( $removeRowButton );
		$container.append( $row );

		$addRowButton.on( 'click', ( e ) => {
			e.preventDefault();
			addRecipientRow( '', true, false );
		} );

		$removeRowButton.on( 'click', ( e ) => {
			e.preventDefault();
			$row.remove();
		} );
	}

	$recipientInput.closest( 'form' ).on( 'submit', () => {
		const emails = [];
		$container.find( '.recipient-email-input' ).each( function () {
			const val = $( this ).val().trim();
			if ( val ) {
				emails.push( val );
			}
		} );
		$recipientInput.val( emails.join( ',' ) );
	} );
}( jQuery ) );
