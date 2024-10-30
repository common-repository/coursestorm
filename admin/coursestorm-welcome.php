<?php
/**
 * CourseStorm WordPress Plugin
 *
 * This class manages the settings that we expose through the wp-admin options page.
 */

if ( !class_exists( 'CourseStorm_Welcome' ) ) {
    class CourseStorm_Welcome {
        public function __construct() {
            $this->register_coursestorm_welcome_page();
            $this->register_actions();
        }

        /**
         * Add our API settings page to the "Settings" menu in wp-admin.
         */
        public function register_coursestorm_welcome_page() {
            add_submenu_page(
                'plugins.php',
                'Welcome to CourseStorm for WordPress',
                'CourseStorm for WordPress Welcome',
                'manage_options',
                'coursestorm-welcome',
                [
                    $this,
                    'welcome_page_content'
                ]
            );
        }

        public function welcome_page_content() {
            require_once dirname( __FILE__ ) . '/../templates/admin/welcome.php';
        }

        /**
         * Welcome Screen Activate
         * 
         * Perform actions upon plugin activation.
         * 
         * @return void
         */
        public static function welcome_screen_activate() {
            set_transient( 'coursestorm_welcome_screen_activation_redirect', true, 30 );
        }

        public function welcome_screen_do_activation_redirect() {
            // Bail if no activation redirect
            if ( ! get_transient( 'coursestorm_welcome_screen_activation_redirect' ) ) {
              return;
            }
          
            // Delete the redirect transient
            delete_transient( 'coursestorm_welcome_screen_activation_redirect' );

            $has_subdomain = isset(get_option( 'coursestorm-settings' )['subdomain']) ? true : false;
          
            // Bail if activating from network, or bulk
            if ( is_network_admin() || isset( $_GET['activate-multi'] ) || $has_subdomain ) {
              return;
            }
          
            // Redirect to welcome page
            wp_redirect( add_query_arg( ['page' => 'coursestorm-welcome'], admin_url( 'plugins.php' ) ) );
            exit;
          
        }

        public function remove_welcome_menu_item() {
            remove_submenu_page( 'plugins.php', 'coursestorm-welcome' );
        }

        public function register_actions() {
            add_action( 'admin_init', [$this, 'welcome_screen_do_activation_redirect'] );
            add_action( 'admin_init', [$this, 'remove_welcome_menu_item'] );
        }
    }

    // Create a new instance of the class
    add_action( 'admin_menu', function() { new CourseStorm_Welcome; } );
}

