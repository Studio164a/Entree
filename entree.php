<?php
/*
Plugin Name: Entree 
Plugin URI: 
Description: Create unlimited menus for your restaurant, café, bar or store, using the standard Wordpress interface you know and love. 
Author: Studio164a
Version: 0.1
Author URI: http://164a.com
*/

class OSFA_Entree {

    /**
     * OSFA_Entree instance
	 *
     * @static
     * @access private
     * @var OSFA_Entree|null
     */
    private static $instance = null;

    /**
     * Template path
     * 
     * @var string
     */
    private $template_path;

    /**
     * Plugin path
     * 
     * @var string
     */
    private $plugin_path;

    /**
     * Create object. OSFA_Entree instance should be retrieved through OSFA_Entree::get_instance() method.
     *
     * @access private
     */
    private function __construct() {       
    	// Set up multi-lingualism
    	load_plugin_textdomain( 'osfa_entree', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

    	// Set up action hooks and filters	
    	add_action( 'init', array(&$this, 'init') );
    	add_action( 'add_meta_boxes', array(&$this, 'add_meta_boxes'));
    	add_action( 'save_post', array(&$this, 'save_post'), 10, 2);
    	add_action( 'wp_enqueue_scripts', array(&$this, 'wp_enqueue_scripts'));
        add_action( 'admin_enqueue_scripts', array(&$this, 'admin_enqueue_scripts'));
        // add_filter( 'template_include', array(&$this, 'template_include'));
        add_filter( 'the_content', array(&$this, 'the_content_filter'));

        // Set up shortcode
        add_shortcode( 'entree_menu', array(&$this, 'shortcode'));
    }

    /**
     * Retrieve object instance
     *
     * @return OSFA_Entree
     */
    public static function get_instance() {
        if ( is_null(self::$instance) ) {
            self::$instance = new OSFA_Entree();
        }
        return self::$instance;
    }       

    /**
     * Runs on init hook. 
     * 
     * @return void
     */
    public function init() {

        // Set up class variables
        $this->template_path = apply_filters( 'entree_template_path', 'entree' );

    	// Create the menu_item post type. 
    	// This can be modified by hooking into the entree_menu_item_post_type filter.
    	register_post_type( 'entree_menu_item',    		
    		apply_filters( 'entree_menu_item_post_type', 
    			array(    		
					'labels' => array(
						'name' 					=> __( 'Menu Item', 'osfa_entree' ),
						'singular_name' 		=> __( 'Menu Item', 'osfa_entree' ),
						'menu_name'				=> _x( 'Menu Items', 'Admin menu name', 'osfa_entree' ),
						'add_new' 				=> __( 'Add Menu Item', 'osfa_entree' ),
						'add_new_item' 			=> __( 'Add New Menu Item', 'osfa_entree' ),
						'edit' 					=> __( 'Edit', 'osfa_entree' ),
						'edit_item' 			=> __( 'Edit Menu Item', 'osfa_entree' ),
						'new_item' 				=> __( 'New Menu Item', 'osfa_entree' ),
						'view' 					=> __( 'View Menu Item', 'osfa_entree' ),
						'view_item' 			=> __( 'View Menu Item', 'osfa_entree' ),
						'search_items' 			=> __( 'Search Menu Items', 'osfa_entree' ),
						'not_found' 			=> __( 'No Menu Items found', 'osfa_entree' ),
						'not_found_in_trash' 	=> __( 'No Menu Items found in trash', 'osfa_entree' ),
						'parent' 				=> __( 'Parent Menu Item', 'osfa_entree' )
					),
					'description' 			=> __( 'This is where you can add new menu items.', 'osfa_entree' ),
					'public' 				=> true,
					'show_ui' 				=> true,
					'publicly_queryable' 	=> true,
					'exclude_from_search' 	=> true,
					'rewrite' 				=> array( 'slug' => 'menu-item' ),
					'hierarchical' 			=> false, // Hierarchical causes memory issues - WP loads all records!
					'supports' 				=> array( 'title', 'editor', 'excerpt', 'thumbnail' ),
					'has_archive' 			=> false,
					'show_in_nav_menus' 	=> false
				)
    		)
		);

		// Register the menu taxonomy. 
		// The taxonomy definition can be modified by hooking into the 
		// entree_menu_taxonomy_args filter.
		$labels = array( 
			'name'					=> _x( 'Menu', 'taxonomy general name', 'osfa_entree' ),
			'singular_name'       	=> _x( 'Menu', 'taxonomy singular name', 'osfa_entree' ),
			'search_items'        	=> __( 'Search Menus', 'osfa_entree' ),
			'all_items'           	=> __( 'All Menus', 'osfa_entree' ),
			'parent_item'         	=> __( 'Parent Menu', 'osfa_entree' ),
			'parent_item_colon'   	=> __( 'Parent Menu:', 'osfa_entree' ),
			'edit_item'           	=> __( 'Edit Menu', 'osfa_entree' ), 
			'update_item'         	=> __( 'Update Menu', 'osfa_entree' ),
			'add_new_item'        	=> __( 'Add New Menu', 'osfa_entree' ),
			'new_item_name'       	=> __( 'New Menu Name', 'osfa_entree' ),
			'menu_name'				=> __( 'Menu', 'osfa_entree' ), 
			'choose_from_most_used' => __( 'Choose from the most used menus', 'osfa_entree' ), 
			'separate_items_with_commas' => __( 'Separate menus with commas', 'osfa_entree' )
		);

		$args = array( 
			'label' 				=> _x( 'menu', 'taxonomy general name', 'osfa_entree' ),
			'labels'				=> $labels,
			'public'				=> true,
			'hierarchical' 			=> false, 
			'show_ui' 				=> true, 
			'show_tagcloud' 		=> false, 
			'show_admin_column' 	=> true, 
			'query_var' 			=> 'menu'
		);

		register_taxonomy( 'entree_menu', null, apply_filters( 'entree_menu_taxonomy_args', $args ) );

		// Make sure the menu taxonomy is added to the menu item post type.
		register_taxonomy_for_object_type( 'entree_menu', 'entree_menu_item' );		
    }   

    /**
     * Runs on wp_enqueue_scripts hook. 
     *
     * @return void
     */
    public function wp_enqueue_scripts() {
    	// wp_register_script('flexslider', plugins_url('media/js/jquery.flexslider-min.js', __FILE__), array('jquery'));
    	// wp_register_script('flexslider-front', plugins_url('media/js/front.js', __FILE__), array('flexslider'));
     	// wp_enqueue_script('flexslider-front');
    }

    /**
     * Runs on admin_enqueue_scripts hook. 
     *
     * @return void
     */
    public function admin_enqueue_scripts() {
        // wp_register_script('entree-admin', plugins_url('media/js/admin.js', __FILE__), array('jquery'));
        // wp_enqueue_script('entree-admin');

        wp_register_style('entree-admin', plugins_url('media/css/admin.css', __FILE__));
        wp_enqueue_style('entree-admin');
    }

    /**
     * Runs on add_meta_boxes hook.
     * 
     * @return void
     */
    public function add_meta_boxes() {
    	add_meta_box( 'entree_menu_item_price', __( 'Price', 'osfa_entree' ), array( &$this, 'entree_menu_item_price_metabox' ), 'entree_menu_item', 'normal', 'high' );
    }

    /**
     * The slider settings metabox.
     * 
     * @return void
     */ 
    public function entree_menu_item_price_metabox( $post ) {
    	$value = get_post_meta( $post->ID, 'entree-price', true );
    	
    	wp_nonce_field( 'entree_menu_item_metabox', '_entree_nonce' ) ?>
    	<p class="osfa-form-row">
    		<label for="entree-price"><?php _e( 'Price', 'osfa_entree' ) ?></label>
    		<textarea name="entree-price" id="entree-price" cols="20" rows="2"><?php echo $value ?></textarea>
    		<span class="osfa-assist"><?php _e( 'If there are multiple price options for this item, add one on each line.', 'osfa_entree' ) ?></span>
		</p>

    	<?php
    }

    /**
     * Runs on save_post hook.
     * 
     * @return void
     */
    public function save_post($post_id, $post) {
        // Verify if this is an auto save routine. 
        // If it is our form has not been submitted, so we dont want to do anything
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
        	return;

        if ( isset( $_POST['post_type'] ) && $_POST['post_type'] == 'entree_menu_item' ) {
        	// Verify this came from the our screen and with proper authorization,
	        // because save_post can be triggered at other times
	        if ( !array_key_exists('_entree_nonce', $_POST ) || !wp_verify_nonce( $_POST['_entree_nonce'], 'entree_menu_item_metabox' ) )
	         	return;

	         // Ensure current user can edit pages
            if ( !current_user_can( 'edit_page', $post_id ) )
                return;

            // Save custom fields
            foreach ( array( 'entree-price' ) as $meta_key ) {
                $meta_value = isset( $_POST[$meta_key] ) ? $_POST[$meta_key] : false;
                update_post_meta( $post_id, $meta_key, $meta_value );
            }
        }	
    }   

    /**
     * Shortcode
     * 
     * @param array $atts
     * @return string
     */  
    public function shortcode($atts) {
    	extract( shortcode_atts( array(
			'menu' => '', 
			'count' => -1, 
            'layout' => 'list'
		), $atts ) );

    	// Get menu items
    	$items = $this->get_items( array( 'menu' => $menu, 'count' => $count ) );

    	// Start output buffering
    	ob_start();

    	if ( $items->found_posts ) {

    		do_action('entree_before_menu_items', $items, $layout); ?>

    		<ul class="entree-menu entree-layout-<?php echo $layout ?>">

    		<?php 
    		// Increment
    		$i = 0;

    		while( $items->have_posts() ) {

    			$items->the_post();

    			do_action('entree_before_menu_item', $items, $layout);

    			include( entree_locate_template('menu_item', $layout ) );

    			do_action('entree_after_menu_item', $items, $layout);

    			$i++;
    		} ?>

    		</ul>

    		<?php do_action('entree_after_menu_items', $items, $layout);

    		wp_reset_query();
    	}

    	// Get output and end buffering
    	$output = ob_get_contents();
    	ob_end_clean();

		return $output;
    }

    /**
     * Get menu items
     * 
     * @param array $args
     * @return WP_Query
     */
    private function get_items($args = array()) {
    	$defaults = array(
    		'menu' => '', 'count' => -1
    	);

    	// Merge defaults with passed arguments
    	$args = array_merge( $defaults, $args );

    	// Execute the query
    	$query_args = array(
    		'post_type' => 'entree_menu_item', 
    		'posts_per_page' => $args['count']
    	);

    	if (isset($args['menu'])) {
    		$query_args['tax_query'][] = array( 
    			'taxonomy' => 'entree_menu', 
    			'field' => 'slug', 
    			'terms' => explode(',', $args['menu'])
    		);
    	}
    	
    	return new WP_Query($query_args);
    }

    /**
     * Get the template path
     * 
     * @access public
     * @return string
     */
    public function get_template_path() {
    	return $this->template_path;
    }

	/**
	 * Get the plugin path.
	 *
	 * @access public
	 * @return string
	 */
	public function get_plugin_path() {
		if ( !$this->plugin_path )
			$this->plugin_path = untrailingslashit( plugin_dir_path( __FILE__ ) );

		return $this->plugin_path;
	}

    /**
     * Add price to menu item content. 
     * 
     * @access public
     * @param string $content
     * @return string
     */
    public function the_content_filter($content) {
    	if ( is_single() && get_post_type() == 'entree_menu_item' ) {    		
    		$content .= entree_template_part('price', false);
    	}

    	return $content;
    }
}

// Instantiate the class, and save it into the $entree variable
$entree = OSFA_Entree::get_instance();


//
// Helper functions
//


/**
 * Locate template, looking first inside the theme directory.
 * 
 * @param $template_path
 * @return string
 */
function entree_locate_template($slug, $name = "") {	
	$entree = OSFA_Entree::get_instance();

    // Create the array of locations
    $locations = array( trailingslashit( $entree->get_template_path() ) . $slug . '.php', $slug );
    if ( strlen( $name ))
        array_unshift( $locations, trailingslashit( $entree->get_template_path() ) . $slug . '-' . $name . '.php' );

	// Look for the template in the theme directory
	$template = locate_template( $locations );

	// If the theme doesn't have the template, use our default template
	if ( !$template ) {
        $template = file_exists( $entree->get_plugin_path() . '/templates/' . $slug . '-' . $name  . '.php') 
            ? $entree->get_plugin_path() . '/templates/' . $slug . '-' . $name . '.php'
            : $entree->get_plugin_path() . '/templates/' . $slug . '.php';
    }

	return $template;
}

/**
 * Returns a small template part. 
 * 
 * @param string $template_name
 * @return string
 */
function entree_template_part($template_name, $echo = true) {
	$template = entree_locate_template($template_name);

	if ( $echo ) {
		include($template);
		return;
	}

	// Start output buffering
	ob_start();

	include($template);

	$html = ob_get_contents();

	// End buffering, and return the output as a string
	ob_end_clean();

	return $html;
}

/**
 * Returns a menu item's prices. 
 * 
 * @param int $item_id
 * @return array
 */
function entree_get_item_price($item_id = "") {
	if (empty($item_id))
		$item_id = get_the_ID();

	$price = get_post_meta($item_id, 'entree-price', true);
	return explode("\n", $price);
}

