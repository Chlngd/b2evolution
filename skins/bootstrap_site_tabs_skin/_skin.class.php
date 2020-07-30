<?php
/**
 * This file implements a class derived of the generic Skin class in order to provide custom code for
 * the skin in this folder.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @package skins
 * @subpackage bootstrap_site_tabs_skin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'skins/model/_site_skin.class.php', 'site_Skin' );

/**
 * Specific code for this skin.
 *
 * ATTENTION: if you make a new skin you have to change the class name below accordingly
 */
class bootstrap_site_tabs_Skin extends site_Skin
{
	/**
	 * Skin version
	 * @var string
	 */
	var $version = '7.2.1';

	/**
	 * Do we want to use style.min.css instead of style.css ?
	 */
	var $use_min_css = true;  // true|false|'check' Set this to true for better optimization

	/**
	 * Get default name for the skin.
	 * Note: the admin can customize it.
	 */
	function get_default_name()
	{
		return 'Bootstrap Site Tabs';
	}


	/**
	 * Get default type for the skin.
	 */
	function get_default_type()
	{
		return 'rwd';
	}


	/**
	 * Does this skin provide normal (collection) skin functionality?
	 */
	function provides_collection_skin()
	{
		return false;
	}


	/**
	 * Does this skin provide site-skin functionality?
	 */
	function provides_site_skin()
	{
		return true;
	}


	/**
	 * What evoSkins API does has this skin been designed with?
	 *
	 * This determines where we get the fallback templates from (skins_fallback_v*)
	 * (allows to use new markup in new b2evolution versions)
	 */
	function get_api_version()
	{
		return 7;
	}


	/**
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 * @return array
	 */
	function get_param_definitions( $params )
	{
		$r = array_merge( array(
				'section_layout_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('CSS files')
				),
					'css_files' => array(
						'label' => T_('CSS files'),
						'note' => '',
						'type' => 'checklist',
						'options' => array(
								array( 'style.css',      'style.css', 0 ),
								array( 'style.min.css',  'style.min.css', 1 ), // default
								array( 'custom.css',     'custom.css', 0 ),
								array( 'custom.min.css', 'custom.min.css', 0 ),
							)
					),
				'section_layout_end' => array(
					'layout' => 'end_fieldset',
				),

				'section_header_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('Header')
				),
			),

					// Generic header params:
					$this->get_site_header_param_definitions(),

			array(
					'section_topmenu_start' => array(
						'layout' => 'begin_fieldset',
						'label'  => T_('Top menu settings')
					),
						'menu_bar_bg_color' => array(
							'label' => T_('Menu bar background color'),
							'defaultvalue' => '#ddd',
							'type' => 'color',
						),
						'menu_bar_logo_padding' => array(
							'label' => T_('Menu bar logo padding'),
							'input_suffix' => ' px ',
							'note' => T_('Set the padding around the logo.'),
							'defaultvalue' => '2',
							'type' => 'integer',
							'size' => 1,
						),
						'tab_bg_color' => array(
							'label' => T_('Tab background color'),
							'defaultvalue' => '#eee',
							'type' => 'color',
						),
						'tab_border_color' => array(
							'label' => T_('Tab border color'),
							'defaultvalue' => '#ddd',
							'type' => 'color',
						),
						'tab_text_color' => array(
							'label' => T_('Tab text color'),
							'defaultvalue' => '#337ab7',
							'type' => 'color',
						),
						'hover_tab_bg_color' => array(
							'label' => T_('Hover tab color'),
							'defaultvalue' => '#fff',
							'type' => 'color',
						),
						'hover_tab_text_color' => array(
							'label' => T_('Hover tab text color'),
							'defaultvalue' => '#23527c',
							'type' => 'color',
						),
						'selected_tab_bg_color' => array(
							'label' => T_('Selected tab color'),
							'defaultvalue' => '#fff',
							'type' => 'color',
						),
						'selected_tab_text_color' => array(
							'label' => T_('Selected tab text color'),
							'defaultvalue' => '#000',
							'type' => 'color',
						),
					'section_topmenu_end' => array(
						'layout' => 'end_fieldset',
					),

					'section_submenu_start' => array(
						'layout' => 'begin_fieldset',
						'label'  => T_('Submenu settings')
					),
						'sub_tab_bg_color' => array(
							'label' => T_('Tab background color'),
							'defaultvalue' => '#eee',
							'type' => 'color',
						),
						'sub_tab_border_color' => array(
							'label' => T_('Tab border color'),
							'defaultvalue' => '#eee',
							'type' => 'color',
						),
						'sub_tab_text_color' => array(
							'label' => T_('Tab text color'),
							'defaultvalue' => '#337ab7',
							'type' => 'color',
						),
						'sub_hover_tab_bg_color' => array(
							'label' => T_('Hover tab color'),
							'defaultvalue' => '#eee',
							'type' => 'color',
						),
						'sub_hover_tab_border_color' => array(
							'label' => T_('Hover tab border color'),
							'defaultvalue' => '#eee',
							'type' => 'color',
						),
						'sub_hover_tab_text_color' => array(
							'label' => T_('Hover tab text color'),
							'defaultvalue' => '#23527c',
							'type' => 'color',
						),
						'sub_selected_tab_bg_color' => array(
							'label' => T_('Selected tab color'),
							'defaultvalue' => '#337ab7',
							'type' => 'color',
						),
						'sub_selected_tab_border_color' => array(
							'label' => T_('Selected tab border color'),
							'defaultvalue' => '#337ab7',
							'type' => 'color',
						),
						'sub_selected_tab_text_color' => array(
							'label' => T_('Selected tab text color'),
							'defaultvalue' => '#fff',
							'type' => 'color',
						),
					'section_submenu_end' => array(
						'layout' => 'end_fieldset',
					),

				'section_header_end' => array(
					'layout' => 'end_fieldset',
				),
				
				'section_floating_nav_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('Floating navigation settings')
				),
						'back_to_top_button' => array(
							'label' => T_('"Back to Top" button'),
							'note' => T_('Check to enable "Back to Top" button'),
							'defaultvalue' => 1,
							'type' => 'checkbox',
						),
				'section_floating_nav_end' => array(
					'layout' => 'end_fieldset',
				),

				'section_footer_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('Footer settings')
				),
					'footer_bg_color' => array(
						'label' => T_('Background color'),
						'defaultvalue' => '#f5f5f5',
						'type' => 'color',
					),
					'footer_text_color' => array(
						'label' => T_('Text color'),
						'defaultvalue' => '#777',
						'type' => 'color',
					),
					'footer_link_color' => array(
						'label' => T_('Link color'),
						'defaultvalue' => '#337ab7',
						'type' => 'color',
					),
				'section_footer_end' => array(
					'layout' => 'end_fieldset',
				),

			), parent::get_param_definitions( $params ) );

		return $r;
	}


	/**
	 * Get ready for displaying the site skin.
	 *
	 * This may register some CSS or JS...
	 */
	function siteskin_init()
	{
		global $Blog, $Session;

		// Include the enabled skin CSS files relative current SITE skin folder:
		$css_files = $this->get_setting( 'css_files' );
		if( is_array( $css_files ) && count( $css_files ) )
		{
			foreach( $css_files as $css_file_name => $css_file_is_enabled )
			{
				if( $css_file_is_enabled )
				{
					require_css( $css_file_name, 'siteskin' );
				}
			}
		}

		// Add custom styles:
		// Top menu:
		$menu_bar_bg_color = $this->get_setting( 'menu_bar_bg_color' );
		$menu_bar_logo_padding = $this->get_setting( 'menu_bar_logo_padding' );
		$tab_bg_color = $this->get_setting( 'tab_bg_color' );
		$tab_border_color = $this->get_setting( 'tab_border_color' );
		$tab_text_color = $this->get_setting( 'tab_text_color' );
		$hover_tab_bg_color = $this->get_setting( 'hover_tab_bg_color' );
		$hover_tab_text_color = $this->get_setting( 'hover_tab_text_color' );
		$selected_tab_bg_color = $this->get_setting( 'selected_tab_bg_color' );
		$selected_tab_text_color = $this->get_setting( 'selected_tab_text_color' );
		// Sub menu:
		$sub_tab_bg_color = $this->get_setting( 'sub_tab_bg_color' );
		$sub_tab_border_color = $this->get_setting( 'sub_tab_border_color' );
		$sub_tab_text_color = $this->get_setting( 'sub_tab_text_color' );
		$sub_hover_tab_bg_color = $this->get_setting( 'sub_hover_tab_bg_color' );
		$sub_hover_tab_border_color = $this->get_setting( 'sub_hover_tab_border_color' );
		$sub_hover_tab_text_color = $this->get_setting( 'sub_hover_tab_text_color' );
		$sub_selected_tab_bg_color = $this->get_setting( 'sub_selected_tab_bg_color' );
		$sub_selected_tab_border_color = $this->get_setting( 'sub_selected_tab_border_color' );
		$sub_selected_tab_text_color = $this->get_setting( 'sub_selected_tab_text_color' );
		// Footer:
		$footer_bg_color = $this->get_setting( 'footer_bg_color' );
		$footer_text_color = $this->get_setting( 'footer_text_color' );
		$footer_link_color = $this->get_setting( 'footer_link_color' );

		$css = '
#evo_site_header .swhead_menus div.level1 {
	background-color: '.$menu_bar_bg_color.';
	border-color: '.$tab_border_color.';
}
#evo_site_header .swhead_sitename.swhead_logo img {
	padding: '.$menu_bar_logo_padding.'px;
}
#evo_site_header .swhead_menus div.level1 nav .pull-left li:not(.active):not(.swhead_sitename) a,
#evo_site_header .swhead_menus div.level1 nav div.pull-right a.btn {
	background-color: '.$tab_bg_color.';
	border-color: '.$tab_border_color.';
	color: '.$tab_text_color.';
}
#evo_site_header .swhead_menus div.level1 nav div.pull-right a.btn {
	border-color: '.$tab_bg_color.';
}
#evo_site_header .swhead_menus div.level1 nav .pull-left li.active a {
	background-color: '.$selected_tab_bg_color.';
	border-color: '.$tab_border_color.';
	color: '.$selected_tab_text_color.';
}
#evo_site_header .swhead_menus div.level1 nav .pull-left li.swhead_sitename a {
	color: '.$tab_text_color.';
}
#evo_site_header .swhead_menus div.level1 nav .pull-left li:not(.active):not(.swhead_sitename) a:hover,
#evo_site_header .swhead_menus div.level1 nav div.pull-right a.btn:hover {
	background-color: '.$hover_tab_bg_color.';
	color: '.$hover_tab_text_color.';
}
#evo_site_header .swhead_menus div.level1 nav div.pull-right a.btn:hover {
	border-color: '.$hover_tab_bg_color.';
}
div.level1 nav ul.nav.nav-tabs {
	border-color: '.$tab_border_color.';
}

div.level2 ul.nav.nav-pills li a {
	background-color: '.$sub_tab_bg_color.';
	border: 1px solid '.$sub_tab_border_color.';
	color: '.$sub_tab_text_color.';
}
div.level2 ul.nav.nav-pills a:hover {
	background-color: '.$sub_hover_tab_bg_color.';
	border: 1px solid '.$sub_hover_tab_border_color.';
	color: '.$sub_hover_tab_text_color.';
}
div.level2 ul.nav.nav-pills li.active a {
	background-color: '.$sub_selected_tab_bg_color.';
	border: 1px solid '.$sub_selected_tab_border_color.';
	color: '.$sub_selected_tab_text_color.';
}

footer#evo_site_footer {
	background-color: '.$footer_bg_color.';
	color: '.$footer_text_color.';
}
footer#evo_site_footer .container a {
	color: '.$footer_link_color.';
}
';

		if( $this->get_setting( 'fixed_header' ) &&
		    ! $Session->get( 'display_containers_'.$Blog->ID ) &&
		    ! $Session->get( 'display_includes_'.$Blog->ID ) &&
		    ! $Session->get( 'customizer_mode_'.$Blog->ID ) )
		{	// Enable fixed position for header only when no debug blocks:
			$css .= '#evo_site_header {
	position: fixed;
	top: 0;
	width: 100%;
	z-index: 10000;
}
body.evo_toolbar_visible #evo_site_header {
	top: 27px;
}
body {
	padding-top: 50px;
}';
		}

		add_css_headline( $css );
	}
}
?>