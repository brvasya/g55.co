<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'game_pre.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo h($title); ?> &#9654; Play Now on G55.CO</title>
<meta name="description" content="<?php echo h($metaDesc); ?>">
<link rel="canonical" href="<?php echo h($canonical); ?>">
<link rel="image_src" href="<?php echo h($imageSrc); ?>">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap">
<link rel="stylesheet" href="/style.css">
<link rel="preload" href="/icons.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
<?php include 'ads/ga.php' ?>
</head>

<body>
<table id="header">
<tr>
<td id="header-left">
<div class="gcse-searchbox-only"></div>
<a id="logo" href="/" title="G55.CO" target="_top"></a>
</td>
<td id="header-right"></td>
</tr>
</table>
<table id="title">
<tr>
<td id="title-left">
<h1>Play <?php echo h($h1); ?></h1>
</td>
</tr>
</table>
<table id="content" style="background-image: url(<?php echo h($imageSrc); ?>);">
<tr id="gradient-top">
<td id="gradient-bottom">
<div id="container">
<div id="tower_l" class="block"><script async src="/ads/160x600.js"></script></div>
<div id="game" class="block">
<button id="fullscreen" onclick="document.querySelector('#game iframe, #game ruffle-embed')?.requestFullscreen();" title="Fullscreen"></button>
<iframe sandbox="allow-scripts allow-same-origin allow-pointer-lock" src="<?php echo h($iframeSrc); ?>"></iframe>
</div>
<ul id="tower_r" class="block"><li id="ads" class="block"><script async src="/ads/336x280.js"></script></li>
<?php foreach ($similar as $p): ?>
<li><a class="tag" style="background-image: url(<?php echo h('/wp-content/uploads/' . $p['id'] . '.png'); ?>);" href="/game.php?id=<?php echo rawurlencode($p['id']); ?>&c=<?php echo rawurlencode($cid); ?>" title="<?php echo h($p['title']); ?>" target="_top"><?php echo h($p['title']); ?></a></li>
<?php endforeach; ?>
<li><a class="tag" id="<?php echo rawurlencode($cid); ?>" href="<?php echo h($moreHref); ?>" title="<?php echo h($moreText); ?>" target="_top"><?php echo h($moreText); ?></a></li>
</ul>
</div>
</td>
</tr>
</table>
<table id="description">
<tr>
<td>
<h2>What is <?php echo h($h1); ?></h2>
<p class="description" onclick="this.classList.toggle('exp');"><?php echo h($desc); ?></p>
</td>
</tr>
</table>
<?php include 'footer.php' ?>
</body>
</html>