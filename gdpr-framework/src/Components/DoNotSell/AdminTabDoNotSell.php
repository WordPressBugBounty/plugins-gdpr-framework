<?php

namespace Codelight\GDPR\Components\DoNotSell;

use Codelight\GDPR\Admin\AdminTab;

class AdminTabDoNotSell extends AdminTab {

	/* @var string */
	protected $slug = 'do-not-sell';

	/* @var PolicyGenerator */
	protected $policyGenerator;

	public function __construct()
	{
		$this->title = _x( 'Do Not Sell My Data', '(Admin)', 'gdpr-framework' );

		add_action( 'ccpa/admin/action/PrivacyManager/generate', array( $this, 'generateDoNotSell' ) );
	}

	public function init() {
		/**
		 * Do Not Sell My Data settings
		 */
		$this->registerSettingSection(
			'ccpa_about_privacy_safe_section',
			_x( 'Do Not Sell My Data', '(Admin)', 'gdpr-framework' ),
			array( $this, 'renderAboutHeader' )
		);

		$this->registerSettingField(
			'ccpa_privacy_safe_shortcode',
			_x( 'Shortcode', '(Admin)', 'gdpr-framework' ), 
			array( $this, 'shortcode' ),
			'ccpa_about_privacy_safe_section'
		);
		$this->registerSettingField(
			'ccpa_privacy_safe_shortcodephp',
			_x( 'Shortcode for PHP', '(Admin)', 'gdpr-framework' ),
			array( $this, 'shortcodephp' ),
			'ccpa_about_privacy_safe_section'
		);
		$this->registerSettingField(
			'ccpa_privacy_safe_posts',
			_x( 'View Requests', '(Admin)', 'gdpr-framework' ),
			array( $this, 'postlink' ),
			'ccpa_about_privacy_safe_section'
		);

	}

	

	public function renderAboutHeader() {
		echo '<p>Place this shortcode on the page you would like to accept requests from users to not sell their information. We recommend placing the shortcode under the privacy tools shortcode on the privacy tools page.</p>';
	}

	public function renderSubmitButton()
	{
		// FRAM-137 - remove unnecessary save button
		// this overrides the placement of a save button which is not needed
	}
	
	public function shortcode() {
		echo "<code>[gdpr_do_not_sell_form]</code>";
	}
	public function shortcodephp() {
		echo "<code>echo do_shortcode('[gdpr_do_not_sell_form]');</code>";
	}
	public function postlink() {
		echo "<a href='/wp-admin/edit.php?post_type=donotsellrequests'>View Requests</a>";
	}
}
