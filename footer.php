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
<span>Copyright &#169; <?php echo date('Y'); ?> <a href="/" title="G55.CO" target="_top">G55.CO</a> | <a href="mailto:crazygames888@gmail.com" title="Contact Us" target="_top">Contact Us</a> | <a href="/privacy-policy.php" title="Privacy Policy" target="_top">Privacy Policy</a> | All Rights Reserved.</span>
</td>
</tr>
</table>
