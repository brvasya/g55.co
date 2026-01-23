<li id="ads" class="block"><script async src="/ads/336x280.js"></script></li>
<?php $category = get_the_category(); query_posts(array('showposts' => 6, 'orderby' => 'rand', 'category_name' => $category[0]->name, 'post__not_in' => array($post->ID))); ?>
<?php while (have_posts()) : the_post(); ?>
<li><a class="tag" style="background-image: url(<?php echo get_post_meta($id, 'wpcf-image-jpg', true); ?>);" href="<?php the_permalink(); ?>" title="<?php the_title(); ?>" target="_top"><?php the_title(); ?></a></li>
<?php endwhile; ?>
<li><a class="tag" id="<?php echo $category[0]->slug; ?>" href="<?php echo get_category_link($category[0]->term_id); ?>" title="More <?php echo $category[0]->name; ?> Games" target="_top">More <?php echo $category[0]->name; ?> Games</a></li>
<?php wp_reset_query(); ?>
