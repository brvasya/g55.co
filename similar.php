<li id="ads" class="block"><script async src="/ads/336x280.js"></script></li>
<?php foreach ($similar as $p): ?>
<li><a class="tag" style="background-image: url(<?php echo h('/wp-content/uploads/' . $p['id'] . '.png'); ?>);" href="/game.php?id=<?php echo rawurlencode($p['id']); ?>&c=<?php echo rawurlencode($cid); ?>" title="<?php echo h($p['title']); ?>" target="_top"><?php echo h($p['title']); ?></a></li>
<?php endforeach; ?>
<li><a class="tag" id="<?php echo rawurlencode($cid); ?>" href="<?php echo h($moreHref); ?>" title="<?php echo h($moreText); ?>" target="_top"><?php echo h($moreText); ?></a></li>
