<?php

namespace EastWood\Log;

/**
 *
 * @method static info(string $tag, string $message)
 * @method static warning(string $tag, string $message)
 * @method static error(string $tag, string $message)
 * @method static debug(string $tag, string $message)
 * @method static verbose(string $tag, string $message)
 * @method static set(string $name, mixed $value)
 * @method static reset()
 *
 */
class Logger
{
    public static $settings = [];

    /**
     * load settings
     * @return array
     */
    public static function load()
    {
        $settings = [];
        if (empty(static::$settings['default'])) {
            static::$settings['default'] = [
                'path' => env('EW_LOG_PATH', '/data1/logs'),
                'rotate' => env('EW_LOG_ROTATE', 'daily'),
                'application' => env('EW_LOG_APPLICATION', 'web')
            ];
        }

        foreach (static::$settings['default'] as $name => $value)
            $settings[$name] = isset(static::$settings['user'][$name]) ? static::$settings['user'][$name] : $value;

        return $settings;
    }

    /**
     * logger factory
     * @param string $level
     * @param string $tag
     * @param string $message
     * @return bool|int
     * @throws \ErrorException
     */
    public final static function factory($level, $tag, $message)
    {
        $settings = static::load();
        $path = implode('/', [$settings['path'], $settings['application']]);

        if (!is_dir($path)) {
            if (!@mkdir($path, 0777, true)) {
                throw new \ErrorException('EastWood Log directory creation failed ' . $path);
            }
        }

        if (!is_writable($path))
            throw new \ErrorException('EastWood Log directory has no write permission ' . $path);

        switch ($settings['rotate']) {
            case 'daily':
                $filename = implode('-', ['app', date('Y'), date('m'), date('d') . '.log']);
                break;
            case 'month':
                $filename = implode('-', ['app', date('Y'), date('m') . '.log']);
                break;
            case 'year':
                $filename = implode('-', ['app', date('Y') . '.log']);
                break;
            default:
                $filename = 'app.log';
        }

        $output = [];
        $output['message'] = implode('||', [
            date('r'),
            microtime(true),
            isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '-',
            isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '-',
            isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '-',
            $settings['application'],
            $level,
            $tag,
            $message
        ]);

        $output['path'] = implode(DIRECTORY_SEPARATOR, [$path, $filename]);
        return file_put_contents($output['path'], $output['message'] . PHP_EOL, FILE_APPEND);
    }

    /**
     * call static
     * @param $method
     * @param $arguments
     * @return bool|mixed
     * @throws \ErrorException
     */
    public static function __callStatic($method, $arguments)
    {
        switch ($method) {
            case 'info':
            case 'warning':
            case 'error':
            case 'debug':
            case 'verbose':
                array_unshift($arguments, $method);
                return call_user_func_array(['self', 'factory'], $arguments);
                break;
            case 'set':
                static::load();
                if (is_array($arguments[0])) {
                    foreach ($arguments[0] as $attr => $newValue) {
                        if (array_key_exists($attr, static::$settings['default'])) static::$settings['user'][$attr] = $newValue;
                    }
                } else {
                    if (isset(static::$settings['default'][$arguments[0]]))
                        static::$settings['user'][$arguments[0]] = isset($arguments[1]) ? $arguments[1] : null;
                }
                return true;
                break;
            case 'reset':
                static::$settings['user'] = [];
                return true;
                break;
            default:
                throw new \ErrorException('Call to undefined method ' . get_called_class() . '::' . $method . '()');
        }
    }
}