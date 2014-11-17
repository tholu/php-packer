<?php
/**
 * @package Packer v3.1 (beta?), PHP port
 * @author Dean Edwards, fixed by Nao (Wedge.org), fixed by tholu
 * @license http://www.opensource.org/licenses/mit-license.php
 * @from http://code.google.com/p/base2/source/browse/trunk/src/apps/packer#packer%2Fphp
 */

class Map implements Iterator
{
  protected $values = array();

  public function __construct($values = NULL)
  {
    if (is_array($values)) $this->merge($values);
  }

  public function clear()
  {
    $this->values = array();
  }

  public function copy()
  {
    return clone $this;
  }

  public function get($key)
  {
    if(isset($this->values[(string)$key]))
      return $this->values[(string)$key];
    else
      return;
  }

  public function getKeys()
  {
    return array_keys($this->values);
  }

  public function getValues()
  {
    return array_values($this->values);
  }

  public function has($key)
  {
    return array_key_exists((string)$key, $this->values);
  }

  public function merge($values)
  {
    foreach ($values as $key => $value) {
      $this->put($key, $value);
    }
    return $this;
  }

  public function put($key = '', $value = NULL)
  {
    $this->values[(string)$key] = $value;
  }

  public function remove($key)
  {
    unset($this->values[(string)$key]);
  }

  public function size()
  {
    return count($this->values);
  }

  public function union($values)
  {
    return $this->copy()->merge($values);
  }

// Iterator

  public function rewind()
  {
    reset($this->values);
  }

  public function current()
  {
    return current($this->values);
  }

  public function key()
  {
    return key($this->values);
  }

  public function next()
  {
    return next($this->values);
  }

  public function previous()
  {
    return previous($this->values);
  }

  public function valid()
  {
    return !is_null(key($this->values));
  }

// Enumerable

  public function every($test)
  {
    foreach ($this->values as $key => $value) {
      if (!$test($value, $key)) return false;
    }
    return true;
  }

  public function filter($test)
  {
    return new self(array_filter($this->values, $test));
  }

  public function invoke($method)
  {
    $result = array();
    foreach ($this->values as $value) {
      array_push($result, call_user_func(array(&$value, $method)));
    }
    return $result;
  }

  public function map($callback)
  {
    return array_map($callback, $this->getValues());
  }

  public function pluck($key)
  {
    $result = array();
    foreach ($this->values as $value) {
      array_push($result, $value->{$key});
    }
    return $result;
  }

  public function reduce($callback, $initial)
  {
    return array_reduce($this->values, $callback, $initial);
  }

  public function some($test)
  {
    foreach ($this->values as $key => $value) {
      if ($test($value, $key)) return true;
    }
    return false;
  }
}