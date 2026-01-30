<?php require_once 'app/game_pre.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title><?php echo h($title); ?> &#9654; Play Free on G55.CO</title>
<meta name="description" content="<?php echo h($metaDesc); ?>">
<link rel="canonical" href="<?php echo h($canonical); ?>">
<link rel="image_src" href="<?php echo h($imageSrc); ?>">
<?php include 'head.php'; ?>
</head>

<body>
<?php include 'header.php'; ?>
<main>
<section>
<div class="title">
<div class="title-left">
<h1>Play <?php echo h($h1); ?></h1>
</div>
</div>
<div class="container">
<aside class="tower_l">
<script async src="/js/160x600.js"></script>
</aside>
<div class="game">
<button class="fullscreen" onclick="document.querySelector('.game iframe')?.requestFullscreen();" title="Fullscreen"></button>
<iframe<?php echo $sandbox; ?> src="<?php echo h($iframeSrc); ?>"></iframe>
</div>
<aside class="tower_r">
<div class="ads"><script async src="/js/336x280.js"></script></div>
<?php foreach ($similar as $p): ?>
<a class="tag" style="background-image: url(<?php echo h('https://cdn.g55.co/' . $p['id'] . '.png'); ?>);" href="/game.php?id=<?php echo rawurlencode($p['id']); ?>&c=<?php echo rawurlencode($cid); ?>"><?php echo h($p['title']); ?></a>
<?php endforeach; ?>
<a class="tag <?php echo rawurlencode($cid); ?>" href="<?php echo h($moreHref); ?>"><?php echo h($moreText); ?></a>
</aside>
</div>
</section>
<section>
<h2>Game Details</h2>
<p class="description" onclick="this.classList.toggle('exp');"><?php echo h($desc); ?></p>
</section>
<?php include 'footer.php'; ?>
</body>
</html>