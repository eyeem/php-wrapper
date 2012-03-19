<?php

class Eyeem_Collection implements Iterator
{

  protected $_index = 0;

  protected $_items = null;

  protected $_objects = array();

  /* Iterator */

  public function rewind()
  {
    $this->_index = 0;
    $this->_items = null;
  }

  public function key()
  {
    return $this->_index;
  }

  public function current()
  {
    return $this->get($this->_index);
  }

  public function next()
  {
    $this->_index ++;
  }

  public function valid()
  {
    if (!isset($this->_items)) {
      $this->_items = $this->getItems();
    }
    if (isset($this->queryParameters['limit']) && $this->_index >= $this->queryParameters['limit']) {
      return false;
    }
    return isset($this->_items[$this->_index]);
  }

  public function get($index)
  {
    if (!isset($this->_items)) {
      $this->_items = $this->getItems();
    }
    $item = $this->_items[$index];
    $id = $item['id'];
    if (empty($this->_objects[$id])) {
      $this->_objects[$id] = $this->getRessourceObject($item);
    }
    return $this->_objects[$id];
  }

}
