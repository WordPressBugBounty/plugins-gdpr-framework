<?php

namespace Codelight\GDPR\Components\Consent;

if ( ! defined( 'ABSPATH' ) ) exit;

use Codelight\GDPR\Admin\AdminTab;

/**
 * Handle rendering and saving the Consent tab on GDPR Options page
 *
 * Class AdminTabConsent
 * @package Codelight\GDPR\Components\Consent
 */
class AdminTabConsent extends AdminTab
{
    /* @var string */
    protected $slug = 'consent';

    /* @var ConsentManager */
    protected $consentManager;

    /**
     * AdminTabConsent constructor.
     *
     * @param ConsentManager $consentManager
     */
    public function __construct(ConsentManager $consentManager)
    {
        $this->consentManager = $consentManager;

        $this->title = _x('Consent', '(Admin)', 'gdpr-framework');

        // If we don't register the settings, WP will not allow this page to be submitted
        $this->registerSetting('consent_types');
        $this->registerSetting('consent_info');
        $this->registerSetting('gdpr_consent_until_display');

        $this->renderErrors();

        // Register handler for this action
        add_action('gdpr/admin/action/update_consent_data', [$this, 'updateConsentData']);
    }

    /**
     * Initialize tab contents and register hooks
     */
    public function init()
    {
        $this->registerSettingSection(
            'gdpr_section_consent',
            _x('Consent', '(Admin)', 'gdpr-framework'),
            [$this, 'renderConsentForm']
		);
        $this->registerSettingSection(
            'gdpr_section_consent_until',
            _x('Additional Settings', '(Admin)', 'gdpr-framework'),
            [$this, 'renderConsentUntil']
		);
		$this->registerSettingField(
			'gdpr_consent_until_display',
			_x( 'Display Consent Calendar', '(Admin)', 'gdpr-framework' ),
			array( $this, 'consent_until_display' ),
			'gdpr_section_consent_until'
		);
    }

    /**
     * Render the contents of the registered section
     */
    public function renderConsentForm()
    {
        global $gdpr;
        $consentInfo = $gdpr->Options->get('consent_info');

        if (is_null($consentInfo)) {
            $consentInfo = $this->getDefaultConsentInfo();
        } elseif (!$consentInfo) {
            $consentInfo = '';
        }

        $nonce = wp_create_nonce("gdpr/admin/action/update_consent_data");
        $defaultConsentTypes = $this->consentManager->getDefaultConsentTypes();
        $customConsentTypes = $this->consentManager->getCustomConsentTypes();

        if (defined('ICL_LANGUAGE_CODE')) {
            $prefix = ICL_LANGUAGE_CODE . '_';
        } else {
            $prefix = '';
        }

        echo gdpr('view')->render('admin/consent', compact('nonce', 'customConsentTypes', 'defaultConsentTypes', 'consentInfo', 'prefix'));
    }

    /**
     * Save the submitted consent types
     */
    public function updateConsentData()
    {
        global $gdpr;
		// Update additional information
        if (isset($_POST['gdpr_consent_info'])) {
            $gdpr->Options->set('consent_info', wp_unslash($_POST['gdpr_consent_info']));
        }

        // Update consent types
        if (isset($_POST['gdpr_consent_types']) && is_array($_POST['gdpr_consent_types'])) {
            $consentTypes = $_POST['gdpr_consent_types'];
        } else {
            $consentTypes = [];
		}
		
		// Strip slashes which WP adds automatically
        if (count($consentTypes)) {
            foreach ($consentTypes as &$type) {
                foreach ($type as $key => $item) {
                    if (is_array($item)) {
                        $type[$key] = array_map('wp_unslash', $item);
                    } else {
                        $type[$key] = wp_unslash($item);
                    }

                    if ('visible' === $key) {
                        $type[$key] = 1;
                    }
                }

                // Security fix (SECURITY-AUDIT.md Finding 2): title/description
                // are plain-text fields in views/admin/consent.php (a text
                // input and a plain textarea, no rich-text editor) but were
                // previously stored completely unsanitized, then echoed
                // unescaped on public consent forms and the admin
                // data-subject search page -- a stored XSS reachable by
                // anyone who can save a custom consent type. Sanitize on the
                // way in as defense in depth alongside escaping on the way out.
                if (isset($type['title'])) {
                    $type['title'] = sanitize_text_field($type['title']);
                }
                if (isset($type['description'])) {
                    $type['description'] = sanitize_textarea_field($type['description']);
                }
            }
		}

        // Fix (PR review Finding 2): drop completely empty repeater/template
        // rows before validating or saving. The hidden "add consent type"
        // template can otherwise submit an all-blank row (see the JS fix in
        // assets/gdpr-admin.js), which would fail validation and block the
        // whole settings save, and would be persisted as junk if it slipped
        // through.
        $consentTypes = array_values(array_filter($consentTypes, function ($type) {
            return '' !== $this->trimField($type, 'slug')
                || '' !== $this->trimField($type, 'title')
                || '' !== $this->trimField($type, 'description');
        }));

		$errors = [];

        if (!empty($consentTypes)) {
            // Security fix (SECURITY-AUDIT.md Finding 2): this validation was
            // disabled, allowing e.g. an empty required title/slug to be saved.
            $errors = $this->validate($consentTypes);
		}

		if (!count($errors)) {
            $this->consentManager->saveCustomConsentTypes($consentTypes);
        } else {
            $errorQuery = http_build_query($errors);
            wp_safe_redirect(gdpr('helpers')->getAdminUrl('&gdpr-tab=consent&') . $errorQuery);
            exit;
        }
    }

    protected function validate($consentTypes)
    {
        // Fix (PR review Finding 2): the previous version indexed
        // $consentType['slug'] directly (undefined-index warning on a partial
        // row) and reused the single key 'errors[]' for every failure, so each
        // new error overwrote the last instead of accumulating. Read fields
        // defensively, tolerate a completely empty template row, and collect
        // all distinct errors.
        $errors = [];

        foreach ($consentTypes as $consentType) {
            $slug        = $this->trimField($consentType, 'slug');
            $title       = $this->trimField($consentType, 'title');
            $description = $this->trimField($consentType, 'description');

            // Ignore a completely empty repeater/template row.
            if ('' === $slug && '' === $title && '' === $description) {
                continue;
            }

            if ('' === $slug) {
                $errors[] = 'slug-empty';
            } elseif (!preg_match('/^[A-Za-z0-9_-]+$/', $slug)) {
                $errors[] = 'slug-invalid';
            }

            if ('' === $title) {
                $errors[] = 'title-empty';
            }
		}

        $errors = array_values(array_unique($errors));

        return empty($errors) ? [] : ['errors' => $errors];
    }

    /**
     * Read a consent-type field as a trimmed string, tolerating a missing key
     * or a non-scalar value (e.g. an array submitted for a text field).
     *
     * @param array  $consentType
     * @param string $key
     * @return string
     */
    protected function trimField($consentType, $key)
    {
        if (!isset($consentType[$key]) || !is_scalar($consentType[$key])) {
            return '';
        }

        return trim((string) $consentType[$key]);
    }

    public function renderErrors()
    {
        if (isset($_GET['errors']) && count($_GET['errors'])) {

            foreach ($_GET['errors'] as $error) {
                if ('slug-empty' === $error) {
                    $message = _x("Consent slug is a required field!", '(Admin)', 'gdpr-framework');
                    gdpr('admin-error')->add('admin/notices/error', compact('message'));
                }

                if ('slug-invalid' === $error) {
                    $message = _x("You may only use alphanumeric characters, dash and underscore in the consent slug field.", '(Admin)', 'gdpr-framework');
                    gdpr('admin-error')->add('admin/notices/error', compact('message'));
                }

                if ('title-empty' === $error) {
                    $message = _x("Consent title is a required field!", '(Admin)', 'gdpr-framework');
                    gdpr('admin-error')->add('admin/notices/error', compact('message'));
                }
            }
        }
    }

    /**
     * @return string
     */
    public function getDefaultConsentInfo()
    {
        // this is merely a one time initializtion, this string can be changed in the Admin Consent tab and is saved into the database with 
        // the key of gdpr_consent_info
        return __('To use this website, you accepted our Privacy Policy. If you wish to withdraw your acceptance, please use the "Delete my data" button below.', 'gdpr-framework');
	}
	public function renderConsentUntil() {
		echo '<p>' . __('Enable this feature to allow users to submit a time limit on how many months their consent is given for their coments and registration.', 'gdpr-framework') . '</p>';
	}
	
	public function consent_until_display(){
		if ( get_option( 'gdpr_consent_until_display' ) === '1' ) {
			$checked = get_option( 'gdpr_consent_until_display' );
		}else{
			$checked = 0;
		}		
		echo gdpr( 'view' )->render( 'admin/consent/enable-consent-until' , compact( 'checked' ) );
	}
}
