<?php

require __DIR__ . '/autoload.php';

use Crada\Apidoc\Builder;
use Crada\Apidoc\Exception;

class Docer {
    public static $src;
    public static $classes;
    public static $title;

    public static function getClasses() {
        if (empty(self::$classes)) {
            // scan kohana controllers
            $path = realpath(Docer::$src . "/app/classes/Controller");
            $objects = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
            );
            foreach($objects as $name => $object){
                $className = str_replace(DIRECTORY_SEPARATOR, '_', substr($name, strpos($name, 'Controller'), -4));
                if (class_exists($className)) { // maybe typos
                    if (false === strpos($className, 'Controller_Test') // skip
                        && false === strpos($className, 'Controller_Base') // skip
                    ) {
                        self::$classes[] = $className;
                    }
                }
            }
        }

        return self::$classes;
    }

    public static function build(){
        try {
            Builder::$mainTpl = file_get_contents(__DIR__ . '/main.html');
            Builder::$paramTableTpl = self::$paramTableTpl;
            Builder::$sandboxFormTpl = self::$sandboxFormTpl;
            Builder::$sandboxFormInputTpl = self::$sandboxFormInputTpl;
            $builder = new Builder(self::getClasses(), self::$src . '/docs', self::$title, 'index.php', __DIR__ . '/index.html');
            $builder->generate();
            echo "done!\n";
        } catch (Exception $e) {
            echo $e->getMessage(),"\n";
        }
    }

    static $paramTableTpl = '
<table class="table table-hover">
    <thead>
        <tr>
            <th>字段</th>
            <th>类型</th>
            <th>是否必填</th>
            <th>说明</th>
        </tr>
    </thead>
    <tbody>
        {{ tbody }}
    </tbody>
</table>';

    static $sandboxFormTpl = '
<form enctype="application/x-www-form-urlencoded" role="form" action="{{ route }}" method="{{ method }}" name="form{{ elt_id }}" id="form{{ elt_id }}">
    <div class="col-md-6">
        参数
        <hr/>
        {{ params }}
    <button type="submit" class="btn btn-success send" rel="{{ elt_id }}">提交</button>
    </div>
    <div class="col-md-6">
        Body
        <hr/>
        <div class="body">
        <textarea name="__body" class="form-control"></textarea>
        </div>
    </div>
</form>
';

    static $sandboxFormInputTpl = '
<div class="form-group">
<div class="input-group">
    <span class="input-group-addon">{{ name }}</span>
    <input type="text" class="form-control input-sm" placeholder="{{ description }}" name="{{ name }}">
</div>
</div>
';
}