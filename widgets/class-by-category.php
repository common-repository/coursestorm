<?php
class CourseStorm_Classes_By_Category extends WP_Widget {
  const DEFAULT_CATEGORY_ID = 0;

  protected $unit_term;
  protected $units_term;
  
  public function __construct() {
    $widget_options = array(
      'classname'     => 'coursestorm_class_by_category',
      'description'   => 'Display a list of CourseStorm classes filtered by category.',
      'show_instance_in_rest' => true
    );

    $this->unit_term = apply_filters( 'coursestorm-unit-name', 'Class' );
    $this->units_term = apply_filters( 'coursestorm-units-name', 'Classes' );
    
    parent::__construct( 'coursestorm_class_by_category', 'CourseStorm Classes by Category', $widget_options );
  }

  public function widget( $args, $instance ) {
    $title = isset( $instance['title'] ) ? apply_filters( 'widget_title', $instance['title'] ) : false;
    $category = $instance[ 'coursestorm_categories' ] ? $instance[ 'coursestorm_categories' ] : 0;
    $options = isset($instance['options']) ? $instance['options'] : [];

    $title = apply_filters( 'coursestorm-units-name', $title );
    echo $args['before_widget'] . $args['before_title'] . ucwords($title) . $args['after_title'];

    // Set all configurable options
    foreach ( $options as $key => $option ) {
      ${$key} = $option;
    }

    $query = $this->classes_by_category_query($category);

    // Remove excerpt and content filters to prevent alterations
    // to widget content and display
    remove_filter( 'get_the_excerpt', 'CourseStorm_Templates::coursestorm_alter_search_results_content' );
    remove_filter( 'get_the_content', 'CourseStorm_Templates::coursestorm_alter_search_results_content' );

    if( $query->have_posts() ) :
      echo '<ul>';
      while ( $query->have_posts() ) : $query->the_post();
        $meta = get_post_meta( get_the_ID() );
        $title = get_the_title( get_the_ID() );

        $date_timestamp = strtotime( $meta['next_session_date'][0] );
        ?>
        <li>
          <?php 
          if ( ! empty( $show_date ) && ! empty( $date_timestamp ) ) :
            echo apply_filters( 'element', 'calendar-icon', $date_timestamp );
          endif;
          ?>

          <div class="coursestorm-classes-by-category-widget-content<?php if ( empty( $show_date ) ) : ?> no-date<?php endif; ?>">
            <?php 
              $image = unserialize( $meta['image'][0] );
              if ( ! empty( $show_image ) && ! empty( $image ) ) :
            ?>
            
              <figure class="coursestorm-classes-by-category-widget-image">
                <a href="<?php the_permalink(); ?>" title="View details for <?php $title; ?>"><img src="<?php echo $image->thumbnail_url; ?>" alt="<?php echo $image->attribution; ?>" /></a>
              </figure>
            <?php endif; ?>

            <h4 class="coursestorm-classes-by-category-widget-title">
              <a href="<?php the_permalink(); ?>" title="View details for <?php echo $title; ?>"><?php echo $title; ?></a>
            </h4>
            
            <?php 
              $program_location = isset( $meta['program_location'] ) ? $meta['program_location'] : null;
              if ( ! empty( $show_program_location ) && ! empty( $program_location ) ) :
            ?>
            <p class="coursestorm-classes-by-category-widget-program-location">Offered by: <?php echo esc_html( $program_location ); ?></p>
            <?php endif; ?>

            <?php 
              $instructor = unserialize( $meta['instructor'][0] );
              if ( ! empty( $show_instructor ) && ! empty( $instructor ) ) :
            ?>
            <p class="coursestorm-classes-by-category-widget-instructor">with <i class="icon-user"></i> <?php echo esc_html( $instructor->first_name . ' ' . $instructor->last_name ); ?></p>
            <?php endif; ?>

            <?php if ( ! empty( $show_description ) ) : ?>
            <p class="coursestorm-classes-by-category-widget-description"><?php the_excerpt(); ?></p>
            <?php endif; ?>

            <?php 
              $location = unserialize( $meta['location'][0] );
              if ( ! empty( $show_location ) && ! empty( $location ) ) :
            ?>
            <p class="coursestorm-classes-by-category-widget-location">
              <div class="coursestorm-classes-by-category-widget-location-name"><?php echo esc_html( $location->name ); ?></div>
              <?php if( ! empty( $location->address ) ) : ?>
                <div class="coursestorm-classes-by-category-widget-address">
                  <?php if( ! empty( $location->address->line1 ) ) : ?>
                  <div>
                    <?php echo ( ! empty( $location->address->line1 ) ) ? esc_html( $location->address->line1 ) : ''; ?>
                    <?php echo ( ! empty( $location->address->line2 ) ) ? ', ' . esc_html( $location->address->line2 ) : ''; ?>
                  </div>
                  <div>
                    <?php echo ( ! empty( $location->address->city ) ) ? esc_html( $location->address->city ) : ''; ?>
                    <?php echo ( ! empty( $location->address->state ) ) ? ', ' . esc_html( $location->address->state ) : ''; ?>
                    <?php echo ( ! empty( $location->address->zip ) ) ? esc_html( $location->address->zip ) : ''; ?>
                  </div>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </p>
            <?php endif; ?>
          </div>
        </li>
        <?php
      endwhile;
      wp_reset_query();
      echo '</ul>';
    else:
      echo '<p>There are no classes to display!</p>';
    endif;
    
    echo $args['after_widget'];
  }

  public function form( $instance ) {

    /* Set up some default widget settings. */
    $defaults = array( 'title' => $this->units_term . ' by Category' );
    $instance = wp_parse_args( (array) $instance, $defaults );

    $options = [
      'show_image' => [
        'value' => '1',
        'description' => 'Show image'
      ],
      'show_description' => [
        'value' => '1',
        'description' => 'Show description'
      ],
      'show_date' => [
        'value' => '1',
        'description' => 'Show date'
      ],
      'show_instructor' => [
        'value' => '1',
        'description' => 'Show instructor'
      ],
      'show_location' => [
        'value' => '1',
        'description' => 'Show location'
      ],
      'show_program_location' => [
        'value' => '1',
        'description' => 'Show program location'
      ]
    ];
    
    $dropdown_args = array(
      'taxonomy' => 'coursestorm_categories',
      'id' => $this->get_field_id( 'coursestorm_categories' ),
      'name' => $this->get_field_name( 'coursestorm_categories' ),
      'show_option_all' => __( 'All Categories' ),
      'hide_empty' => true,
      'hierarchical' => true,
      'depth' => 2,
      'echo' => 0,
      'selected' => isset( $instance[ 'coursestorm_categories' ] ) ? $instance[ 'coursestorm_categories' ] : '0',
      'class' => 'widefat'
    );
    ?>

    <!-- Widget Title -->
    <p>
      <label for="<?php echo $this->get_field_id( 'title' ); ?>">Title:</label>
      <input type="text" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" style="width:100%;" />
    </p>

    <!-- Categories Dropdown -->
    <p>
      <label for="<?php echo $this->get_field_id( 'coursestorm_categories' ); ?>">Choose Class Category:</label>

      <?php 
        echo wp_dropdown_categories( $dropdown_args );
      ?>
    </p>

    <h4>Display options</h4>

    <?php foreach ( $options as $key => $option ) : ?>
      <p>
        <label for="<?php echo esc_attr( $this->get_field_id( 'options[' . $key . ']' ) ); ?>">
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'options[' . $key . ']' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'options[' . $key . ']' ) ); ?>" type="checkbox" value="<?php echo $option['value']; ?>"<?php echo ( ! empty( $instance['options'][$key] ) ) ? ' checked="checked"' : ''; ?>><?php esc_attr_e( $option['description'], 'coursestorm' ); ?> 
        </label>
      </p>
    <?php endforeach;
  }

  public function update( $new_instance, $old_instance ) {
    $options = [
      'show_image',
      'show_description',
      'show_date',
      'show_instructor',
      'show_location',
      'show_program_location'
    ];

    $instance = $old_instance;
    $instance[ 'title' ] = strip_tags( $new_instance[ 'title' ] );
    $instance[ 'coursestorm_categories' ] = $new_instance[ 'coursestorm_categories' ];

    if ( isset ( $new_instance['options'] ) ) {
      foreach ( $options as $option ) {
          $instance['options'][ $option ] = isset( $new_instance['options'][ $option ] ) ? $new_instance['options'][ $option ] : null;
      }
    }

    return $instance;
  }

  public static function register() {
    register_widget( 'CourseStorm_Classes_By_Category' );
  }

  /**
   * Classes by Category Query
   * 
   * Get a query for classes by category
   * 
   * @param int $count Number of posts to retrieve
   * @return object WP_Query Posts limited by $count and ordered by the next session date
   */
  private function classes_by_category_query( int $category = 0, $count = null ) {
    // Get list of courses in selected category
    $args = array(
      'post_type' => 'coursestorm_class',
      'meta_key' => 'upcoming_session_date',
      'orderby' => 'meta_value',
    );
    
    if ( $category !== self::DEFAULT_CATEGORY_ID ) { // if "all categories" is selected
      $args += array(
        'tax_query'   => array(
          array(
            'taxonomy' => 'coursestorm_categories',
            'field' => 'id',
            'terms' => $category,
          )
        )
      );
    }

    return new WP_Query( $args );
  }
}

add_action( 'widgets_init', 'CourseStorm_Classes_By_Category::register' );