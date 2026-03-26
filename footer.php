<footer>
<nav>
<span>Explore All Game Categories</span>
<ul class="categories">
<?php foreach ($categories as $c): ?>
<li><a class="tag <?php echo rawurlencode($c['id']); ?>" href="/?c=<?php echo rawurlencode($c['id']); ?>"><?php echo h($c['name']); ?></a></li>
<?php endforeach; ?>
</ul>
</nav>
<div>
<span>&#169; <?php echo date('Y'); ?> G55.CO</span>
<a href="/privacy-policy.php">Privacy Policy</a>
<a href="mailto:crazygames888@gmail.com">Contact Us</a>
</div>
<div>
<a href="https://coloring.g55.co/">Printable Coloring Pages</a>
<a href="https://coloring.g55.co/?c=animals">Animals Coloring Pages</a>
<a href="https://coloring.g55.co/?c=minecraft">Minecraft Coloring Pages</a>
<a href="https://coloring.g55.co/?c=pokemon">Pokemon Coloring Pages</a>
<a href="https://coloring.g55.co/?c=roblox">Roblox Coloring Pages</a>
<a href="https://coloring.g55.co/?c=sonic">Sonic Coloring Pages</a>
<a href="https://coloring.g55.co/?c=mario">Mario Coloring Pages</a>
</div>
</footer>
