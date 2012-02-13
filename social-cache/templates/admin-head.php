<?php /* shows up in the admin head */ ?>
<link rel='stylesheet' href='<?= $pluginDir; ?>/css/social-cache.css' />
<link rel='stylesheet' href='<?= $pluginDir; ?>/css/social-cache-add.css' />
<script type='text/javascript' src='<?= $pluginDir; ?>/js/parseUri.js'></script>
<script type='text/javascript'>
	var BSS_SOCIAL_CACHE_SETTINGS = {};
	<?php foreach($settings as $id=>$setting): ?>
		BSS_SOCIAL_CACHE_SETTINGS['<?= $id; ?>'] = '<?= $setting['value']; ?>';
	<?php endforeach; ?>
</script>
<script type='text/javascript' src='<?= $pluginDir; ?>/js/social-cache.js'></script>