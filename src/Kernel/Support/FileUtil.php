<?php
/**
 * service-hall.
 * User: ligang
 * Date: 2017/8/5 上午9:52
 */
namespace EasyUtils\Kernel\Support;

class FileUtil
{
    // 初始化的目录
    private static $_firstDir = null;

    /**
     * 删除目录
     * @param $dir
     * @param array $options 有以下参数
     * - traverseSymlinks 如果为true，那么删除软连接内部的所有文件,false则只删除软连接
     * - except 删除时排除某些目录或文件
     * - keepDir 是否保存目录，只删除目录下的文件而不删除目录
     * @param $refresh bool 刷新first目录
     * @throws \Exception
     */
    public static function removeDirectory($dir, $options = [], $refresh = false)
    {
        if (!is_dir($dir)) {
            return;
        }

        if (!self::$_firstDir || $refresh) {
            self::$_firstDir = $dir;
        }

        $except = isset($options['except']) ? $options['except'] : [];

        if (isset($options['traverseSymlinks']) && $options['traverseSymlinks'] || !is_link($dir)) {
            if (!($handle = opendir($dir))) {
                return;
            }

            while (($file = readdir($handle)) !== false) {
                if ($file === '.' || $file === '..' || in_array($file, $except)) {
                    continue;
                }
                $path = $dir . DIRECTORY_SEPARATOR . $file;
                if (is_dir($path)) {
                    static::removeDirectory($path, $options);
                } else {
                    try {
                        unlink($path);
                    } catch (\Exception $e) {
                        if (DIRECTORY_SEPARATOR === '\\') {
                            // last resort measure for Windows
                            $lines = [];
                            exec("DEL /F/Q \"$path\"", $lines, $deleteError);
                        } else {
                            throw $e;
                        }
                    }
                }
            }
            closedir($handle);
        }
        if (is_link($dir)) {
            @unlink($dir);
        } else {
            // 如果存在忽略文件或者要保存的文件  并且  当前目录是根目录 ，那么不删除根目录
            if (($except || (isset($options['keepDir']) && $options['keepDir'])) && self::$_firstDir == $dir) {

            } else {
                rmdir($dir);
            }
        }
    }

    /**
     * Creates a new directory.
     *
     * This method is similar to the PHP `mkdir()` function except that
     * it uses `chmod()` to set the permission of the created directory
     * in order to avoid the impact of the `umask` setting.
     *
     * @param string $path path of the directory to be created.
     * @param int $mode the permission to be set for the created directory.
     * @param bool $recursive whether to create parent directories if they do not exist.
     * @return bool whether the directory is created successfully
     * @throws Exception if the directory could not be created (i.e. php error due to parallel changes)
     */
    public static function createDirectory($path, $mode = 0775, $recursive = true)
    {
        if (is_dir($path)) {
            return true;
        }
        $parentDir = dirname($path);
        // recurse if parent dir does not exist and we are not at the root of the file system.
        if ($recursive && !is_dir($parentDir) && $parentDir !== $path) {
            static::createDirectory($parentDir, $mode, true);
        }
        try {
            if (!mkdir($path, $mode)) {
                return false;
            }
        } catch (\Exception $e) {
            if (!is_dir($path)) {
                throw new \Exception("Failed to create directory \"$path\": " . $e->getMessage(), $e->getCode(), $e);
            }
        }
        try {
            return chmod($path, $mode);
        } catch (\Exception $e) {
            throw new \Exception("Failed to change permissions for directory \"$path\": " . $e->getMessage(), $e->getCode(), $e);
        }
    }

}
