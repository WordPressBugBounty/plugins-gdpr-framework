<?php 

add_action( "wp_ajax_gdpr_add_consent_accept_cookies", "gdpr_add_consent_accept_cookies" );
add_action( "wp_ajax_nopriv_gdpr_add_consent_accept_cookies", "gdpr_add_consent_accept_cookies" );
add_action( "wp_ajax_gdpr_add_consent_deny_cookies", "gdpr_add_consent_deny_cookies" );
add_action( "wp_ajax_nopriv_gdpr_add_consent_deny_cookies", "gdpr_add_consent_deny_cookies" );

/**
 * ajax function on accept cookie button
 */
function gdpr_add_consent_accept_cookies()
{
    $referer = $_SERVER['HTTP_REFERER'];
    $address = $_SERVER['SERVER_NAME'];
    if ($referer) {
        if (strpos($address, $referer) !== 0) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'gdpr_consent';
            $current_user = wp_get_current_user();
            $user_email = sanitize_email($current_user->user_email);
            if ($user_email=="" && isset($_COOKIE['gdpr_key'])) {
                $email = explode("|",$_COOKIE['gdpr_key']);
                $user_email = sanitize_email($email['0']);
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
        } else {
            echo "Error !!!";
            wp_die();
        }
    } else {
        echo "ERROR !!";
        wp_die();
    }
}

/**
 * ajax function on deny cookie button
 */
function gdpr_add_consent_deny_cookies()
{
    $referer = $_SERVER['HTTP_REFERER'];
    $address = $_SERVER['SERVER_NAME'];
    if ($referer){
        if (strpos($address, $referer) !== 0) {
            global $wpdb;
            $table_name    = $wpdb->prefix . 'gdpr_consent';
            $current_user = wp_get_current_user();
            $user_email   = sanitize_email( $current_user->user_email );
            if ( '' == $user_email && isset( $_COOKIE['gdpr_key'] ) ) {
                $email      = explode( '|', sanitize_text_field( wp_unslash( $_COOKIE['gdpr_key'] ) ) );
                $user_email = sanitize_email( $email['0'] );
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
        } else {
            echo "Error !!!";
            wp_die();
        }
    } else {
        echo "ERROR !!";
        wp_die();
    }
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

	$gdpr_cookie_acceptance_content_url = get_option( 'gdpr_popup_content' );

	$gdpr_cookie_acceptance_content_url = do_shortcode( $gdpr_cookie_acceptance_content_url );

	if($gdpr_cookie_acceptance_content_url != ""){ 

		$gdpr_message= __($gdpr_cookie_acceptance_content_url, 'gdpr-framework');

	}else{

		$gdpr_message= __('This website uses cookies to ensure you get the best experience on our website.', 'gdpr-framework');
	}
	
	$gdpr_cookie_dismiss_text_url = get_option( 'gdpr_popup_dismiss_text' );

	$gdpr_cookie_dismiss_text_url = do_shortcode( $gdpr_cookie_dismiss_text_url );

	if($gdpr_cookie_dismiss_text_url != ""){ 

		$gdpr_dismiss= __($gdpr_cookie_dismiss_text_url, 'gdpr-framework');

	}else{

		$gdpr_dismiss = __('Decline', 'gdpr-framework');
	}

	$gdpr_cookie_allow_text_url = get_option( 'gdpr_popup_allow_text' );

	$gdpr_cookie_allow_text_url = do_shortcode( $gdpr_cookie_allow_text_url );

	if($gdpr_cookie_dismiss_text_url != ""){ 

		 $gdpr_allow = __($gdpr_cookie_allow_text_url, 'gdpr-framework');

	}else{

		 $gdpr_allow = __('Accept', 'gdpr-framework');
	}

	$gdpr_cookie_learnmore_text_url = get_option( 'gdpr_popup_learnmore_text' );

	$gdpr_cookie_learnmore_text_url = do_shortcode( $gdpr_cookie_learnmore_text_url );

	if($gdpr_cookie_learnmore_text_url != ""){ 

		$gdpr_link= __($gdpr_cookie_learnmore_text_url, 'gdpr-framework');

	}else{

		$gdpr_link = __('Learn more', 'gdpr-framework');
	}

	$position = get_option( 'gdpr_popup_position' ); #"bottom-left","top","bottom-right",""

	$static = false; # true

	$gdpr_header = get_option( 'gdpr_header' );
	
	$gdpr_header = do_shortcode($gdpr_header);

	if($gdpr_header != ""){ 
		$gdpr_header= __($gdpr_header, 'gdpr-framework');
	}

	$gdpr_popup_background=get_option( 'gdpr_popup_background' );

	$gdpr_popup_text=get_option( 'gdpr_popup_text' );

	$gdpr_button_background=get_option( 'gdpr_popup_button_background' );

	$gdpr_button_text=get_option( 'gdpr_popup_button_text' );

	$gdpr_link_target=get_option( 'gdpr_popup_link_target' );

	if(!$gdpr_link_target){
		$gdpr_link_target="_blank";
	}
	
	$gdpr_button_border=get_option( 'gdpr_popup_border_text' );

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

	$gdpr_popup_theme = get_option( 'gdpr_popup_theme' );

	$gdpr_policy_popup = get_option( 'gdpr_policy_popup' );
	
	$gdpr_hide = get_option('gdpr_onetime_popup');
	
	$type = "opt-out"; #opt-in,opt-out,""
	
	$policy_text = __('Cookie Policy', 'gdpr-framework');

	$get_gdpr_data = array('gdpr_url'=>$gdpr_policy_page_url,'gdpr_message'=>$gdpr_message,'gdpr_dismiss'=>$gdpr_dismiss,'gdpr_allow'=>$gdpr_allow,'gdpr_header'=>$gdpr_header,'gdpr_link'=>$gdpr_link,'gdpr_popup_position'=>$position,'gdpr_popup_type'=>$type,'gdpr_popup_static'=>$static,'gdpr_popup_background'=>$gdpr_popup_background,'gdpr_popup_text'=>$gdpr_popup_text,'gdpr_button_background'=>$gdpr_button_background,'gdpr_button_text'=>$gdpr_button_text,'gdpr_button_border'=>$gdpr_button_border,'gdpr_popup_theme'=>$gdpr_popup_theme,'gdpr_hide'=>$gdpr_hide,'gdpr_popup'=>$gdpr_policy_popup,'policy'=>$policy_text,'ajaxurl' => admin_url( 'admin-ajax.php' ),'gdpr_link_target' => $gdpr_link_target);
	
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