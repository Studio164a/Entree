<?php 
/**
 * Menu item price template
 *
 * @author 		Studio164a
 * @package 	Entree/Templates
 * @version     1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>

<p class="entree-price">
	<?php _e( 'Price: ', 'osfa_entree' ) ?><span><?php entree_item_price(get_the_ID()) ?></span>
</p>