<?php
/**
 * CourseStorm WordPress Plugin
 *
 * This class manages the settings that we expose through the wp-admin options page.
 */

if( !class_exists( 'CourseStorm_WP_API' ) ) {
    require_once dirname( __FILE__ ). '/../lib/coursestorm-wp-api.php';
}
if( !class_exists( 'CourseStorm_Synchronize' ) ) {
    require_once dirname( __FILE__ ). '/../synchronize.php';
}

if ( !class_exists( 'CourseStorm_Admin' ) ) {
  class CourseStorm_Admin {
    protected $_subdomain = null;

    /**
     * @hook admin_menu
     */
    public function __construct() {
      $this->_setSubdomain();
      $this->register_api_options_page();
      $this->maybe_show_api_key_nag();
      $this->register_settings();
      $this->coursestorm_course_import_progress_notice();
      $this->coursestorm_settings_notices();
    }

    /**
     * Display our settings page for fetching our API key.
     */
    public function api_key_options() {
      if ( !current_user_can( 'manage_options' ) ) {
        return;
      }
      $next_scheduled_cron_timestamp = wp_next_scheduled( 'coursestorm_sync' );
      $subdomain = $this->_subdomain;
      $cron_schedule = get_option( 'coursestorm-settings' )['cron_schedule'];

      switch ( $cron_schedule ) {
        case 'twicedaily' :
          $cron_schedule_string = 'The catalog import automatically runs twice per day.';
          break;
        case 'daily' :
          $cron_schedule_string = 'The catalog import automatically runs once per day.';
          break;
        case 'hourly' :
        default :
          $cron_schedule_string = 'The catalog import automatically runs every hour.';
          break;
      }
      
      date_default_timezone_set( get_option( 'timezone_string' ) );
      $now = new DateTime();
      $next = new DateTime( date( 'Y-m-d h:i:s a', $next_scheduled_cron_timestamp ) );
      $interval = $next->diff($now);

      if( $interval->h >= 1 ) {
        if( $interval->h > 1 ) {
          $time_to_next_cron = $interval->format('%h hours %i minutes');
        } else{
          $time_to_next_cron = $interval->format('%h hour %i minutes');
        }
      } else {
        if( $interval->i > 1 ) {
          $time_to_next_cron = $interval->format('%i minutes');
        } else {
          $time_to_next_cron = $interval->format('%i minute');
        }
      }

      require_once dirname( __FILE__ ) . '/../templates/admin/options.php';
    }

    /*
     * If we haven't entered an API key, let's promp the user to do so.
     */
    public function maybe_show_api_key_nag() {
      // Hide the nag on the API key options page.
      global $pagenow;
      if( 
        ($pagenow == 'options-general.php' && isset( $_GET['page'] ) && $_GET['page'] == 'options_coursestorm_api')
        || ($pagenow == 'plugins.php' && isset( $_GET['page'] ) && $_GET['page'] == 'coursestorm-welcome')
      ) {
        return;
      }

      // Otherwise, show the nag if there's no API key set.
      $api_credentials = get_option( 'coursestorm-settings' );
      if( $api_credentials === false ) {
        $admin_notice = function() {
          ?>
            <div class="notice notice-warning is-dismissible">
              <p>Thank you for installing CourseStorm for WordPress! Please <a href="<?= esc_url( get_admin_url( null, 'options-general.php?page=options_coursestorm_api' ) ) ?>">enter your catalog URL</a> to complete configuration.</p>
            </div>
          <?php
        };

        add_action( 'admin_notices', $admin_notice );
      }
    }

    /**
     * CourseStorm Course Import Progress/Status Notice
     * 
     * Display a notice with the status of the
     * import progress.
     */
    public static function coursestorm_course_import_progress_notice() {
      // Initializes the import status as incomplete
      // to get the right notice on page load.
      $course_import_progress = get_transient( 'coursestorm_course_import_progress' );
      $course_import_status = get_transient( 'coursestorm_course_import_status' );

      if( false === $course_import_progress && false === $course_import_status ) :
        return;
      elseif( 'incomplete' === $course_import_status && 0 == $course_import_progress ) : 
        $admin_notice = function() {
          if( $value = get_transient('coursestorm_import_step_transient') ) {
            $current_step = $value;
          } else {
            $current_step = null;
          }
          
          $base_notice = apply_filters( 'coursestorm-unit-name', 'Class' ) . ' import pending.';
          
          switch ($current_step) {
            case 'classes' :
              $notice = $base_notice . '  Currently downloading '.apply_filters( 'coursestorm-units-name', 'classes' ) . '.';
              break;
            case 'categories' :
              $notice = $base_notice . '  Currently downloading categories.';
              break;
            default :
              $notice = $base_notice;
              break;
          }
        ?>
          <div class="notice notice-info">
            <p><?php echo $notice; ?> <a href="" onClick="location.reload();">Refresh to check import status</a></p>
          </div>
        <?php
        };
        add_action( 'admin_notices', $admin_notice );
      elseif( 'incomplete' === $course_import_status && 1 == $course_import_progress ) :
        $admin_notice = function() {
        ?>
          <div class="notice notice-info">
            <p><?php echo apply_filters( 'coursestorm-unit-name', 'Class' ); ?> import is complete. Cleaning up outdated courses. <a href="" onClick="location.reload();">Refresh to check import status</a></p>
          </div>
        <?php
        };
        add_action( 'admin_notices', $admin_notice );
      elseif( 'complete' === $course_import_status ) :
        $message = apply_filters( 'coursestorm-unit-name', 'Class' ) . ' import is complete. <a href="/classes/" target="_blank">View your ' . apply_filters( 'coursestorm-units-name', 'classes' ) . ' page.</a>';
        add_settings_error( 'coursestorm', 'success', $message, 'notice-success' );
        delete_transient( 'coursestorm_course_import_status' );

        unset( $course_import_status );
      elseif( $course_import_progress < 1 ) :
        $admin_notice = function() use ($course_import_progress) {
        ?>
          <div class="notice notice-info">
            <p><?php echo apply_filters( 'coursestorm-unit-name', 'Class' ); ?> import status: <?php echo round($course_import_progress, 2) * 100; ?>% complete. <a href="" onClick="location.reload();">Refresh to check import status</a></p>
          </div>
        <?php
        };
        add_action( 'admin_notices', $admin_notice );
      else :
        return;
      endif;

    }
    
    /**
     * CourseStorm Settings Notices
     * 
     * Display a notice with the status of the settings save.
     */
    public static function coursestorm_settings_notices() {
      if ( $settings_status = get_transient( 'coursestorm_settings_status' ) ) {
        $settings_status = unserialize($settings_status);
        
        $admin_notice = function() use ($settings_status) {
          ?>
            <div class="notice notice-<?php echo $settings_status['type'] ?>">
              <p><?php echo $settings_status['message'] ?></p>
            </div>
          <?php
          };
        add_action( 'admin_notices', $admin_notice );

        delete_transient( 'coursestorm_settings_status' );
      }
    }

    /**
     * Determine whether we're able to connect to the CourseStorm API with the provided
     * credentials before saving them.
     */
    public static function save_options() {
      global $wpdb;

      
      // Get the POST data
      $input = $_POST;
      $subdomain = $input['subdomain'];
      $cron_schedule = $input['cron_schedule'];
      if ( isset( $input['cart_options'] ) ) {
        $cart_options = $input['cart_options'];
      }

      // Let's try to make an API request and see if it succeeds.
      $subdomain_validates = (strlen($subdomain) > 0) ? self::_verify_api_credentials( $subdomain ) : false;
      $is_network = $subdomain_validates ? self::_determine_if_site_is_network( $subdomain ) : false;
      $options = [];
      $settings_status = null;
      $previous_coursestorm_subdomain_setting = isset($input['original_subdomain']) ? $input['original_subdomain'] : null;
      $plugin_options = CourseStorm_Synchronize::get_credentials();
      $previous_cron_schedule = isset($plugin_options['cron_schedule']) ? $plugin_options['cron_schedule'] : null;
      $previous_cart_options = !empty($plugin_options['cart_options']) ? $plugin_options['cart_options'] : null;
      
      if ($subdomain_validates) {
        $options['subdomain'] = $subdomain;

        // See if the subdomain was changed during save, and put up a notice if it was
        $stored_coursestorm_subdomain_setting = isset( $plugin_options['subdomain'] ) ? $plugin_options['subdomain'] : null;
        if ( CourseStorm_Synchronize::is_changing_subdomain( $previous_coursestorm_subdomain_setting, $stored_coursestorm_subdomain_setting )) {
          set_transient( 'coursestorm_course_import_status', 'incomplete', HOUR_IN_SECONDS * 4 );
        }
      } else if( $subdomain_validates === false ) {
        // Make sure we retain the old subdomain setting on failure
        $options['subdomain'] = $previous_coursestorm_subdomain_setting;
        $settings_status = [
          'type' => 'error',
          'message' => __( 'The catalog URL you entered is not valid.', 'coursestorm' ),
        ];
      }

      if ( $previous_cron_schedule != $cron_schedule ) {
        $options['cron_schedule'] = $cron_schedule;

        self::_updateCronSchedule($cron_schedule);
      } else {
        $options['cron_schedule'] = $previous_cron_schedule;
      }

      // Compare to previous setting before setting
      if ( isset( $cart_options ) ) {
        if ( $previous_cart_options['view_cart_location'] !== $cart_options['view_cart_location'] ) {
          $options['cart_options']['view_cart_location'] = $cart_options['view_cart_location'];
        } else {
          $options['cart_options']['view_cart_location'] = $previous_cart_options['view_cart_location'];
        }
      }

      // Catch for initial install, and set the value
      if (empty($options['cart_options']['view_cart_location'])) {
        $options['cart_options']['view_cart_location'] = 'inline';
      }
      
      if ( $is_network ) {
        $options['is_network'] = $is_network;
      }

      // Update the options stored for the site.
      if( count( $options ) ) {
        update_option( 'coursestorm-settings', $options, false);
      }
        
      if( 
        $subdomain_validates
        && count(get_settings_errors( 'coursestorm' )) == 0
        && strlen( $subdomain ) > 0
        && $previous_coursestorm_subdomain_setting != $subdomain
      ) {
        $settings_status = [
          'type' => 'success',
          'message' => __( 'Your settings are saved and your classes are syncing! <a href="" onClick="location.reload();">Refresh to check import status</a>', 'coursestorm' ),
        ];
      } else if ( $subdomain_validates && count(get_settings_errors( 'coursestorm' )) == 0 ) {
        $settings_status = [
          'type' => 'success',
          'message' => __( 'Your settings are saved.', 'coursestorm' ),
        ];
      } else if (count(get_settings_errors( 'coursestorm' )) > 0) {
        $settings_status = [
          'type' => 'error',
          'message' => __( 'There was an error saving your settings. Please try again.', 'coursestorm' ),
        ];
      }

      if ( $settings_status ) {
        set_transient( 'coursestorm_settings_status', serialize($settings_status), HOUR_IN_SECONDS / 60);
      }

      flush_rewrite_rules();

      return $input;
    }

    /**
     * Sync CourseStorm Settings
     * 
     * Do an API call to refresh the CourseStorm settings stored within WP.
     *
     * @param string|null $subdomain CourseStorm Subdomain
     * @return void
     */
    public static function sync_coursestorm_settings($subdomain = null, $display_status = false) {
      if (!$subdomain) {
        $subdomain = get_option( 'coursestorm-settings' )['subdomain'];
      }

      $display_status = (!empty($display_status) || (isset($_POST['display_status']) && $_POST['display_status'])) ? true : false;

      $api = new \CourseStorm_WP_API( $subdomain, COURSESTORM_ENVIRONMENT );

      if ( $result = $api->get( '/info' ) ) {
        update_option( 'coursestorm-site-info', $result, false );

        if ($display_status) {
          $settings_status = [
            'type' => 'success',
            'message' => __( 'Your ' . COURSESTORM_BRAND_NAME . ' settings have been synced.', 'coursestorm' ),
          ];

          set_transient( 'coursestorm_settings_status', serialize($settings_status), HOUR_IN_SECONDS / 60);
        }

        return true;
      }

      $settings_status = [
        'type' => 'error',
        'message' => __( 'Your ' . COURSESTORM_BRAND_NAME . ' settings could not be synced.  Please try again.', 'coursestorm' ),
      ];

      set_transient( 'coursestorm_settings_status', serialize($settings_status), HOUR_IN_SECONDS / 60);

      return false;
    }

    private static function _verify_api_credentials($subdomain, $display_status = true) {
      return self::sync_coursestorm_settings($subdomain, $display_status);
    }
    
    private static function _determine_if_site_is_network($subdomain) {
      $api = new \CourseStorm_WP_API( $subdomain, 'live' );

      if ( $result = $api->get( '/connectedSites' ) ) {
        return true;
      }

      return false;
    }

    /**
     * Add our API settings page to the "Settings" menu in wp-admin.
     */
    public function register_api_options_page() {
      add_options_page(
        'CourseStorm for WordPress Settings',
        'CourseStorm for WordPress',
        'manage_options',
        'options_coursestorm_api',
        array(
          $this,
          'api_key_options'
        )
      );
    }

    /**
     * Add the settings that we'll use to store API credentials.
     */
    public function register_settings() {
      $coursestorm_site_info = get_option( 'coursestorm-site-info' );

      register_setting( 'coursestorm', 'coursestorm-settings', array( $this, 'verify_api_credentials' ) );

      add_settings_section( 'api-credentials', '', '', 'coursestorm' );
      add_settings_section( 'sync-configuration', 'Sync Configuration', '', 'coursestorm' );
      add_settings_field( 'subdomain', 'CourseStorm Catalog URL', array( $this, 'draw_subdomain_field' ), 'coursestorm', 'api-credentials' );
      add_settings_field( 'cron-schedule', 'CourseStorm Cron Schedule', array( $this, 'draw_cron_schdedule_field' ), 'coursestorm', 'sync-configuration' );
      if ( !empty( $coursestorm_site_info->cart ) ) {
        add_settings_section( 'cart-options', 'CourseStorm Cart Options', '', 'coursestorm' );
        add_settings_field( 'cart', 'View Cart Location', array( $this, 'draw_cart_options' ), 'coursestorm', 'cart-options' );
      }
    }

    public function draw_subdomain_field() {
      $settings = (array) get_option( 'coursestorm-settings' );
      $field = 'subdomain';
      $value = isset( $settings[$field] ) ? esc_attr( $settings[$field] ) : '';

      echo "<input type='hidden' name='original_subdomain' id='coursestorm-settings-original-$field' value='$value'>";
      echo "<input type='text' name='coursestorm-settings[$field]' id='coursestorm-settings-$field' value='$value'>.coursestorm." . COURSESTORM_TLD;
    }

    public function draw_cron_schdedule_field() {
      $settings = (array) get_option( 'coursestorm-settings' );
      $field = 'cron_schedule';
      $stored_value = isset( $settings[$field] ) ? esc_attr( $settings[$field] ) : '';
      $description = ' How often would you like the CourseStorm sync process to automatically run?';
      $options = [
        'hourly' => 'Hourly',
        'twicedaily' => 'Twice per day',
        'daily' => 'Daily'
      ];

      echo "<label for='coursestorm-settings[$field]'>";
      echo '<div class="sublabel">' . $description . '</div>';
      echo "<select name='coursestorm-settings[$field]' id='coursestorm-settings-$field'>";
        echo "<option value='' disabled='disabled'>Please select an option</option>";
        foreach ( $options as $key => $value ) {
          $selected = ($key == $stored_value) ? " selected='selected'" : null;
          echo "<option value='$key'$selected>$value</option>";
        }
      echo "</select>";
      echo '<small class="note">Default sync schedule is hourly.</small>';
      echo "</label>";
    }

    public function draw_cart_options() {
      $settings = (array) get_option( 'coursestorm-settings' );

      $section = 'cart_options';
      $field = 'view_cart_location';
      $stored_value = isset( $settings[$section][$field] ) ? $settings[$section][$field] : null;
      $description = ' Where would you like your cart button to show up on your site?';

      $options = [
        'inline'       => 'inline',
        'top-left'     => 'top left',
        'top-right'    => 'top right',
        'bottom-left'  => 'bottom left',
        'bottom-right' => 'bottom right'
      ];

      echo "<label for='coursestorm-settings[$section][$field]'>";
      echo '<div class="sublabel">' . $description . '</div>';
      $selected = null;
      echo "<select name='coursestorm-settings[$section][$field]' id='coursestorm-settings-$field'>";
      foreach ( $options as $key => $value ) {
        if (isset($stored_value) ) {
          $selected = ($key == $stored_value) ? " selected='selected'" : null;
        }
        echo "<option value='$key'$selected>$value</option>";
      }
      unset( $selected );
      echo "</select>";
      echo '<small class="note">If you\'ve added a View Cart link to a menu or in your theme, <br /> choose "inline" to keep it where you\'ve placed it, or choose a position if you\'d <br /> like it to float in a corner of the screen.</small>';
      echo "</label>";

    }

    /**
     * Add a link to the "Settings" page from the wp-admin plugin list.
     */
    public static function plugins_list_settings_link( $links ) {
      $settings_link = '<a href="options-general.php?page=options_coursestorm_api">' . __( 'Settings' ) . '</a>';
      array_push( $links, $settings_link );

      return $links;
    }

    public static function coursestorm_enqueue_admin_scripts($hook) {              
      wp_enqueue_script( 'coursestorm-ajax-script', plugin_dir_url( __FILE__ ) . 'assets/admin.js', array('jquery') );
    
      // in JavaScript, object properties are accessed as ajax_object.ajax_url, ajax_object.subdomain, ajax_object.cron_schedule
      wp_localize_script( 'coursestorm-ajax-script', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
    }

    private function _setSubdomain() {
      global $pagenow;
      $this->_subdomain = ! empty( CourseStorm_Synchronize::get_credentials()['subdomain'] ) ? CourseStorm_Synchronize::get_credentials()['subdomain'] : null;

      if( !$this->_subdomain && ($pagenow == 'options-general.php' && isset( $_GET['page'] ) && $_GET['page'] == 'options_coursestorm_api') ) {
        // Redirect to welcome page
        wp_redirect( add_query_arg( ['page' => 'coursestorm-welcome'], admin_url( 'plugins.php' ) ) );
        exit;
      }
    }

    private static function _updateCronSchedule($cron_schedule) {
      // Unschedule the current cron
      $timestamp = wp_next_scheduled( 'coursestorm_sync' );
      wp_unschedule_event( $timestamp, 'coursestorm_sync' );

      // Create a new cron
      if ( ! wp_next_scheduled( 'coursestorm_sync' ) ) {
        wp_schedule_event( time(), $cron_schedule, 'coursestorm_sync' );
      }
    }
  }

  add_action( 'admin_menu', function() { new CourseStorm_Admin; } );
  add_action( 'admin_enqueue_scripts', array( 'CourseStorm_Admin', 'coursestorm_enqueue_admin_scripts' ) );
  add_action( 'wp_ajax_coursestorm_sync', array( 'CourseStorm_Synchronize', 'run_wp_cron' ) );
  add_action( 'wp_ajax_coursestorm_options_save', array( 'CourseStorm_Admin', 'save_options' ) );
  add_action( 'wp_ajax_coursestorm_settings_sync', array( 'CourseStorm_Admin', 'sync_coursestorm_settings' ) );
  add_action( 'add_option_coursestorm-settings', array( 'CourseStorm_Synchronize', 'run_wp_cron' ) );
  add_action( 'update_option_coursestorm-settings', array( 'CourseStorm_Synchronize', 'run_wp_cron' ) );

}
