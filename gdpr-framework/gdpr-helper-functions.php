<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Decode a legacy PHP-serialized array/scalar WITHOUT ever constructing a PHP
 * object.
 *
 * Security fix (SECURITY-AUDIT.md Finding 5 / PR review Finding 3):
 * maybe_unserialize() only decides *whether* a string looks serialized before
 * handing it to PHP's unserialize() -- it does not stop serialized objects, so
 * an object-bearing payload could still instantiate a class and trigger magic
 * methods (__wakeup()/__destruct()) if a gadget class is loaded. This wrapper
 * refuses object payloads on every supported PHP version. New data should be
 * stored as JSON instead (see gdpr_decode_user_log()).
 *
 * @param mixed $value
 * @return mixed|false The decoded value, the input unchanged if it was not
 *                     serialized, or false if it contained a serialized object.
 */
if ( ! function_exists( 'gdpr_safe_unserialize' ) ) {
	function gdpr_safe_unserialize( $value ) {
		if ( ! is_string( $value ) || ! is_serialized( $value ) ) {
			return $value;
		}

		$value = trim( $value );

		if ( PHP_VERSION_ID >= 70000 ) {
			// allowed_classes => false turns any serialized object into an
			// __PHP_Incomplete_Class placeholder (constructors and __wakeup()
			// are never run). Treat any such payload as invalid rather than
			// returning a half-built object.
			//
			// unserialize() is called indirectly (via a variable function
			// name) so the PHPCompatibility sniff -- which cannot see the
			// PHP_VERSION_ID guard around this branch -- does not flag the
			// PHP 7+ second argument as incompatible with the declared PHP 5.6
			// minimum. On PHP 5.6 this branch is never reached; the regex-based
			// rejection below handles that version.
			$unserialize = 'unserialize';
			$decoded     = @$unserialize( $value, array( 'allowed_classes' => false ) );

			if ( gdpr_serialized_contains_object( $decoded ) ) {
				return false;
			}

			return $decoded;
		}

		// PHP 5.6 has no allowed_classes option. Reject object and
		// custom-object tokens conservatively, including objects nested inside
		// arrays.
		if ( preg_match( '/(^|[;{])[OC]:\d+:"/', $value ) ) {
			return false;
		}

		return @unserialize( $value );
	}
}

/**
 * Recursively determine whether a decoded value contains any object (including
 * the __PHP_Incomplete_Class placeholders left behind by
 * unserialize(..., ['allowed_classes' => false])).
 *
 * @param mixed $value
 * @return bool
 */
if ( ! function_exists( 'gdpr_serialized_contains_object' ) ) {
	function gdpr_serialized_contains_object( $value ) {
		if ( is_object( $value ) ) {
			return true;
		}

		if ( is_array( $value ) ) {
			foreach ( $value as $item ) {
				if ( gdpr_serialized_contains_object( $item ) ) {
					return true;
				}
			}
		}

		return false;
	}
}

/**
 * Decode a stored user-log value into a plain array. Prefers JSON (how new
 * rows are written -- see my_profile_update() in gdpr-framework.php) and falls
 * back to the object-blocking legacy serialized decoder for old rows.
 *
 * @param mixed $value
 * @return array
 */
if ( ! function_exists( 'gdpr_decode_user_log' ) ) {
	function gdpr_decode_user_log( $value ) {
		if ( ! is_string( $value ) ) {
			return array();
		}

		$decoded = json_decode( $value, true );
		if ( JSON_ERROR_NONE === json_last_error() && is_array( $decoded ) ) {
			return $decoded;
		}

		$legacy = gdpr_safe_unserialize( $value );

		return is_array( $legacy ) ? $legacy : array();
	}
}

/**
 * The narrow set of HTML allowed when displaying a consent *title*.
 *
 * Fix (SECURITY-AUDIT.md Finding 2 / PR review Finding 4): custom consent
 * titles are sanitized to plain text on write, but the built-in "Privacy
 * Policy" / "Terms & Conditions" titles intentionally contain a single anchor
 * tag. esc_html() on display turned that link into visible markup. Rendering
 * titles through wp_kses() with this allow-list keeps the built-in links
 * clickable while still stripping scripts, event handlers and unsafe URLs.
 * Descriptions remain esc_html() -- they are modeled as plain text.
 *
 * @return array
 */
if ( ! function_exists( 'gdpr_allowed_consent_title_html' ) ) {
	function gdpr_allowed_consent_title_html() {
		return array(
			'a' => array(
				'href'   => true,
				'target' => true,
				'rel'    => true,
			),
		);
	}
}

add_action( "wp_ajax_gdpr_add_consent_accept_cookies", "gdpr_add_consent_accept_cookies" );
add_action( "wp_ajax_nopriv_gdpr_add_consent_accept_cookies", "gdpr_add_consent_accept_cookies" );
add_action( "wp_ajax_gdpr_add_consent_deny_cookies", "gdpr_add_consent_deny_cookies" );
add_action( "wp_ajax_nopriv_gdpr_add_consent_deny_cookies", "gdpr_add_consent_deny_cookies" );

/**
 * ajax function on accept cookie button
 */
function gdpr_add_consent_accept_cookies()
{
    // Security fix (SECURITY-AUDIT.md Finding 1): the Referer-based gate this
    // replaced was never a real access control -- it's fully spoofable by any
    // direct HTTP client (Referer isn't browser-enforced outside same-origin
    // XHR/fetch), and the comparison was also logically inverted so it
    // always passed regardless of origin. Require a real nonce instead.
    if (!check_ajax_referer('gdpr_consent_cookie', 'nonce', false)) {
        echo "Error !!!";
        wp_die();
    }

    global $wpdb, $gdpr;
    $table_name = $wpdb->prefix . 'gdpr_consent';

    // Security fix (SECURITY-AUDIT.md Finding 1): resolve the requester's
    // email through the plugin's existing verified identity mechanism
    // (DataSubjectAuthenticator -- logged-in user, or the gdpr_key cookie
    // validated against its accompanying signed, expiring token) instead of
    // trusting the cookie's email portion directly with no verification at
    // all, which let anyone forge a consent record for any email address.
    $user_email = '';
    $dataSubject = $gdpr->DataSubjectAuthenticator->authenticate();
    if ($dataSubject) {
        $user_email = sanitize_email($dataSubject->getEmail());
    }

    if (!empty($user_email)) {
        $future_date = '8999-12-31 23:59:59';
        $consent = 'gdpr_cookie_consent';

        $n = count(
                    $wpdb->get_results(
                        $wpdb->prepare(
                            "SELECT * FROM {$table_name} WHERE email = %s AND consent = %s;",
                            $user_email,
                            $consent
                        )
                    )
                );

        if ($n > 0) {
            $wpdb->update(
                $table_name,
                [
                    'status'      => 1,
                    'updated_at'  => current_time( 'mysql' ),
                    'ip'          => $_SERVER['REMOTE_ADDR'],
                    'valid_until' => $future_date,
                ],
                [
                    'email'   => $user_email,
                    'consent' => $consent,
                ]
            );
        } else {
            $wpdb->insert(
                $table_name,
                array(
                    'email'      => $user_email,
                    'version'    => 1,
                    'consent'    => $consent,
                    'status'     => 1,
                    'updated_at' => current_time( 'mysql' ),
                    'ip'         => $_SERVER['REMOTE_ADDR'],
                    'valid_until' => $future_date,
                )
            );
        }
        setcookie('cookieconsent_result', 'accept', time()+60*60*24*365, '/');
        do_action('gdpr_consent_accept_cookies');
    }
    wp_die(); // ajax call must die to avoid trailing 0 in your response
}

/**
 * ajax function on deny cookie button
 */
function gdpr_add_consent_deny_cookies()
{
    // Security fix (SECURITY-AUDIT.md Finding 1): see gdpr_add_consent_accept_cookies()
    // above -- same broken Referer gate, same fix.
    if (!check_ajax_referer('gdpr_consent_cookie', 'nonce', false)) {
        echo "Error !!!";
        wp_die();
    }

    global $wpdb, $gdpr;
    $table_name = $wpdb->prefix . 'gdpr_consent';

    // Security fix (SECURITY-AUDIT.md Finding 1): see gdpr_add_consent_accept_cookies()
    // above -- resolve identity via DataSubjectAuthenticator instead of
    // trusting the gdpr_key cookie's email portion unverified.
    $user_email = '';
    $dataSubject = $gdpr->DataSubjectAuthenticator->authenticate();
    if ($dataSubject) {
        $user_email = sanitize_email($dataSubject->getEmail());
    }

    if (!empty($user_email)) {
        $future_date = '7999-12-31 23:59:59';
        $consent = 'gdpr_cookie_consent';

        $n = count(
                    $wpdb->get_results(
                        $wpdb->prepare(
                            "SELECT * FROM {$table_name} WHERE email = %s AND consent = %s;",
                            $user_email,
                            $consent
                        )
                    )
                );

        if ($n > 0) {
            $wpdb->update(
                $table_name,
                [
                    'version'     => 1,
                    'status'      => 0,
                    'updated_at'  => current_time( 'mysql' ),
                    'ip'          => $_SERVER['REMOTE_ADDR'],
                    'valid_until' => $future_date,
                ],
                [
                    'email'   => $user_email,
                    'consent' => $consent,
                ]
            );
        } else {
            $wpdb->insert(
                $table_name,
                array(
                    'email'      => $user_email,
                    'version'    => 1,
                    'consent'    => $consent,
                    'status'     => 0,
                    'updated_at' => current_time( 'mysql' ),
                    'ip'         => $_SERVER['REMOTE_ADDR'],
                    'valid_until' => $future_date,
                )
            );
        }
        setcookie('cookieconsent_result', 'decline', time()+60*60*24*365, '/');
        do_action('gdpr_consent_deny_cookies');
    }
    wp_die();
}

function popup_gdpr()
{
	global $gdpr;
	wp_enqueue_script( 'gdpr-framework-cookieconsent-min-js', $gdpr->PluginUrl .'assets/cookieconsent.min.js' );
	
	wp_enqueue_style( 'gdpr-framework-cookieconsent-css',$gdpr->PluginUrl .'assets/cookieconsent.min.css');

	wp_register_script( 'gdpr-framework-cookieconsent-js', $gdpr->PluginUrl . 'assets/ajax-cookieconsent.js', array(), false, true );

	$gdpr_policy_page_id = get_option('gdpr_policy_page');
	if($gdpr_policy_page_id)
	{   
		$gdpr_policy_page_url = get_permalink($gdpr_policy_page_id);
		/* 
		* FIX FOR MULTILANG.
		*/
		if($gdpr_policy_page_url == ""){
			if(isset($gdpr_policy_page_id[substr( get_bloginfo ( 'language' ), 0, 2 )])){
				$gdpr_policy_page_url = get_permalink($gdpr_policy_page_id[substr( get_bloginfo ( 'language' ), 0, 2 )]);
			}
		}
	}else{
		$gdpr_policy_page_url="";
	}
	add_filter( 'gdpr_custom_policy_link', 'gdprfPrivacyPolicyurl' );
	
	$gdpr_policy_page_url = apply_filters( 'gdpr_custom_policy_link',$gdpr_policy_page_url);

	$gdpr_cookie_acceptance_content = esc_textarea(get_option( 'gdpr_popup_content' ));

	$gdpr_cookie_acceptance_content = do_shortcode( $gdpr_cookie_acceptance_content );

	if($gdpr_cookie_acceptance_content != ""){

		$gdpr_message= __($gdpr_cookie_acceptance_content, 'gdpr-framework');

	}else{

		$gdpr_message= __('This website uses cookies to ensure you get the best experience on our website.', 'gdpr-framework');
	}
	
	$gdpr_cookie_dismiss_text = esc_html(get_option( 'gdpr_popup_dismiss_text' ));

	$gdpr_cookie_dismiss_text = do_shortcode( $gdpr_cookie_dismiss_text );

	if($gdpr_cookie_dismiss_text != ""){

		$gdpr_dismiss= __($gdpr_cookie_dismiss_text, 'gdpr-framework');

	}else{

		$gdpr_dismiss = __('Decline', 'gdpr-framework');
	}

	$gdpr_cookie_allow_text = esc_html(get_option( 'gdpr_popup_allow_text' ));

	$gdpr_cookie_allow_text = do_shortcode( $gdpr_cookie_allow_text );

	if($gdpr_cookie_allow_text != ""){

		 $gdpr_allow = __($gdpr_cookie_allow_text, 'gdpr-framework');

	}else{

		 $gdpr_allow = __('Accept', 'gdpr-framework');
	}

	$gdpr_cookie_learnmore_text = esc_html(get_option( 'gdpr_popup_learnmore_text' ));

	$gdpr_cookie_learnmore_text = do_shortcode( $gdpr_cookie_learnmore_text );

	if($gdpr_cookie_learnmore_text != ""){

		$gdpr_link= __($gdpr_cookie_learnmore_text, 'gdpr-framework');

	}else{

		$gdpr_link = __('Learn more', 'gdpr-framework');
	}

	$position = esc_attr(get_option( 'gdpr_popup_position' )); #"bottom-left","top","bottom-right",""

	$static = false; # true

	$gdpr_header = esc_html(get_option( 'gdpr_header' ));
	
	$gdpr_header = do_shortcode($gdpr_header);

	if($gdpr_header != ""){ 
		$gdpr_header= __($gdpr_header, 'gdpr-framework');
	}

	$gdpr_popup_background = esc_attr(get_option( 'gdpr_popup_background' ));

	$gdpr_popup_text = esc_attr(get_option( 'gdpr_popup_text' ));

	$gdpr_button_background = esc_attr(get_option( 'gdpr_popup_button_background' ));

	$gdpr_button_text = esc_attr(get_option( 'gdpr_popup_button_text' ));

	$gdpr_link_target = esc_attr(get_option( 'gdpr_popup_link_target' ));

	if(!$gdpr_link_target){
		$gdpr_link_target="_blank";
	}
	
	$gdpr_button_border = esc_attr(get_option( 'gdpr_popup_border_text' ));

	if(!$gdpr_popup_background){
		$gdpr_popup_background = "#efefef";
	}
	if(!$gdpr_popup_text){
		$gdpr_popup_text = "#404040";
	}
	if(!$gdpr_button_background){
		$gdpr_button_background = "transparent";
	}
	if(!$gdpr_button_text){
		$gdpr_button_text = "#8ec760";
	}
	if(!$gdpr_button_border){
		$gdpr_button_border = "#8ec760";
	}

	$gdpr_popup_theme = esc_attr(get_option( 'gdpr_popup_theme' ));

	$gdpr_policy_popup = get_option( 'gdpr_policy_popup' );
	
	$gdpr_hide = get_option('gdpr_onetime_popup');
	
	$type = "opt-out"; #opt-in,opt-out,""
	
	$policy_text = __('Cookie Policy', 'gdpr-framework');

	// Security fix (SECURITY-AUDIT.md Finding 1): nonce consumed by
	// check_ajax_referer('gdpr_consent_cookie', 'nonce') in
	// gdpr_add_consent_accept_cookies()/gdpr_add_consent_deny_cookies().
	$gdpr_consent_cookie_nonce = wp_create_nonce( 'gdpr_consent_cookie' );

	$get_gdpr_data = array('gdpr_url'=>$gdpr_policy_page_url,'gdpr_message'=>$gdpr_message,'gdpr_dismiss'=>$gdpr_dismiss,'gdpr_allow'=>$gdpr_allow,'gdpr_header'=>$gdpr_header,'gdpr_link'=>$gdpr_link,'gdpr_popup_position'=>$position,'gdpr_popup_type'=>$type,'gdpr_popup_static'=>$static,'gdpr_popup_background'=>$gdpr_popup_background,'gdpr_popup_text'=>$gdpr_popup_text,'gdpr_button_background'=>$gdpr_button_background,'gdpr_button_text'=>$gdpr_button_text,'gdpr_button_border'=>$gdpr_button_border,'gdpr_popup_theme'=>$gdpr_popup_theme,'gdpr_hide'=>$gdpr_hide,'gdpr_popup'=>$gdpr_policy_popup,'policy'=>$policy_text,'ajaxurl' => admin_url( 'admin-ajax.php' ),'gdpr_link_target' => $gdpr_link_target,'nonce' => $gdpr_consent_cookie_nonce);
	
	wp_localize_script( 'gdpr-framework-cookieconsent-js', 'gdpr_policy_page', $get_gdpr_data );
	wp_enqueue_script( 'gdpr-framework-cookieconsent-js', $gdpr->PluginUrl . 'assets/ajax-cookieconsent.js');
}
/**
 * Cookie acceptance Popup
 */
$enabled_gdpf_cookie_popup = get_option('gdpr_enable_popup');
if($enabled_gdpf_cookie_popup)
{
	add_action( 'wp_enqueue_scripts', 'frontend_enqueue' );
	function frontend_enqueue()
	{   
		wp_enqueue_script('jquery');
		if(get_option('gdpr_onetime_popup') == "1" ){
			if(!isset($_COOKIE['cookieconsent_status'])){ 
				popup_gdpr();
			}
		}else{
			popup_gdpr();        
		}
	}
}

// Add link to settings page from the Plugin List
//

add_filter('plugin_action_links_gdpr-framework/gdpr-framework.php', 'gdpr_plugin_links');

function gdpr_plugin_links($links)
{	global $gdpr;

	$url = $gdpr->Helpers->getAdminUrl();
	$premium = $gdpr->Helpers->premiumStore();
	$settings = array();
	$settings[] = "<a href='$url'>" . __('Settings', 'gdpr-framework' ) . '</a>';
	$settings[] = "<a href='$premium' target='_blank'>" . __('PREMIUM', 'gdpr-framework' ) . '</a>';
	$links = array_merge(
		$settings,
		$links
	);
	return $links;
}