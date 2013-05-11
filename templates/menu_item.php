<?php 
/**
 * Menu item template
 *
 * @author 		Studio164a
 * @package 	Entree/Templates
 * @version     1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>

<li <?php post_class() ?>>

	<a href="<?php the_permalink() ?>" class="entree-item-title"><h2><?php the_title() ?></h2></a>

	<?php if (has_post_thumbnail()) : ?>

		<a href="<?php the_permalink() ?>" class="entree-item-photo"><?php the_post_thumbnail( apply_filters( 'entree_menu_item_thumbnail', 'post-thumbnail') ) ?></a>

	<?php endif ?>	
	
	<?php the_excerpt() ?>

	<?php entree_template_part('price.php') ?>

</li>