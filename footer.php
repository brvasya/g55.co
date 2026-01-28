<div id="menu">
<h2>Browse More Games</h2>
<ul class="menu">
<?php foreach ($categories as $c): ?>
<li><a class="tag" id="<?php echo rawurlencode($c['id']); ?>" href="/?c=<?php echo rawurlencode($c['id']); ?>"><?php echo h($c['name']); ?></a></li>
<?php endforeach; ?>
</ul>
</div>
<div id="footer">&#169; <?php echo date('Y'); ?> G55.CO <a href="mailto:crazygames888@gmail.com">Contact Us</a> <a href="/privacy-policy.php">Privacy Policy</a></div>
