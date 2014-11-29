<?php
	$this->layout ('index.phpt');
	$this->textdomain ('example');
?>

		<h2>Example structure</h2>
		<p>
			This is just an example structure.
			We want to give you all the freedom you need.
			No rigid structure.
			Just a bunch of classes that you can instanciate in any way you like.
		</p>

		<h2>Configuration</h2>
		<p>Config example: <?php echo $title; ?>.</p>
		<ul>
			<?php foreach ($counts as $k => $v) { ?>
				<li><?php echo $k; ?>: <?php echo $v; ?></li>
			<?php } ?>
		</ul>

		<h2>Text</h2>
		<p><?php echo $this->gettext ('Let\'s give this gettext a try...'); ?></p>

		<h2>Multiple text</h2>
		<?php $beers = rand (1, 3); ?>

		<p><?php echo sprintf ($this->ngettext ('There is %s beer', 'There are %s beers.', $beers), $beers); ?></p>