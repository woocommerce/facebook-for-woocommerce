/*global ajaxurl, facebook_for_woocommerce_feed_status */
jQuery( document ).ready( function( $ ) {
	/**
	 * productExportForm handles the export process.
	 */
	var feedGenerationForm = function( $form ) {
		this.$form = $form;
		this.xhr   = false;

		// Initial state.
		this.$form.find('.facebook-woocommerce-feed-generator-progress').val( facebook_for_woocommerce_feed_status.generation_progress );

		// Methods.
		this.processStep = this.processStep.bind( this );

		// Events.
		$form.on( 'submit', { feedGenerationForm: this }, this.onSubmit );
		if ( facebook_for_woocommerce_feed_status.generation_in_progress ) {
			this.$form.find('.facebook-woocommerce-feed-generator-button').prop( 'disabled', true );
			setTimeout( function() {
				this.processStep();
			}, 30000 );
		}
	};

	/**
	 * Handle export form submission.
	 */
	 feedGenerationForm.prototype.onSubmit = function( event ) {
		event.preventDefault();

		event.data.feedGenerationForm.$form.addClass( 'woocommerce-exporter__exporting' );
		event.data.feedGenerationForm.$form.find('.facebook-woocommerce-feed-generator-progress').val( 0 );
		event.data.feedGenerationForm.$form.find('.facebook-woocommerce-feed-generator-button').prop( 'disabled', true );
		event.data.feedGenerationForm.processStep();
	};

	/**
	 * Process the current export step.
	 */
	 feedGenerationForm.prototype.processStep = function() {
		$.ajax( {
			type: 'POST',
			url: facebook_for_woocommerce_feed_status.ajax_url,
			data: {
				action           : 'facebook_for_woocommerce_do_ajax_feed_generate',
				security         : facebook_for_woocommerce_feed_status.feed_generation_nonce
			},
			dataType: 'json',
			success: function( response ) {
				if ( response.success ) {
					if ( 'done' === response.data.step ) {
						setTimeout( function() {
							$this.$form.removeClass( 'woocommerce-exporter__exporting' );
							$this.$form.find('.woocommerce-exporter-button').prop( 'disabled', false );
						}, 2000 );
					} else {
						setTimeout( function() {
							$this.processStep();
						}, 30000 );
					}
					$this.$form.find('.woocommerce-exporter-progress').val( response.data.percentage )
				}
			}
		} ).fail( function( response ) {
			window.console.log( response );
		} );
	};

	/**
	 * Function to call productExportForm on jquery selector.
	 */
	$.fn.wc_product_export_form = function() {
		new feedGenerationForm( this );
		return this;
	};

	$( '.facebook-for-woocommerce-feed-generator' ).wc_product_export_form();

});
