<?php

namespace EastWood\Log;

/**
 *
 * @method static info(string $tag, string $message)
 * @method static warning(string $tag, string $message)
 * @method static error(string $tag, string $message)
 * @method static debug(string $tag, string $message)
 * @method static verbose(string $tag, string $message)
 * @method static set(mixed $name, mixed $value = null)
 * @method static reset()
 *
 */
class Logger
{
    public static $settings = [];

    /**
     * load settings
     * @throws \ErrorException
     * @return array
     */
    public static function load()
    {
        if (empty(static::$settings))
            throw new \ErrorException('EastWood Log loading configuration error');

        if (!isset(static::$settings['format']))
            static::$settings['format'] = '{date_rfc}||{timestamp}||{host}||{uri}||{verb}||{app}||{level}||{tag}||{message}';
        return static::$settings;
    }

    /**
     * Logger format varable
     * @return array
     */
    public static function getVariable()
    {
        $variable = [
            'date' => [
                'pattern' => '#{(<date>\((.+)\))}#',
                'callback' => function ($format = 'r') {
                    return date($format);
                }
            ],
            'date_rfc2822' => date('r'),
            'timestamp' => microtime(true),
            'unixtime' => time(),
            'pid' => getmypid(),
            'gid' => getmygid(),
            'current_user' => get_current_user(),
            'message' => '{message}',
            'tag' => '{tag}',
            'level' => '{level}',
            'application' => '{application}',
            'request_host' => isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '-',
            'request_uri' => isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '-',
            'request_method' => isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '-',
            'request_body' => file_get_contents('php://input'),
            'request_addr' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '-',
            'request_port' => isset($_SERVER['REMOTE_PORT']) ? $_SERVER['REMOTE_PORT'] : '-',
            'request_user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '-',
            'server_addr' => isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '-',
            'server_port' => isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : '-',
            'server_protocol' => isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : '-',
            'scheme' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') ? 'https' : 'http'
        ];

        /**
         * variable alias
         */
        $variable['date_rfc'] = &$variable['date_rfc2822'];
        $variable['app'] = &$variable['application'];
        $variable['host'] = &$variable['request_host'];
        $variable['uri'] = &$variable['request_uri'];
        $variable['verb'] = &$variable['request_method'];
        $variable['body'] = &$variable['request_body'];
        $variable['user_agent'] = &$variable['request_user_agent'];

        return $variable;
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
            if (!@mkdir($path, 0755, true)) {
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
        $output['message'] = static::$settings['format'];
        $output['path'] = implode(DIRECTORY_SEPARATOR, [$path, $filename]);
        $varable = static::getVariable();
        foreach ($varable as $name => $value) {
            if (is_array($value)) {
                if (preg_match($value['pattern'], $output['message'], $match)) {
                    list($nil, $name) = $match;
                    unset($nil, $match[0], $match[1]);
                    $value = call_user_func_array($value['callback'], $match);
                    $output['message'] = str_replace('{' . $name . '}', $value, $output['message']);
                }
            } else {
                switch ($name) {
                    case 'app':
                    case 'application':
                        $value = $settings['application'];
                        break;
                    case 'level':
                        $value = $level;
                        break;
                    case 'tag':
                        $value = $tag;
                        break;
                    case 'message':
                        $value = $message;
                        break;
                }
                $output['message'] = str_replace('{' . $name . '}', $value, $output['message']);
            }
        }

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
                list(static::$settings) = $arguments;
                return true;
                break;
            case 'reset':
                static::$settings = [];
                return true;
                break;
            default:
                throw new \ErrorException('Call to undefined method ' . get_called_class() . '::' . $method . '()');
        }
    }
}