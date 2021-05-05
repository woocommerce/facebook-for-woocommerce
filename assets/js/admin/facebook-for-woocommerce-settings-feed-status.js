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
		this.processStep  = this.processStep.bind( this );
		this.feedFileInfo = this.feedFileInfo.bind( this );

		this.feedFileInfo( facebook_for_woocommerce_feed_status.file );

		// Events.
		$form.on( 'submit', { feedGenerationForm: this }, this.onSubmit );
		if ( facebook_for_woocommerce_feed_status.generation_in_progress ) {
			this.$form.find('.facebook-for-woocommerce-feed-status-is-generating').show();
			this.$form.find('.facebook-woocommerce-feed-generator-button').prop( 'disabled', true );
			this.processStep();
		} else {
			this.$form.find('.spinner').removeClass('is-active');
		}
	};

	/**
	 * Handle export form submission.
	 */
	 feedGenerationForm.prototype.onSubmit = function( event ) {
		event.preventDefault();

		event.data.feedGenerationForm.$form.addClass( 'woocommerce-exporter__exporting' );
		event.data.feedGenerationForm.$form.find('.spinner').addClass('is-active');
		event.data.feedGenerationForm.$form.find('.facebook-woocommerce-feed-generator-progress').val( 0 );
		event.data.feedGenerationForm.$form.find('.facebook-for-woocommerce-feed-status-batch').text( 0 );
		event.data.feedGenerationForm.$form.find('.facebook-woocommerce-feed-generator-button').prop( 'disabled', true );
		event.data.feedGenerationForm.processStep( true );
	};

	feedGenerationForm.prototype.feedFileInfo = function( fileData ) {
		if ( null === fileData ) {
			return;
		}
		var start = new Date( parseInt( fileData['start'] * 1000 ) );
		this.$form.find('.facebook-for-woocommerce-feed-file-started').text(
			start.toString()
		);
		var end = new Date( parseInt( fileData['end'] * 1000 ) );
		this.$form.find('.facebook-for-woocommerce-feed-file-ended').text(
			end.toString()
		);
		this.$form.find('.facebook-for-woocommerce-feed-file-processing-duration').text(
			( ( parseInt( fileData['end'] ) - parseInt( fileData['start'] ) ) / 60 ).toFixed( 2 )
		);
		this.$form.find('.facebook-for-woocommerce-feed-file-items-count').text(
			fileData['total']
		);
		this.$form.find('.facebook-for-woocommerce-feed-file-size').text(
			fileData['size']
		);
		this.$form.find('.facebook-for-woocommerce-feed-file-location').text(
			fileData['location']
		);
		this.$form.find('.facebook-for-woocommerce-feed-status-file-info').show();
	}

	/**
	 * Process the current export step.
	 */
	 feedGenerationForm.prototype.processStep = function( generate = false ) {
		var $this = this;
		$.ajax( {
			type: 'POST',
			url: facebook_for_woocommerce_feed_status.ajax_url,
			data: {
				action   : 'facebook_for_woocommerce_do_ajax_feed',
				security : facebook_for_woocommerce_feed_status.feed_generation_nonce,
				generate : generate,
			},
			dataType: 'json',
			success: function( response ) {
				if ( response.success ) {
					if ( response.data.done ) {
						setTimeout( function() {
							$this.$form.removeClass( 'woocommerce-exporter__exporting' );
							$this.$form.find('.facebook-woocommerce-feed-generator-button').prop( 'disabled', false );
							$this.$form.find('.spinner').removeClass('is-active');
							$this.$form.find('.facebook-for-woocommerce-feed-status-is-generating').hide();
						}, 2000 );
					} else {
						$this.$form.find('.facebook-for-woocommerce-feed-status-is-generating').show();
						setTimeout( function() {
							$this.processStep();
						}, 10000 );
					}
					$this.$form.find('.facebook-woocommerce-feed-generator-progress').val( response.data.percentage );
					$this.$form.find('.facebook-for-woocommerce-feed-status-batch').text( response.data.page );
					$this.$form.find('.facebook-for-woocommerce-feed-status-total').text( response.data.total );
					$this.feedFileInfo( response.data.file );
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