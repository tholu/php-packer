<?php

/**
 * @package Packer v3.1 (beta?), PHP port
 * @author Dean Edwards, fixed by Nao (Wedge.org), fixed by tholu
 * @license http://www.opensource.org/licenses/mit-license.php
 * @from http://code.google.com/p/base2/source/browse/trunk/src/apps/packer#packer%2Fphp
 */
class Words extends Collection
{
  public static function sorter($word1, $word2)
  {
    $diff = $word2->count - $word1->count;
    return $diff == 0 ? $word1->index - $word2->index : $diff;
  }

  public function add($word, $item = null)
  {
    if (!$this->has($word)) parent::add($word);
    $word = $this->get($word);
    if ($word->index == 0) {
      $word->index = $this->size();
    }
    $word->count++;
    return $word;
  }

  public function put($key = '', $item = null)
  {
    if (!($item instanceof Word)) {
      $item = new Word($key);
    }
    parent::put($key, $item);
  }

  public function sort($sorter = null)
  {
    if (!isset($sorter)) {
      $sorter = array('Words', 'sorter');
    }
    return parent::sort($sorter);
  }
}