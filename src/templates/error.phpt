<!DOCTYPE html>
<html>
	<head>

		<!-- Latest compiled and minified CSS -->
		<?php echo $this->css ('https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap.min.css'); ?>

		<!-- Optional theme -->
		<?php echo $this->css ('https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap-theme.min.css'); ?>

		<!-- Latest compiled and minified JavaScript -->
		<?php echo $this->js ('https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/js/bootstrap.min.js'); ?>

	</head>

	<body>

		<div class="container">

			<div class="alert alert-danger">
				<h4><?php echo $status; ?></h4>
				<p><?php echo $message; ?></p>
			</div>
		</div>

	</body>
</html>