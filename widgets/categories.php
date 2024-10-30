<?php
class CourseStorm_Categories_Widget extends WP_Widget {
  protected $unit_term;
  protected $units_term;
  
  public function __construct() {
    $widget_options = array(
      'classname'     => 'coursestorm_categories',
      'description'   => 'Displays a heirarchical list of CourseStorm categories.',
    );

    $this->unit_term = apply_filters( 'coursestorm-unit-name', 'Class' );
		$this->units_term = apply_filters( 'coursestorm-units-name', 'Classes' );

    parent::__construct( 'coursestorm_categories', 'CourseStorm Categories', $widget_options );
  }

  public function widget( $args, $instance ) {
    $title = apply_filters( 'widget_title', $instance['title'] );
		$title = apply_filters( 'coursestorm-units-name', $title );
    $term = get_queried_object();
    echo $args['before_widget'] . $args['before_title'] . ucwords($title) . $args['after_title'];

    CourseStorm_Templates::element('category-dropdown', ['id' => 'categories-widget-select', 'term' => $term]);
    wp_enqueue_script('coursestorm-filters', plugin_dir_url( __FILE__ ) . '../assets/coursestorm.js', array('jquery'), '', true );

    echo $args['after_widget'];
  }

  public function form( $instance ) {

    /* Set up some default widget settings. */
    $defaults = array( 'title' => $this->unit_term . ' Categories' );
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
    register_widget( 'CourseStorm_Categories_Widget' );
  }
}

add_action( 'widgets_init', 'CourseStorm_Categories_Widget::register' );
