<?php

/**
 * @package Packer v3.1 (beta?), PHP port
 * @author Dean Edwards, fixed by Nao (Wedge.org), fixed by tholu
 * @license http://www.opensource.org/licenses/mit-license.php
 * @from http://code.google.com/p/base2/source/browse/trunk/src/apps/packer#packer%2Fphp
 */
class RegGrpItem
{
  private static $LOOKUP = '/\\$(\\d+)/';
  private static $LOOKUP_SIMPLE = '/^\\$\\d+$/';

  private static $FUNCTION_PARSER = array(
    '/\\\\/' => '\\\\',
    '/"/' => '\\x22',
    '/\\n/' => '\\n',
    '/\\r/' => '\\r',
    '/\\$(\\d+)/' => '\'.@$a[$1].\''
  );

  private $expression = '';
  public $replacement = '';

  public function __construct($expression, $replacement = RegGrp::IGNORE)
  {
    if ($replacement instanceof RegGrpItem) $replacement = $replacement->replacement;

    // Does the pattern use sub-expressions?
    if (!is_callable($replacement) && preg_match(self::$LOOKUP, $replacement)) {
      // A simple lookup? (e.g. "$2")
      if (preg_match(self::$LOOKUP_SIMPLE, $replacement)) {
        // Store the index (used for fast retrieval of matched strings)
        $replacement = (int)substr($replacement, 1);
      } else { // A complicated lookup (e.g. "Hello $2 $1")
        // Build a function to do the lookup
        // Improved version by Alexei Gorkov:
        $replacement = preg_replace(array_keys(self::$FUNCTION_PARSER), array_values(self::$FUNCTION_PARSER), $replacement);
        $replacement = preg_replace('/([\'"])\\1\\.(.*)\\.\\1\\1$/', '$1', $replacement);
        $replacement = create_function('', '$a=func_get_args();return \'' . $replacement . '\';');
      }
    }

    $this->expression = $expression;
    $this->replacement = $replacement;
  }

  public function __get($key)
  {
    $value = null;
    if ($key == 'length') {
      $value = RegGrp::count($this->expression);
    }
    return $value;
  }

  public function __toString()
  {
    return $this->expression;
  }
}