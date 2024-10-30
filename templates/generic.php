<?php
/**
 * The Template for displaying a Course Catalog/Index page
 *
 */
if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}
get_header();


  do_action( 'coursestorm_before_main_content' );
  
  while( have_posts() ) : the_post();

    the_content();
  ?>

 <?php
    $class_args = array(
      'post_type' 			=> 'coursestorm_class'
    );

    $classes = new WP_Query( $class_args );

    $classes_label = apply_filters( 'coursestorm-units-name', 'Classes' );

    if ($classes->have_posts()) {
      echo do_shortcode( '[coursestorm_featured_slider]' );
      echo do_shortcode( '[coursestorm_browse_categories]' );
    } else {
      $shortcode_content = '<div class="coursestorm-browse-categories no-categories">';
      $shortcode_content .= '<h2>Check back soon — more ' . strtolower($classes_label) . ' coming!</h2>';
      $shortcode_content .= '<p>';
      $shortcode_content .= "We have lots of great " . strtolower($classes_label) . " on the way. Check back soon to see what's new!";
      $shortcode_content .= '</p>';
      $shortcode_content .= '</p>';
      $shortcode_content .= '</div>';

      echo $shortcode_content;
    }
  endwhile;
  do_action( 'coursestorm_after_main_content', false ); // false = don't display sidebar

get_footer(); ?>