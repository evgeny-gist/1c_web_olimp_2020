<?php

function dd($object, $comment = '')
{
    if ($comment) $comment = $comment . ' -> ';
    echo "<style>body{background: black;color: bisque}</style>\r";
    echo "<pre>$comment \r\r" . print_r($object, true) . "\r</pre>\r\r";
}
