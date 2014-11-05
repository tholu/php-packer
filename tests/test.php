<?php
require '../src/Map.php';
require '../src/Collection.php';
require '../src/Words.php';
require '../src/Encoder.php';
require '../src/Base62.php';
require '../src/Privates.php';
require '../src/Shrinker.php';
require '../src/Minifier.php';
require '../src/RegGrpItem.php';
require '../src/RegGrp.php';
require '../src/Parser.php';
require '../src/Packer.php';
require '../src/init.php';

$js = file_get_contents('test.js');
$packer = new Packer;
$packed_js = $packer->pack($js,true,true,true);
echo $packed_js;