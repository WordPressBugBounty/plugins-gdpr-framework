<?php

namespace Codelight\GDPR\Components\WordpressUser;

use Codelight\GDPR\DataSubject\DataSubject;
use Codelight\GDPR\DataSubject\DataSubjectManager;

class RegistrationForm
{
    /* @var DataSubjectManager */
    protected $dataSubjectManager;

    public function __construct(DataSubjectManager $dataSubjectManager)
    {
        global $gdpr;
        $this->dataSubjectManager = $dataSubjectManager;
        if(!$gdpr->Options->get('register_checkbox')){
            if ($gdpr->Options->get('policy_page') || $gdpr->Options->get('custom_policy_page')) {
                add_action('register_form', [$this, 'addRegisterFormCheckbox']);
                add_filter('registration_errors', [$this, 'validate'], PHP_INT_MAX);
            }
        }
    }

    public function addRegisterFormCheckbox()
    {
        global $gdpr;
		$privacyPolicyUrl = ! get_permalink( gdpr( 'options' )->get( 'custom_policy_page' ) ) ? get_permalink( gdpr( 'options' )->get( 'policy_page' ) ) : get_permalink( gdpr( 'options' )->get( 'custom_policy_page' ) );
        add_filter( 'gdpr_custom_policy_link', 'gdprfPrivacyPolicyurl' );
        $privacyPolicyUrl = apply_filters( 'gdpr_custom_policy_link',$privacyPolicyUrl);
        $termsPage = ! $gdpr->Options->get('custom_terms_page') ? $gdpr->Options->get('terms_page') : $gdpr->Options->get('custom_terms_page');

        if($gdpr->Options->get('custom_terms_page')){
			$termsPage = $gdpr->Options->get('custom_terms_page');
			if ($termsPage) {
				$termsUrl = $termsPage;
			} else {
				$termsUrl = false;
			}
		}else{
			$termsPage = $gdpr->Options->get('terms_page');
			if ($termsPage) {
				$termsUrl = get_permalink($termsPage);
			} else {
				$termsUrl = false;
			}
		}

        echo gdpr('view')->render(
            'modules/wordpress-user/registration-terms-checkbox',
            compact('privacyPolicyUrl', 'termsUrl')
        );
    }

    public function validate(\WP_Error $errors)
    {
        if (empty($_POST['gdpr_terms']) || !$_POST['gdpr_terms']) {
            $errors->add('gdpr_error', __('<strong>ERROR</strong>: You must accept the terms and conditions.', 'gdpr-framework'));
        } else {
            $dataSubject = $this->dataSubjectManager->getByEmail($_POST['user_email']);
            $dataSubject->giveConsent('privacy-policy');
        }

        return $errors;
    }
}
