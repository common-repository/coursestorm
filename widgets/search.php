<?php
class CourseStorm_Search_Widget extends WP_Widget {
  protected $unit_term;
  protected $units_term;
  
  public function __construct() {
    $widget_options = array(
      'classname'     => 'coursestorm_search',
      'description'   => 'A search box to search CourseStorm classes on your site.',
    );

    $this->unit_term = apply_filters( 'coursestorm-unit-name', 'Class' );
    $this->units_term = apply_filters( 'coursestorm-units-name', 'Classes' );

    parent::__construct( 'coursestorm_search', 'CourseStorm Search', $widget_options );
  }

  public function widget( $args, $instance ) {
    // Make sure the we have the CS JS for form validation
    wp_enqueue_script('coursestorm-filters', plugin_dir_url( __DIR__ ) . 'assets/coursestorm.js', array('jquery'), '', true );
    
    if (isset($instance['title'])) {
      $title = apply_filters( 'widget_title', $instance['title'] );
      $title = apply_filters( 'coursestorm-unit-name', $title );
    }
    $term = get_queried_object();
    $cs_options = get_option('coursestorm-settings');
    $is_network = ( isset($cs_options['is_network']) && $cs_options['is_network']) ? true : false;

    $radius_options = [
      0 => 'Search within',
      10 => '10 miles',
      15 => '15 miles',
      20 => '20 miles',
      30 => '30 miles',
      40 => '40 miles',
      50 => '50 miles',
      75 => '75 miles',
      100 => '100 miles',
      150 => '150 miles',
      200 => '200 miles',
      300 => '300 miles',
      400 => '400 miles'
    ];

    if (isset($title) && !empty($title)) {
      echo $args['before_widget'] . $args['before_title'] . ucwords($title) . $args['after_title'];
    }

    $field_prefix = CourseStorm_Search::COURSESTORM_SEARCH_PREFIX;
    $location_param = get_query_var($field_prefix . 'location');
    $location = !empty( $location_param ) ? $location_param : null;
    $element_properties = [
      'id' => 'categories-widget-select',
      'term' => $term,
      'is_network' => $is_network,
      'radius_options' => $radius_options,
      'search' => [
        'term' => get_query_var($field_prefix . 'term'),
        'radius' => get_query_var($field_prefix . 'radius'),
        'location' => (is_array( $location ) || is_object( $location )) ? $location->city . ', ' . $location->state : $location
      ]
    ];

    CourseStorm_Templates::element(
      'search',
      $element_properties
    );

    echo $args['after_widget'];
  }

  public function form( $instance ) {

    /* Set up some default widget settings. */
    $defaults = array( 'title' => $this->unit_term . ' Search' );
    $instance = wp_parse_args( (array) $instance, $defaults );
    ?>


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
    register_widget( 'CourseStorm_Search_Widget' );
  }

  public static function shortcode() {
    $units_term = apply_filters( 'coursestorm-units-name', 'Classes' );
    ob_start();
    // Not using units term property because we are not instantiated.
    the_widget( 'CourseStorm_Search_Widget', ['title' => 'Search ' . $units_term] ); 
    $contents = ob_get_clean();
    return $contents;
  }
}

add_action( 'widgets_init', 'CourseStorm_Search_Widget::register' );
add_shortcode('cs-network-search','CourseStorm_Search_Widget::shortcode');
