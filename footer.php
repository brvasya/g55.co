<section id="categories">
<h2>Browse More Games</h2>
<nav aria-label="game categories">
<ul class="categories">
<?php foreach ($categories as $c): ?>
<li><a class="tag <?php echo rawurlencode($c['id']); ?>" href="/?c=<?php echo rawurlencode($c['id']); ?>"><?php echo h($c['name']); ?></a></li>
<?php endforeach; ?>
</ul>
</nav>
</section>
</main>
<footer id="footer">&#169; <?php echo date('Y'); ?> G55.CO <a href="/privacy-policy.php">Privacy Policy</a> <a href="mailto:crazygames888@gmail.com">Contact Us</a></footer>
