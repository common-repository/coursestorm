<?php if (!empty($properties['label'])) : ?>
<label <?php if ( $id = $properties['id'] ) : ?> for="<?php echo $id; ?>"<?php endif; ?> class="screen-reader-text">
    <?php echo $properties['label']; ?>
</label>
<?php endif; ?>

<select<?php if ( $name = $properties['name'] ) : ?> name="<?php echo $name; ?>"<?php endif; ?><?php if ( $id = $properties['id'] ) : ?> id="<?php echo $id; ?>"<?php endif; ?>  <?php if ( $class = $properties['class'] ) : ?> class="<?php echo $class; ?>"<?php endif; ?>>
    <?php if( $empty = $properties['empty'] ) : ?>
        <option value=""><?php echo $empty; ?></option>
    <?php endif; ?>
    <?php foreach ( $properties['options'] as $opt_key => $opt_value ) : ?>
        <option value="<?php echo $opt_key; ?>" <?php selected( get_query_var( 'sort' ), $opt_key ); ?>><?php echo $opt_value; ?></option>
    <?php endforeach; ?>
</select>
