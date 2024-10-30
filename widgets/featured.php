<?php
class CourseStorm_Featured_Widget extends WP_Widget {
  protected $unit_term;
  protected $units_term;
  
  public function __construct() {
    $widget_options = array(
      'classname'     => 'coursestorm_featured',
      'description'   => 'Displays a list of featured CourseStorm classes.',
    );

    $this->unit_term = apply_filters( 'coursestorm-unit-name', 'Class' );
    $this->units_term = apply_filters( 'coursestorm-units-name', 'Classes' );

    parent::__construct( 'coursestorm_featured', 'CourseStorm Featured Classes', $widget_options );
  }

  public function widget( $args, $instance ) {
    if (isset($instance['title'])) {
      $title = apply_filters( 'widget_title', $instance['title'] );
      $title = apply_filters( 'coursestorm-units-name', $title );
      echo $args['before_widget'] . $args['before_title'] . ucwords($title) . $args['after_title'];
    }

    // Get list of featured courses
    $query_args = array(
      'meta_key'    => 'featured',
      'meta_value'  => 1,
      'post_type'   => 'coursestorm_class',
    );

    $featured_courses = get_posts( $query_args );
    if( sizeof( $featured_courses ) ) {
      echo '<ul>';
      foreach( $featured_courses as $course ) {
        $course_url = get_post_meta( $course->ID, 'url', true );
        ?>
        <li><a href="<?php echo esc_url( get_permalink( $course->ID ) ); ?>"><?php echo esc_html( $course->post_title ); ?></a></li>
        <?php
      }
      echo '</ul>';
    } else {
      echo '<p>There are no featured ' . strtolower( $this->units_term ) . ' to display!</p>';
    }

    echo $args['after_widget'];
  }

  public function form( $instance ) {

    /* Set up some default widget settings. */
    $defaults = array( 'title' => 'Featured ' . $this->units_term );
    $instance = wp_parse_args( (array) $instance, $defaults ); ?>

    <!-- Widget Title: Text Input -->
    <p>
      <label for="<?php echo $this->get_field_id( 'title' ); ?>">Title:</label>
      <input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" style="width:96%;" />
    </p>

    <?php
  }

  public function update( $new_instance, $old_instance ) {
    $instance = $old_instance;
    $instance[ 'title' ] = strip_tags( $new_instance[ 'title' ] );
    return $instance;
  }

  public static function register() {
    register_widget( 'CourseStorm_Featured_Widget' );
  }
}

add_action( 'widgets_init', 'CourseStorm_Featured_Widget::register' );
