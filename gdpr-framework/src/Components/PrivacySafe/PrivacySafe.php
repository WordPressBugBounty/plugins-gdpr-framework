<?php

namespace Codelight\GDPR\Components\PrivacySafe;

if ( ! defined( 'ABSPATH' ) ) exit;

class PrivacySafe {

	public function __construct() {
		 add_filter( 'gdpr/admin/tabs', array( $this, 'registerAdminTab' ), 90 );
	}

	public function registerAdminTab( $tabs ) {
		 $tabs['privacy-safe'] = new AdminTabPrivacySafe();
		return $tabs;
	}
}

