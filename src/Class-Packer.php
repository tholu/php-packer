<?php
/**
 * @package Packer v3.1 (beta?), PHP port
 * @author Dean Edwards, fixed by Nao (Wedge.org), fixed by tholu
 * @license http://www.opensource.org/licenses/mit-license.php
 * @from http://code.google.com/p/base2/source/browse/trunk/src/apps/packer#packer%2Fphp
 */

// Found this by chance, it was broken so I fixed it.
// It's all in a day's work for script-repair man.

/****************************************************************
/* include('base2/classes.php'); */


class Map implements Iterator {
  protected $values = array();

  public function __construct($values = NULL) {
    if (is_array($values)) $this->merge($values);
  }

  // Map

  public function clear() {
    $this->values = array();
  }

  public function copy() {
    return clone $this;
  }

  public function get($key) {
    return $this->values[(string)$key];
  }

  public function getKeys() {
    return array_keys($this->values);
  }

  public function getValues() {
    return array_values($this->values);
  }

  public function has($key) {
    return array_key_exists((string)$key, $this->values);
  }

  public function merge($values) {
    foreach ($values as $key => $value) {
      $this->put($key, $value);
    }
    return $this;
  }

  public function put($key = '', $value = NULL) {
    $this->values[(string)$key] = $value;
  }

  public function remove($key) {
    unset($this->values[(string)$key]);
  }

  public function size() {
    return count($this->values);
  }

  public function union($values) {
    return $this->copy()->merge($values);
  }

  // Iterator

  public function rewind() {
    reset($this->values);
  }

  public function current() {
    return current($this->values);
  }

  public function key() {
    return key($this->values);
  }

  public function next() {
    return next($this->values);
  }

  public function previous() {
    return previous($this->values);
  }

  public function valid() {
    return !is_null(key($this->values));
  }

  // Enumerable

  public function every($test) {
    foreach ($this->values as $key => $value) {
      if (!$test($value, $key)) return false;
    }
    return true;
  }

  public function filter($test) {
    return new self(array_filter($this->values, $test));
  }

  public function invoke($method) {
    $result = array();
    foreach ($this->values as $value) {
      array_push($result, call_user_func(array(&$value, $method)));
    }
    return $result;
  }

  public function map($callback) {
    return array_map($callback, $this->getValues());
  }

  public function pluck($key) {
    $result = array();
    foreach ($this->values as $value) {
      array_push($result, $value->{$key});
    }
    return $result;
  }

  public function reduce($callback, $initial) {
    return array_reduce($this->values, $callback, $initial);
  }

  public function some($test) {
    foreach ($this->values as $key => $value) {
      if ($test($value, $key)) return true;
    }
    return false;
  }
}

class Collection extends Map {
  public function __toString() {
    return '('.implode(',', $this->getKeys()).')';
  }

  public function add($key, $item = null) {
    // Duplicates not allowed using add().
    // But you can still overwrite entries using put().
    assert(!$this->has($key));

    $this->put($key, $item);
  }

  public function getAt($index) {
    return $this->get($this->getKey($index));
  }

  public function indexOf($key) {
    return array_search($key, $this->getKeys());
  }

  public function insertAt($index, $key, $item) {
    assert($this->isValidIndex($index));
    assert(!$this->has($key));

    array_splice($this->values, $index, 1, null); // Placeholder
    $this->put($key, $item);
  }

  public function item($key) {
    if (is_int($key))
      $key = $this->getKey($key);
    return $this->get($key);
  }

  public function putAt($index, $item) {
    assert($this->isValidIndex($index));

    $this->put($this->getKey($index), $item);
  }

  public function removeAt($index) {
    $this->remove($this->getKey($index));
  }

  public function reverse() {
    array_reverse($this->values, TRUE);
    return $this;
  }

  public function sort($sorter = null) {
    if (isset($sorter))
      uasort($this->values, $sorter);
    else
      asort($this->values);
    return $this;
  }

  public function slice($start = 0, $end = null) {
    $values = $this->values;
    $length = $end;
    if (isset($end) && $end > 0)
      $length = $end - $this->size();
    $this->values = array_slice($values, $start, $length, TRUE);
    $sliced = $this->copy();
    $this->values = $values;
    return $sliced;
  }

  private function getKey($index) {
    $size = $this->size();
    if ($index < 0) $index += $size;
    $keys = $this->getKeys();
    return $keys[$index];
  }

  private function isValidIndex($index) {
    $size = $this->size();
    if ($index < 0) $index += $size;
    return ($index >= 0) && ($index < $size);
  }
}

// A collection of regular expressions and their associated replacement values.
// A Base class for creating parsers.

class RegGrp extends Collection {
  const IGNORE = '$0';

  private static $BACK_REF    = '/\\\\(\\d+)/';
  private static $ESCAPE_CHARS  = '/\\\\./';
  private static $ESCAPE_BRACKETS = '/\\(\\?[:=!]|\\[[^\\]]+\\]/';
  private static $BRACKETS    = '/\\(/';

  public static function count($expression) {
    // Count the number of sub-expressions in a RegExp/RegGrp.Item.
    $expression = preg_replace(self::$ESCAPE_CHARS, '', (string)$expression);
    $expression = preg_replace(self::$ESCAPE_BRACKETS, '', $expression);
    return preg_match_all(self::$BRACKETS, $expression, $dummy);
  }

  public $ignoreCase = false;

  private $offset = 0;

  public function __construct($values = null, $ignoreCase = false) {
    parent::__construct($values);
    $this->ignoreCase = !!$ignoreCase;
  }

  public function __toString() {
    $this->offset = 1;
    return '('.implode(')|(', array_map(array(&$this, '_item_toString'), $this->getValues())).')';
  }

  public function exec($string, $override = null) {
    if ($this->size() == 0) return (string)$string;
    if (isset($override)) $this->_override = $override;
    $result = preg_replace_callback('/'.$this.'/', array(&$this, '_replacer'), $string);
    unset($this->_override);
    return $result;
  }

  public function test($string) {
    // Not implemented
  }

  private function _item_toString($item) {
    // Fix back references.
    $expression = preg_replace_callback(self::$BACK_REF, array(&$this, '_fixBackReference'), (string) $item);
    $this->offset += $item->length + 1;
    return $expression;
  }

  private function _fixBackReference($match, $index) {
    return '\\'.($this->offset + (int)$index);
  }

  private function _replacer($arguments) {
    if (empty($arguments)) return '';

    $offset = 1; $i = 0;
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

  public function put($key = '', $item = null) {
    if (!($item instanceof RegGrpItem)) {
      $item = new RegGrpItem($key, $item);
    }
    parent::put($key, $item);
  }
}

class RegGrpItem {
  private static $LOOKUP      = '/\\$(\\d+)/';
  private static $LOOKUP_SIMPLE = '/^\\$\\d+$/';

  private static $FUNCTION_PARSER = array(
    '/\\\\/' => '\\\\',
    '/"/'   => '\\x22',
    '/\\n/' => '\\n',
    '/\\r/' => '\\r',
    '/\\$(\\d+)/' => '\'.@$a[$1].\''
  );

  private $expression = '';
  public  $replacement = '';

  public function __construct($expression, $replacement = RegGrp::IGNORE) {
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
        $replacement = create_function('', '$a=func_get_args();return \''.$replacement.'\';');
      }
    }

    $this->expression = $expression;
    $this->replacement = $replacement;
  }

  public function __get($key) {
    $value = null;
    if ($key == 'length') {
      $value = RegGrp::count($this->expression);
    }
    return $value;
  }

  public function __toString() {
    return $this->expression;
  }
}

/****************************************************************
/* include('Words.php'); */

class Words extends Collection {
  public static function sorter($word1, $word2) {
    $diff = $word2->count - $word1->count;
    return $diff == 0 ? $word1->index - $word2->index : $diff;
  }

  public function add($word, $item = null) {
    if (!$this->has($word)) parent::add($word);
    $word = $this->get($word);
    if ($word->index == 0) {
      $word->index = $this->size();
    }
    $word->count++;
    return $word;
  }

  public function put($key = '', $item = null) {
    if (!($item instanceof Word)) {
      $item = new Word($key);
    }
    parent::put($key, $item);
  }

  public function sort($sorter = null) {
    if (!isset($sorter)) {
      $sorter = array('Words', 'sorter');
    }
    return parent::sort($sorter);
  }
}

class Word {
  public $count = 0;
  public $encoded = '';
  public $index = 0;
  private $text = '';

  public function __construct($text) {
    $this->text = $text;
  }

  public function __toString() {
    return $this->text;
  }

  public function clear() {
    $this->text = '';
  }
}

/****************************************************************
/* include('Parser.php'); */

class Parser extends RegGrp {
  public static $dictionary = array(
    'OPERATOR'    => 'return|typeof|[\\[(\\^=,{}:;&|!*?]',
    'CONDITIONAL' => '\\/\\*@\\w*|\\w*@\\*\\/|\\/\\/@\\w*|@\\w+',
    'COMMENT1'    => '\\/\\/[^\\n]*',
    'COMMENT2'    => '\\/\\*[^*]*\\*+([^\\/][^*]*\\*+)*\\/',
    'REGEXP'    => '\\/(\\\\[\\/\\\\]|[^*\\/])(\\\\.|[^\\/\\n\\\\])*\\/[gim]*',
    'STRING1'   => '\'(\\\\.|[^\'\\\\])*?\'',
    'STRING2'   => '"(\\\\.|[^"\\\\])*?"'
  );

  public function put($expression = '', $replacement = null) {
    parent::put(Parser::$dictionary->exec($expression), $replacement);
  }
}

Parser::$dictionary = new RegGrp(Parser::$dictionary);

/****************************************************************
/* include('Encoder.php'); */

class Encoder {
  protected $words;
  private $parser;
  private $encoder;

  public function __construct($pattern = null, $encoder = null, $ignore = null) {
    $this->parser = new Parser($ignore);
    if (isset($pattern)) $this->parser->put($pattern, '');
    $this->encoder = $encoder;
  }

  public function search($script) {
    $this->words = new Words;
    $this->parser->putAt(-1, array(&$this, '_addWord'));
    $this->parser->exec($script);
  }

  public function encode($script) {
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

  public function _replacer($word) {
    return $this->words->get($word)->encoded;
  }

  public function _addWord($word) {
    $this->words->add($word);
    return $word;
  }
}

/****************************************************************
/* include('Packer.php'); */

class Packer {
  public static $data = array(
    'STRING1' => RegGrp::IGNORE,
    'STRING2' => RegGrp::IGNORE,
    'CONDITIONAL' => RegGrp::IGNORE, // Conditional comments
    '(OPERATOR)\\s*(REGEXP)' => '$1$2'
  );

  public static function encode52($n) {
    $left = $n < 52 ? '' : self::encode52((int)($n / 52));
    $n = $n % 52;
    $right = $n > 25 ? chr($n + 39) : chr($n + 97);
    $encoded = $left.$right;
    if (preg_match('/^(do|if|in)$/', $encoded)) {
      $encoded = substr($encoded, 1).'0';
    }
    return $encoded;
  }

  public static function encode62($n) {
    $left = $n < 62 ? '' : self::encode62((int)($n / 62));
    $n = $n % 62;
    $right = $n > 35 ? chr($n + 29) : base_convert($n, 10, 36);
    return $left.$right;
  }

  private $minifier;
  private $shrinker;
  private $privates;
  private $base62;

  public function __construct() {
    $this->minifier = new Minifier;
    $this->shrinker = new Shrinker;
    $this->privates = new Privates;
    $this->base62 = new Base62;
  }

  public function pack($script = '', $base62 = false, $shrink = true, $privates = false) {
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

// Initialze static object properties

Packer::$data = new Parser(Packer::$data);

/****************************************************************
/* include('Minifier.php'); */

class Minifier {
  public function minify($script) {
    $script .= "\n";
    $script = preg_replace('/\\\\\\r?\\n/', '', $script);
    $script = self::$comments->exec($script);
    $script = self::$clean->exec($script);
    $script = self::$whitespace->exec($script);
    $concatenated = self::$concat->exec($script);
    while ($concatenated != $script) {
      $script = $concatenated;
      $concatenated = self::$concat->exec($script);
    }
    return $script;
  }

  public static $clean = array(
    '\\(\\s*([^;)]*)\\s*;\\s*([^;)]*)\\s*;\\s*([^;)]*)\\)' => '($1;$2;$3)', // For (;;) loops
    'throw[^};]+[};]' => RegGrp::IGNORE, // A safari 1.3 bug
    ';+\\s*([};])' => '$1'
  );

  public static $comments = array(
    ';;;[^\\n]*\\n' => '',
    '(COMMENT1)(\\n\\s*)(REGEXP)?' => '$3$4',
    '(COMMENT2)\\s*(REGEXP)?' => array(__CLASS__, '_commentParser')
  );

  public static function _commentParser($match, $comment = '', $dummy = '', $regexp = '') {
    if (preg_match('/^\\/\\*@/', $comment) && preg_match('/@\\*\\/$/', $comment)) {
      $comment = self::$conditionalComments->exec($comment);
    } else {
      $comment = '';
    }
    return $comment.' '.$regexp;
  }

  public static $conditionalComments;

  public static $concat = array(
    '(STRING1)\\+(STRING1)' => array(__CLASS__, '_concatenater'),
    '(STRING2)\\+(STRING2)' => array(__CLASS__, '_concatenater')
  );

  public static function _concatenater($match, $string1, $plus, $string2) {
    return substr($string1, 0, -1).substr($string2, 1);
  }

  public static $whitespace = array(
    '\\/\\/@[^\\n]*\\n' => RegGrp::IGNORE,
    '@\\s+\\b' => '@ ', // Protect conditional comments
    '\\b\\s+@' => ' @',
    '(\\d)\\s+(\\.\\s*[a-z\\$_\\[(])' => '$1 $2', // http://dean.edwards.name/weblog/2007/04/packer3/#comment84066
    '([+-])\\s+([+-])' => '$1 $2', // c = a++ +b;
    '(\\w)\\s+(\\pL)' => '$1 $2', // http://code.google.com/p/base2/issues/detail?id=78
    '\\b\\s+\\$\\s+\\b' => ' $ ', // var $ in
    '\\$\\s+\\b' => '$ ', // object$ in
    '\\b\\s+\\$' => ' $', // return $object
//    '\\b\\s+#' => ' #',  // CSS
    '\\b\\s+\\b' => ' ',
    '\\s+' => ''
  );
}

// Initialize static object properties

//eval("var e=this.encode62=" + this.ENCODE62);
Minifier::$clean = Packer::$data->union(new Parser(Minifier::$clean));
Minifier::$concat = new Parser(Minifier::$concat);
Minifier::$concat->merge(Packer::$data);
Minifier::$comments = Packer::$data->union(new Parser(Minifier::$comments));
Minifier::$conditionalComments = Minifier::$comments->copy();
Minifier::$conditionalComments->putAt(-1, ' $3');
Minifier::$whitespace = Packer::$data->union(new Parser(Minifier::$whitespace));
Minifier::$whitespace->removeAt(2); // Conditional comments
Minifier::$comments->removeAt(2);

/****************************************************************
/* include('Shrinker.php'); */

class Shrinker {
  const PREFIX = "\x02";
  const SHRUNK = '\\x02\\d+\\b';

  private static $ESCAPE = '/([\\/()[\\]{}|*+-.,^$?\\\\])/';

  public static function rescape($string) {
    // Make a string safe for creating a RegExp.
    return preg_replace(self::$ESCAPE, '\\\\$1', $string);
  }

  // Identify blocks, particularly identify function blocks (which define scope)
  private $BLOCK      = '/((catch|do|if|while|with|function)\\b[^~{};]*(\\(\\s*[^{};]*\\s*\\))\\s*)?(\\{[^{}]*\\})/';
  private $BRACKETS   = '/\\{[^{}]*\\}|\\[[^\\[\\]]*\\]|\\([^\\(\\)]*\\)|~[^~]+~/';
  private $ENCODED_BLOCK  = '/~#?(\\d+)~/';
  private $ENCODED_DATA = '/\\x01(\\d+)\\x01/';
  private $IDENTIFIER   = '/[a-zA-Z_$][\\w\\$]*/';
  private $SCOPED     = '/~#(\\d+)~/';
  private $VAR      = '/\\bvar\\b/';
  private $VARS     = '/\\bvar\\s+[\\w$]+[^;#]*|\\bfunction\\s+[\\w$]+/';
  // The following line is from Packer 4. See my original bug report: http://dean.edwards.name/weblog/2007/04/packer3/#comment377355
  private $VAR_TIDY   = '/\\b(const|let|var|function|catch\\s*\\()\\b|\\s+in\\b[^;]*/';
  private $VAR_EQUAL    = '/\\s*=[^,;]*/';

  private $count = 0; // Number of variables
  private $blocks; // Store program blocks (anything between braces {})
  private $data;   // Store program data (strings and regexps)
  private $script;

  public function shrink($script = '') {
    $script = $this->encodeData($script);

    $this->blocks = array();
    $script = $this->decodeBlocks($this->encodeBlocks($script), $this->ENCODED_BLOCK);
    unset($this->blocks);

    $this->count = 0;
    $this->script = $script;
    $shrunk = new Encoder(Shrinker::SHRUNK, array(&$this, '_varEncoder'));
    $script = $shrunk->encode($script);
    unset($this->script);

    return $this->decodeData($script);
  }

  private function decodeBlocks($script, $encoded) {
    // Put the blocks back
    while (preg_match($encoded, $script)) {
      $script = preg_replace_callback($encoded, array(&$this, '_blockDecoder'), $script);
    }
    return $script;
  }

  private function encodeBlocks($script) {
    // Encode blocks, as we encode we replace variable and argument names
    while (preg_match($this->BLOCK, $script)) {
      $script = preg_replace_callback($this->BLOCK, array(&$this, '_blockEncoder'), $script);
    }
    return $script;
  }

  private function decodeData($script) {
    // Put strings and regular expressions back
    $script = preg_replace_callback($this->ENCODED_DATA, array(&$this, '_dataDecoder'), $script);
    unset($this->data);
    return $script;
  }

  private function encodeData($script) {
    $this->data = array(); // Encoded strings and regular expressions
    // Encode strings and regular expressions
    return Packer::$data->exec($script, array(&$this, '_dataEncoder'));
  }

  /* Callback functions (public because of php's crappy scoping) */

  public function _blockDecoder($matches) {
    return $this->blocks[$matches[1]];
  }

  public function _blockEncoder($match) {
    $prefix = $match[1]; $blockType = $match[2]; $args = $match[3]; $block = $match[4];
    if (!$prefix) $prefix = '';
    if ($blockType == 'function') {
      // Decode the function block (THIS IS THE IMPORTANT BIT)
      // We are retrieving all sub-blocks and will re-parse them in light
      // of newly shrunk variables
      $block = $args.$this->decodeBlocks($block, $this->SCOPED);
      $prefix = preg_replace($this->BRACKETS, '', $prefix);

      // Create the list of variable and argument names
      $args = substr($args, 1, -1);

      if ($args != '_no_shrink_') {
        preg_match_all($this->VARS, $block, $matches);
        $vars = preg_replace($this->VAR, ';var', implode(';', $matches[0]));
        while (preg_match($this->BRACKETS, $vars)) {
          $vars = preg_replace($this->BRACKETS, '', $vars);
        }
        $vars = preg_replace(array($this->VAR_TIDY, $this->VAR_EQUAL), '', $vars);
      }
      $block = $this->decodeBlocks($block, $this->ENCODED_BLOCK);

      // Process each identifier
      if ($args != '_no_shrink_') {
        $count = 0;
        preg_match_all($this->IDENTIFIER, $args.','.$vars, $matches);
        $processed = array();
        foreach ($matches[0] as $id) {
          if (empty($processed[$id])) {
            $processed[$id] = true;
            $id = self::rescape($id);
            // Encode variable names
            while (preg_match('/'.Shrinker::PREFIX.$count.'\\b/', $block)) $count++;
            $reg = '/([^\\w$.])'.$id.'([^\\w$:])/';
            while (preg_match($reg, $block)) {
              $block = preg_replace($reg, '$1'.Shrinker::PREFIX.$count.'$2', $block);
            }
            $block = preg_replace('/([^{,\\w$.])'.$id.':/', '$1'.Shrinker::PREFIX.$count.':', $block);
            $count++;
          }
        }
        $this->total = max(@$this->total, $count);
      }
      $replacement = $prefix.'~'.count($this->blocks).'~';
      array_push($this->blocks, $block);
    } else {
      $replacement = '~#'.count($this->blocks).'~';
      array_push($this->blocks, $prefix.$block);
    }
    return $replacement;
  }

  public function _dataDecoder($matches) {
    return $this->data[$matches[1]];
  }

  public function _dataEncoder($match, $operator = '', $regexp = '') {
    $replacement = "\x01".count($this->data)."\x01";
    if ($regexp) {
      $replacement = $operator.$replacement;
      $match = $regexp;
    }
    array_push($this->data, $match);
    return $replacement;
  }

  public function _varEncoder() {
    // Find the next free short name
    do $shortId = Packer::encode52($this->count++);
    while (preg_match('/[^\\w$.]'.$shortId.'[^\\w$:]/', $this->script));
    return $shortId;
  }
}





/****************************************************************
/* include('Privates.php'); */

class Privates extends Encoder {
  const PATTERN = '\\b_[\\da-zA-Z$][\\w$]*\\b';

  public function __construct() {
    return parent::__construct(Privates::PATTERN, array(&$this, '_encoder'), Privates::$IGNORE);
  }

  public function search($script) {
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

  public static function _encoder($index) {
    return '_'.Packer::encode62($index);
  }
}

/****************************************************************
/* include('Base62.php'); */

class Base62 extends Encoder {
  private static $WORDS    = '/\\b[\\da-zA-Z]\\b|\\w{2,}/';

  private static $ENCODE10 = 'String';
  private static $ENCODE36 = 'function(c){return c.toString(36)}';
  private static $ENCODE62 = 'function(c){return(c<62?\'\':e(parseInt(c/62)))+((c=c%62)>35?String.fromCharCode(c+29):c.toString(36))}';

  public static $UNPACK   = 'eval(function(p,a,c,k,e,r){e=%5;if(\'0\'.replace(0,e)==0){
while(c--)r[e(c)]=k[c];k=[function(e){return r[e]||e}];e=function(){return\'%6\'};c=1};
while(c--)if(k[c])p=p.replace(new RegExp(\'\\\\b\'+e(c)+\'\\\\b\',\'g\'),k[c]);
return p}(\'%1\',%2,%3,\'%4\'.split(\'|\'),0,{}))';

  public static function sorter($word1, $word2) {
    return $word1->index - $word2->index;
  }

  public function getPattern() {
    $words = $this->words->size() == 0 ?  '\\x0' : preg_replace(array('/\\|{2,}/', '/^\\|+|\\|+$/'), array('|', ''), implode('|', $this->words->map(strval)));
    return '/\\b('.$words.')\\b/';
  }

  public function search($script) {
    $this->words = new Words;
    preg_match_all(Base62::$WORDS, $script, $matches, PREG_PATTERN_ORDER);
    foreach ($matches[0] as $word) {
      $this->words->add($word);
    }
  }

  public function encode($script) {
    $this->search($script);

    $this->words->sort();

    $encoded = new Collection; // a dictionary of base62 -> base10
    $size = $this->words->size();
    for ($i = 0; $i < $size; $i++) {
      $encoded->put(Packer::encode62($i), $i);
    }

    $index = 0;
    foreach ($this->words as $word) {
      if ($encoded->has($word)) {
        $word->index = $encoded->get($word);
        $word->clear();
      } else {
        while ($this->words->has(Packer::encode62($index))) $index++;
        $word->index = $index++;
        if ($word->count == 1) {
          $word->clear();
        }
      }
      $word->replacement = Packer::encode62($word->index);
      if (strlen($word->replacement) == strlen($word)) {
        $word->clear();
      }
    }

    // sort by encoding
    $this->words->sort(array('Base62', 'sorter'));

    // trim unencoded words
    $this->words = $this->words->slice(0, preg_match_all('/\\|/', $this->getKeyWords(), $matches) + 1);

    $script = preg_replace_callback($this->getPattern(), array(&$this, '_word_replacement'), $script);

    /* build the packed script */

    $p = $this->escape($script);
    $a = '[]';
    $c = max($this->words->size(), 1);
    $k = $this->getKeyWords();
    $e = $this->getEncoder();
    $d = $this->getDecoder();

    // the whole thing
    return $this->format(Base62::$UNPACK, $p,$a,$c,$k,$e,$d);
  }

  private function format($string) {
    // Replace %n with arguments[n].
    // e.g. format("%1 %2%3 %2a %1%3", "she", "se", "lls");
    // ==> "she sells sea shells"
    // Only %1 - %9 supported.
    $this->args = func_get_args();
    return preg_replace_callback('/%([1-'.count($this->args).'])/', array(&$this, '_formatter'), $string);
  }

  public function _formatter($matches) {
    return $this->args[$matches[1]];
  }

  private function escape($script) {
    // Single quotes wrap the final string so escape them.
    // Also, escape new lines (required by conditional comments).
    return preg_replace(array('/([\\\\\\\'])/', '/[\\r\\n]+/'), array('\\\\$1', '\\n'), $script);
  }

  private function getDecoder() {
    // returns a pattern used for fast decoding of the packed script
    $trim = new RegGrp(array(
      '(\\d)(\\|\\d)+\\|(\\d)' => '$1-$3',
      '([a-z])(\\|[a-z])+\\|([a-z])' => '$1-$3',
      '([A-Z])(\\|[A-Z])+\\|([A-Z])' => '$1-$3',
      '\\|' => ''
    ));
    $pattern = $trim->exec(implode('|', array_slice($this->words->map(array(&$this, '_word_replacement')), 0, 62)));

    if ($pattern == '') return '^$';

    $pattern = '['.$pattern.']';

    $size = $this->words->size();
    if ($size > 62) {
      $pattern = '(' . $pattern . '|';
      $encoded = Packer::encode62($size);
      $c = $encoded[0];
      if ($c > '9') {
        $pattern .= '[\\\\d';
        if ($c >= 'a') {
          $pattern .= 'a';
          if ($c >= 'z') {
            $pattern .= '-z';
            if ($c >= 'A') {
              $pattern .= 'A';
              if ($c > 'A') $pattern .= '-' . $c;
            }
          } else if ($c == 'b') {
            $pattern .= '-' . $c;
          }
        }
        $pattern .= ']';
      } else if ($c == 9) {
        $pattern .= '\\\\d';
      } else if ($c == 2) {
        $pattern .= '[12]';
      } else if ($c == 1) {
        $pattern .= '1';
      } else {
        $pattern .= '[1-' . $c . ']';
      }
      $pattern .= "\\\\w)";
    }

    return $pattern;
  }

  private function getEncoder() {
    if ($this->words->size() > 36) return self::$ENCODE62;
    if ($this->words->size() > 10) return self::$ENCODE36;
    return self::$ENCODE10;
  }

  private function getKeyWords() {
    return preg_replace('/\\|+$/', '', implode('|', $this->words->map(strval)));
  }

  public function put($key, $item = NULL) {
    if (!($item instanceof Base62Item)) {
      $item = new Base62Item($key, $item);
    }
    parent::put($key, $item);
  }

  public function _word_replacement($word) {
    if (is_array($word)) $word = $word[0];
    if ((string)$word == '') return $word;
    if (is_string($word)) $word = $this->words->get($word);
    return $word->replacement;
  }
}

Base62::$UNPACK = preg_replace('/[\\r\\n]/', '', Base62::$UNPACK);
