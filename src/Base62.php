<?php

/**
 * @package Packer v3.1 (beta?), PHP port
 * @author Dean Edwards, fixed by Nao (Wedge.org), fixed by tholu
 * @license http://www.opensource.org/licenses/mit-license.php
 * @from http://code.google.com/p/base2/source/browse/trunk/src/apps/packer#packer%2Fphp
 */
class Base62 extends Encoder
{
  private static $WORDS = '/\\b[\\da-zA-Z]\\b|\\w{2,}/';

  private static $ENCODE10 = 'String';
  private static $ENCODE36 = 'function(c){return c.toString(36)}';
  private static $ENCODE62 = 'function(c){return(c<62?\'\':e(parseInt(c/62)))+((c=c%62)>35?String.fromCharCode(c+29):c.toString(36))}';

  public static $UNPACK = 'eval(function(p,a,c,k,e,r){e=%5;if(\'0\'.replace(0,e)==0){
while(c--)r[e(c)]=k[c];k=[function(e){return r[e]||e}];e=function(){return\'%6\'};c=1};
while(c--)if(k[c])p=p.replace(new RegExp(\'\\\\b\'+e(c)+\'\\\\b\',\'g\'),k[c]);
return p}(\'%1\',%2,%3,\'%4\'.split(\'|\'),0,{}))';

  public static function sorter($word1, $word2)
  {
    return $word1->index - $word2->index;
  }

  public function getPattern()
  {
    $words = $this->words->size() == 0 ? '\\x0' : preg_replace(array('/\\|{2,}/', '/^\\|+|\\|+$/'), array('|', ''), implode('|', $this->words->map('strval')));
    return '/\\b(' . $words . ')\\b/';
  }

  public function search($script)
  {
    $this->words = new Words;
    preg_match_all(Base62::$WORDS, $script, $matches, PREG_PATTERN_ORDER);
    foreach ($matches[0] as $word) {
      $this->words->add($word);
    }
  }

  public function encode($script)
  {
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
    return $this->format(Base62::$UNPACK, $p, $a, $c, $k, $e, $d);
  }

  private function format($string)
  {
    // Replace %n with arguments[n].
    // e.g. format("%1 %2%3 %2a %1%3", "she", "se", "lls");
    // ==> "she sells sea shells"
    // Only %1 - %9 supported.
    $this->args = func_get_args();
    return preg_replace_callback('/%([1-' . count($this->args) . '])/', array(&$this, '_formatter'), $string);
  }

  public function _formatter($matches)
  {
    return $this->args[$matches[1]];
  }

  private function escape($script)
  {
    // Single quotes wrap the final string so escape them.
    // Also, escape new lines (required by conditional comments).
    return preg_replace(array('/([\\\\\\\'])/', '/[\\r\\n]+/'), array('\\\\$1', '\\n'), $script);
  }

  private function getDecoder()
  {
    // returns a pattern used for fast decoding of the packed script
    $trim = new RegGrp(array(
      '(\\d)(\\|\\d)+\\|(\\d)' => '$1-$3',
      '([a-z])(\\|[a-z])+\\|([a-z])' => '$1-$3',
      '([A-Z])(\\|[A-Z])+\\|([A-Z])' => '$1-$3',
      '\\|' => ''
    ));
    $pattern = $trim->exec(implode('|', array_slice($this->words->map(array(&$this, '_word_replacement')), 0, 62)));

    if ($pattern == '') return '^$';

    $pattern = '[' . $pattern . ']';

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

  private function getEncoder()
  {
    if ($this->words->size() > 36) return self::$ENCODE62;
    if ($this->words->size() > 10) return self::$ENCODE36;
    return self::$ENCODE10;
  }

  private function getKeyWords()
  {
    return preg_replace('/\\|+$/', '', implode('|', $this->words->map('strval')));
  }

  public function put($key, $item = NULL)
  {
    if (!($item instanceof Base62Item)) {
      $item = new Base62Item($key, $item);
    }
    parent::put($key, $item);
  }

  public function _word_replacement($word)
  {
    if (is_array($word)) $word = $word[0];
    if ((string)$word == '') return $word;
    if (is_string($word)) $word = $this->words->get($word);
    return $word->replacement;
  }
}

Base62::$UNPACK = preg_replace('/[\\r\\n]/', '', Base62::$UNPACK);