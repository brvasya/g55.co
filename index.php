<?php require_once 'app/index_pre.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo h($title); ?></title>
<meta name="description" content="<?php echo h($metaDesc); ?>">
<link rel="canonical" href="<?php echo h($canonical); ?>">
<?php include 'head.php'; ?>
</head>

<body>
<?php include 'header.php'; ?>
<main>
<article>
<section class="description <?php echo (!empty($cid)) ? 'c ' . $cid : 'c play'; ?>">
<h1><?php echo h($h1); ?></h1>
<?php if (empty($activeSeriesKey)): ?>
<p><?php echo $desc; ?></p>
<?php endif; ?>
</section>
<?php if (!empty($seriesCategories)): ?>
<nav class="cluster">
<h2>Related Category Tags</h2>
<ul class="series">
<?php foreach ($seriesCategories as $series): ?>
<li><a class="tag" href="<?php echo h($series['url']); ?>"><?php echo h($series['title']); ?></a></li>
<?php endforeach; ?>
</ul>
</nav>
<?php endif; ?>
<section class="grid">
<?php foreach ($gridItems as $it): ?>
<a class="thumbnail" style="background-image: url(<?php echo h($it['image']); ?>);" href="/game.php?id=<?php echo rawurlencode($it['id']); ?>&c=<?php echo rawurlencode($it['category']); ?>"><span class="<?php echo rawurlencode($it['category']); ?>"><?php echo h($it['title']); ?></span></a>
<?php endforeach; ?>
</section>
</article>
<?php if (!empty($pager) && $pager['total_pages'] > 1): ?>
<nav class="pagination">
<?php if ($pager['has_prev']): ?>
<a class="tag" href="<?php echo h($prevUrl) ?>">Prev Page</a>
<?php endif; ?>
<?php if ($pager['has_next']): ?>
<a class="tag" href="<?php echo h($nextUrl) ?>">Next Page</a>
<?php endif; ?>
</nav>
<?php endif; ?>
</main>
<?php include 'footer.php'; ?>
</body>
</html>
