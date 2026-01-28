<?php require_once __DIR__ . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'game_pre.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title><?php echo h($title); ?> &#9654; Play Free on G55.CO</title>
<meta name="description" content="<?php echo h($metaDesc); ?>">
<link rel="canonical" href="<?php echo h($canonical); ?>">
<link rel="image_src" href="<?php echo h($imageSrc); ?>">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap">
<link rel="stylesheet" href="/css/style.css">
<link rel="preload" href="/css/icons.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
<script async src="https://cse.google.com/cse.js?cx=f088a66cef0354852"></script>
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-4677496585017452" crossorigin="anonymous"></script>
<script async src="https://www.googletagmanager.com/gtag/js?id=G-BV72Y8RMLN"></script>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());
gtag('config', 'G-BV72Y8RMLN');
</script>
</head>

<body>
<div id="header">
<div id="header-left">
<div class="gcse-searchbox-only"></div>
<a id="logo" href="/"></a>
</div>
<div id="header-right"></div>
</div>
<div id="title">
<div id="title-left">
<h1>Play <?php echo h($h1); ?></h1>
</div>
</div>
<div id="container">
<div id="tower_l" class="block"><script async src="/js/160x600.js"></script></div>
<div id="game" class="block">
<button id="fullscreen" onclick="document.querySelector('#game iframe')?.requestFullscreen();" title="Fullscreen"></button>
<iframe<?php echo $sandbox; ?> src="<?php echo h($iframeSrc); ?>"></iframe>
</div>
<ul id="tower_r" class="block"><li id="ads" class="block"><script async src="/js/336x280.js"></script></li>
<?php foreach ($similar as $p): ?>
<li><a class="tag" style="background-image: url(<?php echo h('https://cdn.g55.co/' . $p['id'] . '.png'); ?>);" href="/game.php?id=<?php echo rawurlencode($p['id']); ?>&c=<?php echo rawurlencode($cid); ?>"><?php echo h($p['title']); ?></a></li>
<?php endforeach; ?>
<li><a class="tag" id="<?php echo rawurlencode($cid); ?>" href="<?php echo h($moreHref); ?>"><?php echo h($moreText); ?></a></li>
</ul>
</div>
<div id="description">
<h2>What is <?php echo h($h1); ?></h2>
<p class="description" onclick="this.classList.toggle('exp');"><?php echo h($desc); ?></p>
</div>
<?php include 'footer.php'; ?>
</body>
</html>