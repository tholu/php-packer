<?php
/**
 * @package Packer v3.1 (beta?), PHP port
 * @author Dean Edwards, fixed by Nao (Wedge.org), fixed by tholu
 * @license http://www.opensource.org/licenses/mit-license.php
 * @from http://code.google.com/p/base2/source/browse/trunk/src/apps/packer#packer%2Fphp
 */

// A collection of regular expressions and their associated replacement values.
// A Base class for creating parsers.

class RegGrp extends Collection
{
  const IGNORE = '$0';

  private static $BACK_REF = '/\\\\(\\d+)/';
  private static $ESCAPE_CHARS = '/\\\\./';
  private static $ESCAPE_BRACKETS = '/\\(\\?[:=!]|\\[[^\\]]+\\]/';
  private static $BRACKETS = '/\\(/';

  public static function count($expression)
  {
    // Count the number of sub-expressions in a RegExp/RegGrp.Item.
    $expression = preg_replace(self::$ESCAPE_CHARS, '', (string)$expression);
    $expression = preg_replace(self::$ESCAPE_BRACKETS, '', $expression);
    return preg_match_all(self::$BRACKETS, $expression, $dummy);
  }

  public $ignoreCase = false;

  private $offset = 0;

  public function __construct($values = null, $ignoreCase = false)
  {
    parent::__construct($values);
    $this->ignoreCase = !!$ignoreCase;
  }

  public function __toString()
  {
    $this->offset = 1;
    return '(' . implode(')|(', array_map(array(&$this, '_item_toString'), $this->getValues())) . ')';
  }

  public function exec($string, $override = null)
  {
    if ($this->size() == 0) return (string)$string;
    if (isset($override)) $this->_override = $override;
    $result = preg_replace_callback('/' . $this . '/', array(&$this, '_replacer'), $string);
    unset($this->_override);
    return $result;
  }

  public function test($string)
  {
    // Not implemented
  }

  private function _item_toString($item)
  {
    // Fix back references.
    $expression = preg_replace_callback(self::$BACK_REF, array(&$this, '_fixBackReference'), (string)$item);
    $this->offset += $item->length + 1;
    return $expression;
  }

  private function _fixBackReference($match, $index)
  {
    return '\\' . ($this->offset + (int)$index);
  }

  private function _replacer($arguments)
  {
    if (empty($arguments)) return '';

    $offset = 1;
    $i = 0;
    // Loop through the RegGrp items.
    foreach ($this->values as $item) {
      $next = $offset + $item->length + 1;
      if (!empty($arguments[$offset])) { // Do we have a result?
        $replacement = isset($this->_override) ? $this->_override : $item->replacement;
        if (is_callable($replacement))
          return call_user_func_array($replacement, array_slice($arguments, $offset, $item->length + 1));
        elseif (is_int($replacement))
          return $arguments[$offset + $replacement];
        else
          return $replacement;
      }
      $offset = $next;
    }
    return $arguments[0];
  }

  public function put($key = '', $item = null)
  {
    if (!($item instanceof RegGrpItem)) {
      $item = new RegGrpItem($key, $item);
    }
    parent::put($key, $item);
  }
}