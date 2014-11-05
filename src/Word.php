<?php

/**
 * @package Packer v3.1 (beta?), PHP port
 * @author Dean Edwards, fixed by Nao (Wedge.org), fixed by tholu
 * @license http://www.opensource.org/licenses/mit-license.php
 * @from http://code.google.com/p/base2/source/browse/trunk/src/apps/packer#packer%2Fphp
 */
class Word
{
  public $count = 0;
  public $encoded = '';
  public $index = 0;
  private $text = '';

  public function __construct($text)
  {
    $this->text = $text;
  }

  public function __toString()
  {
    return $this->text;
  }

  public function clear()
  {
    $this->text = '';
  }
}