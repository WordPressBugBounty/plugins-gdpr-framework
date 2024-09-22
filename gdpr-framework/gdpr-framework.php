<?php

/**
 * Plugin Name:       The GDPR Framework
 * Plugin URI:        https://www.data443.com/gdpr-framework/
 * Description:       Tools to help make your website GDPR-compliant. Fully documented, extendable and developer-friendly.
 * Author:            Data443
 * Requires at least: 4.7
 * Requires PHP:      5.6
 * Version:           2.1.0
 * Author URI:        https://www.data443.com/
 * Text Domain:       gdpr-framework
 * Domain Path:       /languages
 *
 * @package WordPress
 */

if (!defined('WPINC')) 
{ 
    die;
}

if (defined('GDPR_FRAMEWORK_PRO_VERSION')) {
    function GDPR_admin_notices() {
        ?>
        <div style="clear:both"></div>
        <div class="error iwp" style="padding:10px;">
            The PREMIUM GDPR plugin is already installed. Please deactivate this plugin to continue using the FREE version.
        </div>
        <div style="clear:both"></div>
    <?php }
    add_action('admin_notices', 'GDPR_admin_notices');
    return;
}

define('GDPR_FRAMEWORK_VERSION', '2.1.0');

define('GDPR_DEFAULT_UNKNOWN_USER_MESSAGE', 'Message received.');

add_shortcode( 'gdpr_privacy_safe', 'render_privacy_safe' ); // preserve backward compatibility
add_shortcode( 'data443_privacy_safe', 'render_privacy_safe' );

/**
 * Render WHMCS Seal Generator Addon Javascript
 */
function render_privacy_safe() {
    global $gdpr;
	wp_register_script( 'gdpr_whmcs_seal_generator', $gdpr->PluginUrl . 'assets/js/showseal.js', null, true, true );
	wp_localize_script(
		'gdpr_whmcs_seal_generator',
		'gdpr_seal_var',
		array(
			'gdpr_imageparams'   => esc_attr( get_option( 'gdpr_privacy_safe_params' ) ),
			'gdpr_imagecode'     => esc_attr( get_option( 'gdpr_privacy_safe_imagecode' ) ),
			'gdpr_showimagefunc' => 'showimage_' . esc_attr( get_option( 'gdpr_privacy_safe_imagecode' ) ),
		)
	);
	wp_enqueue_script( 'gdpr_whmcs_seal_generator', basename( dirname( __FILE__ ) ) . 'assets/js/showseal.js', null, true, true );

	$seal_code = '<div class="data443-privacy-safe" style="font-size:12px;text-align: left;">';

	if( get_option( 'gdpr_privacy_safe_imagecode' ) !== '' && get_option( 'gdpr_privacy_safe_params' ) !== '' ){
		$seal_code .= '<a href="javascript:;" onclick="openpopup_' . esc_attr( get_option( 'gdpr_privacy_safe_imagecode' ) ) . '();">
		<img id="data443-privacy-safe-image" src="https://orders.data443.com/seal/seal.php?params=' . esc_attr( get_option( 'gdpr_privacy_safe_params' ) ) . '" alt="Data443 Privacy Safe" />
		</a>';
	}
	if( get_option( 'gdpr_privacy_safe_backlink' ) === '1' ){
		$seal_code .= '<span style="display:block;">Privacy Management Service by <a href="https://data443.com/products/global-privacy-manager/" target="_blank">Data443</a></span>';
	}
	$seal_code .= '</div>';
	// scale the size of the link text based on the loaded scaled image
	$seal_code .= "<script>jQuery('#data443-privacy-safe-image').load(function(){var px='12px';var w=jQuery(this).width();if(w>0&&w<=150){px='6px'}else if(w<300){px='10px'};jQuery('.data443-privacy-safe').css({'font-size': px});})</script>";
	return $seal_code;
}

function gdpr_framework_load_textdomain() 
{
	load_plugin_textdomain('gdpr-framework', false, basename( dirname( __FILE__ ) ) . '/languages/');
}
add_action('init', 'gdpr_framework_load_textdomain');

/**
 * Our custom post type function
 */
function create_custom_post_type()
{
	$args = array(
		'label'               => 'Do Not Sell Info',
		'public'              => true,
		'has_archive'         => false,
		'exclude_from_search' => true,
		'publicly_queryable'  => false,
		'show_in_menu'        => false,
		'menu_position'       => 20,
		'show_ui'             => true,
		'hierarchical'        => false,
		'rewrite'             => array( 'slug' => 'donotsellrequests' ),
		'query_var'           => true,
		'supports'            => array( 'title', 'editor', 'excerpt', 'custom-fields', 'post-formats' ),
	);
	register_post_type( 'donotsellrequests', $args );
}

/**
 * Hooking up our function to theme setup
 */
add_action( 'init', 'create_custom_post_type' );

// Add Columns to our custom posts

add_filter('manage_donotsellrequests_posts_columns', function ($columns) {
    $columns = array_merge($columns, ['donotsell_first_name' => __('First Name', 'textdomain')]);
    $columns = array_merge($columns, ['donotsell_last_name' => __('Last Name', 'textdomain')]);
    $columns = array_merge($columns, ['title' => __('Email', 'textdomain')]);
    $taken_out = $columns['date'];
    unset($columns['date']);
    $columns['date'] = $taken_out;
    return $columns;
});

add_action('manage_donotsellrequests_posts_custom_column', function ($column_key, $post_id) {
    if ($column_key == 'donotsell_first_name') {
        $data = get_post_meta($post_id, 'donotsell_first_name', true);
        _e($data, 'textdomain');
    } elseif ($column_key == 'donotsell_last_name') {
        $data = get_post_meta($post_id, 'donotsell_last_name', true);
        _e($data, 'textdomain');
    }

}, 10, 2);

/**
 * Helper function for prettying up errors
 *
 * @param string $message
 * @param string $subtitle
 * @param string $title
 */
$gdpr_error = function($message, $subtitle = '', $title = '') 
{
    $title = $title ?: _x('WordPress GDPR &rsaquo; Error', '(Admin)', 'gdpr-framework');
    $message = "<h1>{$title}<br><small>{$subtitle}</small></h1><p>{$message}</p>";
    wp_die($message, $title);
};

/**
 * Ensure compatible version of PHP is used
 */
if (version_compare(phpversion(), '5.6.0', '<')) 
{
    $gdpr_error(
        _x('You must be using PHP 5.6.0 or greater.', '(Admin)', 'gdpr-framework'),
        _x('Invalid PHP version', '(Admin)', 'gdpr-framework')
    );
}

/**
 * Ensure compatible version of WordPress is used
 */
if (version_compare(get_bloginfo('version'), '4.3', '<')) 
{
    $gdpr_error(
        _x('You must be using WordPress 4.3.0 or greater.', '(Admin)', 'gdpr-framework'),
        _x('Invalid WordPress version', '(Admin)', 'gdpr-framework')
    );
}

 /**
  * Fix issue with redeclate function issue on DIVI theme.
  */
function TermAndConditionWithPrivacyContent() 
{
    return 'I accept the %sTerms and Conditions%s and the %sPrivacy Policy%s';
}

function gdprfPrivacyPolicy() 
{
    return 'I accept the %sPrivacy Policy%s';
}

function gdprfPrivacyPolicyurl($policypage) 
{
    $policypageURL = get_option( 'gdpr_custom_policy_page' );
    if($policypageURL=="")
    {
        return $policypage;
    }else{
        return $policypageURL;
    }
}

function gdpr_privacy_accpetance($gdpr_error_massage)
{
    return $gdpr_error_massage;
}

/**
 * Save user logs
 */
add_action( 'profile_update', 'my_profile_update', 10, 2 );

function my_profile_update( $user_id, $old_user_data ) 
{
    $data = (array) $old_user_data->data;
   
    $all_meta_for_user = get_user_meta( $user_id );
    if($all_meta_for_user['nickname']['0']){
        $data['nickname'] = $all_meta_for_user['nickname']['0'];
    }
    if($all_meta_for_user['first_name']['0']){
        $data['first_name'] = $all_meta_for_user['first_name']['0'];
    }
    if($all_meta_for_user['last_name']['0']){
        $data['last_name'] = $all_meta_for_user['last_name']['0'];
    }
    if($all_meta_for_user['description']['0']){
        $data['description'] = $all_meta_for_user['description']['0'];
    }
    $userdata = serialize($data);
    $model = new \Codelight\GDPR\Components\Consent\UserConsentModel();
    $model->savelog($user_id,$userdata);
}

include_once(dirname(__FILE__).'/autoload.php');

register_activation_hook(__FILE__, function () {
	if (in_array('gdpr-framework-pro/gdpr-framework.php', apply_filters('active_plugins', get_option('active_plugins')))) {
		die('This plugin could not be activated because the PRO version of this plugin is active.');
	}
	$model = new \Codelight\GDPR\Components\Consent\UserConsentModel();
	$model->createTable();
	$model->createUserTable();
	if (apply_filters('gdpr/data-subject/anonymize/change_role', true) && ! get_role('anonymous')) {

		add_role(
			'anonymous',
			_x('Anonymous', '(Admin)', 'gdpr-framework'),
			array()
		);
	}

	update_option('gdpr_enable_stylesheet', true);
	update_option('gdpr_enable', true);
});

require_once('gdpr-helper-functions.php');
require_once('bootstrap.php');
