<?php 
    $formAction = (get_query_var( 'taxonomy' ) == 'coursestorm_categories') ? home_url( $wp->request ) : home_url( strtolower(apply_filters( 'coursestorm-unit-name', 'class' )) );
?>
<form role="search" method="get" id="coursestorm-searchform" action="<?php echo $formAction; ?>" >
    <div>
        <input type="hidden" name="post_type" value="coursestorm_class" />

        <label for="<?php echo CourseStorm_Search::COURSESTORM_SEARCH_PREFIX;?>term">
            <span class="screen-reader-text"><?php echo __('Search for:'); ?></span>
            <input type="text" <?php if ($properties['search']['term']) : ?>value="<?php echo $properties['search']['term'] ?>"<?php endif; ?> name="<?php echo CourseStorm_Search::COURSESTORM_SEARCH_PREFIX;?>term" id="<?php echo CourseStorm_Search::COURSESTORM_SEARCH_PREFIX;?>term" placeholder="Search for <?php echo apply_filters( 'coursestorm-units-name', 'classes' ); ?>" />
        </label>

        <?php if ( $properties['is_network'] ) : ?>
            <label for="<?php echo CourseStorm_Search::COURSESTORM_SEARCH_PREFIX;?>radius">
                <span class="screen-reader-text"><?php echo __('Search Radius'); ?></span>
                <input type="hidden" id="<?php echo CourseStorm_Search::COURSESTORM_SEARCH_PREFIX;?>radius" name="<?php echo CourseStorm_Search::COURSESTORM_SEARCH_PREFIX;?>radius" value="50">
            </label>

            <label for="<?php echo CourseStorm_Search::COURSESTORM_SEARCH_PREFIX;?>location">
                <span class="screen-reader-text"><?php echo __('Town/Zip:'); ?></span>
                <input type="text" <?php if ($properties['search']['location']) : ?>value="<?php echo $properties['search']['location']; ?>"<?php endif; ?> name="<?php echo CourseStorm_Search::COURSESTORM_SEARCH_PREFIX;?>location" id="<?php echo CourseStorm_Search::COURSESTORM_SEARCH_PREFIX;?>location" placeholder="Near town/city" />
            </label>
        <?php endif; ?>

        <?php if ( !empty( get_query_var('sort') ) ) : ?>
            <?php $sort_value = esc_html(get_query_var('sort')); ?>
            <input type="hidden" value="<?php echo $sort_value; ?>" name="sort" />
        <?php elseif ( $properties['is_network'] ) : ?>
            <input type="hidden" value="distance" name="sort" />
        <?php endif; ?>

        <input type="submit" id="searchsubmit" value="<?php echo esc_attr__('Search'); ?>" />
    
    </div>
</form>