php-packer
==========

A PHP version of Packer, JavaScript obfuscation library originally created by Dean Edwards, ported to PHP by Nicolas Martin.
Packed for composer by Thomas Lutz.

## Installation

Simply run `composer require tholu/php-packer`.

## Usage (slightly changed from previous used implementation!)

```php
<?php
require 'vendor/autoload.php';

$js = file_get_contents('test.js');

/*
 * params of the constructor :
 * $script:           the JavaScript to pack, string.
 * $encoding:         level of encoding, int or string :
 *                    0,10,62,95 or 'None', 'Numeric', 'Normal', 'High ASCII'.
 *                    default: 62.
 * $fastDecode:       include the fast decoder in the packed result, boolean.
 *                    default : true.
 * $specialChars:     if you are flagged your private and local variables
 *                    in the script, boolean.
 *                    default: false.
 * $removeSemicolons: whether to remove semicolons from the source script.
 *                    default: true.
 */

// $packer = new Packer($script, $encoding, $fastDecode, $specialChars, $removeSemicolons);
$packer = new Packer($js, 'Normal', true, true);
$packed_js = $packer->pack();
echo $packed_js;
```