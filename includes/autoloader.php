<?php
spl_autoload_register('autoLoader');

function autoLoader($classname)
{
    $path = __dir__."/src/";

    include $path.$classname.'.php';
}
