<?php
/**
 * This file implements the UI view for the General blog properties.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004 by The University of North Carolina at Charlotte as contributed by Jason Edgecombe {@link http://tst.uncc.edu/team/members/jason_bio.php}.
 *
 * {@internal
 * The University of North Carolina at Charlotte grants Fran�ois PLANQUE the right to license
 * Jason EDGECOMBE's contributions to this file and the b2evolution project
 * under the GNU General Public License (http://www.opensource.org/licenses/gpl-license.php)
 * and the Mozilla Public License (http://www.opensource.org/licenses/mozilla1.1.php).
 * }}
 *
 * @package admin
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 * @author jwedgeco: Jason EDGECOMBE (for hire by UNC-Charlotte)
 * @author edgester: Jason EDGECOMBE (personal contributions, not for hire)
 *
 * @version $Id$
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

// Prepare last part of blog URL preview:
switch( $edited_Blog->get( 'access_type' ) )
{
	case 'default':
		$blog_urlappend = 'index.php';
		break;

	case 'index.php':
		$blog_urlappend = 'index.php'.( $Settings->get('links_extrapath') ? '/'.$edited_Blog->get( 'stub' ) : '?blog='.$edited_Blog->ID );
		break;

	case 'stub':
		$blog_urlappend = $edited_Blog->get( 'stub' );
		break;
}

?>
<script type="text/javascript">
	<!--
	blog_baseurl = '<?php $edited_Blog->disp( 'baseurl', 'formvalue' ); ?>';
	blog_urlappend = '<?php echo str_replace( "'", "\'", $blog_urlappend ) ?>';

	function update_urlpreview( base, append )
	{
		if( typeof base == 'string' ){ blog_baseurl = base; }
		if( typeof append == 'string' ){ blog_urlappend = append; }

		text = blog_baseurl + blog_urlappend;

		if( document.getElementById( 'urlpreview' ).hasChildNodes() )
		{
			document.getElementById( 'urlpreview' ).firstChild.data = text;
		}
		else
		{
			document.getElementById( 'urlpreview' ).appendChild( document.createTextNode( text ) );
		}
	}
	//-->
</script>


<form action="blogs.php<?php
if( $action == 'edit' )
{ // leave the action=edit URL intact. Hidden POST-action will have priority.
	echo '?action=edit&amp;blog='.$blog;
}
?>" class="fform" method="post">
	<input type="hidden" name="action" value="<?php echo $next_action ?>" />
	<input type="hidden" name="blog" value="<?php echo $blog; ?>" />
	<input type="hidden" name="blogtemplate" value="<?php echo $blogtemplate; ?>" />

	<fieldset>
		<legend><?php echo T_('General parameters') ?></legend>
		<?php
			form_text( 'blog_name', $edited_Blog->get( 'name' ), 50, T_('Full Name'), T_('Will be displayed on top of the blog.') );
			form_text( 'blog_shortname', $edited_Blog->get( 'shortname', 'formvalue' ), 12, T_('Short Name'), T_('Will be used in selection menus and throughout the admin interface.') );
			form_select( 'blog_locale', $edited_Blog->get( 'locale' ), 'locale_options', T_('Main Locale'), T_('Determines the language of the navigation links on the blog.') );
		?>
	</fieldset>

	<fieldset>
		<legend><?php echo T_('Access parameters') ?></legend>

		<?php
			form_radio( 'blog_siteurl_type', $blog_siteurl_type,
					array(
						array( 'relative',
										T_('Relative to baseurl').':',
										'',
										'<span class="nobr"><code>'.$baseurl.'</code>'.
										'<input type="text" id="blog_siteurl_relative" name="blog_siteurl_relative" size="30" maxlength="120" value="'.format_to_output( $blog_siteurl_relative, 'formvalue' ).'" onkeyup="update_urlpreview( \''.$baseurl.'\'+this.value );" onfocus="document.getElementsByName(\'blog_siteurl_type\')[0].checked=true; update_urlpreview( \''.$baseurl.'\'+this.value );" /></span>'.
										'<div class="notes">'.T_('With trailing slash. By default, leave this field empty. If you want to use a subfolder, you must handle it accordingly on the Webserver (e-g: create a subfolder + stub file or use mod_rewrite).').'</div>',
										'onclick="document.getElementById( \'blog_siteurl_relative\' ).focus();"'
						),
						array( 'absolute',
										T_('Absolute URL').':',
										'',
										'<input type="text" id="blog_siteurl_absolute" name="blog_siteurl_absolute" size="40" maxlength="120" value="'.format_to_output( $blog_siteurl_absolute, 'formvalue' ).'" onkeyup="update_urlpreview( this.value );" onfocus="document.getElementsByName(\'blog_siteurl_type\')[1].checked=true; update_urlpreview( this.value );" />'.
										'<span class="notes">'.T_('With trailing slash.').'</span>',
										'onclick="document.getElementById( \'blog_siteurl_absolute\' ).focus();"'
						)
					),
					T_('Blog Folder URL'), true );


			if( $default_blog_ID = $Settings->get('default_blog_ID') )
			{
				$Debuglog->add('Default blog is set to: '.$default_blog_ID);
				if( $default_Blog = $BlogCache->get_by_ID($default_blog_ID, false) )
				{ // Default blog exists
					$defblog = $default_Blog->dget('shortname');
				}
			}
			form_radio( 'blog_access_type', $edited_Blog->get( 'access_type' ),
					array(
						array( 'default', T_('Automatic detection by index.php'),
										T_('Match absolute URL or use default blog').
											' ('.( !isset($defblog)
												?	/* TRANS: NO current default blog */ T_('No default blog is currently set')
												: /* TRANS: current default blog */ T_('Current default :').' '.$defblog ).
											')',
										'',
										'onclick="update_urlpreview( false, \'index.php\' );"'
						),
						array( 'index.php', T_('Explicit reference on index.php'),
										T_('You might want to use extra-path info with this.'),
										'',
										'onclick="update_urlpreview( false, \'index.php'.( $Settings->get('links_extrapath') ? "/'+document.getElementById( 'blog_urlname' ).value" : '?blog='.$edited_Blog->ID."'" ).' )"'
						),
						array( 'stub', T_('Explicit reference to stub file (Advanced)').':',
										'',
										'<label for="blog_stub">'.T_('Stub name').':</label>'.
										'<input type="text" name="blog_stub" id="blog_stub" size="20" maxlength="'.$maxlength_urlname_stub.'" value="'.$edited_Blog->dget( 'stub', 'formvalue' ).'" onkeyup="update_urlpreview( false, this.value );" onfocus="update_urlpreview( false, this.value ); document.getElementsByName(\'blog_access_type\')[2].checked = true;" />'.
										'<div class="notes">'.T_("For this to work, you must handle it accordingly on the Webserver (e-g: create a stub file or use mod_rewrite).").'</div>',
										'onclick="document.getElementById( \'blog_stub\' ).focus();"'
						),
					), T_('Preferred access type'), true );


			form_text( 'blog_urlname', $edited_Blog->get( 'urlname' ), 20, T_('URL blog name'), T_('Used to uniquely identify this blog. Appears in URLs when using extra-path info.'), $maxlength_urlname_stub );

			form_info( T_('URL preview'), '<span id="urlpreview">'.$edited_Blog->dget( 'baseurl', 'entityencoded' ).$blog_urlappend.'</span>' );
		?>
	</fieldset>

	<fieldset>
		<legend><?php echo T_('Default display options') ?></legend>
		<?php
			form_select( 'blog_default_skin', $edited_Blog->get( 'default_skin' ), 'skin_options', T_('Default skin') , T_('This is the default skin that will be used to display this blog.') );

			form_checkbox( 'blog_force_skin', 1-$edited_Blog->get( 'force_skin' ), T_('Allow skin switching'), T_('Users will be able to select another skin to view the blog (and their prefered skin will be saved in a cookie).') );

			form_checkbox( 'blog_disp_bloglist', $edited_Blog->get( 'disp_bloglist' ), T_('Display public blog list'), T_('Check this if you want to display the list of all blogs on your blog page (if your skin supports this).') );

			form_checkbox( 'blog_in_bloglist', $edited_Blog->get( 'in_bloglist' ), T_('Include in public blog list'), T_('Check this if you want to this blog to be displayed in the list of all public blogs.') );

			form_select_object( 'blog_links_blog_ID', $edited_Blog->get( 'links_blog_ID' ), $BlogCache, T_('Default linkblog'), T_('Will be displayed next to this blog (if your skin supports this).'), true );
		?>
	</fieldset>

	<fieldset>
		<legend><?php echo T_('Description') ?></legend>
		<?php
			form_text( 'blog_tagline', $edited_Blog->get( 'tagline' ), 50, T_('Tagline'), T_('This is diplayed under the blog name on the blog template.'), 250 );

			form_textarea( 'blog_longdesc', $edited_Blog->get( 'longdesc' ), 8, T_('Long Description'), T_('This is displayed on the blog template.'), 50, 'large' );

			form_text( 'blog_description', $edited_Blog->get( 'description' ), 60, T_('Short Description'), T_('This is is used in meta tag description and RSS feeds. NO HTML!'), 250, 'large' );

			form_text( 'blog_keywords', $edited_Blog->get( 'keywords' ), 60, T_('Keywords'), T_('This is is used in meta tag keywords. NO HTML!'), 250, 'large' );

			form_textarea( 'blog_notes', $edited_Blog->get( 'notes' ), 8, T_('Notes'), T_('Additional info.'), 50, 'large' );
		?>
	</fieldset>

	<?php form_submit(); ?>

</form>


<script type="text/javascript">
	<!--
	document.getElementById( 'blog_name' ).focus();
	// -->
</script>