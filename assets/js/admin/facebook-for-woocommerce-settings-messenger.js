jQuery( document ).ready( function( $ ) {

	/**
	 * Toggles availability of input in setting groups.
	 *
	 * @param {boolean} enable whether fields in this group should be enabled or not
	 */
	function toggleSettingOptions( enable ) {

		$( '.messenger-field' ).each( function() {

			let $element = $( this );

			if ( $( this ).hasClass( 'wc-enhanced-select' ) ) {
				$element = $( this ).next( 'span.select2-container' );
			}

			if ( enable ) {
				$element.css( 'pointer-events', 'all' ).css( 'opacity', '1.0' );
			} else {
				$element.css( 'pointer-events', 'none' ).css( 'opacity', '0.4' );
			}
		} );
	}

	if ( $( 'form.wc-facebook-settings' ).hasClass( 'disconnected' ) ) {
		toggleSettingOptions( false );
	}

	$( 'input#wc_facebook_enable_messenger' ).on( 'change', function ( e ) {

		if ( $( 'form.wc-facebook-settings' ).hasClass( 'disconnected' ) ) {
			$( this ).css( 'pointer-events', 'none' ).css( 'opacity', '0.4' );
			return;
		}

		toggleSettingOptions( $( this ).is( ':checked' ) );

	} ).trigger( 'change' );

	// adds a character counter on the Messenger greeting textarea
	$( 'textarea#wc_facebook_messenger_greeting' ).on( 'focus change keyup keydown keypress', function() {

		const maxChars = parseInt( $( this ).attr( 'maxlength' ), 10 );
		let chars      = $( this ).val().length,
			$counter   = $( 'span.characters-counter' ),
			$warning   = $counter.find( 'span' );

		$counter.html( chars + ' / ' + maxChars + '<br/>' ).append( $warning ).css( 'display', 'block' );

		if ( chars > maxChars ) {
			$counter.css( 'color', '#DC3232' ).find( 'span' ).show();
		} else {
			$counter.css( 'color', '#999999' ).find( 'span' ).hide();
		}

	} );


	// init the color picker
	$( '#wc_facebook_messenger_color_hex' )

		.iris( {
			change: function( event, ui ) {
				$( this ).parent().find( '.colorpickpreview' ).css( { backgroundColor: ui.color.toString() } );
			},
			hide:   true,
			border: true
		})

		.on( 'click focus', function( event ) {
			event.stopPropagation();
			$( '.iris-picker' ).hide();
			$( this ).closest( 'td' ).find( '.iris-picker' ).show();
			$( this ).data( 'original-value', $( this ).val() );
		} )

		.on( 'change', function() {

			if ( $( this ).is( '.iris-error' ) ) {

				var original_value = $( this ).data( 'original-value' );

				if ( original_value.match( /^\#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/ ) ) {
					$( this ).val( $( this ).data( 'original-value' ) ).change();
				} else {
					$( this ).val( '' ).change();
				}
			}
		} );

	$( 'body' ).on( 'click', function() {
		$( '.iris-picker' ).hide();
	} );

} );
