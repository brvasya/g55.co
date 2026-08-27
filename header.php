<header>
<a class="logo" href="/"></a>
<div class="gcse-searchbox-only"></div>
<button class="tag install">Install app</button>
</header>
<nav>
<ul class="categories">
<?php foreach ($categories as $c): ?>
<li><a class="tag <?php echo rawurlencode($c['id']); ?>" href="/?c=<?php echo rawurlencode($c['id']); ?>"><?php echo h($c['name']); ?></a></li>
<?php endforeach; ?>
</ul>
</nav>
