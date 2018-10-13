<?php

/* psr-0 & kohana */
function api_doc_autoload($className){
    $className = ltrim($className, '\\');
    $fileName  = '';

    if ($lastNsPos = strrpos($className, '\\')) {
        $namespace = substr($className, 0, $lastNsPos);
        $className = substr($className, $lastNsPos + 1);
        $fileName  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
    }

    $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

    if (file_exists(__DIR__ . DIRECTORY_SEPARATOR .$fileName)) {
        require __DIR__ . DIRECTORY_SEPARATOR . $fileName;
    }
}

spl_autoload_register('api_doc_autoload');