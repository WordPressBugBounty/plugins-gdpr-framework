<?php

namespace Codelight\GDPR\Admin;

/**
 * Handles general admin functionality
 *
 * Class WordpressAdmin
 *
 * @package Codelight\GDPR\Admin
 */
class WordpressAdmin
{
    protected $adminPage;

    public function __construct(WordpressAdminPage $adminPage)
    {
        $this->adminPage = $adminPage;

        // Allow turning off helpers
        if (apply_filters('gdpr/admin/helpers/enabled', true)) {
            new AdminHelper();
        }

        $this->setup();

    }

    /**
     * Set up hooks
     */
    protected function setup()
    {
        // Register the main GDPR options page
        add_action('admin_menu', [$this, 'registerGDPROptionsPage']);

        // Register General admin tab
        add_filter('gdpr/admin/tabs', [$this, 'registerAdminTabGeneral'], 0);

        // Enqueue assets
        add_action('admin_enqueue_scripts', [$this, 'enqueue']);

        // Register post states
        add_filter('display_post_states', [$this, 'registerPostStates'], 10, 2);

        // Show help notice
        add_action('current_screen', [$this, 'maybeShowHelpNotice'], 999);

        //
        add_action( 'delete_user', [$this, 'gdprf_delete_userlogs']);
    }


    public function maybeShowHelpNotice()
    {
        if ('tools_page_privacy' === get_current_screen()->base) {
            //gdpr('admin-notice')->add('admin/notices/help');
        }
    }

    /**
     * Register the GDPR options page in WP admin
     */
    public function registerGDPROptionsPage()
    {
        add_management_page(
            _x('Privacy & GDPR Settings', '(Admin)', 'gdpr-framework'),
            _x('Data443 GDPR', '(Admin)', 'gdpr-framework'),
            'manage_options',
            'privacy',
            [$this->adminPage, 'renderPage']
        );
    }

    /**
     * Register General admin tab
     *
     * @param $tabs
     * @return array
     */
    public function registerAdminTabGeneral($tabs)
    {
        global $gdpr;
        $tabs['general'] = $gdpr->AdminTabGeneral;

        return $tabs;
    }

    /**
     * Enqueue all admin scripts and styles
     */
    public function enqueue()
    {
        global $gdpr;
        /**
         * General admin styles
         */
        wp_enqueue_style(
            'gdpr-admin',
            $gdpr->PluginUrl . 'assets/gdpr-admin.css'
        );
        
        
        $screen = get_current_screen();
        if($screen->base=='tools_page_privacy'){
                    
            /**
             * jQuery UI dialog for modals
             */
            wp_enqueue_style('wp-jquery-ui-dialog');
            wp_enqueue_script(
                'gdpr-admin',
                $gdpr->PluginUrl . 'assets/gdpr-admin.js',
                ['jquery-ui-dialog']
            );

            /**
             * jQuery Repeater
             */
            wp_enqueue_script(
                'jquery-repeater',
                $gdpr->PluginUrl . 'assets/jquery.repeater.min.js',
                ['jquery']
            );
            
            /**
             * Select2
             */

            wp_dequeue_script( 'select2css' );
            wp_dequeue_script( 'select2' );

            wp_enqueue_style(
                'select2css',
                $gdpr->PluginUrl . 'assets/select2-4.0.5.css'
            );
    
            wp_enqueue_script(
                'select2',
                $gdpr->PluginUrl . 'assets/select2-4.0.3.js',
                ['jquery']
            );
            
            wp_enqueue_script(
                'conditional-show',
                $gdpr->PluginUrl . 'assets/conditional-show.js',
                ['jquery']
            );
            /**
             * Color Picker
             */
            wp_enqueue_script( 'iris',$gdpr->PluginUrl .'assets/iris.min.js' );
            wp_enqueue_script( 'iris-init',$gdpr->PluginUrl .'assets/iris-init.js' );
        }
    }

    /**
     * Add a new Post State for our super important system pages
     */
    public function registerPostStates($postStates, $post)
    {
        global $gdpr;
        if ($gdpr->Options->get('policy_page') == $post->ID) {
            $postStates['gdpr_policy_page'] = _x('Privacy Policy Page', '(Admin)', 'gdpr-framework');
        }

        if ($gdpr->Options->get('tools_page') == $post->ID) {
            $postStates['gdpr_tools_page'] = _x('Privacy Tools Page', '(Admin)', 'gdpr-framework');
        }

        return $postStates;
    }
    //Delete userlogs if user deleted from admin panel.
    public function gdprf_delete_userlogs($user_id)
    {
        global $wpdb;

        $this->logtableName = $wpdb->prefix . 'gdpr_userlogs';

        return $wpdb->delete(
            $this->logtableName,
            [
                'user_id'   => $user_id,
            ]
        );
    }
    
}