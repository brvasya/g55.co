<nav>
<?php foreach ($grouped['clusters'] as $cluster): ?>
<h2><?php echo h($cluster[0]['name']) ?> Games</h2>
<ul class="categories">
<?php foreach ($cluster as $c): ?>
<li><a class="tag <?php echo rawurlencode($c['id']); ?>" href="/?c=<?php echo rawurlencode($c['id']); ?>"><?php echo h($c['name']); ?></a></li>
<?php endforeach; ?>
</ul>
<?php endforeach; ?>
<h2>Browse More Games</h2>
<ul class="categories">
<?php foreach ($grouped['browse_more'] as $c): ?>
<li><a class="tag <?php echo rawurlencode($c['id']); ?>" href="/?c=<?php echo rawurlencode($c['id']); ?>"><?php echo h($c['name']); ?></a></li>
<?php endforeach; ?>
</ul>
</nav>
</main>
<footer>
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
