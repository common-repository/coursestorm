<?php
/**
 * The template for displaying all CourseStorm archives (including category archive)
 *
 */
if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}
get_header();

  do_action( 'coursestorm_before_main_content', true ); ?>

    <header class="page-header">
      <h1 class="page-title">
        <?php
          if ( is_tax() ) {
            $title = single_term_title( '', false );
            $term = get_queried_object();
            $parent = ( isset( $term->parent ) ) ? get_term_by( 'id', $term->parent, 'coursestorm_categories' ) : false;
          } else {
            $title = apply_filters( 'coursestorm-units-name', 'Classes' );
            $term = null;
          }
          echo ucwords($title);
        ?>
      </h1>
      <?php if ( isset( $parent ) && $parent ) {
        echo '<p class="coursestorm-category-parent">in <a href="' . get_term_link( $parent ) . '">' . $parent->name . '</a></p>';
      } ?>
    </header><!-- .page-header -->

    <div class="coursestorm-course-filters">
      <?php the_widget('CourseStorm_Search_Widget', ['title' => null]); ?>

      <form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="GET" id="sort-filter-form" class="coursestorm-filter-form sort">
        <?php
          $select_params = array(
            'empty' => 'Sort ' . apply_filters( 'coursestorm-units-name', 'classes' ),
            'options' => array(
              'title' => 'Alphabetically',
              'date' => 'Date (sooner to later)',
              'price' => 'Price (low to high)'
            ),
            'name' => 'sort',
            'id' => 'sort',
            'class' => 'coursestorm-sort-classes',
            'label' => 'Sort ' . apply_filters( 'coursestorm-units-name', 'classes' )
          );
          if ($location = get_query_var('coursestorm_search_location')) {
            $select_params['options']['distance'] = 'Distance from ' . $location;
          }
          apply_filters( 'element', 'html-select', $select_params );
        ?>
        <button type="submit" id="sort-submit" class="coursestorm-filter-submit">Sort</button>
      </form>

      <?php CourseStorm_Templates::element('category-dropdown', ['id' => 'categories-filter-select', 'term' => $term]); ?>
    </div>

    <?php    
    if ( have_posts() ) :
      while ( have_posts() ) : the_post();

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

        <article id="class-<?php the_ID(); ?>" <?php post_class('coursestorm-course-archive'); ?>>
          <div class="entry-content">
            <a href="<?php the_permalink(); ?>" title="Read more about <?php the_title(); ?>">
              <?php if ( !empty( $course_img ) ) : ?>
              <img src="<?php echo esc_url( $course_img->thumbnail_url ); ?>" class="coursestorm-course-image" alt="<?php echo esc_html( $course_img->attribution ); ?>">
              <?php else : ?>
              <div class="coursestorm-course-image icon-book"></div>
              <?php endif;?>
            </a>

            <div class="coursestorm-course-info">

              <div class="coursestorm-details-top">

                <header class="coursestorm-details-title">
                  <h1 class="coursestorm-course-title entry-title">
                    <a href="<?php the_permalink(); ?>" title="Read more about <?php the_title(); ?>"><?php the_title(); ?></a>
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
                        $start_time = isset( $session->start_time ) ? $session->start_time : '23:59:59';
                        $session_start_datetime = $start_date . ' ' . $start_time;
                  ?>
                      <div class="coursestorm-details-date">
                        <p class="coursestorm-date-info">
                          <i class="icon-calendar"></i>
                          <?php if ( $site_supports_sessions && count($sessions) >= 1 ) : ?>
                            <?php if ( strtotime($session_start_datetime) <= time() ) : ?>
                              <strong>Current session: </strong>
                            <?php else : ?>
                              <strong>Next session: </strong>
                            <?php endif; ?>
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

            <?php if ( $registration_status && count( $registration_status ) ) :?>
              <?php foreach ( $registration_status as $status => $label) : ?>
              <div class="coursestorm-registration-status coursestorm-registration-<?php echo $status; ?>"><?php echo $label; ?></div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </article><!-- #class-<?php the_ID(); ?> -->

        <?php
      endwhile;

      the_posts_pagination();

    else:
      echo '<div class="entry">';
      echo '<div class="entry-content">';
      echo wpautop( 'Sorry, we could not find any courses that match your search.' );
      the_widget('CourseStorm_Search_Widget', ['title' => null]);
      echo '</div>';
      echo '</div>';
    endif;

  do_action( 'coursestorm_after_main_content', false ); // false = no sidebar

get_footer(); ?>
