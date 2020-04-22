<?php


class Comment
{
    public $id, $parent_id, $is_freeze, $level, $childrens;

    public function __construct()
    {
        $this->is_freeze = false;
        $this->childrens = [];
    }
}