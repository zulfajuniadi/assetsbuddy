<html>
<head>
	<title>Test Minifier</title>
	<?php echo $css; ?>
</head>
<body>
	<div class="span12">
		<h1>This file has minified and merged css and javascript. <span id="outlet"></span></h1>
		<a href="min/clearCache">Reset Cache</a>
	</div>
	<?php echo $tmpl; ?>
	<?php echo $js; ?>
</body>
</html>