<?php defined('SYSPATH') OR die('No direct access allowed.'); ?>
<!doctype html>
<html lang=pl>
<meta charset=utf-8>
<title>Przekierowanie</title>
<script>window.top.location.href="<?= $url ?>";</script>
<?php if (empty($silent)): ?>
<p>Powinieneś zostać automatycznie przekierowany, jeżeli do tego nie doszło, kliknij <a href="<?= $url ?>" target="_top">tutaj</a></p>
<?php endif ?>