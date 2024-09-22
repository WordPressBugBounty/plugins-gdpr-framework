<?php

namespace Codelight\GDPR\Updater;

class Updater
{
    public function __construct()
    {
        // Put the latest version number into the database
        update_option('gdpr_plugin_version', GDPR_FRAMEWORK_VERSION);

        // remove obsolete options
        if (get_option('gdpr_classidocs_integration', false)) {
            delete_option('gdpr_classidocs_integration');
            delete_option('gdpr_classidocs_url');
            delete_option('gdpr_classidocs_username');
            delete_option('gdpr_classidocs_password');
        }
    }
}