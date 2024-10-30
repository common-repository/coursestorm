<?php
//this check makes sure that this file is called manually.
if (!defined("WP_UNINSTALL_PLUGIN")) 
    exit();

//put plugin uninstall code here
clean_up_transients();
clear_coursestorm_crons();

coursestorm_delete_options();

coursestorm_delete_old_subdomain_records();

flush_rewrite_rules();

function coursestorm_delete_old_subdomain_records() {
    // Search for any existing posts that are associated with this custom post type.
    $args = array(
        'nopaging' => true,
        'post_type'   => 'coursestorm_class',
    );
    $posts = get_posts( $args );
    // $terms = get_terms( array( 'taxonomy' => 'coursestorm_categories', 'fields' => 'ids', 'hide_empty' => false ) );
    $terms = load_terms( 'coursestorm_categories' );
    
    $categories_deleted = coursestorm_delete_categories( $terms );
    $posts_deleted = coursestorm_delete_posts( $posts );

    return $posts_deleted && $categories_deleted ? true : false;
}

function coursestorm_delete_options() {
    $widget_options = get_coursestorm_widgets_options();
    
    coursestorm_delete_widget_options( $widget_options );
    delete_option( 'coursestorm-settings' );
    delete_option( 'coursestorm-site-info' );
    delete_option( 'coursestorm_flushed_permalinks' );
}

function coursestorm_delete_widget_options($options) {
    foreach ( $options as $option ) {
        delete_option($option);
    }
}

function coursestorm_delete_categories($terms) {
    foreach ( $terms as $term ) {
        wp_delete_term( $term['term_id'], 'coursestorm_categories' );
    }

    return true;
}

function coursestorm_delete_posts($posts) {
    foreach( $posts as $post ) {
        // Delete the post categories
        wp_delete_post( $post->ID, true );
    }

    return true;
}

function load_terms($taxonomy){
    global $wpdb;
    $query = 'SELECT DISTINCT 
            t.term_id 
        FROM '
            . $wpdb->dbname . '.' . $wpdb->prefix . 'terms t 
        INNER JOIN ' 
            . $wpdb->dbname . '.' . $wpdb->prefix . 'term_taxonomy tax 
        ON 
            `tax`.term_id = `t`.term_id
        WHERE 
            ( `tax`.taxonomy = \'' . $taxonomy . '\')';                     
    $result =  $wpdb->get_results($query , ARRAY_A);
    return $result;                 
}

function get_coursestorm_widgets_options() {
    global $wpdb;
    $query = 'SELECT DISTINCT 
            * 
        FROM '
            . $wpdb->dbname . '.' . $wpdb->prefix . 'options o
        WHERE 
            ( `o`.`option_name` = \'%widget_coursestorm%\')';
    $result =  $wpdb->get_results($query , ARRAY_A);
    return $result; 
}

/**
 * Clean up transients
 *
 * @return void
 */
function clean_up_transients() {
    delete_transient( 'coursestorm_course_import_progress' );
    delete_transient( 'coursestorm_sync_triggered_from_ajax_call' );
    delete_transient( 'coursestorm_sync_triggered_manually' );
    delete_transient( 'coursestorm_import_step_transient' );
    delete_transient( 'coursestorm_import_current_course_page_transient' );
    delete_transient( 'coursestorm_import_courses_raw_course_list' );
    delete_transient( 'coursestorm_sync_triggered_from_options_page' );
}

function clear_coursestorm_crons() {
    wp_clear_scheduled_hook('coursestorm_sync');
    wp_clear_scheduled_hook('coursestorm_sync_courses');
    wp_clear_scheduled_hook('coursestorm_sync_categories');
    wp_clear_scheduled_hook('coursestorm_sync_save_courses_as_posts');
}