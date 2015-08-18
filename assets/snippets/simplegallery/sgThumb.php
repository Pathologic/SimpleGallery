<?php
$file = array_pop(explode('/',$input));
return str_replace($file,$options.'/'.$file,$input);
?>