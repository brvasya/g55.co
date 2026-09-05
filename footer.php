<?php if (!empty($seriesCategories)): ?>
<nav class="cluster">
<h2>Related <?php echo h($cat['name']); ?> Games</h2>
<ul class="series">
<?php foreach ($seriesCategories as $series): ?>
<li><a class="tag" href="<?php echo h($series['url']); ?>"><?php echo h($series['title']); ?></a></li>
<?php endforeach; ?>
</ul>
</nav>
<?php endif; ?>
</main>
<footer>
<div><a href="/privacy-policy.php">Privacy Policy</a><a href="mailto:crazygames888@gmail.com">Contact Us</a></div>
<div>&#169; <?php echo date('Y'); ?> G55.CO</div>
</footer>
