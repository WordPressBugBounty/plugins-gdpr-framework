<?php

namespace Codelight\GDPR\Components\PrivacySafe;

use Codelight\GDPR\Admin\AdminTab;
use Codelight\GDPR\Components\WHMCS\WHMCS;

class AdminTabPrivacySafe extends AdminTab {

	/* @var string */
	protected $slug = 'privacy-safe';

	/* @var PolicyGenerator */
	protected $policyGenerator;

	public function __construct() {
		 $this->title = _x( 'Privacy Safe', '(Admin)', 'gdpr-framework' );
		$this->registerSetting( 'gdpr_privacy_safe_params' );
		$this->registerSetting( 'gdpr_privacy_safe_imagecode' );
		$this->registerSetting( 'gdpr_privacy_safe_backlink_selected' );
		$this->registerSetting( 'gdpr_privacy_safe_backlink' );

		add_action( 'gdpr/admin/action/PrivacyManager/generate', array( $this, 'generatePrivacySafe' ) );

	}

	public function init() {
		/**
		 * Privacy Safe settings
		 */
		$this->registerSettingSection(
			'gdpr_about_privacy_safe_section',
			_x( 'Privacy Safe by Data443', '(Admin)', 'gdpr-framework' ),
			array( $this, 'renderAboutHeader' )
		);
		$this->registerSettingSection(
			'gdpr_link_privacy_safe_section',
			_x( 'Register for Privacy Safe', '(Admin)', 'gdpr-framework' ),
			array( $this, 'renderLinkHeader' )
		);

		$this->registerSettingSection(
			'gdpr_privacy_safe_section',
			_x( 'Privacy Safe Settings', '(Admin)', 'gdpr-framework' ),
			array( $this, 'renderGuideHeader' )
		);

		$this->registerSettingField(
			'gdpr_privacy_safe_params',
			_x( 'Seal Code', '(Admin)', 'gdpr-framework' ),
			array( $this, 'params' ),
			'gdpr_privacy_safe_section'
		);
		$this->registerSettingField(
			'gdpr_privacy_safe_imagecode',
			_x( 'Image Code', '(Admin)', 'gdpr-framework' ),
			array( $this, 'imagecode' ),
			'gdpr_privacy_safe_section'
		);
		$this->registerSettingField(
			'gdpr_privacy_safe_shortcode',
			_x( 'Shortcode', '(Admin)', 'gdpr-framework' ), 
			array( $this, 'shortcode' ),
			'gdpr_privacy_safe_section'
		);
		$this->registerSettingField(
			'gdpr_privacy_safe_shortcodephp',
			_x( 'Shortcode for PHP', '(Admin)', 'gdpr-framework' ),
			array( $this, 'shortcodephp' ),
			'gdpr_privacy_safe_section'
		);
		$this->registerSettingField(
			'gdpr_privacy_safe_backlink',
			_x( 'Support Data443', '(Admin)', 'gdpr-framework' ),
			array( $this, 'backlinkphp' ),
			'gdpr_privacy_safe_section'
		);


	}

	public function renderAboutHeader() {
		echo '<img src="' . esc_url( plugins_url( 'PrivacySafe/Privacy-Safe-Brand.png', dirname(__FILE__) ) ) . '" style="float:right;margin:15px;"/><p>'. esc_html_x('Strengthen your reputation. The privacy safe seal assures your customers that your business is in compliance with privacy laws and regulations. The privacy safe seal will verify that the GDPR Framework plugin is installed.', '(Admin)', 'gdpr-framework') . '</p>';
	}
	public function renderLinkHeader() {
		echo '<p>'. esc_html_x('Register now to activate your Privacy Safe seal. Visit the link below, complete the complete the checkout process. Once approved you will recieve notice to get your seal code and image code. Enter those here and save. You can then place the seal where you would like on your site.', '(Admin)', 'gdpr-framework') . '</p><p><a href="https://orders.data443.com/cart.php?a=add&pid=31&carttpl=standard_cart" target="_blank" class="button button-primary">' . esc_html_x('Register Here', '(Admin)', 'gdpr-framework') . '</a></p>';
	}
	public function renderGuideHeader() {
		echo '<p>' . esc_html_x('Embed the shortcode provided to display your privacy safe seal.', '(Admin)', 'gdpr-framework') . '</p>';
	}
	public function params() {
		echo "<input type='text' name='gdpr_privacy_safe_params' placeholder='' value='" . get_option( 'gdpr_privacy_safe_params' ) . "'>";
	}
	public function imagecode() {
		echo "<input type='text' name='gdpr_privacy_safe_imagecode' placeholder='' value='" . get_option( 'gdpr_privacy_safe_imagecode' ) . "'>";
	}
	public function shortcode() {
		echo "<code>[data443_privacy_safe]</code>";
	}
	public function shortcodephp() {
		echo "<code>echo do_shortcode('[data443_privacy_safe]');</code>";
	}
	public function backlinkphp() {

		if ( get_option( 'gdpr_privacy_safe_backlink_selected' ) === '1' ) {
			$checked = get_option( 'gdpr_privacy_safe_backlink' );
		}else{
			$checked = 1;
		}		
		echo gdpr( 'view' )->render( 'admin/privacy-safe/enable-backlink' , compact( 'checked' ) );
	}

	public function renderSubmitButton() {
		submit_button( esc_html_x( 'Save', '(Admin)', 'gdpr-framework' ) );
	}
}
