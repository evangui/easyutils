<?php
/*
 * 通用的单例trait
 *
 * SingletonTrait.php
 * 2019-01-29 16:09  guiyj<guiyj007@gmail.com>
 */
namespace EasyUtils\Kernel\traits;

trait SingletonTrait
{
    protected static $instanceList;
    protected static $self;

    protected function __construct() {}

    protected static function instance($key, $instance=null)
    {
        if (empty($instance)) {
            return isset(self::$instanceList[$key]) ? self::$instanceList[$key] : null;
        }
        self::$instanceList[$key] = $instance;
    }

    public static function getInstance()
    {
        if (!self::$self) {
            $called_class = get_called_class();
            self::$self = new $called_class();
        }
        return self::$self;
    }
}