<?php
	/**
	 * This template generates an RSS 2.0 feed for the requested blog's latest comments
	 *
	 * See {@link http://backend.userland.com/rss}
	 *
	 * b2evolution - {@link http://b2evolution.net/}
	 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
	 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
	 *
	 * @package xmlsrv
	 */
	$skin = '';								// We don't want this do be displayed in a skin !
	$disp = 'comments';				// What we want is the latest comments
	$show_statuses = array(); // Restrict to published comments
	/**
	 * Initialize everything:
	 */
	require dirname(__FILE__).'/../b2evocore/_blog_main.php' ;
	$CommentList = & new CommentList( $blog, "'comment'", $show_statuses, '',	'',	'DESC',	'',	20 );
	header("Content-type: application/xml");
	echo "<?xml version=\"1.0\"?".">";
?>
<!-- generator="b2evolution/<?php echo $b2_version ?>" -->
<rss version="2.0" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:admin="http://webns.net/mvcb/" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:content="http://purl.org/rss/1.0/modules/content/">
	<channel>
		<title><?php 
			$Blog->disp( 'name', 'xml' );
			single_cat_title( ' - ', 'xml' ); 
			single_month_title( ' - ', 'xml' );
			single_post_title( ' - ', 'xml' );
			last_comments_title( ' - ', 'xml' ) ;
		?></title>
		<link><?php $Blog->disp( 'lastcommentsurl', 'xml' ) ?></link>
		<description></description>
		<language><?php $Blog->disp( 'locale', 'xml' ) ?></language>
		<docs>http://backend.userland.com/rss</docs>
		<admin:generatorAgent rdf:resource="http://b2evolution.net/?v=<?php echo $b2_version ?>"/>
		<ttl>60</ttl>
		<?php while( $Comment = $CommentList->get_next() )
		{ // Loop through comments: ?>
		<item>
			<title><?php echo format_to_output( T_('In response to:'), 'xml' ) ?> <?php $Comment->Item->title( '', '', false, 'xml' ) ?></title>
			<pubDate><?php $Comment->time( 'r', true ); ?></pubDate>
			<guid isPermaLink="false">c<?php $Comment->ID() ?>@<?php echo $baseurl ?></guid>
			<description><?php $Comment->content( 'xml' ) ?></description>
			<content:encoded><![CDATA[<?php $Comment->content() ?>]]></content:encoded>
			<link><?php $Comment->permalink() ?></link>
		</item>
		<?php } // End of comment loop. ?>
	</channel>
</rss>
<?php log_hit(); // log the hit on this page ?>