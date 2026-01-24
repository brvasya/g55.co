<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'index_pre.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title><?php echo h($title); ?> Games &#9654; Play Free on G55.CO</title>
<meta name="description" content="<?php echo h($metaDesc); ?>">
<link rel="canonical" href="<?php echo h($canonical); ?>">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap">
<link rel="stylesheet" href="/css/style.css">
<link rel="preload" href="/css/icons.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.8.1/jquery.min.js"></script>
<script src="/js/mason.min.js"></script>
<script async src="https://cse.google.com/cse.js?cx=f088a66cef0354852"></script>
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6180203036822393" crossorigin="anonymous"></script>
<script async src="https://www.googletagmanager.com/gtag/js?id=G-BV72Y8RMLN"></script>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());
gtag('config', 'G-BV72Y8RMLN');
</script>
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
<h1><?php echo h($title); ?> Games</h1>
</td>
</tr>
</table>
<table id="content">
<tr>
<td>
<div id="games">
<div class="games">
<?php foreach ($gridItems as $it): ?>
<a class="thumbnail" style="background-image: url(<?php echo h($it['image']); ?>);" href="/game.php?id=<?php echo rawurlencode($it['id']); ?>&c=<?php echo rawurlencode($it['category']); ?>" title="<?php echo h($it['title']); ?>" target="_top"><span class="caption" id="<?php echo rawurlencode($c['id']); ?>"><?php echo h($it['title']); ?></span></a>
<?php endforeach; ?>
</div>
</div>
</td>
</tr>
</table>
<table id="description">
<tr>
<td>
<p class="description" onclick="this.classList.toggle('exp');"><?php echo h($metaDesc); ?></p>
</td>
</tr>
</table>
<table id="menu">
<tr>
<td>
<h2>Discover All Games</h2>
<ul class="menu">
<?php foreach ($categories as $c): ?>
<li><a class="tag" id="<?php echo rawurlencode($c['id']); ?>" href="/?c=<?php echo rawurlencode($c['id']); ?>" title="<?php echo h($c['name']); ?>" target="_top"><?php echo h($c['name']); ?></a></li>
<?php endforeach; ?>
</ul>
</td>
</tr>
</table>
<table id="footer">
<tr>
<td id="footer-left"></td>
<td id="footer-right">
<span>Copyright &#169; <?php echo date('Y'); ?> G55.CO | <a href="mailto:crazygames888@gmail.com" title="Contact Us" target="_top">Contact Us</a> | <a href="/privacy-policy.php" title="Privacy Policy" target="_top">Privacy Policy</a> | All Rights Reserved.</span>
</td>
</tr>
</table>
<script src="/js/resize.js"></script>
</body>
</html>