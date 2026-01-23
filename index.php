<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'index_pre.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo h($title); ?> Games &#9654; Play Now</title>
<meta name="description" content="<?php echo h($metaDesc); ?>">
<link rel="canonical" href="<?php echo h($canonical); ?>">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap">
<link rel="stylesheet" href="/style.css">
<link rel="preload" href="/icons.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.1/jquery.min.js"></script>
<script src="/mason.min.js"></script>
<?php include '/ads/ga.php' ?>
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
<tr id="gradient-top">
<td id="gradient-bottom">
<div id="games">
<div class="games">
<?php foreach ($gridItems as $it): ?>
<a class="thumbnail" style="background-image: url(<?php echo h($it['image']); ?>);" href="/game.php?id=<?php echo rawurlencode($it['id']); ?>&c=<?php echo rawurlencode($it['category']); ?>" title="<?php echo h($it['title']); ?>" target="_top"><span class="caption"><?php echo h($it['title']); ?></span></a>
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
<?php include 'footer.php' ?>
<script src="/resize.js"></script>
</body>
</html>