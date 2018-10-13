<?php

/**
 * 分页
 */
class Page {
    public $current = 1;
    public $size;
    public $total;
    public $uri;
    public $url;//含参数
    public $query;

    public $items;
    public $next;
    public $prev;

    public $offset = 0;
    public $count = 1;

    public static $defaultSize = 20;
    public static $maxPage = 1000;
    public static $maxSize = 100;
    public static $maxEdge = 2;

    public function __construct() {
        $this->size = self::$defaultSize;
        $this->items = array();
    }

    public function size($size = null){
        if ($size > 0) {
            $this->size = $size;
            $this->_offset();
        }

        return $this;
    }

    public function current($page = null){
        if ($page > 0) {
            $this->current = $page;
            $this->_offset();
        }
        return $this;
    }

    public function total($total) {
        $this->total = (int) $total;
        return $this;
    }

   public function build(){
        $this->count = $this->total ? ceil($this->total/$this->size) : 1;
        $this->count = min($this->count, self::$maxPage);
        $url = $this->uri;
        $query = $this->query;
        unset($query['p']);

        if (count($query)) {
            $url .= '?' . http_build_query($query) . '&';
        } else {
            $url .= '?';
        }
        $this->url = $url;

        for ($i = 1; $i <= $this->count; $i++) {
            if (abs($i - $this->current) > self::$maxEdge){
                if ($i - 1 > self::$maxEdge && $this->count - $i > self::$maxEdge){
                    if (($i - $this->current) < 0) {
                        if (! isset($this->items['leftskip'])) {
                            $this->items['leftskip'] = array(
                                'text' => '...',
                            );
                        }
                    } else {
                        if (! isset($this->items['rightskip'])) {
                            $this->items['rightskip'] = array(
                                'text' => '...',
                            );
                        }
                    }
                    continue;
                }
            }

            $query['p'] = $i;
            $this->items[$i] = array(
                'text' => $i,
                'url' => $url . 'p=' . $i,
            );

            if ($this->current == $i) {
                $this->items[$i]['current'] = $i;
                if ($i > 1) {
                    $this->prev = array(
                        'text' => '&lt;',
                        'url' => $url . 'p=' . ($i - 1),
                    );
                }

                if ($i < $this->count) {
                    $this->next = array(
                        'text' => '&gt;',
                        'url' => $url . 'p=' . ($i + 1),
                    );
                }
            }
        }

    }

    private function _offset(){
        $this->offset = ($this->current - 1) * $this->size;
    }
}
