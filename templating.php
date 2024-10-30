<?php
class CourseStorm_Templates {
  /**
   * Utilize our custom CourseStorm templates when the time is right.
   */
  public static function select_appropriate_template( $template ) {
    // Properly handle category archives
    if( is_archive() && is_tax() && get_query_var('taxonomy') == 'coursestorm_categories' ) {
      $new_template = locate_template('coursestorm-archive.php');
      if ( file_exists($new_template) ) {
        return $new_template;
      } else {
        return self::find_template( 'archive' );
      }

    }

    // Properly handle the "all courses" archive
    if( is_post_type_archive( 'coursestorm_class' ) ) {
      $new_template = locate_template('coursestorm-archive.php');
      if ( file_exists($new_template) ) {
        return $new_template;
      } else {
        return self::find_template( 'archive' );
      }
    }

    global $post;

    // Fetch the page-specific template if need be
    if( $post ) {

      if( $post->post_type == 'coursestorm_class' && !is_search() ) {
        $new_template = locate_template('coursestorm-single.php');
        if ( file_exists($new_template) ) {
          return $new_template;
        } else {
          return self::find_template( 'single' );
        }
      }

      if ( is_page( 'classes' ) ) {
        $new_template = locate_template('coursestorm-generic.php');
        if ( file_exists($new_template) ) {
          return $new_template;
        } else {
          return self::find_template( 'generic' );
        }

      }

      return $template;
    }

    return $template;
  }

  /**
   * Add our generic page template to the "templates" drop down on the home page.
   */
  public static function add_page_template( $templates ) {
    $templates = array_merge( array( self::find_template( 'generic' ) => 'CourseStorm Index' ), $templates );
    return $templates;
  }

  /**
   * Load the appropriate template.
   */
  private static function find_template( $template_name ) {
    $template_slug = rtrim( $template_name, '.php' );
    $template = $template_slug . '.php';

    $file = dirname( __FILE__ ) . '/templates/' . $template;
    return apply_filters( 'coursestorm_template_' . $template_slug, $file );
  }

  /**
   * Add custom sorting filters to the archive template
   */

  public static function add_sort_query_var() {
    add_rewrite_tag( '%sort%', '([^/]+)' );
  }

  public static function sort_classes_based_on_query( $query ) {
    if ( is_admin() )
      return;

    $sort = get_query_var( 'sort' );

    if (
      is_post_type_archive( 'coursestorm_class' ) && $query->is_main_query()
      || is_tax('coursestorm_categories') && $query->is_main_query()
    ) {
      if ($sort) {
        $query->set( 'order', 'ASC' );
        if ( $sort == 'title' ) {
          $query->set( 'orderby', 'title' );
        } elseif ( $sort == 'date' ) {
          $query->set( 'orderby', 'meta_value' );
          $query->set( 'meta_key', 'first_session_date' );
        } elseif ( $sort == 'price' ) {
          $query->set( 'orderby', 'meta_value_num' );
          $query->set( 'meta_key', 'price');
        }
      } else {
        $query->set( 'meta_key', 'first_session_date' );
      }
    }
  }

  /**
   * Upcoming session order by
   * 
   * Force the order by clause for the date and default sort
   * to order by `upcoming_session_date` with NULL values last and
   * sorted alphabetically by `post_title`
   * 
   * @param string $orderby The current `orderby` property in the WP_Query()
   * @return string $orderby
   */
  public static function upcoming_session_order_by($orderby, $wp_query) {
    global $wpdb;
  
    $sort = get_query_var( 'sort' );
    $tablePrefix = $wpdb->prefix;
  
    $queriedObject = $wp_query->get_queried_object();
  
    if (isset($queriedObject)) {
      if (
        (is_array($queriedObject->taxonomies) && in_array('coursestorm_categories', $queriedObject->taxonomies))
        && (is_post_type_archive( 'coursestorm_class' ) || is_tax( 'coursestorm_categories' ))
        && ($sort == 'date' || empty($sort))
        && is_main_query()
      ) {
        $orderby = '-' . $tablePrefix . 'postmeta.meta_value DESC, ' . $tablePrefix . 'postmeta.meta_value ASC, ' . $tablePrefix . 'posts.post_title';
      }
    }
      
    return $orderby;
  }

  public static function coursestorm_plugin_scripts() {

    // Enqueue script for filtering dropdowns
    if (
      is_post_type_archive( 'coursestorm_class' ) ||
      is_tax() && get_query_var('taxonomy') == 'coursestorm_categories'
    ) {
      wp_enqueue_script('coursestorm-filters', plugin_dir_url( __FILE__ ) . 'assets/coursestorm.js', array('jquery'), '', true );
    }

    // Register slideshow script (enqueued within the shortcode function)
    wp_register_script('jquery-cycle-core', plugin_dir_url( __FILE__ ) . 'assets/jquery.cycle2.core.min.js', array('jquery'), '', true );
    
    // CourseStorm widget JS
    $coursestorm_site_info = get_option( 'coursestorm-site-info' );
    if ( isset( $coursestorm_site_info->subdomain ) ) {
      wp_enqueue_script('coursestorm-embed', 'https://' . $coursestorm_site_info->subdomain . '.coursestorm.' . COURSESTORM_TLD . '/js/embed/embed.js', array('jquery'), COURSESTORM_PLUGIN_VERSION, true );
    }

  }

  /**
   * Add ID To Embed Widget Script
   * 
   * @param string $tag The script tag
   * @param string $handle Enqueued script handle
   * @param string $src Enqueue script src
   * @return string Formatted script tag
   */
  public static function add_id_to_embed_widget_script( $tag, $handle, $src ) {
    if ($handle != 'coursestorm-embed') {
      return $tag;
    }

    $pattern = "/src=/";
    return preg_replace( $pattern, "id='coursestorm-embed-script' src=", $tag );
  }

  public static function coursestorm_plugin_styles_for_themes() {
    $template = self::get_template();
    $file_path = 'assets/css/coursestorm-' . $template . '.css';
    $coursestorm_site_info = get_option( 'coursestorm-site-info' );

    if( file_exists( plugin_dir_path( __FILE__ ) . $file_path ) ) {
      wp_register_style( 'coursestorm-' . $template, plugin_dir_url( __FILE__ ) . $file_path, null, COURSESTORM_PLUGIN_VERSION );
      wp_enqueue_style( 'coursestorm-' . $template );
    }

    wp_register_style( 'coursestorm-' . $template . '-embed', 'https://' . $coursestorm_site_info->subdomain . '.coursestorm.' . COURSESTORM_TLD . '/css/embed/embed.css' );
    wp_enqueue_style( 'coursestorm-' . $template . '-embed' );
    wp_register_style( 'coursestorm-' . $template . '-view-cart', 'https://' . $coursestorm_site_info->subdomain . '.coursestorm.' . COURSESTORM_TLD . '/css/embed/view-cart.css' );
    wp_enqueue_style( 'coursestorm-' . $template . '-view-cart' );

    wp_enqueue_style( 'dashicons' );
  }

  /**
   * Register action for displaying HTML before the main content
   *
   * @param bool $full Whether to display the page as full-width
   */
  public static function coursestorm_before_main_content( $full ) {

  	// get the current theme's name
  	$template = get_option( 'template' );

  	// set HTML based on current theme
    switch ( $template ) {
      case 'twentyeleven' :
        echo '<div id="primary"><div id="content" role="main" class="twentyeleven">';
        break;
      case 'twentytwelve' :
        echo '<div id="primary" class="site-content"><div id="content" role="main" class="twentytwelve">';
        break;
      case 'twentythirteen' :
        echo '<div id="primary" class="site-content"><div id="content" role="main" class="entry-content twentythirteen">';
        break;
      case 'twentyfourteen' :
        echo '<div id="primary" class="content-area"><div id="content" role="main" class="site-content twentyfourteen"><div class="tfwc">';
        break;
      case 'twentyfifteen' :
        echo '<div id="primary" role="main" class="content-area twentyfifteen"><div id="main" class="site-main t15wc">';
        break;
      case 'twentysixteen' :
        // echo '<div id="primary" class="content-area twentysixteen"><main id="main" class="site-main" role="main">';
        break;
      case 'twentyseventeen' :
        echo '<div class="wrap twentyseventeen">';
        //echo '<div id="primary" class="content-area"><main id="main" class="site-main" role="main">';
        break;
      case 'twentynineteen':
        echo '<section id="primary" class="content-area">';
        echo '<main id="main" class="site-main">';
        break;
      case 'genesis' :
        echo '<div class="site-inner"><div class="wrap"><div class="content-sidebar-wrap"><main class="content" id="genesis-content">';
        break;
      default :
        echo '<div class="container wrap">';
        break;
    }
  } // end coursestorm_before_main_content()


  /**
   * Register action for displaying HTML after the main content
   *
   * @param bool $sidebar Whether to display the sidebar
   */
  public static function coursestorm_after_main_content( $sidebar ) {

  	$template = get_option( 'template' );

    switch ( $template ) {
      case 'twentyeleven' :
        echo '</div>';
        echo '</div>';
        break;
      case 'twentytwelve' :
        echo '</div></div>';
        break;
      case 'twentythirteen' :
        echo '</div></div>';
        break;
      case 'twentyfourteen' :
        echo '</div></div></div>';
        break;
      case 'twentyfifteen' :
        echo '</div></div>';
        break;
      case 'twentysixteen' :
        // echo '</main></div>';
        break;
      case 'twentyseventeen' :
        //echo '</main></div>';
        echo '</div>';
      case 'twentynineteen':
        echo '</section>';
        echo '</main>';
        break;
      case 'genesis' :
        echo '</main></div></div></div>';
        break;
      default :
        echo '</div>';
        break;
    }

  } // end coursestorm_after_main_content()


  /**
   * Add custom class(es) to the body tag
   * This helps us apply styling that themes might inject using body classes
   */

   public static function coursestorm_body_tags($classes) {

		if (is_page('classes') || is_post_type_archive('coursestorm_class') || is_singular('coursestorm_class') ) {

		  	$template = get_option( 'template' );

			switch ( $template ) {
				case 'twentyseventeen' :
					$classes[] = 'coursestorm-plugin';
					return $classes;
					break;
				case 'genesis' :
					$classes[] = 'full-width-content';
					return $classes;
					break;
				default :
					$classes[] = 'coursestorm-plugin';
					return $classes;
					break;
			}

		} else { return $classes; }

	} // end coursestorm_body_tags()


  /**
   * Register action for displaying the featured courses
   */

  public static function coursestorm_featured_shortcode() {

    $args = array(
      'post_type'       => 'coursestorm_class',
      'posts_per_page'  => 5,
      'meta_key'        => 'featured',
      'meta_value'      => 1,
      'orderby'         => 'rand'
    );

    $featured_courses = get_posts( $args );

    $shortcode_content = '';

    if ( $featured_courses ) {
      // enqueue previously registered script
      wp_enqueue_script('jquery-cycle-core');
      $class_name = apply_filters( 'coursestorm-units-name', 'Classes' );

      $shortcode_content .= '<div class="coursestorm-featured-courses">';
      $shortcode_content .= '<h2>Featured ' . $class_name . '</h2>';
      $shortcode_content .= '<div class="coursestorm-featured-courses-slider cycle-slideshow" data-cycle-slides="> div.coursestorm-single-slide">';

      foreach ( $featured_courses as $course ) {

        // getting all the post_meta variables
        $course_url = get_post_meta( $course->ID, 'url', true );
        $course_img = get_post_meta( $course->ID, 'image', true );
        $course_instructor = get_post_meta( $course->ID, 'instructor', true );
        $course_site = get_post_meta( $course->ID, 'site', true );
        $sessions = get_post_meta( $course->ID, 'sessions', true );
        if (is_string($sessions)) {
          $sessions = [];
        }
        $description = get_post_meta( $course->ID, 'description', true );

  		  // For our initial version, we're only supporting a single session -- the first one returned -- and assume it will always be returned.
        if ( count($sessions) ) {
            $session = coursestorm_get_next_course_session($sessions);
        }

        if ( ! empty( $course_img ) ) {
          $shortcode_content .= '<div class="coursestorm-single-slide" style="background-image: url(' . esc_url( $course_img->thumbnail_url ) . ');">';
            $shortcode_content .= '<div class="coursestorm-slide-description">';
              $shortcode_content .= '<h3><a href="' . esc_url( get_permalink( $course->ID ) ) . '">' . esc_html( $course->post_title ) . '</a></h3>';
              if ( $course_site || $course_instructor ) {
                $shortcode_content .= '<p class="coursestorm-instructor">with ';
                
                if ( $course_instructor ) {
                  $shortcode_content .= '<i class="icon-user"></i> <span class="coursestorm-instructor-name">' . esc_html( $course_instructor->first_name . ' ' . $course_instructor->last_name ) . '</span>';
                  if ( $course_site ) {
                    $shortcode_content .= ',';
                  }
                }
                if ( $course_site ) {
                  if ( $course_site->external_website_url ) {
                    $shortcode_content .= '<a href="' . $course_site->external_website_url . '"';
                  } else {
                    $shortcode_content .= '<span';
                  }
                  $shortcode_content .= ' class="coursestorm-program-name">';
                  $shortcode_content .= $course_site->name;
                  if ( $course_site->external_website_url ) {
                    $shortcode_content .= '</a>';
                  } else {
                    $shortcode_content .= '</span>';
                  }
                }
                
                $shortcode_content .= '</p>';
              }
              if ( $session && $session->start_date ) {
                  $shortcode_content .= '<p class="coursestorm-date-info">' . coursestorm_format_date_range( $session->start_date, $session->end_date ) . '</p>';
              }
            $shortcode_content .= '</div>';
          $shortcode_content .= '</div>';
        }

      } // end foreach

      $shortcode_content .= '<div class="cycle-pager"></div>';

      $shortcode_content .= '</div>';
      $shortcode_content .= '</div>';

    }

    return $shortcode_content;

  } // end coursestorm_display_featured_courses()


  /**
   * Register action for displaying the course categories
   */

  public static function coursestorm_category_shortcode() {

    $category_args = array(
      'taxonomy' => 'coursestorm_categories',
      'hide_empty' => true,
      'echo' => false,
      'title_li' => '',
    );

    $classes_label = apply_filters( 'coursestorm-units-name', 'Classes' );

    $categories = wp_list_categories( $category_args );
    preg_match( "/No categories/", $categories, $matches );

    $class_page_url = get_post_type_archive_link( 'coursestorm_class' );

    $heading = '<h2>Browse Categories</h2>';
    
    $shortcode_content = '<div class="coursestorm-browse-categories';
    if( ! empty ( $matches[0] ) ) {
      $shortcode_content .= ' no-categories">';
      $shortcode_content .= $heading;
      $shortcode_content .= '<p>No categories available</p>';
    } else {
      $shortcode_content .= '">';
      $shortcode_content .= $heading;
      $shortcode_content .= '<ul>';
      $shortcode_content .= $categories;
      $shortcode_content .= '</ul>';
    }

    $class_args = array(
      'post_type' 			=> 'coursestorm_class'
    );

    $classes = new WP_Query( $class_args );

    if ($classes->have_posts()) {
      $shortcode_content .= '<p class="coursestorm-classes-link"><a href="' . $class_page_url . '">Browse all&nbsp;'. strtolower($classes_label) .'&nbsp;&raquo;</a></p>';
    }

    $shortcode_content .= '</div>';

    return $shortcode_content;

  } // end coursestorm_display_course_categories()

  public static function add_category_slug_as_class($css_classes, $category, $depth, $args) {
    $css_classes[] = 'coursestorm-' . $category->slug;
    return $css_classes;
  }

  private static function get_template() {
    return get_option( 'template' );
  }

  public static function element( $element, $properties = null ) {
    global $wp;
    require 'templates/elements/' . $element . '.php';
  }

  public static function coursestorm_alter_search_results_content( $content ) {
    // Get post taxonomies
    $taxonomies = get_post_taxonomies();

    // Alter post output for courses on the search page.
    if ( in_array( 'coursestorm_categories', $taxonomies ) && is_search() && is_main_query()) : 
      $coursestorm_site_info = get_option( 'coursestorm-site-info' );
      $coursestorm_site_plugins = array_column($coursestorm_site_info->plug_ins, 'key');
      $site_supports_sessions = in_array( 'course_sessions', $coursestorm_site_plugins );
      $course_url = get_post_meta( get_the_ID(), 'url', true );
      $course_img = get_post_meta( get_the_ID(), 'image', true );
      $course_instructor = get_post_meta( get_the_ID(), 'instructor', true );
      $course_site = get_post_meta( get_the_ID(), 'site', true );
      $course_continuous_enrollment = get_post_meta( get_the_ID(), 'continuous_enrollment', true );
      $sessions = get_post_meta( get_the_ID(), 'sessions', true );
      if (is_string($sessions)) {
        $sessions = [];
      }
      $price = get_post_meta( get_the_ID(), 'price', true );
      $price_formatted = $price == '0' ? 'Free' : '$' . esc_html( $price );
      $description = get_post_meta( get_the_ID(), 'description', true );
    ?>
      <a href="<?php the_permalink(); ?>" title="Read more about <?php the_title(); ?>">
        <?php if ( isset( $course_img ) ) : ?>
        <img src="<?php echo esc_url( $course_img->thumbnail_url ); ?>" class="coursestorm-course-image" alt="<?php echo esc_html( $course_img->attribution ); ?>">
        <?php else : ?>
        <div class="coursestorm-course-image icon-book"></div>
        <?php endif;?>
      </a>

      <div class="coursestorm-course-info">

        <div class="coursestorm-details-top">

          <header class="coursestorm-details-title">
            <h1 class="coursestorm-course-title entry-title">
              <a href="<?php the_permalink(); ?>" title="<?php the_title(); ?>"><?php the_title(); ?></a>
            </h1>

            <?php if ( $course_site || $course_instructor ) : ?>
              <p class="coursestorm-instructor">
              with 
              <?php if ( $course_instructor ) : ?>
                  <i class="icon-user"></i>
                  <span class="coursestorm-instructor-name"><?php echo esc_html( $course_instructor->first_name . ' ' . $course_instructor->last_name ); ?></span><?php if ( $course_site) : ?><span class="coursestorm-instructor-separator">,</span><?php endif; ?>
                  <?php endif; ?>
              <?php if ( $course_site ) : ?>
                <?php if ($course_site->external_website_url) : ?>
                  <a href="<?php echo esc_html( $course_site->external_website_url );?>" class="coursestorm-program-name"><?php echo esc_html( $course_site->name ); ?></a>
                <?php else : ?>
                  <span class="coursestorm-program-name"><?php echo esc_html( $course_site->name ); ?></span>
                <?php endif; ?>
              <?php endif; ?>
              </p>
            <?php endif; ?>

            <?php if ( count($sessions) ) :
              $next_session = coursestorm_get_next_course_session( $sessions, true, $course_continuous_enrollment );
              
              if ($next_session) :
                $start_date = $next_session->start_date;
                $end_date = ($start_date != $next_session->end_date) ? $next_session->end_date : null;
                if ($start_date ) :
            ?>
                <div class="coursestorm-details-date">
                  <p class="coursestorm-date-info">
                    <i class="icon-calendar"></i>
                    <?php if ( $site_supports_sessions && count($sessions) >= 1 ) : ?>
                      <strong>Next session: </strong>
                    <?php endif; ?>
                    <?php echo coursestorm_format_date_range( $start_date, $end_date ); ?>
                  </p>
                </div>
              <?php 
                      endif;
                    endif;
                    unset($next_session);
                  endif; 
              ?>
          </header>

          <div class="coursestorm-course-register">
            <span class="coursestorm-course-price">
              <?php if ( $price || $price === '0' ) {
                echo esc_html( $price_formatted );
              } else {
                echo '<span class="coursestorm-no-price">Price not available</span>';
              } ?>
            </span>
            <a href="<?php the_permalink(); ?>" title="More information about <?php the_title(); ?>" class="coursestorm-action-button">More Info</a>
          </div>
        </div><!-- /coursestorm-details-top -->

        <div class="coursestorm-course-description">
          <?php echo wpautop( $description ); ?>
        </div>

        <?php
          $course_continuous_enrollment = get_post_meta( get_the_ID(), 'continuous_enrollment', true );
          $register_online = get_post_meta( get_the_ID(), 'register_online', true );
          $next_session = null;
          if (count($sessions)) :
            $next_session = coursestorm_get_next_course_session( $sessions, false, $course_continuous_enrollment );

            if ($next_session && ($payment_plan_schedule = $next_session->payment_plan_schedule)) : 
              apply_filters( 'element', 'payment-plan-schedule', $next_session->payment_plan_schedule );
            endif;
          endif;
        ?>
      </div>

      <?php
        // Get the registration status for the course
        $registration_status = CourseStorm_Templates::get_course_sessions_registration_status($sessions, $next_session, $course_continuous_enrollment, $register_online);
      ?>

      <?php if ( count( $registration_status ) ) :?>
        <?php foreach ( $registration_status as $status => $label) : ?>
        <div class="coursestorm-registration-status coursestorm-registration-<?php echo $status; ?>"><?php echo $label; ?></div>
        <?php endforeach; ?>
      <?php endif; ?>
    <?php endif;
  }

  /**
   * Get Course Sessions Registration Status
   * 
   * Determine the registration status for the course based
   * on the course sessions.
   *
   * @param array $sessions
   * @param object $next_session
   * @param bool $continuous_enrollment
   * @param bool $register_online
   * @return void
   */
  public static function get_course_sessions_registration_status($sessions = [], $next_session = null, $continuous_enrollment = null, $register_online = null) {
    if (count($sessions)) {
      // Get sessions that are open for registration
      $sessionsOpenForRegistration = array_filter(
        $sessions,
        function ($session) {
          return $session->open_for_registration == true;
        }
      );

      // Get sessions that are open for waiting list
      $sessionsOpenForWaitingList = array_filter(
        $sessions,
        function ($session) {
          return $session->open_for_waiting_list == true;
        }
      );

      // Get sessions that have a status of open
      $sessionsThatAreOpen = array_filter(
        $sessions,
        function ($session) {
          return $session->status == 'open';
        }
      );

      // Determine if the next session has begun
      $nextSessionHasBegun = (isset($next_session->start_date) && (strtotime($next_session->start_date) <= time())) && $continuous_enrollment;

      $courseHasASessionThatIsOpenForRegistration = (bool) count($sessionsOpenForRegistration);
      $courseHasASessionThatIsOpenForWaitingList = (bool) count($sessionsOpenForWaitingList);
      $courseHasASessionThatIsOpen = (bool) count($sessionsThatAreOpen);

      $registration_status = [];

      switch (true) {
        case ( ! $courseHasASessionThatIsOpen ) :
          $registration_status['closed'] = 'Registration closed';
          break;
        case ( ! $courseHasASessionThatIsOpenForRegistration && $courseHasASessionThatIsOpenForWaitingList ) :
          $registration_status['waiting_list'] = 'Waiting list available';
          break;
        case ( (!isset($next_session) && isset(end($sessions)->start_date)) || (is_object( $next_session ) && $next_session->id === end($sessions)->id) && $nextSessionHasBegun ) :
          $registration_status['in_progress'] = 'Class has begun';
          break;
        case ( ! $register_online ) :
          $registration_status['unavailable'] = 'Online registration unavailable';
          break;
        case ( ! $courseHasASessionThatIsOpenForRegistration ) :
          $registration_status['unavailable'] = 'Registration unavailable';
          break;
      }
    } else {
      $registration_status['unavailable'] = 'Registration unavailable';
    }

    return $registration_status;
  }
  
  public static function get_course_session_registration_status($session = null, $continuous_enrollment = null, $register_online = null) {
    if ($session) {
      $sessionIsOpen = isset($session->status) && $session->status == 'open';
      $sessionIsCancelled = isset($session->status) && $session->status == 'cancelled';

      $sessionHasBegun = (isset($session->start_date) && (strtotime($session->start_date) <= time())) && $continuous_enrollment;

      $registration_status = [];

      switch (true) {
        case ( $sessionIsCancelled ) :
          $registration_status['cancelled'] = 'Next class is canceled';
          break;
        case ( ! $sessionIsOpen ) :
          $registration_status['closed'] = 'Registration closed';
          break;
        case ( $sessionHasBegun ) :
          $registration_status['in_progress'] = 'Class has begun';
          break;
        case ( ! $register_online ) :
          $registration_status['unavailable'] = 'Online registration unavailable';
          break;
      }
    } else {
      // We don't have a session, so we assume the class is cancelled
      $registration_status['cancelled'] = 'Class is canceled';
    }

    return $registration_status;
  }

  /** Redirect to post type page
   * 
   * If we are on the classes page, and we don't have any
   * categories or featured classes, redirect to the post type page.
   *
   * @return void
   */
  public static function redirect_to_post_type_page() {
    $pagename = get_query_var('pagename');
    
    if ($pagename == 'classes') {
      $category_args = array(
        'taxonomy' => 'coursestorm_categories',
        'hide_empty' => true,
        'echo' => false,
        'title_li' => '',
      );
  
      $categories = wp_list_categories( $category_args );
      preg_match( "/No categories/", $categories, $matches );
  
      $featured_class_args = array(
        'meta_key'  			=> 'featured',
        'meta_value'      => 1,
        'post_type' 			=> 'coursestorm_class'
      );
      
      $featured_classes = new WP_Query( $featured_class_args );

      $class_args = array(
        'post_type' 			=> 'coursestorm_class'
      );
      
      $classes = new WP_Query( $class_args );
      
      if ( ! empty ( $matches[0] ) && !$featured_classes->have_posts() && $classes->have_posts() ) {
        // We don't have any categories or featured classes, but have classes.
        // Redirect to the classes page.
        wp_redirect(get_post_type_archive_link( 'coursestorm_class' ));
        exit;
      }
    }
  }

  public static function unit_filter($string) {
    if(defined('COURSESTORM_UNIT_TERM')) {
      $uppercase_string = str_replace('Class', COURSESTORM_UNIT_TERM, $string, $uppercase_count);
      $lowercase_string = str_replace('class', COURSESTORM_UNIT_TERM, $string, $lowercase_count);

      if ($uppercase_count) {
        return $uppercase_string;
      } else if ($lowercase_count) {
        return $lowercase_string;
      }
    }

    return $string;
  }

  public static function units_filter($string) {
    if(defined('COURSESTORM_UNITS_TERM')) {
      $uppercase_string = str_replace('Classes', COURSESTORM_UNITS_TERM, $string, $uppercase_count);
      $lowercase_string = str_replace('classes', COURSESTORM_UNITS_TERM, $string, $lowercase_count);

      if ($uppercase_count) {
        return $uppercase_string;
      } else if ($lowercase_count) {
        return $lowercase_string;
      }
    }

    return $string;
  }

  public static function coursestorm_change_page_title($title) {
    $page_title = isset($title['title']) ? $title['title'] : null;

    if (is_page('classes') || is_post_type_archive('coursestorm_class') || is_singular('coursestorm_class') ) {
      if ($page_title) {
        $title['title'] = ucfirst(apply_filters( 'coursestorm-units-name', $page_title ));
      }
    }

    return $title;
  }

  /**
   * Admin widget JS for class by category widget
   * 
   * Handle font size issues with legacy widget display in admin widget editor
   *
   * @param array $sessions
   * @param object $next_session
   * @param bool $continuous_enrollment
   * @param bool $register_online
   * @return void
   */
  public static function class_category_admin_widget_js () {
      ?>
          <script>
            // Set widget styles in admin. appearance -> customize based on parent width
            // because legacy widget is in iFrame
            jQuery(document).ready(function($) {
              if(jQuery('#widgets-editor', window.parent.document).length == 0) {
                  jQuery('div.widget.coursestorm_class_by_category').closest('body').addClass('widget-white-background');
              } else {
                  jQuery('div.widget.coursestorm_class_by_category').closest('body').addClass('widget-green-background');
              }

              if (jQuery('#widgets-editor', window.parent.document).length || jQuery('#customize-controls', window.parent.document).length) {
                if (parent.document.body.clientWidth >= 700) {
                  jQuery('div.widget.coursestorm_class_by_category h2.widgettitle').addClass('editor-large-widgettitle');
                } else {
                  jQuery('div.widget.coursestorm_class_by_category h2.widgettitle').removeClass('editor-large-widgettitle');
                }

                $( window ).resize(function() {
                  if (parent.document.body.clientWidth >= 700) {
                    jQuery('div.widget.coursestorm_class_by_category h2.widgettitle').addClass('editor-large-widgettitle');
                  } else {
                    jQuery('div.widget.coursestorm_class_by_category h2.widgettitle').removeClass('editor-large-widgettitle');
                  }
                });
              }
            });
          </script>
      <?php
  }
}

add_filter( 'body_class','CourseStorm_Templates::coursestorm_body_tags' );
add_filter( 'template_include', 'CourseStorm_Templates::select_appropriate_template' );
add_filter( 'theme_page_templates', 'CourseStorm_Templates::add_page_template' );
add_filter( 'get_the_excerpt', 'CourseStorm_Templates::coursestorm_alter_search_results_content' );
add_filter( 'get_the_content', 'CourseStorm_Templates::coursestorm_alter_search_results_content' );
add_filter( 'element', 'CourseStorm_Templates::element', 10, 2 );
add_filter( 'coursestorm-unit-name', 'CourseStorm_Templates::unit_filter', 10, 1 );
add_filter( 'coursestorm-units-name', 'CourseStorm_Templates::units_filter', 10, 1 );
add_filter( 'category_css_class', 'CourseStorm_Templates::add_category_slug_as_class', 10, 4);
add_filter( 'document_title_parts', 'CourseStorm_Templates::coursestorm_change_page_title', 10, 1);

add_action( 'init', 'CourseStorm_Templates::add_sort_query_var' );
add_action( 'pre_get_posts', 'CourseStorm_Templates::sort_classes_based_on_query' );
add_filter( 'posts_orderby', 'CourseStorm_Templates::upcoming_session_order_by', 10, 2 );
add_filter( 'script_loader_tag','CourseStorm_Templates::add_id_to_embed_widget_script',10,3);
add_action( 'pre_get_posts', 'CourseStorm_Search::network_radius_search' );
add_action( 'wp_enqueue_scripts', 'CourseStorm_Templates::coursestorm_plugin_scripts' );
add_action( 'wp_enqueue_scripts', 'CourseStorm_Templates::coursestorm_plugin_styles_for_themes', 100 );

add_action('template_redirect', 'CourseStorm_Templates::redirect_to_post_type_page');

add_action( 'coursestorm_before_main_content', 'CourseStorm_Templates::coursestorm_before_main_content');
add_action( 'coursestorm_after_main_content', 'CourseStorm_Templates::coursestorm_after_main_content');
add_action( 'wp_head', 'CourseStorm_Templates::class_category_admin_widget_js' );

add_shortcode( 'coursestorm_featured_slider', 'CourseStorm_Templates::coursestorm_featured_shortcode' );
add_shortcode( 'coursestorm_browse_categories', 'CourseStorm_Templates::coursestorm_category_shortcode' );
