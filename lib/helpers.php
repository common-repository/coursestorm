<?php
/**
 * Format a range of dates as formatted on the "official" CourseStorm page.
 * Example: February 1st to 30th, 2018
 *
 * @param string $start_date The starting date
 * @param string $end_date The ending date
 * @return string
 */
function coursestorm_format_date_range( $start_date, $end_date ) {
  if ($start_date) {
    $start = strtotime( $start_date );
    $end = strtotime( $end_date );

    // Properly format dates with no end date set.
    if( empty($end_date) || $start == $end ) {
      return '<strong>' . date( 'F jS', $start ) . '</strong>, ' . date( 'Y', $start );
    }

    // Formatting if years are different, months are the same, or otherwise
    if(date('Y', $start) != date('Y', $end)) {
      return '<strong>' . date( 'F jS', $start ) . '</strong>, ' . date('Y', $start) . '<strong> to ' . date( 'F jS', $end ) . '</strong>, ' . date( 'Y', $end );
    } elseif (date( 'M', $start ) == date( 'M', $end )) {
      return '<strong>' . date( 'F jS', $start ) . ' to ' . date( 'jS', $end ) . '</strong>, ' . date( 'Y', $end );
    } else {
      return '<strong>' . date( 'F jS', $start ) . ' to ' . date( 'F jS', $end ) . '</strong>, ' . date( 'Y', $end );
    }
  }

  return false;
}

/**
 * Format a time string as formatted on the "official" CourseStorm page.
 * Example: Thursday for 3 weeks from 6:30 - 8:00pm
 *
 * @param int $duration The length of the class, in weeks.
 * @param string $start_date The day the class starts on (to get the weekday)
 * @param string $start_time The time the class starts at
 * @param string $end_time The time the class ends at
 * @param string $days_of_the_week The days of the week that the class occurs
 * @return string
 */
function coursestorm_format_time_info( $duration, $start_date, $start_time, $end_time, $days_of_the_week = [] ) {
  $start_date = strtotime( $start_date );
  $start_time = isset( $start_time ) ? strtotime( $start_time ) : null;
  $end_time = isset( $end_time ) ? strtotime( $end_time ): null;
  $formatted_string = '';

  if( $days_of_the_week ) {
    $days_of_the_week = isset( $days_of_the_week ) ? $days_of_the_week : date( 'D', $start_date );
    $formatted_string = '<strong>';
    $formatted_string .= $days_of_the_week;
    $formatted_string .= '</strong>';
  }
  if( isset( $duration ) && $duration > 0 ) {
    $week_word = $duration > 1 ? 'weeks' : 'week';
    $formatted_string .= ' for <i>';
    $formatted_string .= $duration;
    $formatted_string .= ' ';
    $formatted_string .= $week_word;
    $formatted_string .= '</i>';
  }

  // Format appropriately if it starts and ends on the same side of noon.
  if ( isset( $start_time ) && isset( $end_time ) ) {
    if( $days_of_the_week ) {
      $formatted_string .= ' from ';
    }
    if ($start_time) {
      $formatted_string .= '<strong>';
      if( date( 'a', $start_time ) == date( 'a', $end_time ) ) {
        $formatted_string .= date( 'g:i', $start_time ) . ' - ' . date( 'g:i a', $end_time );
      } else {
        $formatted_string .= date( 'g:i a', $start_time ) . ' - ' . date( 'g:i a', $end_time );
      }
    }
  } elseif ( isset( $start_time ) && ! isset( $end_time ) ) {
    if( $days_of_the_week ) {
      $formatted_string .= ' at ';
    }
    $formatted_string .= '<strong> ';
    $formatted_string .= date( 'g:i a', $start_time );
  }

  if ($start_time) {
    $formatted_string .= '</strong>';
  }
  return $formatted_string;
}

function coursestorm_format_days_of_the_week( $days_of_the_week, $return = '' ) {
  $i = 0;

  foreach ( $days_of_the_week as $day_of_the_week ) :
    $return .= ($i > 0 ) ? ', ' : '';
    $return .= $day_of_the_week;
    $i++;
  endforeach;

  return $return;
}

/**
 * Get next course session
 *
 * @param array $sessions 
 * @param boolean $upcoming Should we only return upcoming (not cancelled) sessions
 * @return object|null $session
 */
function coursestorm_get_next_course_session( array $sessions, $upcoming = false, $continuous_enrollement = false ) {
  // Force timezone setting
  $timezone = get_option('timezone_string');
  $previous_timezone_value = date_default_timezone_get();
  date_default_timezone_set($timezone);

  foreach ( $sessions as $session ) {
    $return = null;

    $start_time = !empty( $session->start_time ) ? $session->start_time : '23:59:59';
    $session_start_datetime = $session->start_date . ' ' . $start_time;
    $end_time = !empty( $session->end_time ) ? $session->end_time : '23:59:59';
    $session_end_datetime = !empty($session->end_date) ? $session->end_date . ' ' . $end_time : null;
    if ($continuous_enrollement && (strtotime($session_end_datetime) >= time())) {
      // return the first session if we have a course with continuous enrollment
      $return = $session;
    } else if ( strtotime($session_start_datetime) >= time() ) {
      // Skip this session if it is cancelled and we only want upcoming sessions.
      if ($upcoming && $session->status == 'cancelled') {
        continue;
      }

      $return = $session;
    }

    if ($return) {
      break;
    }
  }

  coursestorm_reset_timezone($previous_timezone_value);
  
  if (isset($return)) {
    return $return;
  }

  return null;
}

/**
 * Reset Timezone
 * 
 * Reset the timezone to the provided timezone string
 *
 * @param string $timezone
 * @return void
 */
function coursestorm_reset_timezone($timezone) {
  date_default_timezone_set($timezone);
}