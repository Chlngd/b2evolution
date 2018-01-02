<?php
/**
 * This file implements the email campaign class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}.
*
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobject.class.php', 'DataObject' );


/**
 * Email Campaign Class
 *
 * @package evocore
 */
class EmailCampaign extends DataObject
{
	var $date_ts;

	var $enlt_ID;

	var $email_title;

	var $email_html;

	var $email_text;

	var $email_plaintext;

	var $sent_ts;

	var $auto_sent_ts;

	var $use_wysiwyg = 0;

	var $send_ctsk_ID;

	var $auto_send = 'no';

	var $sequence;

	var $Newsletter = NULL;

	/**
	 * @var array|NULL User IDs which assigned for this email campaign
	 *   'all'     - All active users which accept newsletter of this campaign
	 *   'filter'  - Filtered active users which accept newsletter of this campaign
	 *   'receive' - Users which already received email newsletter
	 *   'wait'    - Users which still didn't receive email by some reason (Probably their newsletter limit was full)
	 */
	var $users = NULL;

	/**
	 * @var string
	 */
	var $renderers;

	/**
	 * Constructor
	 *
	 * @param object table Database row
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( 'T_email__campaign', 'ecmp_', 'ecmp_ID', 'date_ts' );

		if( $db_row == NULL )
		{
			$this->set_renderers( array( 'default' ) );
		}
		else
		{
			$this->ID = $db_row->ecmp_ID;
			$this->date_ts = $db_row->ecmp_date_ts;
			$this->enlt_ID = $db_row->ecmp_enlt_ID;
			$this->email_title = $db_row->ecmp_email_title;
			$this->email_html = $db_row->ecmp_email_html;
			$this->email_text = $db_row->ecmp_email_text;
			$this->email_plaintext = $db_row->ecmp_email_plaintext;
			$this->sent_ts = $db_row->ecmp_sent_ts;
			$this->auto_sent_ts = $db_row->ecmp_auto_sent_ts;
			$this->renderers = $db_row->ecmp_renderers;
			$this->use_wysiwyg = $db_row->ecmp_use_wysiwyg;
			$this->send_ctsk_ID = $db_row->ecmp_send_ctsk_ID;
			$this->auto_send = $db_row->ecmp_auto_send;
			$this->sequence = $db_row->ecmp_sequence;
		}
	}


	/**
	 * Get delete cascade settings
	 *
	 * @return array
	 */
	static function get_delete_cascades()
	{
		return array(
				array( 'table'=>'T_email__campaign_send', 'fk'=>'csnd_camp_ID', 'msg'=>T_('%d links with users') ),
				array( 'table'=>'T_links', 'fk'=>'link_ecmp_ID', 'msg'=>T_('%d links to destination email campaigns'),
						'class'=>'Link', 'class_path'=>'links/model/_link.class.php' ),
			);
	}


	/**
	 * Add recipients for this campaign into DB
	 *
	 * @param array|NULL Array of user IDs, NULL - to get user IDs from current filterset of users list
	 */
	function add_recipients( $filtered_users_IDs = NULL )
	{
		global $DB;

		if( $filtered_users_IDs === NULL )
		{	// Get user IDs from current filterset of users list:
			$filtered_users_IDs = get_filterset_user_IDs();
		}

		if( count( $filtered_users_IDs ) )
		{	// If users are found in the filterset

			// Get all active users which accept email newsletter of this campaign:
			$new_users_SQL = new SQL( 'Get recipients of newsletter #'.$this->get( 'enlt_ID' ) );
			$new_users_SQL->SELECT( 'user_ID' );
			$new_users_SQL->FROM( 'T_users' );
			$new_users_SQL->FROM_add( 'INNER JOIN T_email__newsletter_subscription ON enls_user_ID = user_ID AND enls_subscribed = 1' );
			$new_users_SQL->WHERE( 'user_ID IN ( '.$DB->quote( $filtered_users_IDs ).' )' );
			$new_users_SQL->WHERE_and( 'user_status IN ( "activated", "autoactivated" )' );
			$new_users_SQL->WHERE_and( 'enls_enlt_ID = '.$DB->quote( $this->get( 'enlt_ID' ) ) );
			$new_users = $DB->get_col( $new_users_SQL->get(), 0, $new_users_SQL->title );

			// Remove the filtered recipients which didn't receive email newsletter yet:
			$this->remove_recipients();

			// Get users which already received email newsletter:
			$old_users = $this->get_recipients( 'receive' );

			// Exclude old users from new users (To store value of csnd_emlog_ID):
			$new_users = array_diff( $new_users, $old_users );

			if( count( $new_users ) )
			{	// Insert new users for this campaign:
				$insert_SQL = 'INSERT INTO T_email__campaign_send ( csnd_camp_ID, csnd_user_ID ) VALUES';
				foreach( $new_users as $user_ID )
				{
					$insert_SQL .= "\n".'( '.$DB->quote( $this->ID ).', '.$DB->quote( $user_ID ).' ),';
				}
				$DB->query( substr( $insert_SQL, 0, -1 ) );
			}
		}
	}


	/**
	 * Remove the filtered recipients which didn't receive email newsletter yet
	 */
	function remove_recipients()
	{
		if( empty( $this->ID ) )
		{	// Email campaign must be created in DB:
			return;
		}

		global $DB;

		$DB->query( 'DELETE FROM T_email__campaign_send
			WHERE csnd_camp_ID = '.$DB->quote( $this->ID ).'
			  AND csnd_emlog_ID IS NULL' );
	}


	/**
	 * Get a member param by its name
	 *
	 * @param mixed Name of parameter
	 * @return mixed Value of parameter
	 */
	function get( $parname )
	{
		switch( $parname )
		{
			case 'name':
				if( $Newsletter = & $this->get_Newsletter() )
				{	// Get name of newsletter:
					return $Newsletter->get( 'name' );
				}
				else
				{	// Get email title of this campaign:
					return $this->get( 'email_title' );
				}
				break;

			default:
				return parent::get( $parname );
		}
	}


	/**
	 * Get Newsletter object of this email campaign
	 *
	 * @return object Newsletter
	 */
	function & get_Newsletter()
	{
		if( ! isset( $this->Newsletter ) )
		{	// Initialize Newsletter:
			$NewsletterCache = & get_NewsletterCache();
			$this->Newsletter = & $NewsletterCache->get_by_ID( $this->get( 'enlt_ID', false, false ) );
		}

		return $this->Newsletter;
	}


	/**
	 * Get recipient user IDs of this campaign
	 *
	 * @param string Type of users:
	 *   'all'     - All active users which accept newsletter of this campaign
	 *   'filter'  - Filtered active users which accept newsletter of this campaign
	 *   'receive' - Users which already received email newsletter
	 *   'wait'    - Users which still didn't receive email by some reason (Probably their newsletter limit was full)
	 * @return array user IDs
	 */
	function get_recipients( $type = 'all' )
	{
		global $DB;

		if( ! is_null( $this->users ) )
		{	// Get users from cache:
			return $this->users[ $type ];
		}

		// Get users from DB:
		$users_SQL = new SQL( 'Get recipients of campaign #'.$this->ID );
		$users_SQL->SELECT( 'user_ID, csnd_emlog_ID, csnd_user_ID, enls_user_ID' );
		$users_SQL->FROM( 'T_users' );
		$users_SQL->FROM_add( 'INNER JOIN T_email__campaign_send ON ( csnd_camp_ID = '.$DB->quote( $this->ID ).' AND ( csnd_user_ID = user_ID OR csnd_user_ID IS NULL ) )' );
		$users_SQL->FROM_add( 'LEFT JOIN T_email__newsletter_subscription ON enls_user_ID = user_ID AND enls_subscribed = 1 AND enls_enlt_ID = '.$DB->quote( $this->get( 'enlt_ID' ) ) );
		$users_SQL->WHERE( 'user_status IN ( "activated", "autoactivated" )' );
		$users = $DB->get_results( $users_SQL->get(), OBJECT, $users_SQL->title );

		$this->users['all'] = array();
		$this->users['filter'] = array();
		$this->users['receive'] = array();
		$this->users['wait'] = array();
		$this->users['unsub_all'] = array();
		$this->users['unsub_filter'] = array();
		$this->users['unsub_receive'] = array();
		$this->users['unsub_wait'] = array();

		foreach( $users as $user_data )
		{
			if( $user_data->enls_user_ID === NULL )
			{	// This user is unsubscribed from newsletter of this email campaign:
				$this->users['unsub_all'][] = $user_data->user_ID;
			}
			else
			{	// This user is subscribed to newsletter of this email campaign:
				$this->users['all'][] = $user_data->user_ID;
			}
			if( $user_data->csnd_emlog_ID > 0 )
			{	// This user already received newsletter email:
				if( $user_data->enls_user_ID === NULL )
				{	// This user is unsubscribed from newsletter of this email campaign:
					$this->users['unsub_receive'][] = $user_data->user_ID;
					$this->users['unsub_filter'][] = $user_data->user_ID;
				}
				else
				{	// This user is subscribed to newsletter of this email campaign:
					$this->users['receive'][] = $user_data->user_ID;
					$this->users['filter'][] = $user_data->user_ID;
				}
			}
			elseif( $user_data->csnd_user_ID > 0 )
			{	// This user didn't receive email yet:
				if( $user_data->enls_user_ID === NULL )
				{	// This user is unsubscribed from newsletter of this email campaign:
					$this->users['unsub_wait'][] = $user_data->user_ID;
					$this->users['unsub_filter'][] = $user_data->user_ID;
				}
				else
				{	// This user is subscribed to newsletter of this email campaign:
					$this->users['wait'][] = $user_data->user_ID;
					$this->users['filter'][] = $user_data->user_ID;
				}
			}
		}

		return $this->users[ $type ];
	}


	/**
	 * Get the recipients number of this campaign
	 *
	 * @param string Type of users:
	 *   'all'     - All active users which accept newsletter of this campaign
	 *   'filter'  - Filtered active users which accept newsletter of this campaign
	 *   'receive' - Users which already received email newsletter
	 *   'wait'    - Users which still didn't receive email by some reason (Probably their newsletter limit was full)
	 * @param boolean TRUE to return as link to page with recipients list
	 * @return integer Number of users
	 */
	function get_recipients_count( $type = 'all', $link = false )
	{
		$recipients_count = count( $this->get_recipients( $type ) );

		if( $link )
		{	// Initialize URL to page with reciepients of this Email Campaign:
			$campaign_edit_modes = get_campaign_edit_modes( $this->ID );
			switch( $type )
			{
				case 'receive':
					$recipient_type = 'sent';
					break;
				case 'wait':
					$recipient_type = 'readytosend';
					break;
				case 'filter':
				default:
					$recipient_type = 'filtered';
					break;
			}

			$unsub_recipients_count = count( $this->get_recipients( 'unsub_'.$type ) );
			if( $unsub_recipients_count > 0 )
			{	// If unsubscribed users exist:
				$recipients_count = $recipients_count.' ('.T_('still subscribed').') + '.$unsub_recipients_count.' ('.T_('unsubscribed').')';
			}
			$recipients_count = '<a href="'.$campaign_edit_modes['recipient']['href'].( empty( $type ) ? '' : '&amp;recipient_type='.$recipient_type ).'">'.$recipients_count.'</a>';
		}

		return $recipients_count;
	}


	/**
	 * Insert object into DB based on previously recorded changes.
	 *
	 * @return boolean true
	 */
	function dbinsert()
	{
		// Update the message fields:
		$this->update_message_fields();

		$r = parent::dbinsert();

		// Update recipients:
		$this->update_recipients();

		return $r;
	}


	/**
	 * Update the DB based on previously recorded changes
	 *
	 * @return boolean true on success, false on failure to update, NULL if no update necessary
	 */
	function dbupdate()
	{
		// Update the message fields:
		$this->update_message_fields();

		$r = parent::dbupdate();

		// Update recipients only if newsletter has been changed:
		$this->update_recipients();

		return $r;
	}


	/**
	 * Update the message fields:
	 *     - email_html - Result of the rendered plugins from email_text
	 *     - email_plaintext - Text extraction from email_html
	 */
	function update_message_fields()
	{
		global $Plugins;

		$email_text = $this->get( 'email_text' );

		// Render inline file tags like [image:123:caption] or [file:123:caption] :
		$email_text = render_inline_files( $email_text, $this, array(
				'check_code_block' => true,
				'image_size'       => 'original',
			) );

		// This must get triggered before any internal validation and must pass all relevant params.
		$Plugins->trigger_event( 'EmailFormSent', array(
				'content'         => & $email_text,
				'dont_remove_pre' => true,
				'renderers'       => $this->get_renderers_validated(),
			) );

		// Save prerendered message:
		$Plugins->trigger_event( 'FilterEmailContent', array(
				'data'          => & $email_text,
				'EmailCampaign' => $this
			) );
		$this->set( 'email_html', format_to_output( $email_text ) );

		// Save plain-text message:
		$email_plaintext = preg_replace( '#<a[^>]+href="([^"]+)"[^>]*>[^<]*</a>#i', ' [ $1 ] ', $this->get( 'email_html' ) );
		$email_plaintext = preg_replace( '#<img[^>]+src="([^"]+)"[^>]*>#i', ' [ $1 ] ', $email_plaintext );
		$email_plaintext = preg_replace( '#[\n\r]#i', ' ', $email_plaintext );
		$email_plaintext = preg_replace( '#<(p|/h[1-6]|ul|ol)[^>]*>#i', "\n\n", $email_plaintext );
		$email_plaintext = preg_replace( '#<(br|h[1-6]|/li|code|pre|div|/?blockquote)[^>]*>#i', "\n", $email_plaintext );
		$email_plaintext = preg_replace( '#<li[^>]*>#i', "- ", $email_plaintext );
		$email_plaintext = preg_replace( '#<hr ?/?>#i', "\n\n----------------\n\n", $email_plaintext );
		$this->set( 'email_plaintext', strip_tags( $email_plaintext ) );
	}


	/**
	 * Update recipients after newsletter of this email campaign was changed
	 *
	 * @param boolean TRUE to force the updating
	 */
	function update_recipients( $force_update = false )
	{
		if( empty( $this->ID ) )
		{	// Email campaign must be created in DB:
			return;
		}

		if( ! $force_update && empty( $this->newsletter_is_changed ) )
		{	// Newsletter of this email campaign was not changed, Don't update recipients:
			return;
		}

		global $DB;

		// Remove the filtered recipients of previous newsletter which didn't receive it yet:
		$this->remove_recipients();

		// Insert recipients of current newsletter:
		$DB->query( 'INSERT INTO T_email__campaign_send ( csnd_camp_ID, csnd_user_ID )
			SELECT '.$this->ID.', enls_user_ID
			  FROM T_email__newsletter_subscription
			 WHERE enls_enlt_ID = '.$this->get( 'enlt_ID' ).'
				 AND enls_subscribed = 1
			 ON DUPLICATE KEY UPDATE csnd_camp_ID = csnd_camp_id, csnd_user_ID = csnd_user_ID' );
	}


	/**
	 * Load data from Request form fields.
	 *
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request()
	{
		global $Plugins;

		if( param( 'ecmp_enlt_ID', 'integer', NULL ) !== NULL )
		{	// Newsletter ID:
			param_string_not_empty( 'ecmp_enlt_ID', T_('Please select a newsletter.') );
			$this->newsletter_is_changed = ( get_param( 'ecmp_enlt_ID' ) != $this->get( 'enlt_ID' ) );
			$this->set_from_Request( 'enlt_ID' );
		}

		if( param( 'ecmp_email_title', 'string', NULL ) !== NULL )
		{	// Email title:
			param_string_not_empty( 'ecmp_email_title', T_('Please enter an email title.') );
			$this->set_from_Request( 'email_title' );
		}

		if( param( 'ecmp_email_html', 'html', NULL ) !== NULL )
		{	// Email HTML message:
			param_check_html( 'ecmp_email_html', T_('Please enter an HTML message.') );
			$this->set_from_Request( 'email_html' );
		}

		// Renderers:
		if( param( 'renderers_displayed', 'integer', 0 ) )
		{	// use "renderers" value only if it has been displayed (may be empty):
			$renderers = $Plugins->validate_renderer_list( param( 'renderers', 'array:string', array() ), array( 'EmailCampaign' => & $this ) );
			$this->set_renderers( $renderers );
		}

		if( param( 'ecmp_email_text', 'html', NULL ) !== NULL )
		{	// Save original message:
			$this->set_from_Request( 'email_text' );
		}

		if( param( 'ecmp_auto_send', 'string', NULL ) !== NULL )
		{	// Auto send:
			$this->set_from_Request( 'auto_send' );
			if( $this->get( 'auto_send' ) == 'sequence' )
			{	// Day in sequence:
				param( 'ecmp_sequence', 'integer', NULL );
				$this->set_from_Request( 'sequence', NULL, true );
			}
		}

		return ! param_errors_detected();
	}


	/**
	 * Check if campaign are ready to send emails
	 *
	 * @param boolean TRUE to display messages about empty fields
	 * @param string Mode: 'test' - used to don't check some fields
	 * @return boolean TRUE if all fields are filled
	 */
	function check( $display_messages = true, $mode = '' )
	{
		if( $display_messages )
		{ // Display message
			global $Messages;
		}

		$result = true;

		if( empty( $this->email_title ) )
		{ // Email title is empty
			if( $display_messages )
			{
				$Messages->add_to_group( T_('Please enter an email title for this campaign.'), 'error', T_('Validation errors:') );
			}
			$result = false;
		}

		if( empty( $this->email_text ) )
		{	// Email message is empty:
			if( $display_messages )
			{
				$Messages->add_to_group( T_('Please enter the email text for this campaign.'), 'error', T_('Validation errors:') );
			}
			$result = false;
		}

		if( $mode != 'test' && count( $this->get_recipients( 'wait' ) ) == 0 )
		{ // No users found which wait this newsletter
			if( $display_messages )
			{
				$Messages->add_to_group( T_('No recipients found for this campaign.'), 'error', T_('Validation errors:') );
			}
			$result = false;
		}

		return $result;
	}


	/**
	 * Send one email
	 *
	 * @param integer User ID
	 * @param string Email address
	 * @param string Mode: 'test' - to send test email newsletter
	 * @return boolean TRUE on success
	 */
	function send_email( $user_ID, $email_address = '', $mode = '' )
	{
		$newsletter_params = array(
				'include_greeting' => false,
				'message_html'     => $this->get( 'email_html' ),
				'message_text'     => $this->get( 'email_plaintext' ),
				'newsletter'       => $this->get( 'enlt_ID' ),
			);

		if( $mode == 'test' )
		{ // Send a test newsletter
			global $current_User;

			$newsletter_params['boundary'] = 'b2evo-'.md5( rand() );
			$headers = array( 'Content-Type' => 'multipart/mixed; boundary="'.$newsletter_params['boundary'].'"' );

			$UserCache = & get_UserCache();
			if( $test_User = & $UserCache->get_by_ID( $user_ID, false, false ) )
			{ // Send a test email only when test user exists
				$message = mail_template( 'newsletter', 'auto', $newsletter_params, $test_User );
				return send_mail( $email_address, NULL, $this->get( 'email_title' ), $message, NULL, NULL, $headers );
			}
			else
			{ // No test user found
				return false;
			}
		}
		else
		{	// Send a newsletter to real user:
			// Force email sending to not activated users if email campaign is configurated to auto sending (e-g to send email on auto subscription on registration):
			$force_on_non_activated = in_array( $this->get( 'auto_send' ), array( 'subscription', 'sequence' ) );
			$r = send_mail_to_User( $user_ID, $this->get( 'email_title' ), 'newsletter', $newsletter_params, $force_on_non_activated, array(), $email_address );
			if( $r )
			{	// Update last sending data for newsletter per user:
				global $DB, $servertimenow;
				$DB->query( 'UPDATE T_email__newsletter_subscription
					SET enls_last_sent_manual_ts = '.$DB->quote( date2mysql( $servertimenow ) ).',
					    enls_send_count = enls_send_count + 1
					WHERE enls_user_ID = '.$DB->quote( $user_ID ).'
					  AND enls_enlt_ID = '.$DB->quote( $this->get( 'enlt_ID' ) ) );
			}
			return $r;
		}
	}


	/**
	 * Send email newsletter for all users of this campaign
	 *
	 * @param boolean TRUE to print out messages
	 * @param array Force users instead of users which are ready to receive this email campaign
	 */
	function send_all_emails( $display_messages = true, $user_IDs = NULL )
	{
		global $DB, $localtimenow, $mail_log_insert_ID, $Settings, $Messages;

		if( $user_IDs === NULL )
		{	// Send emails only for users which still don't receive emails:
			$user_IDs = $this->get_recipients( 'wait' );
		}
		else
		{	// Exclude users which already received this email campaign to avoid double sending even with forcing user IDs:
			$receive_user_IDs = $this->get_recipients( 'receive' );
			$user_IDs = array_diff( $user_IDs, $receive_user_IDs );
		}

		if( empty( $user_IDs ) )
		{	// No users, Exit here:
			return;
		}

		$DB->begin();

		// Update date of sending
		$this->set( 'sent_ts', date( 'Y-m-d H:i:s', $localtimenow ) );
		$this->dbupdate();

		$UserCache = & get_UserCache();

		// Get chunk size to limit a sending at a time:
		$email_campaign_chunk_size = intval( $Settings->get( 'email_campaign_chunk_size' ) );

		$email_success_count = 0;
		$email_skip_count = 0;
		foreach( $user_IDs as $user_ID )
		{
			if( $email_campaign_chunk_size > 0 && $email_success_count >= $email_campaign_chunk_size )
			{	// Stop the sending because of chunk size:
				break;
			}

			if( ! ( $User = & $UserCache->get_by_ID( $user_ID, false, false ) ) )
			{	// Skip wrong recipient user:
				continue;
			}

			// Send email to user:
			$result = $this->send_email( $user_ID );

			if( empty( $mail_log_insert_ID ) )
			{	// ID of last inserted mail log is defined in function mail_log()
				// If it was not inserted we cannot mark this user as received this newsletter:
				$result = false;
			}

			if( $result )
			{	// Email newsletter was sent for user successfully:
				$DB->query( 'REPLACE INTO T_email__campaign_send ( csnd_camp_ID, csnd_user_ID, csnd_emlog_ID )
					VALUES ( '.$DB->quote( $this->ID ).', '.$DB->quote( $user_ID ).', '.$DB->quote( $mail_log_insert_ID ).' )' );

				// Update arrays where we store which users received email and who waiting it now:
				$this->users['receive'][] = $user_ID;
				if( ( $wait_user_ID_key = array_search( $user_ID, $this->users['wait'] ) ) !== false )
				{
					unset( $this->users['wait'][ $wait_user_ID_key ] );
				}
				$email_success_count++;
			}
			else
			{	// This email sending was skipped:
				$email_skip_count++;
			}

			if( $display_messages )
			{	// Print the messages:
				if( $result === true )
				{ // Success
					echo sprintf( T_('Email was sent to user: %s'), $User->get_identity_link() ).'<br />';
				}
				else
				{ // Failed, Email was NOT sent
					if( ! check_allow_new_email( 'newsletter_limit', 'last_newsletter', $user_ID ) )
					{ // Newsletter email is limited today for this user
						echo '<span class="orange">'.sprintf( T_('User %s has already received max # of newsletters today.'), $User->get_identity_link() ).'</span><br />';
					}
					else
					{ // Another error
						echo '<span class="red">'.sprintf( T_('Email was not sent to user: %s'), $User->get_identity_link() ).'</span><br />';
					}
				}

				evo_flush();
			}
		}

		$DB->commit();

		if( $display_messages )
		{	// Print the messages:
			$Messages->clear();
			$wait_count = count( $this->users['wait'] );
			if( $wait_count > 0 )
			{	// Some recipients still wait this newsletter:
				$Messages->add( sprintf( T_('Emails have been sent to a chunk of %s recipients. %s recipients were skipped. %s recipients have not been sent to yet.'),
						$email_campaign_chunk_size, $email_skip_count, $wait_count ), 'warning' );
			}
			else
			{	// All recipients received this bewsletter:
				$Messages->add( T_('Emails have been sent to all recipients of this campaign.'), 'success' );
			}
			echo '<br />';
			$Messages->display();
		}
	}


	/**
	 * Get the list of validated renderers for this EmailCampaign. This includes stealth plugins etc.
	 * @return array List of validated renderer codes
	 */
	function get_renderers_validated()
	{
		if( ! isset( $this->renderers_validated ) )
		{
			global $Plugins;
			$this->renderers_validated = $Plugins->validate_renderer_list( $this->get_renderers(), array( 'EmailCampaign' => & $this ) );
		}
		return $this->renderers_validated;
	}


	/**
	 * Get the list of renderers for this Message.
	 * @return array
	 */
	function get_renderers()
	{
		return explode( '.', $this->renderers );
	}


	/**
	 * Set the renderers of the Message.
	 *
	 * @param array List of renderer codes.
	 * @return boolean true, if it has been set; false if it has not changed
	 */
	function set_renderers( $renderers )
	{
		return $this->set_param( 'renderers', 'string', implode( '.', $renderers ) );
	}


	/**
	 * Get current Cronjob of this email campaign
	 *
	 * @return object Cronjob
	 */
	function & get_Cronjob()
	{
		$CronjobCache = & get_CronjobCache();

		$Cronjob = & $CronjobCache->get_by_ID( $this->get( 'send_ctsk_ID' ), false, false );

		return $Cronjob;
	}


	/**
	 * Create a scheduled job to send newsletters of this email campaign
	 *
	 * @param boolean TRUE if cron job should be created to send next chunk of waiting users, FALSE - to create first cron job
	 */
	function create_cron_job( $next_chunk = false )
	{
		global $Messages, $servertimenow, $current_User;

		if( ! $next_chunk && ( $email_campaign_Cronjob = & $this->get_Cronjob() ) )
		{	// If we create first cron job but this email campaign already has one:
			if( $current_User->check_perm( 'options', 'view' ) )
			{	// If user has an access to view cron jobs:
				global $admin_url;
				$Messages->add( sprintf( T_('A scheduled job was already created for this campaign, <a %s>click here</a> to view it.'),
					'href="'.$admin_url.'?ctrl=crontab&amp;action=view&amp;cjob_ID='.$email_campaign_Cronjob->ID.'" target="_blank"' ), 'error' );
			}
			else
			{	// If user has no access to view cron jobs:
				$Messages->add( T_('A scheduled job was already created for this campaign.'), 'error' );
			}

			return false;
		}

		if( $this->get_recipients_count( 'wait' ) > 0 )
		{	// Create cron job only when at least one user is waiting a newsletter of this email campaing:
			load_class( '/cron/model/_cronjob.class.php', 'Cronjob' );
			$email_campaign_Cronjob = new Cronjob();

			$start_datetime = $servertimenow;
			if( $next_chunk )
			{	// Send next chunk only after delay:
				global $Settings;
				$start_datetime += $Settings->get( 'email_campaign_cron_repeat' );
			}
			$email_campaign_Cronjob->set( 'start_datetime', date2mysql( $start_datetime ) );

			// no repeat.

			// key:
			$email_campaign_Cronjob->set( 'key', 'send-email-campaign' );

			// params: specify which post this job is supposed to send notifications for:
			$email_campaign_Cronjob->set( 'params', array(
					'ecmp_ID' => $this->ID,
				) );

			// Save cronjob to DB:
			$r = $email_campaign_Cronjob->dbinsert();

			if( ! $r )
			{	// Error on cron job inserting:
				return false;
			}

			// Memorize the cron job ID which is going to handle this email campaign:
			$this->set( 'send_ctsk_ID', $email_campaign_Cronjob->ID );

			$Messages->add( T_('A scheduled job has been created for this campaign.'), 'success' );
		}
		else
		{	// If no waiting users then don't create a cron job and reset ID of previous cron job:
			$this->set( 'send_ctsk_ID', NULL, true );

			$Messages->add( T_('No scheduled job has been created for this campaign because it has no waiting recipients.'), 'warning' );
		}

		// Update the changed email campaing settings:
		$this->dbupdate();

		return true;
	}


	/**
	 * Get title of sending method
	 *
	 * @return string
	 */
	function get_sending_title()
	{
		$titles = array(
				'no'           => T_('Manual'),
				'subscription' => T_('At subscription'),
				'sequence'     => T_('Sequence'),
			);

		if( isset( $titles[ $this->get( 'auto_send' ) ] ) )
		{
			return $titles[ $this->get( 'auto_send' ) ]
				.( $this->get( 'auto_send' ) == 'sequence' ? ': '.$this->get( 'sequence' ) : '' );
		}

		// Unknown sending method
		return $this->get( 'auto_send' );
	}
}

?>