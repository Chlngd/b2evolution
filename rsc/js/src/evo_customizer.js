/**
 * This file is used for customizer mode
 */

jQuery( document ).on( 'ready', function()
{
	jQuery( '#evo_customizer__backoffice' ).on( 'load', function()
	{	// If iframe with settings has been loaded
		jQuery( this ).contents().find( 'form' ).attr( 'target', 'evo_customizer__updater' );
		if( jQuery( this ).contents().find( '.evo_customizer__buttons' ).length )
		{	// Set proper bottom margin because buttons block has a fixed position at the bottom:
			jQuery( this ).contents().find( 'body' ).css( 'margin-bottom', jQuery( this ).contents().find( '.evo_customizer__buttons' ).outerHeight() - 1 );
		}

		if( jQuery( this ).contents().find( '.alert.alert-success' ).length )
		{	// Reload front-office iframe with collection preview if the back-office iframe has a message about success updating:
			jQuery( '#evo_customizer__frontoffice' ).get(0).contentDocument.location.reload();
		}

		// Remove the message of successful action:
		var success_messages = jQuery( this ).contents().find( '.alert.alert-success' );
		var messages_wrapper = success_messages.parent();
		success_messages.remove();
		if( ! messages_wrapper.find( '.alert' ).length )
		{	// Remove messages wrapper completely if it had only successful messages:
			messages_wrapper.closest( '.action_messages' ).remove();
		}

		// Set proper space before form after top tabs:
		var tabs_height = jQuery( this ).contents().find( '.evo_customizer__tabs' ).outerHeight();
		jQuery( this ).contents().find( '.evo_customizer__content' ).css( 'margin-top', tabs_height + 'px' );

		jQuery( this ).contents().find( '.evo_customizer__tabs a' ).click( function()
		{	// Check to enable/disable designer mode between switching skin and widgets menu entries:
			var designer_mode = ( jQuery( this ).attr( 'href' ).indexOf( 'view=coll_widgets' ) > -1 ) ? 'enable' : 'disable';
			if( designer_mode != jQuery( '#evo_customizer__frontoffice' ).data( 'designer-mode' ) )
			{	// Reload front office iframe only when designer mode was changed:
				jQuery( '#evo_customizer__frontoffice' ).get(0).contentDocument.location.href += '&designer_mode=' + designer_mode;
				// Save current state of designer mode:
				jQuery( '#evo_customizer__frontoffice' ).data( 'designer-mode', designer_mode );
			}
		} );
	} );

	jQuery( '#evo_customizer__updater' ).on( 'load', function()
	{	// If iframe with settings has been loaded
		if( jQuery( this ).contents().find( '.alert.alert-success' ).length )
		{	// Reload iframe with collection preview if the updater iframe has a message about success updating:
			jQuery( '#evo_customizer__frontoffice' ).get(0).contentDocument.location.reload();
		}

		// If the updater iframe has the messages about error or warning updating:
		if( jQuery( this ).contents().find( '.alert:not(.alert-success)' ).length || 
		// OR if the settings iframe has the error message from previous updating:
			jQuery( '#evo_customizer__backoffice' ).contents().find( '.alert' ).length )
		{	// Update settings/back-office iframe with new content what we have in updater iframe currently:
			jQuery( '#evo_customizer__backoffice' ).contents().find( 'form' ).removeAttr( 'target' ).submit();
		}
	} );

	jQuery( '#evo_customizer__frontoffice' ).on( 'load', function()
	{	// If iframe with collection preview has been loaded
		jQuery( this ).contents().find( 'body[class*=coll_]' ).each( function()
		{	// Check if iframe really loads current collection:
			var backoffice_iframe = jQuery( '#evo_customizer__backoffice' );
			var body_class = jQuery( this ).attr( 'class' );
			var instance_name = body_class.match( /(^| )instance_([a-z\d]+)( |$)/i );
			instance_name = ( typeof( instance_name[2] ) == 'undefined' ? false : instance_name[2] );
			if( instance_name === false || backoffice_iframe.data( 'instance' ) != instance_name )
			{	// If page of other site is loaded in front-office iframe:
				alert( evo_js_lang_not_controlled_page );
				location.href = jQuery( '#evo_customizer__frontoffice' ).get( 0 ).contentWindow.location.href.replace( 'customizer_mode=enable&show_toolbar=hidden&redir=no', '' );
				return;
			}
			var coll_id = body_class.match( /(^| )coll_(\d+)( |$)/ );
			coll_id = ( typeof( coll_id[2] ) == 'undefined' ? 0 : coll_id[2] );
			if( coll_id && backoffice_iframe.data( 'coll-id' ) != coll_id )
			{	// Reload left/back-office iframe to customize current loaded collection if different collection has been loaded to the right/front-office iframe:
				backoffice_iframe.get( 0 ).contentWindow.location.href = backoffice_iframe.get( 0 ).contentWindow.location.href.replace( /([\?&]blog=)\d+(&|$)/, '$1' + coll_id + '$2' );
				backoffice_iframe.data( 'coll-id', coll_id );
			}
		} );

		jQuery( this ).contents().find( 'a' ).each( function()
		{	// Prepare links of new loaded content of front-office iframe:
			if( jQuery( this ).closest( '#evo_toolbar' ).length )
			{	// Skip links of evo toolbar:
				return;
			}
			var link_url = jQuery( this ).attr( 'href' );
			var collection_url = jQuery( '#evo_customizer__frontoffice' ).data( 'coll-url' );
			if( typeof( link_url ) != 'undefined' && link_url.indexOf( collection_url ) === 0 )
			{	// Append param to hide evo toolbar and don't redirect for links of the current collection:
				jQuery( this ).attr( 'href', link_url + ( link_url.indexOf( '?' ) === -1 ? '?' : '&' ) + 'customizer_mode=enable&show_toolbar=hidden&redir=no' );
			}
			else
			{	// Open all links of other collections and side sites on top window in order to update settings frame or close it:
				jQuery( this ).attr( 'target', '_top' );
			}
		} );

		var evo_toolbar = jQuery( this ).contents().find( '#evo_toolbar' );
		if( evo_toolbar.length )
		{	// Grab evo toolbar from front-office iframe with actual data for current loaded page:
			jQuery( '#evo_toolbar' ).html( evo_toolbar.html() );
		}
	} );

	jQuery( '.evo_customizer__collapser' ).click( function()
	{	// Collapse customizer iframe:
		jQuery( '.evo_customizer__wrapper' ).addClass( 'evo_customizer__collapsed' );
		jQuery( this ).hide();
		jQuery( '.evo_customizer__expander' ).show();
	} );

	jQuery( '.evo_customizer__expander' ).click( function()
	{	// Expand customizer iframe:
		jQuery( '.evo_customizer__wrapper' ).removeClass( 'evo_customizer__collapsed' );
		jQuery( this ).hide();
		jQuery( '.evo_customizer__collapser' ).show();
	} );
} );