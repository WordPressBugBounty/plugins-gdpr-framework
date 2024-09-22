<?php
namespace Codelight\GDPR;

use Codelight\GDPR\DataSubject\DataSubjectIdentificator;
use Codelight\GDPR\DataSubject\DataSubjectAuthenticator;
use Codelight\GDPR\Modules\WPML\WPML;
use Codelight\GDPR\DataSubject\DataExporter;

class Singleton {
    var $View;
    var $Options;
    var $Consent;
    var $Helpers;
    var $Themes;
    var $DataSubject;
    var $AdminNotice;
    var $AdminError;
    var $AdminInstallerNotice;
    var $PrivacySafe;
    var $AdminModal;
    var $AdminPage;
    var $AdminTabGeneral;
    var $PluginUrl;
    var $PluginPath;
    var $PluginTemplatePath;
    var $InstallerWizardUrl;
    var $DataSubjectAuthenticator;
    var $DataSubjectIdentificator;
    var $DataExporter;
    var $UserConsentModel;
    var $DataManager;
    var $Controller;
    var $HelpUrl;

    function init($path) {
        $this->PluginUrl = plugin_dir_url($path);
        $this->PluginPath = plugin_dir_path($path);
        $this->PluginTemplatePath = plugin_dir_path($path) . 'views/';
        $this->HelpUrl = 'https://www.data443.com/gdpr-framework/';

        $this->InstallerWizardUrl = self_admin_url("index.php?page=gdpr-setup&gdpr-step=");

        $this->View = new View();
        $this->Options = new Options\Options();
        $model = new Components\Consent\UserConsentModel();
        $this->UserConsentModel = $model;
        $this->Consent = new Components\Consent\ConsentManager($model);
        $this->Helpers = new Helpers();
        $this->Themes = new Components\Themes\Themes();
        $this->DataSubject = new DataSubject\DataSubjectManager($this->Consent);

        new WPML();
        $identificator = new DataSubjectIdentificator($this->DataSubject, $this->Options, $model);
        $authenticator = new DataSubjectAuthenticator($this->DataSubject, $identificator);
        new Router($authenticator);
        $this->DataSubjectAuthenticator = $authenticator;
        $this->DataSubjectIdentificator = $identificator;
        $this->DataExporter = new DataExporter();
        new Components\PrivacyToolsPage\PrivacyToolsPage();
        new Components\WordpressComments\WordpressComments($this->DataSubject);
        $this->DataManager = new Components\WordpressUser\DataManager();
        new Components\WordpressUser\WordpressUser($this->DataSubject, $this->DataManager);
        new Components\Themes\Themes();

        if (defined('WPCF7_VERSION')) {
            new Modules\ContactForm7\ContactForm7($this->DataSubject, $this->Consent);
        }

        if (defined('FLAMINGO_VERSION')) {
            new Modules\ContactForm7\Flamingo();
        }

        if ( defined('WC_VERSION') ) {
            new Modules\WooCommerceGdpr\WooCommerceGdpr($this->DataSubject, $this->Consent);
        }
        
        if ( defined('EDD_VERSION') ) {
            new Modules\EddGdpr\EddGdpr($this->DataSubject, $this->Consent);
        }

        if ( defined('ES_PLUGIN_VERSION') ) {
            new Modules\NewsletterGdpr\NewsletterGdpr($this->DataSubject, $this->Consent);
        }
    }
}