<?php

/**
 * @package Packer v3.1 (beta?), PHP port
 * @author Dean Edwards, fixed by Nao (Wedge.org), fixed by tholu
 * @license http://www.opensource.org/licenses/mit-license.php
 * @from http://code.google.com/p/base2/source/browse/trunk/src/apps/packer#packer%2Fphp
 */
class Privates extends Encoder
{
  const PATTERN = '\\b_[\\da-zA-Z$][\\w$]*\\b';

  public function __construct()
  {
    return parent::__construct(Privates::PATTERN, array(&$this, '_encoder'), Privates::$IGNORE);
  }

  public function search($script)
  {
    parent::search($script);
    $private = $this->words->get('_private');
    if ($private) $private->count = 99999;
    return $private;
  }

  public static $IGNORE = array(
    'CONDITIONAL' => RegGrp::IGNORE,
    '(OPERATOR)(REGEXP)' => RegGrp::IGNORE
  );

  /* callback functions (public because of php's crappy scoping) */

  public static function _encoder($index)
  {
    return '_' . Packer::encode62($index);
  }
}