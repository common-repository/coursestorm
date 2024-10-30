<?php
class CourseStorm_CPT {
  /**
   * Register our custom post type.
   */
  public static function register() {
    $args = array(
      'label'               => 'Classes',
      'labels'              => array(
        'search_items'        => 'Search Classes',
      ),
      'description'         => 'Classes synchronized via CourseStorm',
      'public'              => false,
      'exclude_from_search' => false,
      'publicly_queryable'  => true,
      'show_in_nav_menus'   => false,
      'show_in_admin_bar'   => false,
      'map_meta_cap'        => true,
      'menu_icon'           => 'dashicons-calendar-alt',
      'capabilities'        => array( 'read_post' ),
      'hierarchial'         => false,
      'supports'            => array( 'title' ),
      'taxonomies'          => array( 'coursestorm_categories' ),
      'rewrite'             => array( 'slug' => strtolower( apply_filters( 'coursestorm-unit-name', 'class' ) ), 'with_front' => false ),
      'has_archive'         => true,
    );

    $taxonomy_args = array(
      'hierarchial'         => false,
      'label'               => 'Class Categories',
      'show_admin_column'   => true,
      'show_ui'             => false,
      'rewrite'             => array( 'slug' => 'classes/categories', 'with_front' => false ),

    );

    register_taxonomy( 'coursestorm_categories', null, $taxonomy_args );
    register_post_type( 'coursestorm_class', $args );
  }

  /**
   * Remove access to post-new.php
   */
  public static function remove_post_new_access() {
    global $_REQUEST,$pagenow;
    if ( !empty( $_REQUEST['post_type'] )
      && 'coursestorm_class' == $_REQUEST['post_type']
      && !empty($pagenow)
      && 'post-new.php' == $pagenow ) {
        wp_safe_redirect( admin_url( 'edit.php?post_type=coursestorm_class' ) );
    }
  }

  /**
   * Remove the "Add New" menu.
   */
   public static function remove_add_new_menu() {
     remove_submenu_page( 'edit.php?post_type=coursestorm', 'post-new.php?post_type=coursestorm_class' );
   }

   /**
    * Remove the "add new" link on the edit page.
    */
  public static function remove_post_new_link() {
    global $post_new_file,$post_type_object;
    if ( !isset( $post_type_object ) || $post_type_object->name != 'coursestorm_class' ) return false;

    $post_type_object->labels->add_new = 'Return to Index';
    $post_new_file = admin_url( 'edit.php?post_type=coursestorm_class' );
  }

  /**
   * Show our custom taxonomy in the sort list.
   */
  public static function show_sort_options() {
    global $typenow;

    if ( $typenow == 'coursestorm_class' ) {
      $args = array(
        'show_option_all'   => "Show All Categories",
        'taxonomy'          => 'coursestorm_categories',
        'name'              => 'coursestorm_categories',
        'selected'          => isset( $_REQUEST['coursestorm_categories'] ) ? $_REQUEST['coursestorm_categories'] : null,
      );

      wp_dropdown_categories( $args );
    }
  }

  /**
   * Ensure that we pass our category query into the URL as well.
   */
  public static function add_terms_to_request( $request ) {
    $current_url = substr( $GLOBALS['PHP_SELF'], -18);
    if ( is_admin() && $current_url == '/wp-admin/edit.php' && isset( $request['post_type'] ) && $request['post_type'] == 'coursestorm_class' ) {
      if( isset( $request['coursestorm_categories'] ) ) {
        $term_details = get_term( $request['coursestorm_categories'], 'coursestorm_categories' );
        if( $term_details ) {
          $request['term'] = $term_details->name;
          $request['coursestorm_categories'] = $term_details->slug;
        }
      }
    }

    return $request;
  }

  /**
   * Alter the columns that we display in edit.php
   */
  public static function admin_curate_table_columns( $columns ) {
    $new_columns = array(
      'class_date'  => 'Start Date',
      'instructor'  => 'Instructor',
    );

    unset( $columns['cb'] );
    unset( $columns['date'] );
    return array_merge( $columns, $new_columns );
  }

  /**
   * Populate data for our custom table columns.
   */
  public static function admin_populate_table_columns( $column ) {
    global $post;

    switch( $column ) {
      case 'class_date':
        $session = get_post_meta( $post->ID, 'sessions', true );

        if( isset( $session[0]['start_date'] ) ) {
          echo $session[0]['start_date'];
        }
        break;
      case 'instructor':
        $instructor_details = get_post_meta( $post->ID, 'instructor', true );

        if( isset( $instructor_details['first_name'] ) && isset( $instructor_details['last_name'] ) ) {
          echo $instructor_details['first_name'] . ' ' . $instructor_details['last_name'];
        }
        break;
    }
  }
}

add_action( 'init', 'CourseStorm_CPT::register' );
add_action( 'admin_menu', 'CourseStorm_CPT::remove_post_new_access' );
add_action( 'admin_menu', 'CourseStorm_CPT::remove_add_new_menu' );
add_action( 'admin_head', 'CourseStorm_CPT::remove_post_new_link' );
add_action( 'restrict_manage_posts', 'CourseStorm_CPT::show_sort_options' );
add_action( 'request', 'CourseStorm_CPT::add_terms_to_request' );
add_action( 'manage_coursestorm_posts_columns', 'CourseStorm_CPT::admin_curate_table_columns' );