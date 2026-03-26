<?php require_once 'app/game_pre.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo h($title); ?></title>
<meta name="description" content="<?php echo h($metaDesc); ?>">
<link rel="canonical" href="<?php echo h($canonical); ?>">
<link rel="image_src" href="<?php echo h($imageSrc); ?>">
<?php include 'head.php'; ?>
</head>

<body>
<?php include 'header.php'; ?>
<main>
<article>
<section class="description <?php echo rawurlencode($cid); ?>">
<h1><?php echo h($h1); ?></h1>
<p><?php echo h($desc); ?></p>
</section>
<section class="game">
<aside class="tower_l">
<script async src="/js/160x600.js"></script>
</aside>
<div class="embed">
<button class="fullscreen" onclick="document.querySelector('.embed iframe')?.requestFullscreen();" title="Fullscreen"></button>
<iframe<?php echo $sandbox; ?> src="<?php echo h($iframeSrc); ?>"></iframe>
</div>
<aside class="tower_r">
<div class="ads"><script async src="/js/336x280.js"></script></div>
<?php foreach ($similar as $p): ?>
<a class="tag" style="background-image: url(<?php echo h('https://cdn.g55.co/' . $p['id'] . '.png'); ?>);" href="/game.php?id=<?php echo rawurlencode($p['id']); ?>&c=<?php echo rawurlencode($cid); ?>"><?php echo h($p['title']); ?></a>
<?php endforeach; ?>
<a class="tag <?php echo rawurlencode($cid); ?>" href="<?php echo h($moreHref); ?>"><?php echo h($moreText); ?></a>
</aside>
</section>
<?php if (!empty($currentCluster)): ?>
<nav class="cluster">
<h2>Explore <?php echo h($currentCluster[0]['name']) ?> Games</h2>
<ul class="categories">
<?php foreach ($currentCluster as $c): ?>
<li><a class="tag <?php echo rawurlencode($c['id']); ?>" href="/?c=<?php echo rawurlencode($c['id']); ?>"><?php echo h($c['name']); ?></a></li>
<?php endforeach; ?>
</ul>
</nav>
<?php endif; ?>
</article>
<nav class="pagination">
<?php if ($prevUrl): ?>
<a class="tag" href="<?php echo h($prevUrl); ?>">Prev: <?php echo h($prevTitle); ?></a>
<?php endif; ?>
<?php if ($nextUrl): ?>
<a class="tag" href="<?php echo h($nextUrl); ?>">Next: <?php echo h($nextTitle); ?></a>
<?php endif; ?>
</nav>
</main>
<?php include 'footer.php'; ?>
</body>
</html>