<table id="menu">
<tr>
<td>
<?php echo is_single() ? '<h3>Discover All Games</h3>' : '<h2>Discover All Games</h2>'; ?>

<ul class="menu">
<?php $categories = get_categories(array('hide_empty' => false, 'parent' => 0)); foreach ($categories as $category) : $category_link = get_category_link($category->term_id); ?>
<li><a class="tag" id="<?php echo $category->slug; ?>" href="<?php echo $category_link; ?>" title="<?php echo $category->name; ?>" target="_top"><?php echo $category->name; ?></a></li>
<?php endforeach; ?>
</ul>
</td>
</tr>
</table>
<table id="footer">
<tr>
<td id="footer-left"></td>
<td id="footer-right">
<span>Copyright &#169; <?php echo date('Y'); ?> <a href="<?php echo home_url(); ?>/" title="<?php bloginfo('name'); ?>" target="_top"><?php bloginfo('name'); ?></a> | <a href="mailto:crazygames888@gmail.com" title="Contact Us" target="_top">Contact Us</a> | <a href="<?php echo home_url(); ?>/privacy-policy/" title="Privacy Policy" target="_top">Privacy Policy</a> | All Rights Reserved.</span>
</td>
</tr>
</table>
