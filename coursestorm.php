<?php
/*
Plugin Name:  CourseStorm Class Registration for WordPress
Plugin URI:
Description:  Display your CourseStorm catalog on your website.
Version:      1.3.11
Author:       CourseStorm
Author URI:   https://www.coursestorm.com
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  coursestorm
*/

define('COURSESTORM_SIGNUP_URL', 'https://www.coursestorm.com/simple-online-registration/?utm_campaign=wordpress-plugin&utm_source=plugin&utm_medium=wordpress-plugin');
define('COURSESTORM_PLUGIN_VERSION', '1.3.11');
define('COURSESTORM_HTTP_REQUEST_TIMEOUT_IN_SECONDS', '30');
define( 'COURSESTORM_BRAND_NAME', 'CourseStorm' );
define( 'COURSESTORM_DEBUG', false );

if ( COURSESTORM_DEBUG ) {
  define('COURSESTORM_ENVIRONMENT', 'dev');
  define('COURSESTORM_TLD', 'test');
} else {
  define('COURSESTORM_ENVIRONMENT', 'live');
  define('COURSESTORM_TLD', 'com');
}

require_once dirname( __FILE__ ) . '/lib/helpers.php';
require_once dirname( __FILE__ ) . '/lib/coursestorm-api.php';
require_once dirname( __FILE__ ) . '/lib/coursestorm-wp-api.php';
require_once dirname( __FILE__ ) . '/templating.php';

require_once dirname( __FILE__ ) . '/admin/coursestorm-cpt.php';
require_once dirname( __FILE__ ) . '/admin/coursestorm-welcome.php';
require_once dirname( __FILE__ ) . '/synchronize.php';
require_once dirname( __FILE__ ) . '/search.php';

require_once dirname( __FILE__ ) . '/widgets/featured.php';
require_once dirname( __FILE__ ) . '/widgets/categories.php';
require_once dirname( __FILE__ ) . '/widgets/class-by-category.php';
require_once dirname( __FILE__ ) . '/widgets/upcoming-classes.php';
require_once dirname( __FILE__ ) . '/nav.php';
require_once dirname( __FILE__ ) . '/widgets/search.php';

if ( is_admin() ) {
  require_once dirname( __FILE__ ) . '/admin/coursestorm-options.php';
  require_once dirname( __FILE__ ) . '/admin/coursestorm-welcome.php';
}

/**
 * Handle plugin activation and deactivation to make sure our cron events are properly scheduled.
 */
function coursestorm_register_cron_events() {
  set_transient( 'coursestorm_sync_triggered_from_plugin_activation', 'yes', 10);
  if( !wp_next_scheduled( 'coursestorm_sync' ) ) {
    wp_schedule_event( time(), 'hourly', 'coursestorm_sync' );
  }
}

function coursestorm_deregister_cron_events() {
  wp_clear_scheduled_hook( 'coursestorm_sync' );
}

function coursestorm_refresh_permalinks() {
  $set = get_option( 'coursestorm_flushed_permalinks' );
  if( $set !== true ) {
    flush_rewrite_rules( false );
    update_option( 'coursestorm_flushed_permalinks', true );
  }
}

function coursestorm_insert_classes_page() {
  if ( ! current_user_can( 'activate_plugins' ) ) return;

  global $wpdb;

  if ( null === $wpdb->get_row( "SELECT post_name FROM {$wpdb->prefix}posts WHERE post_name = 'classes'", 'ARRAY_A' ) ) {

    $page_name = apply_filters( 'coursestorm-units-name', 'Classes' );
    $classes_page = array(
      'post_type' => 'page',
      'post_title' => $page_name,
      'post_status' => 'publish',
      'post_author' => get_current_user_id(),
      'page_template' => 'generic.php'
    );

    wp_insert_post( $classes_page );

  }
}

function custom_rewrite_rule() {
  add_rewrite_rule('^classes/category/([^/]*)/?','index.php?taxonomy$matches[1]','top');
}
add_action('init', 'custom_rewrite_rule', 10, 0);

add_action( 'coursestorm_sync', 'CourseStorm_Synchronize::start_sync' );
add_action( 'wp_async_task_coursestorm_sync_catalog', 'CourseStorm_Synchronize::synchronize_catalog' );
add_action( 'wp_async_task_coursestorm_sync_categories', 'CourseStorm_Synchronize::synchronize_categories' );
add_action( 'wp_async_task_coursestorm_sync_courses_fetch', 'CourseStorm_Synchronize::fetch_courses' );
add_action( 'wp_async_task_coursestorm_sync_courses_save', 'CourseStorm_Synchronize::save_courses' );
add_action( 'wp_async_task_coursestorm_sync_courses_delete', 'CourseStorm_Synchronize::delete_old_courses' );
add_filter( 'query_vars', 'CourseStorm_Search::add_query_vars_filter' );
register_activation_hook( __FILE__, 'coursestorm_register_cron_events' );
register_activation_hook( __FILE__, 'coursestorm_refresh_permalinks' );
register_activation_hook( __FILE__, 'coursestorm_insert_classes_page' );
register_activation_hook( __FILE__, ['CourseStorm_Welcome', 'welcome_screen_activate'] );

register_deactivation_hook( __FILE__, 'coursestorm_deregister_cron_events' );

new CourseStorm_SyncCatalog_Async_Task();
new CourseStorm_SyncCategories_Async_Task();
new CourseStorm_SyncCoursesFetch_Async_Task();
new CourseStorm_SyncCoursesSave_Async_Task();
new CourseStorm_SyncCoursesDelete_Async_Task();

/**
 * Add our generic stylesheet for layouts.
 */
function coursestorm_css() {
  wp_register_style( 'coursestorm', plugin_dir_url( __FILE__ ) . 'assets/coursestorm.css' );
  wp_enqueue_style( 'coursestorm' );
  wp_register_style( 'coursestorm-fonts', plugin_dir_url( __FILE__ ) . 'assets/fontello.css' );
  wp_enqueue_style( 'coursestorm-fonts' );
}
add_action( 'wp_enqueue_scripts', 'coursestorm_css' );

function coursestorm_admin_css() {
  global $wp_version;
  $version = $wp_version.'-'.COURSESTORM_PLUGIN_VERSION;
  wp_enqueue_style('coursestorm-admin-theme', plugins_url('admin/assets/admin.css', __FILE__), null, $version);
}
add_action('admin_enqueue_scripts', 'coursestorm_admin_css');

/**
 * Add a link to the plugin's setting pages in the plugin list.
 */
$plugin_name = plugin_basename( __FILE__ );
add_action( "plugin_action_links_$plugin_name", array( 'CourseStorm_Admin', 'plugins_list_settings_link' ) );
