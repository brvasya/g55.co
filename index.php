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
<link rel="canonical" href="<?php echo home_url($wp->request); ?>/">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap">
<link rel="stylesheet" href="<?php echo bloginfo('template_url'); ?>/style.css">
<link rel="preload" href="<?php echo bloginfo('template_url'); ?>/icons.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.1/jquery.min.js"></script>
<script src="<?php echo bloginfo('template_url'); ?>/mason.min.js"></script>
<?php include 'ads/ga.php' ?>
</head>

<body>
<?php include 'header.php' ?>
<table id="title">
<tr>
<td id="title-left">
<span id="counter"><?php echo number_format($wp_query->found_posts); ?></span>
<h1><?php echo h($title); ?> Games</h1>
</td>
</tr>
</table>
<table id="content">
<tr id="gradient-top">
<td id="gradient-bottom">
<div id="games">
<div class="games">
<?php while (have_posts()) : the_post(); ?>
<?php include 'thumbnail.php' ?>
<?php endwhile; ?>
<?php wp_reset_query(); ?>
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