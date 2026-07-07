<nav>
<h2>Explore All Game Categories</h2>
<ul class="categories">
<?php foreach ($categories as $c): ?>
<li><a class="tag <?php echo rawurlencode($c['id']); ?>" href="/?c=<?php echo rawurlencode($c['id']); ?>"><?php echo h($c['name']); ?></a></li>
<?php endforeach; ?>
</ul>
</nav>
<footer>
<div>&#169; <?php echo date('Y'); ?> G55.CO</div>
<div>
<a href="/privacy-policy.php">Privacy Policy</a>
<a href="mailto:crazygames888@gmail.com">Contact Us</a>
</div>
</footer>
