<?php
/*
Plugin Name: Shutter Reloaded Plus
Plugin URI: http://www.itinfo.ro/shutter-reloaded-plus/
Description: Added keyboard controls, outside the image click closes the slideshow, image click goes to the next image, bigger, sprite icons, google analytics tracking. Darkens the current page and displays an image on top like Lightbox, Thickbox, etc. However this script is a lot smaller and faster.
Version: 0.5
Author: Danaila Iulian Nicu
Author URI: http://www.itinfo.ro/

Released under the GPL version 2 or newer, http://www.gnu.org/copyleft/gpl.html

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.
*/

function srel_txtdomain() {
	static $srel_load_txtdomain = true;

	if ( $srel_load_txtdomain ) {
		if ( defined('WP_PLUGIN_DIR') )
			load_plugin_textdomain('srel-l10n', '', 'shutter-reloaded/languages');
		else
			load_plugin_textdomain('srel-l10n', ABSPATH . '/' . PLUGINDIR . '/shutter-reloaded/languages');

		$srel_load_txtdomain = false;
	}
}

function srel_makeshutter() {
	global $post, $srel_autoset, $addshutter;

	$options = get_option( 'srel_options', array() );

	if($options['onlyonsingle']){
		if(!is_single() && !is_page()){
			return false;
		}
	}

	$srel_main = get_option( 'srel_main', '' );
	$srel_included = get_option( 'srel_included', array() );
	$srel_excluded = get_option( 'srel_excluded', array() );
	$srel_autoset = $addshutter = false;

	srel_txtdomain();

	switch( $srel_main ) {
		case 'srel_pages' :
			if ( in_array($post->ID, $srel_included) )
				$addshutter = 'shutterReloaded.init();';
			break;
		case 'auto_set' :
			if ( ! in_array($post->ID, $srel_excluded) ) {
				$addshutter = "shutterReloaded.init('sh');";
				$srel_autoset = true;
			}
			break;
		case 'srel_class' :
			$addshutter = "shutterReloaded.init('sh');";
			break;
		case 'srel_lb' :
			$addshutter = "shutterReloaded.init('lb');";
			break;
		default :
			if ( ! in_array($post->ID, $srel_excluded) )
				$addshutter = 'shutterReloaded.init();';
	}

	?>
	<link rel="stylesheet" href="<?php echo plugins_url( 'shutter-reloaded.css', __FILE__ ); ?>" type="text/css" media="screen" />
	<?php

	$css = '';
	if ( $options['btncolor'] != 'cccccc' )
		$css .= "div#shNavBar a {color: #" . $options['btncolor'] . ";}\n";
	if ( $options['menucolor'] != '3e3e3e' )
		$css .= "div#shNavBar {background-color:#" . $options['menucolor'] . ";}\n";
	if ( $options['countcolor'] != '999999' )
		$css .= "div#shNavBar {color:#" . $options['countcolor'] . ";}\n";
	if ( $options['shcolor'] != '000000' || $options['opacity'] != '80' )
		$css .= "div#shShutter{background-color:#" . $options['shcolor'] . ";opacity:" . ($options['opacity']/100) . ";filter:alpha(opacity=" . $options['opacity'] . ");}\n";
	if ( $options['capcolor'] != 'ffffff' )
		$css .= "div#shDisplay div#shTitle {color:#" . $options['capcolor'] . ";}\n";

	if ( !empty($css) )
		echo "<style type='text/css'>\n$css</style>\n";


	if ( !empty($options['headload']) )
		srel_addjs(true);
	else
		add_action('get_footer', 'srel_addjs', 99);
}
add_action('wp_head', 'srel_makeshutter');

function srel_addjs($head = false) {
	global $addshutter;

	$options = get_option( 'srel_options', array() );
	$url = plugin_dir_url( __FILE__ );

	$args = array(
		'imgDir' => $url . '/menu/',
		'imageCount' => !empty($options['imageCount']),
		'FS' => !empty($options['startFull']),
		'textBtns' => !empty($options['textBtns']),
		'oneSet' => !empty($options['oneset']),
		'showfblike' => !empty($options['showfblike'])
	);
	if($options['showfblike']){
	?>
	<div id="fb-root"></div>
	<script>(function(d, s, id) {
	  var js, fjs = d.getElementsByTagName(s)[0];
	  if (d.getElementById(id)) return;
	  js = d.createElement(s); js.id = id;
	  js.src = "//connect.facebook.net/en_US/all.js#xfbml=1&appId=490336411021291";
	  fjs.parentNode.insertBefore(js, fjs);
	}(document, 'script', 'facebook-jssdk'));</script>
	<?php }?>
	<script type="text/javascript">
	var shutterSettings = <?php echo json_encode($args); ?>;
	</script>
	<script src="<?php echo $url; ?>/shutter-reloaded.js" type="text/javascript"></script>
	<script type="text/javascript">
	<?php echo $head ? 'try{shutterAddLoad( function(){' . $addshutter . '} );}catch(e){}' : 'try{' . $addshutter . '}catch(e){}'; ?>
	</script>
	<?php
}

function srel_auto_set($content) {
	global $srel_autoset;

	if ( $srel_autoset )
		return preg_replace_callback('/<a ([^>]+)>/i', 'srel_callback', $content);

	return $content;
}
add_filter('the_content', 'srel_auto_set', 65 );

function srel_callback($a) {
	global $post;
	$str = $a[1];

	if ( preg_match('/href=[\'"][^"\']+\.(?:gif|jpeg|jpg|png)/i', $str) ) {
		if ( false !== strpos(strtolower($str), 'class=') )
			return '<a ' . preg_replace('/(class=[\'"])/i', '$1shutterset_' . $post->ID . ' ', $str) . '>';
		else
			return '<a class="shutterset_' . $post->ID . '" ' . $str . '>';
	}
	return $a[0];
}

function srel_activate() {
	$def = array( 'shcolor' => '000000', 'opacity' => '70', 'capcolor' => 'ffffff', 'menucolor' => '000000', 'btncolor' => 'cccccc', 'countcolor' => '999999', 'headload' => 0, 'oneset' => 1, 'imageCount' => 1, 'textBtns' => 0, 'custom' => 0, 'showfblike' => 1, 'onlyonsingle' => 0 , 'overwritenextgen' => 1 );
	if ( false === get_option('srel_main') )
		update_option('srel_main', '');

	//if ( false === get_option('srel_options') )
		update_option('srel_options', $def);

	if ( false === get_option('srel_included') )
		update_option('srel_included', array());

	if ( false === get_option('srel_excluded') )
		update_option('srel_excluded', array());
}
register_activation_hook( __FILE__, 'srel_activate' );
//add_action('shutter-reloaded-plus/shutter-reloaded.php', 'srel_activate');


function srel_optpage() {
	define('SREL_SETTINGS', true);
	include_once('admin-page.php');
}

function srel_addmenu() {
	if ( function_exists('add_theme_page') ) {
		srel_txtdomain();
		add_theme_page(__('Shutter Reloaded Plus', 'srel-l10n'), __('Shutter Reloaded Plus', 'srel-l10n'), 'manage_options',  'shutter-reloaded', 'srel_optpage');
	}
}
add_action('admin_menu', 'srel_addmenu');