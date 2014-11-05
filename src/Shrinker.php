<?php

/**
 * @package Packer v3.1 (beta?), PHP port
 * @author Dean Edwards, fixed by Nao (Wedge.org), fixed by tholu
 * @license http://www.opensource.org/licenses/mit-license.php
 * @from http://code.google.com/p/base2/source/browse/trunk/src/apps/packer#packer%2Fphp
 */
class Shrinker
{
  const PREFIX = "\x02";
  const SHRUNK = '\\x02\\d+\\b';

  private static $ESCAPE = '/([\\/()[\\]{}|*+-.,^$?\\\\])/';

  public static function rescape($string)
  {
// Make a string safe for creating a RegExp.
    return preg_replace(self::$ESCAPE, '\\\\$1', $string);
  }

// Identify blocks, particularly identify function blocks (which define scope)
  private $BLOCK = '/((catch|do|if|while|with|function)\\b[^~{};]*(\\(\\s*[^{};]*\\s*\\))\\s*)?(\\{[^{}]*\\})/';
  private $BRACKETS = '/\\{[^{}]*\\}|\\[[^\\[\\]]*\\]|\\([^\\(\\)]*\\)|~[^~]+~/';
  private $ENCODED_BLOCK = '/~#?(\\d+)~/';
  private $ENCODED_DATA = '/\\x01(\\d+)\\x01/';
  private $IDENTIFIER = '/[a-zA-Z_$][\\w\\$]*/';
  private $SCOPED = '/~#(\\d+)~/';
  private $VAR = '/\\bvar\\b/';
  private $VARS = '/\\bvar\\s+[\\w$]+[^;#]*|\\bfunction\\s+[\\w$]+/';
// The following line is from Packer 4. See my original bug report: http://dean.edwards.name/weblog/2007/04/packer3/#comment377355
  private $VAR_TIDY = '/\\b(const|let|var|function|catch\\s*\\()\\b|\\s+in\\b[^;]*/';
  private $VAR_EQUAL = '/\\s*=[^,;]*/';

  private $count = 0; // Number of variables
  private $blocks; // Store program blocks (anything between braces {})
  private $data;   // Store program data (strings and regexps)
  private $script;

  public function shrink($script = '')
  {
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

  private function decodeBlocks($script, $encoded)
  {
// Put the blocks back
    while (preg_match($encoded, $script)) {
      $script = preg_replace_callback($encoded, array(&$this, '_blockDecoder'), $script);
    }
    return $script;
  }

  private function encodeBlocks($script)
  {
// Encode blocks, as we encode we replace variable and argument names
    while (preg_match($this->BLOCK, $script)) {
      $script = preg_replace_callback($this->BLOCK, array(&$this, '_blockEncoder'), $script);
    }
    return $script;
  }

  private function decodeData($script)
  {
// Put strings and regular expressions back
    $script = preg_replace_callback($this->ENCODED_DATA, array(&$this, '_dataDecoder'), $script);
    unset($this->data);
    return $script;
  }

  private function encodeData($script)
  {
    $this->data = array(); // Encoded strings and regular expressions
// Encode strings and regular expressions
    return Packer::$data->exec($script, array(&$this, '_dataEncoder'));
  }

  /* Callback functions (public because of php's crappy scoping) */

  public function _blockDecoder($matches)
  {
    return $this->blocks[$matches[1]];
  }

  public function _blockEncoder($match)
  {
    $prefix = $match[1];
    $blockType = $match[2];
    $args = $match[3];
    $block = $match[4];
    if (!$prefix) $prefix = '';
    if ($blockType == 'function') {
// Decode the function block (THIS IS THE IMPORTANT BIT)
// We are retrieving all sub-blocks and will re-parse them in light
// of newly shrunk variables
      $block = $args . $this->decodeBlocks($block, $this->SCOPED);
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
        preg_match_all($this->IDENTIFIER, $args . ',' . $vars, $matches);
        $processed = array();
        foreach ($matches[0] as $id) {
          if (empty($processed[$id])) {
            $processed[$id] = true;
            $id = self::rescape($id);
// Encode variable names
            while (preg_match('/' . Shrinker::PREFIX . $count . '\\b/', $block)) $count++;
            $reg = '/([^\\w$.])' . $id . '([^\\w$:])/';
            while (preg_match($reg, $block)) {
              $block = preg_replace($reg, '$1' . Shrinker::PREFIX . $count . '$2', $block);
            }
            $block = preg_replace('/([^{,\\w$.])' . $id . ':/', '$1' . Shrinker::PREFIX . $count . ':', $block);
            $count++;
          }
        }
        $this->total = max(@$this->total, $count);
      }
      $replacement = $prefix . '~' . count($this->blocks) . '~';
      array_push($this->blocks, $block);
    } else {
      $replacement = '~#' . count($this->blocks) . '~';
      array_push($this->blocks, $prefix . $block);
    }
    return $replacement;
  }

  public function _dataDecoder($matches)
  {
    return $this->data[$matches[1]];
  }

  public function _dataEncoder($match, $operator = '', $regexp = '')
  {
    $replacement = "\x01" . count($this->data) . "\x01";
    if ($regexp) {
      $replacement = $operator . $replacement;
      $match = $regexp;
    }
    array_push($this->data, $match);
    return $replacement;
  }

  public function _varEncoder()
  {
// Find the next free short name
    do $shortId = Packer::encode52($this->count++);
    while (preg_match('/[^\\w$.]' . $shortId . '[^\\w$:]/', $this->script));
    return $shortId;
  }
}