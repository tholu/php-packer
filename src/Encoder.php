<?php

/**
 * @package Packer v3.1 (beta?), PHP port
 * @author Dean Edwards, fixed by Nao (Wedge.org), fixed by tholu
 * @license http://www.opensource.org/licenses/mit-license.php
 * @from http://code.google.com/p/base2/source/browse/trunk/src/apps/packer#packer%2Fphp
 */
class Encoder
{
  protected $words;
  private $parser;
  private $encoder;

  public function __construct($pattern = null, $encoder = null, $ignore = null)
  {
    $this->parser = new Parser($ignore);
    if (isset($pattern)) $this->parser->put($pattern, '');
    $this->encoder = $encoder;
  }

  public function search($script)
  {
    $this->words = new Words;
    $this->parser->putAt(-1, array(&$this, '_addWord'));
    $this->parser->exec($script);
  }

  public function encode($script)
  {
    $this->search($script);
    $this->words->sort();
    $index = 0;
    foreach ($this->words as $word) {
      $word->encoded = call_user_func($this->encoder, $index++);
    }
    $this->parser->putAt(-1, array(&$this, '_replacer'));
    $script = $this->parser->exec($script);
    unset($this->words);
    return $script;
  }

  public function _replacer($word)
  {
    return $this->words->get($word)->encoded;
  }

  public function _addWord($word)
  {
    $this->words->add($word);
    return $word;
  }
}