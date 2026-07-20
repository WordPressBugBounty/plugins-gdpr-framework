<?php

namespace Codelight\GDPR\Components\PrivacyToolsPage;

if ( ! defined( 'ABSPATH' ) ) exit;

use Codelight\GDPR\DataSubject\DataSubject;
use Codelight\GDPR\DataSubject\DataSubjectAuthenticator;
use Codelight\GDPR\DataSubject\DataSubjectIdentificator;
use Codelight\GDPR\DataSubject\DataSubjectManager;
use Codelight\GDPR\DataSubject\DataExporter;
use Codelight\GDPR\Components\Consent\UserConsentModel;

/**
 * Handle the data page on front-end
 *
 * Class DataPageController
 *
 * @package Codelight\GDPR\Components\DataPage
 */
class PrivacyToolsPageController {

	/* @var DataSubjectAuthenticator */
	protected $dataSubjectAuthenticator;

	/* @var DataSubjectIdentificator */
	protected $dataSubjectIdentificator;

	/* @var DataSubjectManager */
	protected $dataSubjectManager;

	protected $UserConsentModel;

	protected $dataExporter;

	/**
	 * DataPageController constructor.
	 *
	 * @param DataSubjectIdentificator $dataSubjectIdentificator
	 * @param DataSubjectManager       $dataSubjectManager
	 */
	public function __construct(
		DataSubjectAuthenticator $dataSubjectAuthenticator,
		DataSubjectIdentificator $dataSubjectIdentificator,
		DataSubjectManager $dataSubjectManager,
		DataExporter $dataExporter,
		UserConsentModel $UserConsentModel
	) {
		$this->dataSubjectAuthenticator = $dataSubjectAuthenticator;
		$this->dataSubjectIdentificator = $dataSubjectIdentificator;
		$this->dataSubjectManager       = $dataSubjectManager;
		$this->dataExporter             = $dataExporter;

		$this->UserConsentModel = $UserConsentModel;

		if ( ! gdpr( 'options' )->get( 'enable' ) ) {
			return;
		}

		$this->setup();
	}

	protected function setup() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_donotsell' ) );

		// Listen to 'identify' action and send an email
		add_action( 'gdpr/frontend/action/identify', array( $this, 'sendIdentificationEmail' ) );

		add_action( 'gdpr/frontend/privacy-tools-page/content', array( $this, 'renderConsentForm' ), 10, 2 );
		add_action( 'gdpr/frontend/privacy-tools-page/content', array( $this, 'renderExportForm' ), 20, 2 );
		add_action( 'gdpr/frontend/privacy-tools-page/content', array( $this, 'renderDeleteForm' ), 30, 2 );
		add_action( 'gdpr/frontend/privacy-tools-page/content', array( $this, 'renderDoNotSellForm' ), 40, 2 );

		add_action( 'gdpr/frontend/privacy-tools-page/action/withdraw_consent', array( $this, 'withdrawConsent' ), 10, 2 );
		add_action( 'gdpr/frontend/privacy-tools-page/action/export', array( $this, 'export' ), 10, 2 );
		add_action( 'gdpr/frontend/privacy-tools-page/action/forget', array( $this, 'forget' ), 10, 2 );
		add_action( 'wp_ajax_donot_sell_save_post', array( $this, 'donot_sell_save_post' ) );
		add_action( 'wp_ajax_nopriv_donot_sell_save_post', array( $this, 'donot_sell_save_post' ) );
		add_action( 'wp_ajax_nopriv_validation_privacysafe', array( $this, 'validation_privacysafe' ) );
	}

	public function enqueue_donotsell() {
		global $gdpr;
		wp_enqueue_script(
			'donot-sell-form',
			$gdpr->PluginUrl . 'assets/js/gdpr-donotsell.js',
			array( 'jquery' ),
			GDPR_FRAMEWORK_VERSION,
			true
		);
		wp_localize_script(
			'donot-sell-form',
			'localized_donot_sell_form',
			array(
				'admin_donot_sell_ajax_url' => admin_url( 'admin-ajax.php' ),
			)
		);
	}

	public function enqueue() {
		global $gdpr;
		if ( ! gdpr( 'options' )->get( 'enable_stylesheet' ) || ! is_page( gdpr( 'options' )->get( 'tools_page' ) ) ) {
			return;
		}

		wp_enqueue_style(
			'gdpr-framework-privacy-tools',
			$gdpr->PluginUrl . 'assets/css/privacy-tools.css'
		);

	}

	public function validation_privacysafe() {
		return true;
		exit;
	}

    /**
     * If the given email address exists as a data subject, send an authentication email to that address
     */
    public function sendIdentificationEmail() {
        // Additional safety check
        if ( ! is_email( $_REQUEST['email'] ) ) {
            $this->redirect( array( 'gdpr_notice' => 'invalid_email' ) );
        } else {
            $requested_email = sanitize_email( $_REQUEST['email'] );
        }

        if ( $this->dataSubjectIdentificator->isDataSubject( $requested_email ) ) {
            $this->dataSubjectIdentificator->sendIdentificationEmail( $requested_email );
        } else {
            $user = get_user_by( 'email', $requested_email );
            if (empty($user)) {
                $this->redirect( array( 'gdpr_notice' => 'unregistered_user' ) );
            } else {
                $this->dataSubjectIdentificator->sendNoDataFoundEmail( $requested_email );
            }
        }

        $this->redirect( array( 'gdpr_notice' => 'email_sent' ) );
    }

	/**
	 * Render the page contents.
	 * This is only called via the shortcode.
	 */
	public function render() {
		$dataSubject = $this->dataSubjectAuthenticator->authenticate();
		$this->renderNotices();

		if ( $dataSubject ) {
			$this->renderPrivacyTools( $dataSubject );
		} else {
			$this->renderIdentificationForm();
		}
	}

	/**
	 * Display notices to the user.
	 * The contents of the notices are currently hardcoded inside the template.
	 */
	protected function renderNotices() {
		if ( ! isset( $_REQUEST['gdpr_notice'] ) ) {
			return;
		}

		echo gdpr( 'view' )->render( 'privacy-tools/notices' );
	}

	/**
	 * Render the contents of the identification form
	 */
	protected function renderIdentificationForm() {
		$nonce = wp_create_nonce( 'gdpr/frontend/action/identify' );
		// FRAM-144 Fix reference of an undefined variable 'notices'
		if (!isset($notices)) {
			$notices = "NOTICES PLACEHOLDER";
		}
		echo gdpr( 'view' )->render( 'privacy-tools/form-identify', compact( 'nonce', 'notices' ) );
	}

	/**
	 * Render the contents of the Privacy Tools page
	 *
	 * @param DataSubject $dataSubject
	 */
	protected function renderPrivacyTools( DataSubject $dataSubject ) {
		 $email = $dataSubject->getEmail();
		echo gdpr( 'view' )->render( 'privacy-tools/privacy-tools', compact( 'dataSubject', 'email' ) );
	}

	/**
	 * Render the form that allows withdrawing consent
	 *
	 * @param DataSubject $dataSubject
	 */
	public function renderConsentForm( DataSubject $dataSubject ) {
		 $consentData = $dataSubject->getVisibleConsentData();
		if ( $consentData ) {
			foreach ( $consentData as &$item ) {
				$item['withdraw_url'] = add_query_arg(
					array(
						'gdpr_action' => 'withdraw_consent',
						'gdpr_nonce'  => wp_create_nonce( 'gdpr/frontend/privacy-tools-page/action/withdraw_consent' ),
						'email'       => $dataSubject->getEmail(),
						'consent'     => $item['slug'],
					)
				);
			}
		}

		$info = gdpr( 'options' )->get( 'consent_info' );

		if (empty($info)) {
			$consentInfo = "";
		} else {
			$consentInfo = wpautop( $info );
		}

		echo gdpr( 'view' )->render(
			'privacy-tools/form-consent',
			compact( 'consentData', 'consentInfo' )
		);
	}

	/**
	 * Render the form that allows the data subject to export their data
	 *
	 * @param DataSubject $dataSubject
	 */
	public function renderExportForm( DataSubject $dataSubject ) {
		$email = $dataSubject->getEmail();
		$nonce = wp_create_nonce( 'gdpr/frontend/privacy-tools-page/action/export' );

		echo gdpr( 'view' )->render(
			'privacy-tools/form-export',
			compact( 'email', 'nonce' )
		);
	}

	/**
	 * Render the form that allows the data subject to delete their data
	 *
	 * @param DataSubject $dataSubject
	 */
	public function renderDeleteForm( DataSubject $dataSubject ) {
		// Let's not allow admins to delete themselves
		if ( current_user_can( 'manage_options' ) ) {
			echo gdpr( 'view' )->render( 'privacy-tools/notice-admin-role' );
			return;
		}
		$email     = $dataSubject->getEmail();
		$gdpr_user = get_user_by( 'email', $email );
		if ( isset( $gdpr_user->data->ID ) ) {
			if ( user_can( $gdpr_user->data->ID, 'manage_options' ) ) {
				echo gdpr( 'view' )->render( 'privacy-tools/notice-admin-role' );
				return;
			}
		}
		$action = 'forget';
		$nonce  = wp_create_nonce( 'gdpr/frontend/privacy-tools-page/action/forget' );
		$user   = wp_get_current_user();
		echo gdpr( 'view' )->render(
			'privacy-tools/form-delete',
			compact( 'action', 'email', 'nonce' )
		);
	}

	/**
	 * Render the "Do Not Sell My Data" (CCPA) request form as a section of the
	 * Privacy Tools page, alongside the consent/export/delete sections. This is
	 * the same form the [gdpr_do_not_sell_form] shortcode renders, prefilled
	 * from the authenticated data subject. Submitting it hits
	 * donot_sell_save_post(), which only records the request when the submitted
	 * email matches the verified identity (see that method for details).
	 *
	 * @param DataSubject $dataSubject
	 */
	public function renderDoNotSellForm( DataSubject $dataSubject ) {
		global $gdpr;

		$defaultConsentTypes = $gdpr->Consent->getbySlugConsent( 'do-not-sell-info' );

		$first_name = '';
		$last_name  = '';
		$user_email = $dataSubject->getEmail();

		$user = get_user_by( 'email', $user_email );
		if ( $user ) {
			$metaFirstName = get_user_meta( $user->ID, 'first_name', true );
			$first_name    = ( '' !== $metaFirstName ) ? $metaFirstName : $user->user_nicename;
			$last_name     = get_user_meta( $user->ID, 'last_name', true );
		}

		echo gdpr( 'view' )->render(
			'privacy-tools/donotsell',
			compact( 'defaultConsentTypes', 'first_name', 'last_name', 'user_email' )
		);
	}

	/**
	 * Withdraw the consent
	 *
	 * @param DataSubject $dataSubject
	 */
	public function withdrawConsent( DataSubject $dataSubject ) {
		$consent = sanitize_key( $_REQUEST['consent'] );
		$dataSubject->withdrawConsent( $consent );
		$this->redirect( array( 'gdpr_notice' => 'consent_withdrawn' ) );
	}

	/**
	 * Trigger the export action.
	 *
	 * @param DataSubject $dataSubject
	 */
	public function export( DataSubject $dataSubject ) {
		$format = sanitize_key( $_REQUEST['gdpr_format'] );
		$data   = $dataSubject->export( $format );

		if ( ! is_null( $data ) ) {
			// If there is data, download it
			$this->dataExporter->export( $data, $dataSubject, $format );
		} else {
			// If there's no data, then show notification that your request has been sent.
			$this->redirect( array( 'gdpr_notice' => 'request_sent' ) );
		}
	}

	/**
	 * Trigger the forget action.
	 *
	 * @param DataSubject $dataSubject
	 */
	public function forget( DataSubject $dataSubject ) {
		$deleted = $dataSubject->forget();

		if ( $deleted ) {
			$this->dataSubjectAuthenticator->deleteSession();
			$this->redirect( array( 'gdpr_notice' => 'data_deleted' ) );
		} else {
			// If request was sent to admin, then show notification
			$this->redirect( array( 'gdpr_notice' => 'request_sent' ) );
		}

	}

	/**
	 * Redirect the visitor to an appropriate location
	 *
	 * @param array $args
	 * @param null  $baseUrl
	 */
	protected function redirect( $args = array(), $baseUrl = null ) {
		if ( ! $baseUrl ) {
			// If custom tools page URL is set
			if ( gdpr( 'options' )->get( 'custom_tools_page' ) ) {
				$privacyToolsUrl = gdpr( 'options' )->get( 'custom_tools_page' );
				$baseUrl         = apply_filters( 'redirect_after_gdpr_submit', $privacyToolsUrl );
			} else {
				$privacyToolsUrl = gdpr( 'options' )->get( 'tools_page' );
				$baseUrl         = $privacyToolsUrl ? get_permalink( $privacyToolsUrl ) : home_url();
				$baseUrl         = apply_filters( 'redirect_after_gdpr_submit', $baseUrl );
			}
			// Avoid infinite loop redirect

		}

		wp_safe_redirect( add_query_arg( $args, $baseUrl ) );
		exit;
	}

	public function gdpr_get_formatted_billing_name_and_address( $user_id ) {
		$address  = get_user_meta( $user_id, 'billing_address_1', true ) . ' ';
		$address .= get_user_meta( $user_id, 'billing_address_2', true ) . ' ';
		$address .= get_user_meta( $user_id, 'billing_city', true ) . ' ';
		$address .= get_user_meta( $user_id, 'billing_state', true ) . ' ';
		$address .= get_user_meta( $user_id, 'billing_postcode', true ) . ' ';
		$address .= get_user_meta( $user_id, 'billing_country', true ) . ' ';
		return $address;
	}
	public function donot_sell_save_post() {
		$r = array();

		if ( empty( $_POST['form_data'] ) ) {
			$r['error'] = __( 'Missing form data.', 'gdpr-framework' );
			echo json_encode( $r );
			exit;
		}

		parse_str( wp_unslash( $_POST['form_data'] ), $form_data );

		// Security fix (SECURITY-AUDIT.md Finding 6): this endpoint is
		// wp_ajax_nopriv_* (anonymous, by design) but previously had no
		// nonce at all, letting anyone script arbitrary submissions. The
		// nonce is rendered into the real form in
		// views/privacy-tools/donotsell.php. Note: the nonce is CSRF
		// protection ONLY -- see the identity check below.
		if ( empty( $form_data['_wpnonce'] ) || ! wp_verify_nonce( $form_data['_wpnonce'], 'gdpr_donot_sell' ) ) {
			$r['error'] = __( 'Security check failed, please refresh the page and try again.', 'gdpr-framework' );
			echo json_encode( $r );
			exit;
		}

		// Security fix: a valid nonce proves the request is not a blind CSRF,
		// but it does NOT prove the submitter controls the email address --
		// the form and its nonce are intentionally exposed to anonymous
		// visitors. Previously anyone could therefore script two
		// wp_gdpr_consent rows ('do-not-sell-info' and 'receive-communications')
		// for an arbitrary victim's address. Treat CSRF protection and
		// identity verification as separate controls: never mutate consent
		// for an email we have not verified the submitter owns.
		$submitted_email = isset( $form_data['donotsell_email'] )
			? sanitize_email( $form_data['donotsell_email'] )
			: '';

		if ( ! $submitted_email || ! is_email( $submitted_email ) ) {
			$r['error'] = __( 'Please enter a valid email address.', 'gdpr-framework' );
			echo json_encode( $r );
			exit;
		}

		// Derive a server-verified identity. authenticate() returns a
		// DataSubject for a logged-in user, or for a visitor who has completed
		// the plugin's signed, expiring email-identification link (the
		// gdpr_key cookie validated against its stored token); otherwise false.
		$dataSubject    = $this->dataSubjectAuthenticator->authenticate();
		$verified_email = $dataSubject ? sanitize_email( $dataSubject->getEmail() ) : '';

		$email_matches = $verified_email
			&& hash_equals( strtolower( $verified_email ), strtolower( $submitted_email ) );

		if ( ! $email_matches ) {
			// The submitter has not proven ownership of this address. Send the
			// plugin's signed, expiring identification link and record nothing
			// until they complete it and resubmit.
			$this->dataSubjectIdentificator->sendIdentificationEmail( $submitted_email );

			$r['verification_required'] = true;
			$r['error']                 = __( 'Please check your email and verify the address before completing this request.', 'gdpr-framework' );
			echo json_encode( $r );
			exit;
		}

		// From this point forward, use only the server-verified email.
		$email = $verified_email;

		$authorname = '';
		if ( is_user_logged_in() ) {
			$current_user = wp_get_current_user();
			$authorname   = esc_html( $current_user->user_login );
		}

		$postarr  = array(
			'ID'          => '', // If ID stays empty the post will be created.
			'post_author' => $authorname,
			'post_title'  => $email,
			'post_status' => 'publish',
			'post_type'   => 'donotsellrequests',
		);
		$new_post = wp_insert_post(
			$postarr,
			true
		);

		// Post was not created/updated, so let's output the error message.
		if ( is_wp_error( $new_post ) ) {
			$r['error'] = $new_post->get_error_message();

			echo json_encode( $r );

			exit;
		}

		$post_id = intval( $new_post );
		if ( $post_id ) {
			add_post_meta( $post_id, 'donotsell_first_name', isset( $form_data['donotsell_first_name'] ) ? sanitize_text_field( $form_data['donotsell_first_name'] ) : '' );
			add_post_meta( $post_id, 'donotsell_last_name', isset( $form_data['donotsell_last_name'] ) ? sanitize_text_field( $form_data['donotsell_last_name'] ) : '' );
			add_post_meta( $post_id, 'donotsell_consent', isset( $form_data['donotsell_consent'] ) ? sanitize_text_field( $form_data['donotsell_consent'] ) : '' );
		}

		if ( ! empty( $form_data['donotsell_consent'] ) ) {
			$this->UserConsentModel->give( $email, 'do-not-sell-info', null );
			$this->UserConsentModel->give( $email, 'receive-communications', null );
		}

		// Gets post info in array format as it's easier to debug via console if needed.
		$post_array = get_post( $post_id, ARRAY_A );

		if ( $post_array ) {
			$r['donotsellrequests'] = $post_array;
		}

		echo json_encode( $r );
		exit;
	}
}
