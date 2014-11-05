<?php

/**
 * @package Packer v3.1 (beta?), PHP port
 * @author Dean Edwards, fixed by Nao (Wedge.org), fixed by tholu
 * @license http://www.opensource.org/licenses/mit-license.php
 * @from http://code.google.com/p/base2/source/browse/trunk/src/apps/packer#packer%2Fphp
 */
class Packer
{
  public static $data = array(
    'STRING1' => RegGrp::IGNORE,
    'STRING2' => RegGrp::IGNORE,
    'CONDITIONAL' => RegGrp::IGNORE, // Conditional comments
    '(OPERATOR)\\s*(REGEXP)' => '$1$2'
  );

  public static function encode52($n)
  {
    $left = $n < 52 ? '' : self::encode52((int)($n / 52));
    $n = $n % 52;
    $right = $n > 25 ? chr($n + 39) : chr($n + 97);
    $encoded = $left . $right;
    if (preg_match('/^(do|if|in)$/', $encoded)) {
      $encoded = substr($encoded, 1) . '0';
    }
    return $encoded;
  }

  public static function encode62($n)
  {
    $left = $n < 62 ? '' : self::encode62((int)($n / 62));
    $n = $n % 62;
    $right = $n > 35 ? chr($n + 29) : base_convert($n, 10, 36);
    return $left . $right;
  }

  private $minifier;
  private $shrinker;
  private $privates;
  private $base62;

  public function __construct()
  {
    $this->minifier = new Minifier;
    $this->shrinker = new Shrinker;
    $this->privates = new Privates;
    $this->base62 = new Base62;
  }

  public function pack($script = '', $base62 = false, $shrink = true, $privates = false)
  {
    // Minification (remove useless whitespace)
    $script = $this->minifier->minify($script);
    // Shrinking (shorten local variable names)
    if ($shrink) $script = $this->shrinker->shrink($script);
    // Privates (shorten _variables and _functions)
    if ($privates) $script = $this->privates->encode($script);
    // Base62 encoding (obfuscation/compression)
    if ($base62) $script = $this->base62->encode($script);
    return $script;
  }
}
