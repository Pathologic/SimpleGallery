<?php
return preg_replace('#(^.*[\\\/])#','${1}' . $options . '/', $input);
