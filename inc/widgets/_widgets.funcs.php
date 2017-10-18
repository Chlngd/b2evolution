<?php
/**
 * This file implements additional functional for widgets.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 *
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );


/**
 * Add a widget to global array in order to insert it in DB by single SQL query later
 *
 * @param integer Container ID
 * @param string Type
 * @param string Code
 * @param integer Order
 * @param array|string|NULL Widget params
 * @param integer 1 - enabled, 0 - disabled
 */
function add_basic_widget( $container_ID, $code, $type, $order, $params = NULL, $enabled = 1 )
{
	global $basic_widgets_insert_sql_rows, $DB;

	if( is_null( $params ) )
	{ // NULL
		$params = 'NULL';
	}
	elseif( is_array( $params ) )
	{ // array
		$params = $DB->quote( serialize( $params ) );
	}
	else
	{ // string
		$params = $DB->quote( $params );
	}

	$basic_widgets_insert_sql_rows[] = '( '
		.$container_ID.', '
		.$order.', '
		.$enabled.', '
		.$DB->quote( $type ).', '
		.$DB->quote( $code ).', '
		.$params.' )';
}


/**
 * Insert the basic widgets for a collection
 *
 * @param integer should never be 0
 * @param array the list of skin ids which are set for the given blog ( normal, mobile and tablet skin ids )
 * @param boolean should be true only when it's called after initial install
 * fp> TODO: $initial_install is used to know if we want to trust globals like $blog_photoblog_ID and $blog_forums_ID. We don't want that.
 *           We should pass a $context array with values like 'photo_source_coll_ID' => 4.
 *           Also, checking $blog_forums_ID is unnecessary complexity. We can check the colleciton kind == forum
 * @param string Kind of blog ( 'std', 'photo', 'group', 'forum' )
 */
function insert_basic_widgets( $blog_id, $skin_ids, $initial_install = false, $kind = '' )
{
	global $DB, $install_test_features, $basic_widgets_insert_sql_rows;

	// Initialize this array first time and clear after previous call of this function
	$basic_widgets_insert_sql_rows = array();

	// Load skin functions needed to get the skin containers
	load_funcs( 'skins/_skin.funcs.php' );

	// Handle all blog IDs which can go from function create_demo_contents()
	global $blog_home_ID, $blog_a_ID, $blog_b_ID, $blog_photoblog_ID, $blog_forums_ID, $blog_manual_ID, $events_blog_ID;
	$blog_home_ID = intval( $blog_home_ID );
	$blog_a_ID = intval( $blog_a_ID );
	$blog_b_ID = intval( $blog_b_ID );
	$blog_photoblog_ID = intval( $blog_photoblog_ID );
	$blog_forums_ID = intval( $blog_forums_ID );
	$blog_manual_ID = intval( $blog_manual_ID );
	$events_blog_ID = intval( $events_blog_ID );

	// Get all containers declared in the given blog's skins
	$blog_containers = get_skin_containers( $skin_ids );

	// Additional sub containers:
	$blog_containers['front_page_column_a'] = array( 'Front Page Column A', 1, 0 );
	$blog_containers['front_page_column_b'] = array( 'Front Page Column B', 2, 0 );
	$blog_containers['user_page_reputation'] = array( 'User Page - Reputation', 100, 0 );

	// Additional page containers:
	$blog_containers['widget_page_section_1'] = array( 'Widget Page Section 1', 10, 0, 9 );
	$blog_containers['widget_page_section_2'] = array( 'Widget Page Section 2', 20, 0, 9 );
	$blog_containers['widget_page_section_3'] = array( 'Widget Page Section 3', 30, 0, 9 );

	// Create rows to insert for all collection containers:
	$widget_containers_sql_rows = array();
	foreach( $blog_containers as $wico_code => $wico_data )
	{
		$widget_containers_sql_rows[] = '( "'
			/* Code          */.$wico_code.'", "'
			/* Name          */.$wico_data[0].'", '
			/* Collection ID */.$blog_id.', '
			/* Order         */.$wico_data[1].', '
			/* Main or Sub   */.( isset( $wico_data[2] ) ? intval( $wico_data[2] ) : '1' ).', '
			/* Item Type ID  */.( isset( $wico_data[3] ) ? intval( $wico_data[3] ) : 'NULL' ).', '
			/* Item ID       */.( isset( $wico_data[4] ) ? intval( $wico_data[4] ) : 'NULL' )
			.' )';
	}

	// Insert widget containers records by one SQL query
	$DB->query( 'INSERT INTO T_widget__container( wico_code, wico_name, wico_coll_ID, wico_order, wico_main, wico_ityp_ID, wico_item_ID ) VALUES'
		.implode( ', ', $widget_containers_sql_rows ) );

	$insert_id = $DB->insert_id;
	foreach( $blog_containers as $wico_code => $wico_data )
	{
		$blog_containers[ $wico_code ]['wico_ID'] = $insert_id;
		$insert_id++;
	}

	// Init insert widget query and default params
	$default_blog_param = 's:7:"blog_ID";s:0:"";';
	if( $initial_install && ! empty( $blog_photoblog_ID ) )
	{ // In the case of initial install, we grab photos out of the photoblog (Blog #4)
		$default_blog_param = 's:7:"blog_ID";s:1:"'.intval( $blog_photoblog_ID ).'";';
	}


	/* Header */
	if( array_key_exists( 'header', $blog_containers ) )
	{
		$wico_id = $blog_containers['header']['wico_ID'];
		add_basic_widget( $wico_id, 'coll_title', 'core', 1 );
		add_basic_widget( $wico_id, 'coll_tagline', 'core', 2 );
	}


	/* Menu */
	if( array_key_exists( 'menu', $blog_containers ) )
	{
		$wico_id = $blog_containers['menu']['wico_ID'];
		if( $kind != 'main' )
		{ // Don't add widgets to Menu container for Main collections
			// Home page
			add_basic_widget( $wico_id, 'basic_menu_link', 'core', 5, array( 'link_type' => 'home' ) );
			if( $blog_id == $blog_b_ID )
			{ // Recent Posts
				add_basic_widget( $wico_id, 'basic_menu_link', 'core', 10, array( 'link_type' => 'recentposts', 'link_text' => T_('News') ) );
			}
			if( $kind == 'forum' )
			{ // Latest Topics and Replies ONLY for forum
				add_basic_widget( $wico_id, 'basic_menu_link', 'core', 13, array( 'link_type' => 'recentposts', 'link_text' => T_('Latest topics') ) );
				add_basic_widget( $wico_id, 'basic_menu_link', 'core', 15, array( 'link_type' => 'latestcomments', 'link_text' => T_('Latest replies') ) );
			}
			if( $kind == 'manual' )
			{ // Latest Topics and Replies ONLY for forum
				add_basic_widget( $wico_id, 'basic_menu_link', 'core', 13, array( 'link_type' => 'recentposts', 'link_text' => T_('Latest pages') ) );
				add_basic_widget( $wico_id, 'basic_menu_link', 'core', 15, array( 'link_type' => 'latestcomments', 'link_text' => T_('Latest comments') ) );
			}
			if( $kind == 'forum' || $kind == 'manual' )
			{	// Add menu with flagged items:
				add_basic_widget( $wico_id, 'flag_menu_link', 'core', 17, array( 'link_text' => ( $kind == 'forum' ) ? T_('Flagged topics') : T_('Flagged pages') ) );
			}
			if( $kind == 'photo' )
			{ // Add menu with Photo index
				add_basic_widget( $wico_id, 'basic_menu_link', 'core', 18, array( 'link_type' => 'mediaidx', 'link_text' => T_('Index') ) );
			}
			if( $kind == 'forum' )
			{ // Add menu with User Directory and Profile Visits ONLY for forum
				add_basic_widget( $wico_id, 'basic_menu_link', 'core', 20, array( 'link_type' => 'users' ) );
				add_basic_widget( $wico_id, 'basic_menu_link', 'core', 21, array( 'link_type' => 'users' ) );
			}
			// Pages list:
			add_basic_widget( $wico_id, 'coll_page_list', 'core', 25 );
			if( $kind == 'forum' )
			{ // My Profile
				add_basic_widget( $wico_id, 'basic_menu_link', 'core', 30, array( 'link_type' => 'myprofile' ), 0 );
			}
			if( $kind == 'std' )
			{ // Categories
				add_basic_widget( $wico_id, 'basic_menu_link', 'core', 33, array( 'link_type' => 'catdir' ) );
				// Archives
				add_basic_widget( $wico_id, 'basic_menu_link', 'core', 35, array( 'link_type' => 'arcdir' ) );
				// Latest comments
				add_basic_widget( $wico_id, 'basic_menu_link', 'core', 37, array( 'link_type' => 'latestcomments' ) );
			}
			add_basic_widget( $wico_id, 'msg_menu_link', 'core', 50, array( 'link_type' => 'messages' ), 0 );
			add_basic_widget( $wico_id, 'msg_menu_link', 'core', 60, array( 'link_type' => 'contacts', 'show_badge' => 0 ), 0 );
			add_basic_widget( $wico_id, 'basic_menu_link', 'core', 70, array( 'link_type' => 'login' ), 0 );
			if( $kind == 'forum' )
			{ // Register
				add_basic_widget( $wico_id, 'basic_menu_link', 'core', 80, array( 'link_type' => 'register' ) );
			}
		}
	}

	/* Item Single Header */
	if( array_key_exists( 'item_single_header', $blog_containers ) )
	{
		$wico_id = $blog_containers['item_single_header']['wico_ID'];
		if( in_array( $kind, array( 'forum', 'group' ) ) )
		{
			add_basic_widget( $wico_id, 'item_info_line', 'core', 10, 'a:14:{s:5:"title";s:0:"";s:9:"flag_icon";i:1;s:14:"permalink_icon";i:0;s:13:"before_author";s:10:"started_by";s:11:"date_format";s:8:"extended";s:9:"post_time";i:1;s:12:"last_touched";i:1;s:8:"category";i:0;s:9:"edit_link";i:0;s:16:"widget_css_class";s:0:"";s:9:"widget_ID";s:0:"";s:16:"allow_blockcache";i:0;s:11:"time_format";s:4:"none";s:12:"display_date";s:12:"date_created";}' );
			add_basic_widget( $wico_id, 'item_tags', 'core', 20 );
			add_basic_widget( $wico_id, 'item_seen_by', 'core', 30 );
		}
		else
		{
			add_basic_widget( $wico_id, 'item_info_line', 'core', 10 );
		}
	}

	/* Item Single */
	if( array_key_exists( 'item_single', $blog_containers ) )
	{
		$wico_id = $blog_containers['item_single']['wico_ID'];
		add_basic_widget( $wico_id, 'item_content', 'core', 10 );
		add_basic_widget( $wico_id, 'item_attachments', 'core', 15 );
		add_basic_widget( $wico_id, 'item_link', 'core', 17 );
		if( $blog_id != $blog_a_ID && ( empty( $events_blog_ID ) || $blog_id != $events_blog_ID ) && ! in_array( $kind, array( 'forum', 'group' ) ) )
		{ // Item Tags
			add_basic_widget( $wico_id, 'item_tags', 'core', 20 );
		}
		if( $blog_id == $blog_b_ID )
		{ // About Author
			add_basic_widget( $wico_id, 'item_about_author', 'core', 25 );
		}
		if( ( $blog_id == $blog_a_ID || ( ! empty( $events_blog_ID ) && $blog_id == $events_blog_ID ) ) && $install_test_features )
		{ // Google Maps
			add_basic_widget( $wico_id, 'evo_Gmaps', 'plugin', 30 );
		}
		if( $blog_id == $blog_a_ID || $kind == 'manual' )
		{ // Small Print
			add_basic_widget( $wico_id, 'item_small_print', 'core', 40, array( 'format' => ( $blog_id == $blog_a_ID ? 'standard' : 'revision' ) ) );
		}
		if( ! in_array( $kind, array( 'forum', 'group' ) ) )
		{ // Seen by
			add_basic_widget( $wico_id, 'item_seen_by', 'core', 50 );
		}
		if( $kind != 'forum' )
		{	// Item voting panel:
			add_basic_widget( $wico_id, 'item_vote', 'core', 60 );
		}
	}

	/* Item Page */
	if( array_key_exists( 'item_page', $blog_containers ) )
	{
		$wico_id = $blog_containers['item_page']['wico_ID'];
		add_basic_widget( $wico_id, 'item_content', 'core', 10 );
		add_basic_widget( $wico_id, 'item_attachments', 'core', 15 );
		add_basic_widget( $wico_id, 'item_seen_by', 'core', 50 );
		add_basic_widget( $wico_id, 'item_vote', 'core', 60 );
	}

	/* Sidebar Single */
	if( $kind == 'forum' )
	{
		if( array_key_exists( 'sidebar_single', $blog_containers ) )
		{
			$wico_id = $blog_containers['sidebar_single']['wico_ID'];
			add_basic_widget( $wico_id, 'coll_related_post_list', 'core', 1 );
		}
	}


	/* Page Top */
	if( array_key_exists( 'page_top', $blog_containers ) )
	{
		$wico_id = $blog_containers['page_top']['wico_ID'];
		add_basic_widget( $wico_id, 'user_links', 'core', 10 );
	}


	/* Sidebar */
	if( array_key_exists( 'sidebar', $blog_containers ) )
	{
		$wico_id = $blog_containers['sidebar']['wico_ID'];
		if( $kind == 'manual' )
		{
			$search_form_params = array( 'title' => T_('Search this manual:') );
			add_basic_widget( $wico_id, 'coll_search_form', 'core', 10, $search_form_params );
			add_basic_widget( $wico_id, 'content_hierarchy', 'core', 20 );
		}
		else
		{
			if( $install_test_features )
			{
				if( $kind != 'forum' && $kind != 'manual' )
				{ // Current filters widget
					add_basic_widget( $wico_id, 'coll_current_filters', 'core', 5 );
				}
				// User login widget
				add_basic_widget( $wico_id, 'user_login', 'core', 10 );
			}
			if( ( ! $initial_install || $blog_id != $blog_forums_ID ) && $kind != 'forum' )
			{ // Don't install these Sidebar widgets for blog 'Forums'
				add_basic_widget( $wico_id, 'user_profile_pics', 'core', 20 );
				if( $blog_id > $blog_a_ID )
				{
					add_basic_widget( $wico_id, 'evo_Calr', 'plugin', 30 );
				}
				add_basic_widget( $wico_id, 'coll_longdesc', 'core', 40, array( 'title' => '$title$' ) );
				add_basic_widget( $wico_id, 'coll_search_form', 'core', 50 );
				add_basic_widget( $wico_id, 'coll_category_list', 'core', 60 );

				if( $blog_id == $blog_home_ID )
				{ // Advertisements, Install only for blog #1 home blog
					$advertisement_type_ID = $DB->get_var( 'SELECT ityp_ID FROM T_items__type WHERE ityp_name = "Advertisement"' );
					add_basic_widget( $wico_id, 'coll_item_list', 'core', 70, array(
							'title' => 'Advertisement (Demo)',
							'item_type' => empty( $advertisement_type_ID ) ? '#' : $advertisement_type_ID,
							'blog_ID' => $blog_id,
							'order_by' => 'RAND',
							'limit' => 1,
							'disp_title' => false,
							'item_title_link_type' => 'linkto_url',
							'attached_pics' => 'first',
							'item_pic_link_type' => 'linkto_url',
							'thumb_size' => 'fit-160x160',
						) );
				}

				if( $blog_id != $blog_b_ID )
				{
					add_basic_widget( $wico_id, 'coll_media_index', 'core', 80, 'a:11:{s:5:"title";s:12:"Random photo";s:10:"thumb_size";s:11:"fit-160x120";s:12:"thumb_layout";s:4:"grid";s:12:"grid_nb_cols";s:1:"1";s:5:"limit";s:1:"1";s:8:"order_by";s:4:"RAND";s:9:"order_dir";s:3:"ASC";'.$default_blog_param.'s:11:"widget_name";s:12:"Random photo";s:16:"widget_css_class";s:0:"";s:9:"widget_ID";s:0:"";}' );
				}
				if( ! empty( $blog_home_ID ) && ( $blog_id == $blog_a_ID || $blog_id == $blog_b_ID ) )
				{
					$sidebar_type_ID = $DB->get_var( 'SELECT ityp_ID FROM T_items__type WHERE ityp_name = "Sidebar link"' );
					add_basic_widget( $wico_id, 'coll_item_list', 'core', 90, array(
							'blog_ID'              => $blog_home_ID,
							'item_type'            => empty( $sidebar_type_ID ) ? '#' : $sidebar_type_ID,
							'title'                => 'Linkblog',
							'item_group_by'        => 'chapter',
							'item_title_link_type' => 'auto',
							'item_type_usage'      => 'special',
						) );
				}
			}
			if( $kind == 'forum' )
			{
				add_basic_widget( $wico_id, 'user_avatars', 'core', 90, array(
						'title'           => 'Most Active Users',
						'limit'           => 6,
						'order_by'        => 'numposts',
						'rwd_block_class' => 'col-lg-3 col-md-3 col-sm-4 col-xs-6'
					) );
			}
			add_basic_widget( $wico_id, 'coll_xml_feeds', 'core', 100 );
			add_basic_widget( $wico_id, 'mobile_skin_switcher', 'core', 110 );
		}
	}


	/* Sidebar 2 */
	if( array_key_exists( 'sidebar_2', $blog_containers ) )
	{
		if( $kind != 'forum' )
		{
		$wico_id = $blog_containers['sidebar_2']['wico_ID'];
		add_basic_widget( $wico_id, 'coll_post_list', 'core', 1 );
		if( $blog_id == $blog_b_ID )
		{
			add_basic_widget( $wico_id, 'coll_item_list', 'core', 5, array(
					'title'                => 'Sidebar links',
					'order_by'             => 'RAND',
					'item_title_link_type' => 'auto',
					'item_type_usage'      => 'special',
				) );
		}
		add_basic_widget( $wico_id, 'coll_comment_list', 'core', 10 );
		add_basic_widget( $wico_id, 'coll_media_index', 'core', 15, 'a:11:{s:5:"title";s:13:"Recent photos";s:10:"thumb_size";s:10:"crop-80x80";s:12:"thumb_layout";s:4:"flow";s:12:"grid_nb_cols";s:1:"3";s:5:"limit";s:1:"9";s:8:"order_by";s:9:"datestart";s:9:"order_dir";s:4:"DESC";'.$default_blog_param.'s:11:"widget_name";s:11:"Photo index";s:16:"widget_css_class";s:0:"";s:9:"widget_ID";s:0:"";}' );
		add_basic_widget( $wico_id, 'free_html', 'core', 20, 'a:5:{s:5:"title";s:9:"Sidebar 2";s:7:"content";s:162:"This is the "Sidebar 2" container. You can place any widget you like in here. In the evo toolbar at the top of this page, select "Customize", then "Blog Widgets".";s:11:"widget_name";s:9:"Free HTML";s:16:"widget_css_class";s:0:"";s:9:"widget_ID";s:0:"";}' );
		}
	}


	/* Front Page Main Area */
	if( array_key_exists( 'front_page_main_area', $blog_containers ) )
	{
		$wico_id = $blog_containers['front_page_main_area']['wico_ID'];
		if( $kind == 'main' )
		{ // Display blog title and tagline for main blogs
			add_basic_widget( $wico_id, 'coll_title', 'core', 1 );
			add_basic_widget( $wico_id, 'coll_tagline', 'core', 2 );
		}

		if( $kind == 'main' )
		{ // Hide a title of the front intro post
			$featured_intro_params = array( 'disp_title' => 0 );
		}
		else
		{
			$featured_intro_params = NULL;
		}
		add_basic_widget( $wico_id, 'coll_featured_intro', 'core', 10, $featured_intro_params );
		if( $kind == 'main' )
		{ // Add user links widget only for main kind blogs
			add_basic_widget( $wico_id, 'user_links', 'core', 15 );
		}

		if( $kind == 'main' )
		{ // Display the posts from all other blogs if it is allowed by blogs setting "Collections to aggregate"
			$post_list_params = array(
					'blog_ID'          => '',
					'limit'            => 5,
					'layout'           => 'list',
					'thumb_size'       => 'crop-80x80',
				);
		}
		else
		{
			$post_list_params = NULL;
		}
		add_basic_widget( $wico_id, 'coll_featured_posts', 'core', 20, $post_list_params );

		if( $blog_id == $blog_b_ID )
		{	// Install widget "Poll" only for Blog B on install:
			add_basic_widget( $wico_id, 'poll', 'core', 40, array( 'poll_ID' => 1 ) );
		}

		add_basic_widget( $wico_id, 'subcontainer_row', 'core', 50, array(
				'column1_container' => 'front_page_column_a',
				'column1_class'     => ( $kind == 'main' ? 'col-xs-12' : 'col-sm-6 col-xs-12' ),
				'column2_container' => 'front_page_column_b',
				'column2_class'     => 'col-sm-6 col-xs-12',
			) );
		if( $blog_id == $blog_b_ID )
		{	// Install widget "Poll" only for Blog B on install:
			add_basic_widget( $wico_id, 'poll', 'core', 60, array( 'poll_ID' => 1 ) );
		}
	}


	/* Front Page Column A */
	if( array_key_exists( 'front_page_column_a', $blog_containers ) )
	{
		$wico_id = $blog_containers['front_page_column_a']['wico_ID'];
		add_basic_widget( $wico_id, 'coll_post_list', 'core', 10, array( 'title' => T_('More Posts'), 'featured' => 'other' ) );
	}


	/* Front Page Column B */
	if( array_key_exists( 'front_page_column_b', $blog_containers ) )
	{
		$wico_id = $blog_containers['front_page_column_b']['wico_ID'];
		if( $kind != 'main' )
		{	// Don't install the "Recent Commnets" widget for Main collections:
			add_basic_widget( $wico_id, 'coll_comment_list', 'core', 10 );
		}
	}


	/* Front Page Secondary Area */
	if( array_key_exists( 'front_page_secondary_area', $blog_containers ) )
	{
		$wico_id = $blog_containers['front_page_secondary_area']['wico_ID'];
		if( $kind == 'main' )
		{	// Install the "Organization Members" widget only for Main collections:
			add_basic_widget( $wico_id, 'org_members', 'core', 10 );
		}
		add_basic_widget( $wico_id, 'coll_flagged_list', 'core', 20 );
		if( $kind == 'main' )
		{	// Install the "Content Block" widget only for Main collections:
			add_basic_widget( $wico_id, 'content_block', 'core', 30, array( 'item_slug' => 'this-is-a-content-block' ) );
		}
	}


	/* Forum Front Secondary Area */
	if( array_key_exists( 'forum_front_secondary_area', $blog_containers ) )
	{
		$wico_id = $blog_containers['forum_front_secondary_area']['wico_ID'];
		if( $kind == 'forum' )
		{
			add_basic_widget( $wico_id, 'coll_activity_stats', 'core', 10 );
		}
	}


	/* 404 Page */
	if( array_key_exists( '404_page', $blog_containers ) )
	{
		$wico_id = $blog_containers['404_page']['wico_ID'];
		add_basic_widget( $wico_id, 'page_404_not_found', 'core', 10 );
		add_basic_widget( $wico_id, 'coll_search_form', 'core', 20 );
		add_basic_widget( $wico_id, 'coll_tag_cloud', 'core', 30 );
	}


	/* Mobile Footer */
	if( array_key_exists( 'mobile_footer', $blog_containers ) )
	{
		$wico_id = $blog_containers['mobile_footer']['wico_ID'];
		add_basic_widget( $wico_id, 'coll_longdesc', 'core', 10 );
		add_basic_widget( $wico_id, 'mobile_skin_switcher', 'core', 20 );
	}


	/* Mobile Navigation Menu */
	if( array_key_exists( 'mobile_navigation_menu', $blog_containers ) )
	{
		$wico_id = $blog_containers['mobile_navigation_menu']['wico_ID'];
		add_basic_widget( $wico_id, 'coll_page_list', 'core', 10 );
		add_basic_widget( $wico_id, 'basic_menu_link', 'core', 20, array( 'link_type' => 'ownercontact' ) );
		add_basic_widget( $wico_id, 'basic_menu_link', 'core', 30, array( 'link_type' => 'home' ) );
		if( $kind == 'forum' )
		{ // Add menu with User Directory
			add_basic_widget( $wico_id, 'basic_menu_link', 'core', 40, array( 'link_type' => 'users' ) );
		}
	}


	/* Mobile Tools Menu */
	if( array_key_exists( 'mobile_tools_menu', $blog_containers ) )
	{
		$wico_id = $blog_containers['mobile_tools_menu']['wico_ID'];
		add_basic_widget( $wico_id, 'basic_menu_link', 'core', 10, array( 'link_type' => 'login' ) );
		add_basic_widget( $wico_id, 'msg_menu_link', 'core', 20, array( 'link_type' => 'messages' ) );
		add_basic_widget( $wico_id, 'msg_menu_link', 'core', 30, array( 'link_type' => 'contacts', 'show_badge' => 0 ) );
		add_basic_widget( $wico_id, 'basic_menu_link', 'core', 50, array( 'link_type' => 'logout' ) );
	}


	/* User Profile - Left */
	if( array_key_exists( 'user_profile_left', $blog_containers ) )
	{
		$wico_id = $blog_containers['user_profile_left']['wico_ID'];
		// User Profile Picture(s):
		add_basic_widget( $wico_id, 'user_profile_pics', 'core', 10, array(
				'link_to'           => 'fullsize',
				'thumb_size'        => 'crop-top-320x320',
				'anon_thumb_size'   => 'crop-top-320x320-blur-8',
				'anon_overlay_show' => '1',
				'widget_css_class'  => 'evo_user_profile_pics_main',
			) );
		// User info / Name:
		add_basic_widget( $wico_id, 'user_info', 'core', 20, array(
				'info'             => 'name',
				'widget_css_class' => 'evo_user_info_name',
			) );
		// User info / Nickname:
		add_basic_widget( $wico_id, 'user_info', 'core', 30, array(
				'info'             => 'nickname',
				'widget_css_class' => 'evo_user_info_nickname',
			) );
		// User info / Login:
		add_basic_widget( $wico_id, 'user_info', 'core', 40, array(
				'info'             => 'login',
				'widget_css_class' => 'evo_user_info_login',
			) );
		// Separator:
		add_basic_widget( $wico_id, 'separator', 'core', 60 );
		// User info / :
		add_basic_widget( $wico_id, 'user_info', 'core', 70, array(
				'info'             => 'gender_age',
				'widget_css_class' => 'evo_user_info_gender',
			) );
		// User info / Location:
		add_basic_widget( $wico_id, 'user_info', 'core', 80, array(
				'info'             => 'location',
				'widget_css_class' => 'evo_user_info_location',
			) );
		// Separator:
		add_basic_widget( $wico_id, 'separator', 'core', 90 );
		// User action / Edit my profile:
		add_basic_widget( $wico_id, 'user_action', 'core', 100, array(
				'button'           => 'edit_profile',
			) );
		// User action / Send Message:
		add_basic_widget( $wico_id, 'user_action', 'core', 110, array(
				'button'           => 'send_message',
			) );
		// User action / Add to Contacts:
		add_basic_widget( $wico_id, 'user_action', 'core', 120, array(
				'button'           => 'add_contact',
			) );
		// User action / Block Contact & Report User:
		add_basic_widget( $wico_id, 'user_action', 'core', 130, array(
				'button'           => 'block_report',
				'widget_css_class' => 'btn-group',
			) );
		// User action / Edit in Back-Office:
		add_basic_widget( $wico_id, 'user_action', 'core', 140, array(
				'button'           => 'edit_backoffice',
			) );
		// User action / Delete & Delete Spammer:
		add_basic_widget( $wico_id, 'user_action', 'core', 150, array(
				'button'           => 'delete',
				'widget_css_class' => 'btn-group',
			) );
		// Separator:
		add_basic_widget( $wico_id, 'separator', 'core', 160 );
		// User info / Organizations:
		add_basic_widget( $wico_id, 'user_info', 'core', 170, array(
				'info'             => 'orgs',
				'title'            => T_('Organizations').':',
				'widget_css_class' => 'evo_user_info_orgs',
			) );
	}


	/* User Profile - Right */
	if( array_key_exists( 'user_profile_right', $blog_containers ) )
	{
		$wico_id = $blog_containers['user_profile_right']['wico_ID'];
		// User Profile Picture(s):
		add_basic_widget( $wico_id, 'user_profile_pics', 'core', 10, array(
				'display_main'     => 0,
				'display_other'    => 1,
				'link_to'          => 'fullsize',
				'thumb_size'       => 'crop-top-80x80',
				'widget_css_class' => 'evo_user_profile_pics_other',
			) );
		// User fields:
		add_basic_widget( $wico_id, 'user_fields', 'core', 20 );
		// Reputation:
		add_basic_widget( $wico_id, 'subcontainer', 'core', 30, array(
				'title'     => T_('Reputation'),
				'container' => 'user_page_reputation',
			) );
	}

	/* User Page - Reputation */
	if( array_key_exists( 'user_page_reputation', $blog_containers ) )
	{
		$wico_id = $blog_containers['user_page_reputation']['wico_ID'];
		// User info / Joined:
		add_basic_widget( $wico_id, 'user_info', 'core', 10, array(
				'title' => T_('Joined'),
				'info'  => 'joined',
			) );
		// User info / Last Visit:
		add_basic_widget( $wico_id, 'user_info', 'core', 20, array(
				'title' => T_('Last seen on'),
				'info'  => 'last_visit',
			) );
		// User info / Number of posts:
		add_basic_widget( $wico_id, 'user_info', 'core', 30, array(
				'title' => T_('Number of posts'),
				'info'  => 'posts',
			) );
		// User info / Comments:
		add_basic_widget( $wico_id, 'user_info', 'core', 40, array(
				'title' => T_('Comments'),
				'info'  => 'comments',
			) );
		// User info / Photos:
		add_basic_widget( $wico_id, 'user_info', 'core', 50, array(
				'title' => T_('Photos'),
				'info'  => 'photos',
			) );
		// User info / Audio:
		add_basic_widget( $wico_id, 'user_info', 'core', 60, array(
				'title' => T_('Audio'),
				'info'  => 'audio',
			) );
		// User info / Other files:
		add_basic_widget( $wico_id, 'user_info', 'core', 70, array(
				'title' => T_('Other files'),
				'info'  => 'files',
			) );
		// User info / Spam fighter score:
		add_basic_widget( $wico_id, 'user_info', 'core', 80, array(
				'title' => T_('Spam fighter score'),
				'info'  => 'spam',
			) );
	}


	/* Widget Page Section 1 */
	if( array_key_exists( 'widget_page_section_1', $blog_containers ) )
	{
		$wico_id = $blog_containers['widget_page_section_1']['wico_ID'];
		add_basic_widget( $wico_id, 'coll_featured_posts', 'core', 10 );
	}


	/* Widget Page Section 2 */
	if( array_key_exists( 'widget_page_section_2', $blog_containers ) )
	{
		$wico_id = $blog_containers['widget_page_section_2']['wico_ID'];
		add_basic_widget( $wico_id, 'org_members', 'core', 10 );
	}


	/* Widget Page Section 3 */
	if( array_key_exists( 'widget_page_section_3', $blog_containers ) )
	{
		$wico_id = $blog_containers['widget_page_section_3']['wico_ID'];
		add_basic_widget( $wico_id, 'evo_Gmaps', 'plugin', 10 );
	}


	// Check if there are widgets to create
	if( ! empty( $basic_widgets_insert_sql_rows ) )
	{ // Insert the widget records by single SQL query
		$DB->query( 'INSERT INTO T_widget__widget( wi_wico_ID, wi_order, wi_enabled, wi_type, wi_code, wi_params ) '
		           .'VALUES '.implode( ', ', $basic_widgets_insert_sql_rows ) );
	}
}


/**
 * Get WidgetContainer object from the widget list view widget container fieldset id
 * Note: It is used during creating and reordering widgets
 *
 * @return WidgetContainer
 */
function & get_widget_container( $coll_ID, $container_fieldset_id )
{
	$WidgetContainerCache = & get_WidgetContainerCache();

	if( substr( $container_fieldset_id, 0, 10 ) == 'wico_code_' )
	{ // The widget contianer fieldset id was given by the container code because probably it was not created in the database yet
		$container_code = substr( $container_fieldset_id, 10 );
		$WidgetContainer = $WidgetContainerCache->get_by_coll_and_code( $coll_ID, $container_code );
		if( ! $WidgetContainer )
		{ // The skin container didn't contain any widget before, and it was not saved in the database
			$WidgetContainer = new WidgetContainer();
			$WidgetContainer->set( 'code', $container_code );
			$WidgetContainer->set( 'name', $container_code );
			$WidgetContainer->set( 'coll_ID', $coll_ID );
		}
	}
	elseif( substr( $container_fieldset_id, 0, 8 ) == 'wico_ID_' )
	{ // The widget contianer fieldset id contains the container database ID
		$container_ID = substr( $container_fieldset_id, 8 );
		$WidgetContainer = $WidgetContainerCache->get_by_ID( $container_ID );
	}
	else
	{ // The received fieldset id is not valid
		debug_die( 'Invalid container fieldset id received' );
	}

	return $WidgetContainer;
}


/**
 * Insert shared widget containers
 */
function insert_shared_widgets()
{
	global $Settings, $DB, $basic_widgets_insert_sql_rows;

	// Initialize this array first time and clear after previous call of this function:
	$basic_widgets_insert_sql_rows = array();

	// Declare default shared widget containers:
	$shared_containers = array(
			'site_header'      => array( NT_('Site Header'), 1 ),
			'site_footer'      => array( NT_('Site Footer'), 1 ),
			'main_navigation'  => array( NT_('Main Navigation'), 0 ),
			'right_navigation' => array( NT_('Right Navigation'), 0 ),
		);

	$order = 1;
	foreach( $shared_containers as $container_code => $container_data )
	{
		$widget_containers_sql_rows[] = '( '.$DB->quote( $container_code ).', '
			.$DB->quote( $container_data[0] ).', '
			.'NULL, '
			.$order++.', '
			.$DB->quote( $container_data[1] ).' )';
	}

	// Insert widget containers:
	$DB->query( 'INSERT INTO T_widget__container( wico_code, wico_name, wico_coll_ID, wico_order, wico_main ) VALUES '
		.implode( ', ', $widget_containers_sql_rows ),
		'Insert default shared widget containers' );

	$SQL = new SQL( 'Get all shared widget containers' );
	$SQL->SELECT( 'wico_code, wico_ID' );
	$SQL->FROM( 'T_widget__container' );
	$SQL->WHERE( 'wico_coll_ID IS NULL' );
	$shared_containers = $DB->get_assoc( $SQL );

	/* Site Header */
	if( isset( $shared_containers['site_header'] ) )
	{
		$wico_id = $shared_containers['site_header'];
		add_basic_widget( $wico_id, 'site_logo', 'core', 10 );
		add_basic_widget( $wico_id, 'subcontainer', 'core', 20, array(
				'title'     => T_('Main Navigation'),
				'container' => 'main_navigation',
			) );
		add_basic_widget( $wico_id, 'subcontainer', 'core', 30, array(
				'title'            => T_('Right Navigation'),
				'container'        => 'right_navigation',
				'widget_css_class' => 'floatright',
			) );
	}

	/* Site Footer */
	if( isset( $shared_containers['site_footer'] ) )
	{
		$wico_id = $shared_containers['site_footer'];
		add_basic_widget( $wico_id, 'free_text', 'core', 10, array(
				'content' => T_('Cookies are required to enable core site functionality.'),
			) );
	}

	/* Main Navigation */
	if( isset( $shared_containers['main_navigation'] ) )
	{
		$wico_id = $shared_containers['main_navigation'];
		add_basic_widget( $wico_id, 'colls_list_public', 'core', 10 );
		add_basic_widget( $wico_id, 'basic_menu_link', 'core', 20, array(
				'link_type' => 'aboutsite',
			) );
		add_basic_widget( $wico_id, 'basic_menu_link', 'core', 30, array(
				'link_type' => 'ownercontact',
			) );
		add_basic_widget( $wico_id, 'coll_page_list', 'core', 40, array(
				'item_type' => 9,
			) );
	}

	/* Right Navigation */
	if( isset( $shared_containers['right_navigation'] ) )
	{
		$wico_id = $shared_containers['right_navigation'];
		add_basic_widget( $wico_id, 'basic_menu_link', 'core', 10, array(
				'link_type'        => 'login', 
				'widget_css_class' => 'swhead_item_login',
			) );
		add_basic_widget( $wico_id, 'basic_menu_link', 'core', 20, array(
				'link_type' => 'register',
				'widget_css_class' => 'swhead_item_white',
			) );
		add_basic_widget( $wico_id, 'profile_menu_link', 'core', 30, array(
				'profile_picture_size' => 'crop-top-32x32',
			) );
		add_basic_widget( $wico_id, 'msg_menu_link', 'core', 40 );
		add_basic_widget( $wico_id, 'basic_menu_link', 'core', 50, array(
				'link_type' => 'logout',
			) );
	}

	// Check if there are widgets to create:
	if( ! empty( $basic_widgets_insert_sql_rows ) )
	{	// Insert the widget records by single SQL query:
		$DB->query( 'INSERT INTO T_widget__widget( wi_wico_ID, wi_order, wi_enabled, wi_type, wi_code, wi_params ) '
		           .'VALUES '.implode( ', ', $basic_widgets_insert_sql_rows ) );
	}
}

?>