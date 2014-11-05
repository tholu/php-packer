<?php

/**
 * @package Packer v3.1 (beta?), PHP port
 * @author Dean Edwards, fixed by Nao (Wedge.org), fixed by tholu
 * @license http://www.opensource.org/licenses/mit-license.php
 * @from http://code.google.com/p/base2/source/browse/trunk/src/apps/packer#packer%2Fphp
 */
class Parser extends RegGrp
{
  public static $dictionary = array(
    'OPERATOR' => 'return|typeof|[\\[(\\^=,{}:;&|!*?]',
    'CONDITIONAL' => '\\/\\*@\\w*|\\w*@\\*\\/|\\/\\/@\\w*|@\\w+',
    'COMMENT1' => '\\/\\/[^\\n]*',
    'COMMENT2' => '\\/\\*[^*]*\\*+([^\\/][^*]*\\*+)*\\/',
    'REGEXP' => '\\/(\\\\[\\/\\\\]|[^*\\/])(\\\\.|[^\\/\\n\\\\])*\\/[gim]*',
    'STRING1' => '\'(\\\\.|[^\'\\\\])*?\'',
    'STRING2' => '"(\\\\.|[^"\\\\])*?"'
  );

  public function put($expression = '', $replacement = null)
  {
    parent::put(Parser::$dictionary->exec($expression), $replacement);
  }
}

