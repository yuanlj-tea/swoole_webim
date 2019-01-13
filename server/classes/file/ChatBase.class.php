<?php

/**
 * Created by PhpStorm.
 * User: yuanlj
 * Date: 2017/12/7
 * Time: 17:40
 */
class ChatBase
{
    public function __construct(array $options = array())
    {
        if (!empty($options)) {
            foreach ($options as $k => $v) {
                if (isset($this->$k)) {
                    $this->$k = $v;
                }
            }
        }
    }
}