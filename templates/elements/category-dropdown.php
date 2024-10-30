<?php
$id = ! empty( $properties['id'] ) ? $properties['id'] : null;
// dd(__FILE__.':'.__LINE__, $properties['term']);
$term = ! empty( $properties['term'] ) ? $properties['term'] : null;


/*
* Hacky way of getting the permalink structure of out custom taxonomy to be used with JS redirect...
*/
$categories = get_terms( array( 'taxonomy' => 'coursestorm_categories', 'hide_empty' => 'true' ) );
    
if( ! empty ( $categories ) ) :
    $category_url = get_term_link( $categories[0] );
    $category_taxonomy = $categories[0]->taxonomy;
?>
    <form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="GET"<?php echo isset( $id ) ? ' id="'.$id.'" ' : null; ?>class="coursestorm-filter-form category" data-category-url="<?php echo $category_url; ?>" data-category-taxonomy="<?php echo $category_taxonomy; ?>">
      <label for="cat" class="screen-reader-text">Filter <?php echo apply_filters( 'coursestorm-units-name', 'classes' ); ?> by category</label>
      <?php
        wp_dropdown_categories( array(
          'taxonomy' => 'coursestorm_categories',
          'show_option_none' => 'Filter by category',
          'show_option_all' => 'Browse all ' . apply_filters( 'coursestorm-units-name', 'classes' ),
          'hierarchical' => 1,
          'depth' => '3',
          'value_field' => 'slug',
          'selected' => ( isset( $term ) && isset( $term->slug ) )? $term->slug : -1,
          'id' => null
        ) );
      ?>
      <button type="submit" id="category-filter-submit" class="coursestorm-filter-submit">Filter</button>
    </form>

<?php
endif;