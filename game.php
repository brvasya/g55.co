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
<article class="game">
<section class="description <?php echo rawurlencode($cid); ?>">
<h1><?php echo h($h1); ?><?php if (!empty($page['creator'])): ?><span> by <?php echo h($page['creator']); ?></span><?php endif; ?></h1>
<p><?php echo h($desc); ?></p>
</section>
<div class="embed">
<button class="fullscreen" onclick="document.querySelector('.embed iframe')?.requestFullscreen();" title="Fullscreen"></button>
<iframe sandbox="allow-scripts allow-same-origin allow-pointer-lock" src="<?php echo h($iframeSrc); ?>" scrolling="no" allowfullscreen></iframe>
</div>
<aside class="tower_r">
<div class="ads"><script async src="/js/336x280.js"></script></div>
<div class="grid">
<?php foreach ($similar as $p): ?>
<a class="thumbnail" style="background-image: url(<?php echo h('https://cdn.g55.co/' . $p['id'] . '.png'); ?>);" href="/game.php?id=<?php echo rawurlencode($p['id']); ?>&c=<?php echo rawurlencode($cid); ?>"><span class="<?php echo rawurlencode($cid); ?>"><?php echo h($p['title']); ?></span></a>
<?php endforeach; ?>
</div>
<a class="tag <?php echo rawurlencode($cid); ?>" href="/?c=<?php echo rawurlencode($cid); ?>">More <?php echo h($cat['name']); ?> Games</a>
</aside>
</article>
<nav class="pagination">
<?php if ($prevUrl): ?>
<a class="tag" href="<?php echo h($prevUrl); ?>">Prev: <?php echo h($prevTitle); ?></a>
<?php endif; ?>
<?php if ($nextUrl): ?>
<a class="tag" href="<?php echo h($nextUrl); ?>">Next: <?php echo h($nextTitle); ?></a>
<?php endif; ?>
</nav>
<?php if ($seriesLinks): ?>
<nav class="cluster">
<h2>More <a href="/?c=<?php echo rawurlencode($cid); ?>&t=<?php echo rawurlencode(series_cluster_key($currentSeriesCluster)); ?>"><?php echo h($currentSeriesTitle); ?> Games</a></h2>
<section class="grid">
<?php foreach ($seriesLinks as $p): ?>
<a class="thumbnail" style="background-image: url(<?php echo h('https://cdn.g55.co/' . $p['id'] . '.png'); ?>);" href="/game.php?id=<?php echo rawurlencode($p['id']); ?>&c=<?php echo rawurlencode($cid); ?>"><span class="<?php echo rawurlencode($cid); ?>"><?php echo h($p['title']); ?></span></a>
<?php endforeach; ?>
</section>
</nav>
<?php endif; ?>
<?php if ($creatorLinks): ?>
<nav class="cluster">
<h2>More <?php echo h($cat['name']); ?> Games by <a href="/?by=<?php echo rawurlencode($currentCreatorTitle); ?>"><?php echo h($currentCreatorTitle); ?></a></h2>
<section class="grid">
<?php foreach ($creatorLinks as $p): ?>
<a class="thumbnail" style="background-image: url(<?php echo h('https://cdn.g55.co/' . $p['id'] . '.png'); ?>);" href="/game.php?id=<?php echo rawurlencode($p['id']); ?>&c=<?php echo rawurlencode($cid); ?>"><span class="<?php echo rawurlencode($cid); ?>"><?php echo h($p['title']); ?></span></a>
<?php endforeach; ?>
</section>
</nav>
<?php endif; ?>
<?php include 'footer.php'; ?>
</body>
</html>
