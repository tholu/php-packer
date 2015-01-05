<?php
require '../src/Packer.php';

$js = file_get_contents('test.js');
$packer = new Packer($js, 'Normal', true, true);
$packed_js = $packer->pack();
echo $packed_js;