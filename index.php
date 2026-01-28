<?php require_once __DIR__ . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'index_pre.php'; ?>
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
<h1><?php echo h($title); ?> Games</h1>
</div>
</div>
<div id="description">
<p<?php echo (!empty($cid)) ? ' id="'.$cid.'"' : ''; ?> class="description<?php echo (!empty($cid)) ? ' c' : ''; ?>" onclick="this.classList.toggle('exp');"><?php echo h($metaDesc); ?></p>
</div>
<div id="games">
<?php foreach ($gridItems as $it): ?>
<a class="thumbnail" style="background-image: url(<?php echo h($it['image']); ?>);" href="/game.php?id=<?php echo rawurlencode($it['id']); ?>&c=<?php echo rawurlencode($it['category']); ?>"><span id="<?php echo rawurlencode($it['category']); ?>"><?php echo h($it['title']); ?></span></a>
<?php endforeach; ?>
</div>
<?php include 'footer.php'; ?>
<script src="/js/resize.js"></script>
</body>
</html>