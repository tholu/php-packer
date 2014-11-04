<?php
require '../src/Class-Packer.php';

$js = file_get_contents('test.js');
$packer = new Packer;
$packed_js = $packer->pack($js,true,true,true);
echo $packed_js;