<?php

class CourseStorm_Upcoming_Classes_Widget extends WP_Widget {
	protected $unit_term;
	protected $units_term;

    /**
	 * Sets up the widgets name etc
	 */
	public function __construct() {
		$widget_ops = array( 
			'classname' => 'coursestorm_upcoming_classes_widget',
			'description' => 'Display a list of upcoming CourseStorm classes.',
		);
		
		$this->unit_term = apply_filters( 'coursestorm-unit-name', 'Class' );
		$this->units_term = apply_filters( 'coursestorm-units-name', 'Classes' );
        
		parent::__construct( 'courseSstorm_upcoming_classes_widget', 'CourseStorm Upcoming Classes', $widget_ops );
	}

	/**
	 * Outputs the content of the widget
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {

		$title = apply_filters( 'widget_title', $instance['title'] );
		$title = apply_filters( 'coursestorm-units-name', $title );
		$show_image = $instance['show_image'];
		$show_instructor = $instance['show_instructor'];
		$show_location = $instance['show_location'];
		$show_description = $instance['show_description'];
		echo $args['before_widget'];
        
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . ucwords($title) . $args['after_title'];
		}

		// Remove excerpt and content filters to prevent alterations
		// to widget content and display
		remove_filter( 'get_the_excerpt', 'CourseStorm_Templates::coursestorm_alter_search_results_content' );
		remove_filter( 'get_the_content', 'CourseStorm_Templates::coursestorm_alter_search_results_content' );

		wp_reset_query();
		$query = $this->upcoming_classes_query($instance['post_count']);
		
		$html = '<ul class="coursestorm-upcoming-classes-widget-list">';
		while ( $query->have_posts() ) : $query->the_post();
			$meta = get_post_meta( get_the_ID() );
			$date_timestamp = strtotime( $meta['upcoming_session_date'][0] );
		?>
			<li class="coursestorm-upcoming-classes-widget-list-item">
				<?php echo apply_filters( 'element', 'calendar-icon', $date_timestamp ); ?>

				<div class="coursestorm-upcoming-classes-widget-content">
					<?php 
						$image = unserialize( $meta['image'][0] );
						if ( ! empty( $show_image ) && ! empty( $image ) ) : 
					?>
					
						<figure class="coursestorm-upcoming-classes-widget-image">
							<a href="<?php the_permalink(); ?>" title="View details for <?php the_title(); ?>"><img src="<?php echo $image->thumbnail_url; ?>" alt="<?php echo $image->attribution; ?>" /></a>
						</figure>
					<?php endif; ?>

					<h4 class="coursestorm-upcoming-classes-widget-title">
						<a href="<?php the_permalink(); ?>" title="View details for <?php the_title(); ?>"><?php the_title(); ?></a>
					</h4>
					
					<?php 
						$instructor = unserialize( $meta['instructor'][0] );
						if (isset($meta['site'])){
							$site = unserialize( $meta['site'][0] );
						}
						if ( ! empty( $show_instructor ) && ( ! empty( $instructor ) || ! empty ( $site ) ) ) : 
					?>
						<p class="coursestorm-upcoming-classes-widget-instructor">
						with 
						<?php if ( $instructor ) : ?>
							<i class="icon-user"></i>
							<span class="coursestorm-instructor-name"><?php echo esc_html( $instructor->first_name . ' ' . $instructor->last_name ); ?></span><?php if ( isset($site) ) : ?>,<?php endif; ?>
							<?php endif; ?>
						<?php if ( isset($site) ) : ?>
							<<?php if ($site->external_website_url) : ?>a href="<?php echo esc_html( $site->external_website_url );?> "<?php else : ?>span <?php endif; ?>class="coursestorm-program-name">
								<?php echo esc_html( $site->name ); ?>
							</<?php if ($site->external_website_url) : ?>a<?php else : ?>span<?php endif; ?>>
						<?php endif; ?>
						</p>
					<?php endif; ?>

					<?php if ( ! empty( $show_description ) ) : ?>
					<p class="coursestorm-upcoming-classes-widget-description"><?php the_excerpt(); ?></p>
					<?php endif; ?>

					<?php 
						$location = unserialize( $meta['location'][0] );
						if ( ! empty( $show_location ) && ! empty( $location ) ) : 
					?>
					<p class="coursestorm-upcoming-classes-widget-location">
						<div class="coursestorm-upcoming-classes-widget-location-name"><?php echo $location->name; ?></div>
						<?php if( ! empty( $location->address ) ) : ?>
							<div class="coursestorm-upcoming-classes-widget-address">
								<?php if( ! empty( $location->address->line1 ) ) : ?>
								<div>
									<?php echo ( ! empty( $location->address->line1 ) ) ? $location->address->line1 : ''; ?>
									<?php echo ( ! empty( $location->address->line2 ) ) ? ', ' . $location->address->line2 : ''; ?>
								</div>
								<div>
									<?php echo ( ! empty( $location->address->city ) ) ? $location->address->city : ''; ?>
									<?php echo ( ! empty( $location->address->state ) ) ? ', ' . $location->address->state : ''; ?>
									<?php echo ( ! empty( $location->address->zip ) ) ? $location->address->zip : ''; ?>
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
		$html .= '</ul>';

		wp_reset_postdata();
        
		echo $args['after_widget'];
	}

	/**
	 * Outputs the options form on admin
	 *
	 * @param array $instance The widget options
	 */
	public function form( $instance ) {
		$defaults = array( 'title' => 'Upcoming ' . $this->units_term );
		$instance = wp_parse_args( (array) $instance, $defaults );

        $title = ! empty( $instance['title'] ) ? $instance['title'] : '';
        $post_count = ! empty( $instance['post_count'] ) ? $instance['post_count'] : '';
        $show_title = ! empty( $instance['show_title'] ) ? $instance['show_title'] : false;
        $show_description = ! empty( $instance['show_description'] ) ? $instance['show_description'] : false;
        $show_instructor = ! empty( $instance['show_instructor'] ) ? $instance['show_instructor'] : false;
		$show_location = ! empty( $instance['show_location'] ) ? $instance['show_location'] : false;
		$show_image = ! empty( $instance['show_image'] ) ? $instance['show_image'] : false;
		?>
		<p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_attr_e( 'Title:', 'text_domain' ); ?> 
                <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
            </label>
		</p>

        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'post_count' ) ); ?>"><?php esc_attr_e( 'Number of classes to show:', 'text_domain' ); ?> 
                <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'post_count' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'post_count' ) ); ?>" type="number" value="<?php echo esc_attr( $post_count ); ?>">
            </label>
		</p>

		<p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'show_description' ) ); ?>">
                <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'show_description' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'show_description' ) ); ?>" type="checkbox" value="1"<?php echo ( ! empty( $show_description ) ) ? ' checked="checked"' : ''; ?>><?php esc_attr_e( 'Show class description', 'text_domain' ); ?> 
            </label>
		</p>

		<p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'show_instructor' ) ); ?>">
                <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'show_instructor' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'show_instructor' ) ); ?>" type="checkbox" value="1"<?php echo ( ! empty( $show_instructor ) ) ? ' checked="checked"' : ''; ?>><?php esc_attr_e( 'Show class instructor', 'text_domain' ); ?> 
            </label>
		</p>

		<p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'show_location' ) ); ?>">
                <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'show_location' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'show_location' ) ); ?>" type="checkbox" value="1"<?php echo ( ! empty( $show_location ) ) ? ' checked="checked"' : ''; ?>><?php esc_attr_e( 'Show class location', 'text_domain' ); ?> 
            </label>
		</p>
		
		<p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'show_image' ) ); ?>">
                <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'show_image' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'show_image' ) ); ?>" type="checkbox" value="1"<?php echo ( ! empty( $show_image ) ) ? ' checked="checked"' : ''; ?>><?php esc_attr_e( 'Show class image', 'text_domain' ); ?> 
            </label>
		</p>
		<?php
	}

	/**
	 * Processing widget options on save
	 *
	 * @param array $new_instance The new options
	 * @param array $old_instance The previous options
	 *
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
        $instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? sanitize_text_field( $new_instance['title'] ) : '';
		$instance['post_count'] = ( ! empty( $new_instance['post_count'] ) ) ? sanitize_text_field( $new_instance['post_count'] ) : '';
		$instance['show_description'] = ( ! empty( $new_instance['show_description'] ) ) ? strip_tags( $new_instance['show_description'] ) : false;
		$instance['show_instructor'] = ( ! empty( $new_instance['show_instructor'] ) ) ? strip_tags( $new_instance['show_instructor'] ) : false;
		$instance['show_location'] = ( ! empty( $new_instance['show_location'] ) ) ? strip_tags( $new_instance['show_location'] ) : false;
		$instance['show_image'] = ( ! empty( $new_instance['show_image'] ) ) ? strip_tags( $new_instance['show_image'] ) : false;
		
		return $instance;
	}

    public static function register() {
        register_widget( 'CourseStorm_Upcoming_Classes_Widget' );
    }

    /**
     * Upcoming Classes Query
     * 
     * Get a query for upcoming classes
     * 
     * @param int $count Number of posts to retrieve
     * @return object WP_Query Posts limited by $count and ordered by the next session date
     */
    private function upcoming_classes_query( $count ) {
		$args = array(
			'meta_key'  			=> 'upcoming_session_date',
			// Exclude NULL next_session_dates
			'meta_query' => array(
				array(
					'key'     => 'upcoming_session_date',
					'value'   => array(''),
					'compare' => 'NOT IN',
				)
			),
			'posts_per_page' 		=> $count,
            'post_type' 			=> 'coursestorm_class',
			'orderby'   			=> 'meta_value',
			'order'					=> 'ASC',
		);
		
		return new WP_Query( $args );
    }
}

add_action( 'widgets_init', 'CourseStorm_Upcoming_Classes_Widget::register' );