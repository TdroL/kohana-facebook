<?php defined('SYSPATH') OR die('No direct access allowed.'); ?>
<!DOCTYPE html>
<!--[if IE 8]>         <html lang="pl" xmlns="http://www.w3.org/1999/xhtml" xmlns:fb="http://www.facebook.com/2008/fbml" class="no-js lt-ie10 lt-ie9"> <![endif]-->
<!--[if IE 9]>         <html lang="pl" xmlns="http://www.w3.org/1999/xhtml" xmlns:fb="http://www.facebook.com/2008/fbml" class="no-js lt-ie10"><![endif]-->
<!--[if lte IE 9]><!--><html lang="pl" xmlns="http://www.w3.org/1999/xhtml" xmlns:fb="http://www.facebook.com/2008/fbml" class="no-js"> <!--<![endif]-->
<?php if ($fb_config['metatags']): ?>
<head prefix="og: http://ogp.me/ns# fb: http://ogp.me/ns/fb#">
	<meta charset="utf-8"/>
	<meta property="fb:app_id" content="<?= $fb_config['app_id'] ?>" />
	<?php foreach ($metatags as $key => $values): ?>
		<?php foreach ((array) $values as $value): ?>
		<meta property="<?= $key ?>" content="<?= HTML::chars($value) ?>" />
		<?php endforeach ?>
	<?php endforeach ?>
<?php else: ?>
<head>
	<meta charset="utf-8"/>
<?php endif ?>
	<title><?= (( ! empty($metatags['og:title']) AND $metatags['og:title'] != $metatags['og:site_name']) ? $metatags['og:title'].' | ' : NULL).Arr::get($metatags, 'og:site_name', 'Facebook App') ?></title>
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width">

	<link rel="stylesheet" href="<?= URL::site('media/css/reset.css').'?v='.filemtime(DOCROOT.'media/css/reset.css') ?>">
	<link rel="stylesheet" href="<?= URL::site('media/css/styles.css').'?v='.filemtime(DOCROOT.'media/css/styles.css') ?>">

	<script src="<?= URL::site('media/js/vendor/modernizr/modernizr.custom.js') ?>"></script>
	<script>
		var global = global || {};
		global.ko = global.ko || {};
		global.ko['base-url'] = '<?= URL::base(Request::current()) ?>';
		global.ko['session-id'] = '<?= $session->id() ?>';

	<?php if (Kohana::$environment == Kohana::PRODUCTION): ?>
		if (window.top.location == window.location) {
			window.top.location = 'https://apps.facebook.com/<?= trim($fb_config['canvas_url'], '/') ?>/' + window.location.pathname.replace(/^<?= preg_quote(URL::base(), '/') ?>/i, ''); // '
		}
	<?php endif ?>
	</script>
</head>
<body id="page-<?= strtolower(trim(Request::current()->directory().'-'.Request::current()->controller().'-'.Request::current()->action(), '-')) ?>" class="home">
	<?php if (Kohana::$environment != Kohana::PRODUCTION): ?>
	<div style="position: absolute; top: 0; left: 0; right: 0; padding: 10px; margin: 0; background: #EBF8A4; border: 1px solid #A2D246; color: #000; font: 14px/1.5 Arial, sans-serif">Warning: App not in production environment</div>
	<div id="iframe-height" style="position: absolute; z-index: 9999; top: 0;
	 left: 0; width: 30px; background: rgba(80, 10, 10, 0.5); height: 0"></div>
	<?php endif ?>

	<div class="canvas">
		<?= $canvas ?>
	</div>

	<div id="fb-root"></div>
	<script src="<?= URL::site('media/js/vendor/jquery/jquery.min.js') ?>"></script>
	<?php if (Kohana::$environment == Kohana::DEVELOPMENT): ?>
	<script src="<?= URL::site('media/js/vendor/jquery/jquery-migrate.min.js') ?>"></script>
	<?php endif ?>
	<!--[if IE]>
	<script>
		$(':checkbox').on('change', function() {
			var $this = $(this);
			$this[$this.is(':checked') ? 'addClass' : 'removeClass']('checked');
		});
	</script>
	<![endif]-->
	<script>
		window.fbAsyncQueue = window.fbAsyncQueue || [];

		window.fbAsyncInit = function() {
			FB.init({
				appId:      '<?= $fb_config['app_id'] ?>',
				channelUrl: '<?= URL::site('channel.html', Request::current()) ?>',
				status:     <?= $fb_config['status'] ? 'true' : 'false' ?>,
				cookie:     <?= $fb_config['cookie'] ? 'true' : 'false' ?>,
				xfbml:      <?= $fb_config['xfbml'] ? 'true' : 'false' ?>
			});

			FB.Canvas.setSize();

			for (var i = 0; i < window.fbAsyncQueue.length; i++) {
				window.fbAsyncQueue[i]();
			}

			window.fbAsyncQueue = { length: 0, push: function (callback) { setTimeout(function () { callback(); }, 0); } };
		};
		(function(e){e.src='//connect.facebook.net/<?= $fb_config['lang'] ?>/all.js';e.async=1;document.getElementById('fb-root').appendChild(e)}(document.createElement('script')));
	</script>
	<script src="<?= URL::site('media/js/plugins.js?v='.filemtime(DOCROOT.'media/js/plugins.js')) ?>"></script>
	<script src="<?= URL::site('media/js/main.js?v='.filemtime(DOCROOT.'media/js/main.js')) ?>"></script>
	<?php if (Kohana::$environment != Kohana::DEVELOPMENT): ?>
	<script>(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)})(window,document,'script','//www.google-analytics.com/analytics.js','ga');ga('create', 'UA-xxxxxxxx-x', 'xxxxxxx.com');ga('send', 'pageview')</script>
	<?php endif?>
</body>
</html>