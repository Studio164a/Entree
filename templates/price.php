<?php 
/**
 * Menu item price template
 *
 * @author 		Studio164a
 * @package 	Entree/Templates
 * @version     1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$prices = entree_get_item_price();

if (count($prices)) : ?>

<p class="entree-price">
	<?php _e( 'Price: ', 'osfa_entree' ) ?>
	<?php foreach ($prices as $price) : ?>	
		<span><?php echo $price ?></span>
	<?php endforeach ?>
</p>