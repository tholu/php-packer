php-packer
==========

A PHP version of Packer, JavaScript obfuscation library originally created by Dean Edwards, fixed by Nao (Wedge.org).
Re-added shorting of variable names and base62 encoding.

## Installation

Simply run `composer require tholu/php-packer`.

## Usage

```php
<?php
require 'vendor/autoload.php';

$js = file_get_contents('test.js');
$packer = new Packer;
// function pack($script = '', $base62 = false, $shrink = true, $privates = false)
$packed_js = $packer->pack($js,true,true,true);
echo $packed_js;
```