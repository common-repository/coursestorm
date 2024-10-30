<?php
class CourseStorm_Nav {
    private $_coursestorm_site_info;

    public function __construct() {
        $this->_coursestorm_site_info = get_option( 'coursestorm-site-info' );

        $this->coursestorm_nav_add_metabox();
		add_filter( 'wp_setup_nav_menu_item', array( $this, 'coursestorm_setup_nav_menu_item' ) );
		add_filter( 'nav_menu_link_attributes', array( $this, 'coursestorm_add_nav_item_attributes' ), 10, 3 );
    }
    /* Registers Login/Logout/Register Links Metabox */
	public function coursestorm_nav_add_metabox() {
        if ( function_exists( 'add_meta_box' ) ) {
            add_meta_box( 'coursestorm', __( 'CourseStorm', 'coursestorm' ), array( $this, 'coursestorm_nav_metabox' ), 'nav-menus', 'side', 'default' );
        }
	}

	/* Displays Login/Logout/Register Links Metabox */
	public function coursestorm_nav_metabox() {
		global $nav_menu_selected_id;

		$elems = array(
			'#coursestormclasses#'	=> apply_filters( 'coursestorm-units-name', 'Classes' ),
		);

		// Add the cart link if cart is enabled.
		if ($this->_coursestorm_site_info->cart) {
			$elems['#coursestormcart#'] = __( 'View Cart', 'coursestorm' );
		}
		$logitems = array(
			'db_id' => 0,
			'object' => 'bawlog',
			'object_id',
			'menu_item_parent' => 0,
			'type' => 'custom',
			'title',
			'url',
			'target' => '',
			'attr_title' => '',
			'classes' => array(),
			'xfn' => '',
		);

		$elems_obj = array();
		foreach ( $elems as $value => $title ) {
			$elems_obj[ $title ] 		= (object) $logitems;
			$elems_obj[ $title ]->object_id	= esc_attr( $value );
			$elems_obj[ $title ]->title	= esc_attr( $title );
			$elems_obj[ $title ]->url	= esc_attr( $value );
		}

		$walker = new Walker_Nav_Menu_Checklist( array() );
		?>
		<div id="coursestorm-links" class="coursestorm-links">

			<div id="tabs-panel-coursestorm-links-all" class="tabs-panel tabs-panel-view-all tabs-panel-active">
				<ul id="coursestorm-links-checklist" class="list:coursestorm-links categorychecklist form-no-clear">
					<?php echo walk_nav_menu_tree( array_map( array($this, 'coursestorm_setup_nav_menu_item'), $elems_obj ), 0, (object) array( 'walker' => $walker ) ); ?>
				</ul>
			</div>

            <span class="add-to-menu">
                <input type="submit"<?php disabled( $nav_menu_selected_id, 0 ); ?> class="button-secondary submit-add-to-menu right" value="<?php esc_attr_e( 'Add to Menu', 'coursestorm' ); ?>" name="add-coursestorm-links-menu-item" id="submit-coursestorm-links" />
                <span class="spinner"></span>
            </span>
		</div>
		<?php
    }
    
    /* Replaces the #keyword# by the correct links with nonce ect */
	public function coursestorm_setup_nav_menu_item( $item ) {
        global $pagenow;

		if ( $pagenow != 'nav-menus.php' && ! defined( 'DOING_AJAX' ) && isset( $item->url ) && strstr( $item->url, '#coursestorm' ) != '' ) {
			$item_url = substr( $item->url, 0, strpos( $item->url, '#', 1 ) ) . '#';
			$item_redirect = str_replace( $item_url, '', $item->url );

			switch ( $item_url ) {
				case '#coursestormcart#' :
					if (!$this->_coursestorm_site_info->cart) {
						// Removing the 'View Cart' nav item if cart is disabled
						// This will remove it on the second reload due to when this
						// filter runs.
						wp_delete_post($item->ID);
					}
                    $domain = $this->_generate_domain();
                    $hashbang_uri = $this->_coursestorm_site_info->cart ? '/cart/view' : null;
                    $uri = str_replace( $item_url, '/#' . $hashbang_uri, $item_url);
                    $item->url = '//' . $domain . $uri;
                    $item->classes = [
                        'coursestorm',
						'view-cart'
					];
					$item->title = $item->title;
					$item->target = '_blank';
					break;
				case '#coursestormclasses#' :
					$item->url = '/classes';
					break;
			}
			$item->url = esc_url( $item->url );
        }
        
		return $item;
	}

	public function coursestorm_add_nav_item_attributes( $atts, $item, $args ) {
		$coursestorm_settings = get_option( 'coursestorm-settings' );

		if ( in_array( 'coursestorm', $item->classes ) && in_array( 'view-cart', $item->classes ) ) {
			$atts += [
				'data-cs-widget-type' => 'view-cart',
				'class' => 'coursestorm-widget'
			];

			if ( !empty( $coursestorm_settings['cart_options']['view_cart_location'] ) ) {
				if (
					$coursestorm_settings['cart_options']['view_cart_location'] == 'inline'
				) {
					$atts['data-cs-widget-location'] = 'false';
				} else {
					$atts['data-cs-widget-location'] = $coursestorm_settings['cart_options']['view_cart_location'];
				}
			}
		}
		return $atts;
	}

	private function _generate_domain() {
		$base_domain = 'coursestorm.com';
		$subdomain = $this->_coursestorm_site_info->subdomain;
		preg_match( '/\.(\w{3,})/', $base_domain, $matches );
		$domain = str_replace( $matches[1], COURSESTORM_TLD, $base_domain );

		return $subdomain . '.' . $domain;
	}
}

add_action( 'admin_menu', function() { new CourseStorm_Nav; } );
add_action( 'init', function() { new CourseStorm_Nav; } );
