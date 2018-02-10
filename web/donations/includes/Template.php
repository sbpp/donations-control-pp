<?php

class Template
{
    private static $engine = null;

    public static function init(array $params = [])
    {
        self::$engine = new Mustache_Engine($params);
    }

    private static function load($tpl)
    {
        return self::$engine->loadTemplate($tpl);
    }

    public static function render($tpl, array $data = [])
    {
        $tpl = self::load($tpl);
        print $tpl->render($data);
    }
}
