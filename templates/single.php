<?php
/**
 * The Template for displaying a single CourseStorm class
 *
 */
if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}
get_header();

    do_action( 'coursestorm_before_main_content' );

    while( have_posts() ) : the_post();
      $coursestorm_site_info = get_option( 'coursestorm-site-info' );
      $coursestorm_site_contact_phone = $coursestorm_site_info->contact_phone;
      $coursestorm_site_plugins = array_column($coursestorm_site_info->plug_ins, 'key');
      $site_supports_sessions = in_array( 'course_sessions', $coursestorm_site_plugins );
      $course_id = get_post_meta( get_the_ID(), 'id', true );
      $course_url = get_post_meta( get_the_ID(), 'url', true );
      $course_img = get_post_meta( get_the_ID(), 'image', true );
      $course_continuous_enrollment = get_post_meta( get_the_ID(), 'continuous_enrollment', true );
      $course_instructor = get_post_meta( get_the_ID(), 'instructor', true );
      $course_site = get_post_meta( get_the_ID(), 'site', true );
      $sessions = get_post_meta( get_the_ID(), 'sessions', true );
      if (is_string($sessions)) {
        $sessions = [];
      }
      $next_session = coursestorm_get_next_course_session( $sessions );

      $price = get_post_meta( get_the_ID(), 'price', true );
      $price_formatted = $price == '0' ? 'Free' : '$' . esc_html($price);
      $description = get_post_meta( get_the_ID(), 'description', true );
      $location = get_post_meta( get_the_ID(), 'location', true );
      $address = ! empty( $location->address ) ? $location->address : null;
      $location_formatted = isset( $address ) ? esc_html( $location->address->line1 . ', ' . $location->address->city . ', ' . $location->address->state . ' ' . $location->address->zip ) : null;
      $register_online = get_post_meta( get_the_ID(), 'register_online', true );
      $room = get_post_meta( get_the_ID(), 'room', true );

      $integrated_checkout = !empty($coursestorm_site_info->integrated_checkout) ? $coursestorm_site_info->integrated_checkout : false;

      // Use the integrated checkout url if we have an SSL,
      // and integrated checkout is enabled.
      if (count($sessions)) {
        $enroll_action_url = !empty($sessions[0]->enroll_action) ? $sessions[0]->enroll_action->url : null;
        $displaying_action_button = false;
      }

      // Determine single course session registration status
      $first_session = isset($sessions[0]) ? $sessions[0] : null;
      $registration_status = CourseStorm_Templates::get_course_session_registration_status($next_session ? $next_session : $first_session, $course_continuous_enrollment, $register_online);
    ?>

    </header><!-- .page-header -->

    <div id="class-<?php the_ID(); ?>" <?php post_class('coursestorm-course-info'); ?>>

      <?php if ( $course_img ) : ?>
        <div class="coursestorm-course-single-image" style="background-image: url('<?php echo esc_url( $course_img->thumbnail_url ); ?>');"></div>
      <?php endif; ?>

      <div class="coursestorm-details-top">

        <div class="coursestorm-details-title">
          <?php the_title( '<h1 class="coursestorm-course-title entry-title">', '</h1>' ); ?>

          <?php if ( $course_site || $course_instructor ) : ?>
            <p class="coursestorm-instructor">
              with 
              <?php if ( $course_instructor ) : ?>
                <i class="icon-user"></i>
                <span class="coursestorm-instructor-name"><?php echo esc_html( $course_instructor->first_name . ' ' . $course_instructor->last_name ); ?></span><?php if ( $course_site) : ?>,<?php endif; ?>
              <?php endif; ?>
              <?php if ( $course_site ) : ?>
                <<?php if ($course_site->external_website_url) : ?>a href="<?php echo esc_html( $course_site->external_website_url );?> "<?php else : ?>span <?php endif; ?>class="coursestorm-program-name">
                  <?php echo esc_html( $course_site->name ); ?>
                </<?php if ($course_site->external_website_url) : ?>a<?php else : ?>span<?php endif; ?>>
              <?php endif; ?>
            </p>
          <?php endif; ?>
        </div>

        <div class="coursestorm-course-register">
          <span class="coursestorm-course-price">
            <?php if ( $price || $price === '0' ) {
              echo esc_html( $price_formatted );
            } else {
              echo '<span class="coursestorm-no-price">Price not available</span>';
            } ?>
          </span>

          <?php if ( !$site_supports_sessions && count($sessions) ) : ?>
            <?php if ($register_online && ( $sessions[0]->open_for_registration || $sessions[0]->open_for_waiting_list )) : ?>
              <?php $displaying_action_button = true; ?>
              <a href="<?php echo $enroll_action_url; ?>" target="_blank" class="coursestorm-widget coursestorm-action-button button" data-cs-widget-type="register-button" data-cs-course-session-id="<?php echo $sessions[0]->id; ?>" data-cs-integrated-checkout="<?php echo ($integrated_checkout) ? 'true' : 'false'; ?>">
            <?php elseif (!$register_online && (!empty( $sessions[0]->enroll_action ) && $sessions[0]->enroll_action->type == 'offline_registration')) : ?>
              <?php $displaying_action_button = true; ?>
              <a href="<?php echo $enroll_action_url; ?>" target="_blank" class=" coursestorm-action-button button">
            <?php endif; ?>
            <?php if ( $displaying_action_button && !empty( $sessions[0]->enroll_action ) ) : ?>
              <?php echo ucwords( str_replace( "_"," ", $sessions[0]->enroll_action->type ) ); ?></a>
            <?php endif; ?>

          <?php endif; ?>

          <?php if ( !$site_supports_sessions && count( $registration_status ) ) :?>
            <?php foreach ( $registration_status as $status => $label) : ?>
            <div class="registration-status coursestorm-registration-status coursestorm-registration-<?php echo $status; ?>"><?php echo $label; ?></div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

      </div><!-- /coursestorm-details-top -->

      <div class="entry-content coursestorm-course-description<?php echo ( $location || (isset( $location ) && $register_online ) ) ? ' has-location' : ''; ?>">
        <?php echo wpautop( $description ); ?>

        <?php 
          if (!$site_supports_sessions && count($sessions) && ($payment_plan_schedule = $sessions[0]->payment_plan_schedule)) : 
            apply_filters( 'element', 'payment-plan-schedule', $payment_plan_schedule );
          endif;
        ?>
      </div>

      <?php if( $location ) : ?>
      <div class="coursestorm-map">
        <div class="coursestorm-location-details">
          <h3 class="icon-location positioned"><?php echo esc_html($location->name) ?></h3>
          <?php if( isset( $location->phone ) ) : ?>
            <span class="coursestorm-location-phone"><?php echo esc_html($location->phone); ?></span>
          <?php endif; ?>
          
          <?php if ($location->address) : ?>
          <div>
            <address>
              <?php echo esc_html($location->address->line1); ?><br>
              <?php echo strtoupper( esc_html($location->address->city) ) . ', ' . esc_html($location->address->state) . ' ' . esc_html($location->address->zip); ?>
            </address>
            
            <a href="http://maps.google.com/maps?t=m&z=12&daddr=<?php echo esc_html($location_formatted); ?>" target="_blank" class="coursestorm-location-link">Get directions</a>
          </div>
          <?php endif; ?>
          <?php if ( ! empty( $room ) ) : ?>
            <div class="coursestorm-location-room">Room: <?php echo esc_html( $room ); ?></div>
          <?php endif; ?>
        </div>

      </div><!-- coursestorm-map -->
      <?php elseif( isset( $location ) && $register_online ) : ?>
      <div class="coursestorm-map online-course">
        <div class="coursestorm-location-details">
          <h3 class="icon-location positioned">Online Class</h3>
          <div class="coursestorm-call-us">For more information, call us at <?php echo $coursestorm_site_contact_phone; ?></div>
        </div>
      </div><!-- coursestorm-map -->
      <?php endif; ?>

      <?php if ( $site_supports_sessions && count($sessions) ) : ?>
      <div class="coursestorm-details-footer">
        <?php foreach ($sessions as $session) : 
          $next_session = coursestorm_get_next_course_session( $sessions );
          // Determine single course session registration status
          $registration_status = CourseStorm_Templates::get_course_session_registration_status($session, $course_continuous_enrollment, $register_online);
          
          ?>
          <div class="coursestorm-course-session">
            <?php echo apply_filters( 'element', 'calendar-icon', strtotime($session->start_date) ); ?>
            <div class="coursestorm-date-info">
              <?php
                $name = isset( $session->name ) ? esc_html( $session->name ) : null;
                $duration = isset($session->length_in_weeks) ? $session->length_in_weeks : null;
                $start_date = strlen( $session->start_date ) > 0 ? $session->start_date : null;
                $start_time = strlen( $session->start_time ) > 0 ? $session->start_time : null;
                $end_date = strlen( $session->end_date ) > 0 ? $session->end_date : null;
                $end_time = strlen( $session->end_time ) > 0 ? $session->end_time : null;
                $days_of_the_week = count($session->days) ? implode( ', ', $session->days ) : null;
                $enroll_action = $session->enroll_action;
                $date_string = coursestorm_format_date_range( $start_date, $end_date);
                $displaying_action_button = false;
            ?>
              <div class="coursestorm-class-date-timeframe">
                <div><?php if ( $name ) :?><strong><?php echo $name; ?><?php if ($name && $date_string) : ?>:<?php endif; ?> </strong><?php endif ?><?php echo $date_string; ?></div>
                <div><?php echo coursestorm_format_time_info( $duration, $start_date, $start_time, $end_time, $days_of_the_week );  ?></div>
              </div>

              <?php
                if ($payment_plan_schedule = $session->payment_plan_schedule) : 
                  apply_filters( 'element', 'payment-plan-schedule', $payment_plan_schedule );
                endif; 
              ?>
            </div>

            <?php if ($register_online && ( $session->open_for_registration || $session->open_for_waiting_list )) : ?>
              <?php $displaying_action_button = true; ?>
              <a href="<?php echo $enroll_action->url; ?>" target="_blank" class="coursestorm-widget coursestorm-action-button button" data-cs-widget-type="register-button" data-cs-course-session-id="<?php echo $session->id; ?>" data-cs-integrated-checkout="<?php echo ($integrated_checkout) ? 'true' : 'false'; ?>">
            <?php elseif (!$register_online && (isset($session->enroll_action) && $session->enroll_action->type == 'offline_registration')) : ?>
              <?php $displaying_action_button = true; ?>
              <a href="<?php echo $enroll_action->url; ?>" target="_blank" class="coursestorm-action-button button">
            <?php endif; ?>
            <?php if ( $displaying_action_button ) : ?>
              <?php echo ucwords( str_replace( "_"," ", $enroll_action->type ) ); ?></a>
            <?php endif; ?>

            <?php if ( count( $registration_status ) ) :?>
              <?php foreach ( $registration_status as $status => $label) : ?>
              <div class="registration-status coursestorm-registration-status coursestorm-registration-<?php echo $status; ?>"><?php echo $label; ?></div>
              <?php endforeach; ?>
            <?php endif; ?>
            
          </div>
          <?php unset($registration_status); ?>
        <?php endforeach; ?>
      </div>
      <?php 
        else :
          $next_session_date = get_post_meta( get_the_ID(), 'next_session_date', true );
          $next_session = coursestorm_get_next_course_session( $sessions, false, $course_continuous_enrollment );
          if ( !$next_session_date && is_object( $next_session ) ) {
            $start_date = isset( $next_session->start_date ) ? $next_session->start_date : null;
            $start_time = isset( $next_session->start_time ) ? $next_session->start_time : '00:00:00';
            if ( isset( $start_date ) ) {
              $next_session_date = $start_date . ' ' . $start_time;
            }
          }

          if ( $next_session_date ) :
            $date_timestamp = strtotime( $next_session_date );
            $duration = isset($next_session->length_in_weeks) ? $next_session->length_in_weeks : null;
            $start_date = strlen( $next_session->start_date ) > 0 ? $next_session->start_date : null;
            $start_time = strlen( $next_session->start_time ) > 0 ? $next_session->start_time : null;
            $end_date = strlen( $next_session->end_date ) > 0 ? $next_session->end_date : null;
            $end_time = strlen( $next_session->end_time ) > 0 ? $next_session->end_time : null;
            $days_of_the_week = count($next_session->days) ? implode(', ', $next_session->days) : null;
      ?>
        <div class="coursestorm-class-date">
          <?php echo apply_filters( 'element', 'calendar-icon', $date_timestamp ); ?>
          <div class="coursestorm-class-date-timeframe">
            <div><?php echo coursestorm_format_date_range( $start_date, $end_date); ?></div>
            <div><?php echo coursestorm_format_time_info( $duration, $start_date, $start_time, $end_time, $days_of_the_week );  ?></div>
          </div>
        </div>
      <?php 
          endif;
        endif;
      ?>

    </div><!-- #class-<?php the_ID(); ?> -->

    <?php endwhile; // end of the loop ?>

  <?php do_action( 'coursestorm_after_main_content', true ); // true = display sidebar ?>

<?php get_footer(); ?>
