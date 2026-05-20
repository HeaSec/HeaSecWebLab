<?php
/**
 * HeaSec天积安全团队 - 靶场会话管理组件
 * Range Session Manager
 * 版本: v2.0.0 (简化版)
 * 创建日期: 2025-11-17
 * 更新日期: 2026-01-25
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 功能：提供基于路径的会话隔离功能，确保每个靶场有独立的会话空间
 */

/**
 * HeaSec会话管理类
 * 提供基于路径的会话隔离和管理功能
 */
class HeaSec_SessionManager
{
    /**
     * 当前会话类型
     */
    private static $currentSessionType = null;

    /**
     * 初始化靶场会话
     *
     * @param string $rangePath 靶场路径，如 '/range/base/http/httpal/'
     * @param string $rangeCode 靶场代码，如 'httpal'
     * @return bool 会话初始化是否成功
     */
    public static function initRangeSession($rangePath, $rangeCode)
    {
        // 生成唯一的会话名称
        $sessionName = 'HEASEC_RANGE_' . strtoupper($rangeCode) . '_SESSION';

        // 设置会话配置
        $sessionConfig = [
            'name' => $sessionName,
            'path' => $rangePath,
            'lifetime' => 3600, // 1小时
            'secure' => false,
            'httponly' => true,
            'samesite' => 'Lax'
        ];

        return self::initSession('range', $sessionConfig);
    }

    /**
     * 通用会话初始化方法
     *
     * @param string $sessionType 会话类型
     * @param array $config 会话配置
     * @return bool 会话初始化是否成功
     */
    private static function initSession($sessionType, $config)
    {
        $targetSessionName = $config['name'];

        // 设置会话安全配置
        self::setSecureSessionConfig($config);

        // 设置会话Cookie参数
        self::setSessionCookieParams($config);

        $sessionStatus = session_status();

        if ($sessionStatus === PHP_SESSION_ACTIVE) {
            if (session_name() === $targetSessionName) {
                self::$currentSessionType = $sessionType;
                self::ensureBaseSessionData();
                return true;
            }

            session_write_close();
        }

        // 设置会话名称
        session_name($targetSessionName);

        // 启动会话
        if (!session_start()) {
            error_log('[HeaSec] Session start failed for type: ' . $sessionType);
            return false;
        }

        // 记录会话类型
        self::$currentSessionType = $sessionType;

        // 初始化会话基础数据
        self::ensureBaseSessionData();

        return true;
    }

    /**
     * 初始化会话基础数据
     */
    private static function ensureBaseSessionData()
    {
        if (!isset($_SESSION['heasec_session_id'])) {
            if (function_exists('random_bytes')) {
                $_SESSION['heasec_session_id'] = bin2hex(random_bytes(16));
            } else {
                $_SESSION['heasec_session_id'] = bin2hex(openssl_random_pseudo_bytes(16));
            }
        }

        if (!isset($_SESSION['heasec_created_at'])) {
            $_SESSION['heasec_created_at'] = time();
        }
    }


    /**
     * 设置安全的会话配置
     *
     * @param array $config 会话配置
     */
    private static function setSecureSessionConfig($config)
    {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_samesite', $config['samesite']);

        $isSecure = $config['secure'] || (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
        ini_set('session.cookie_secure', $isSecure ? 1 : 0);

        // 会话垃圾回收配置
        ini_set('session.gc_probability', 1);
        ini_set('session.gc_divisor', 100);
        ini_set('session.gc_maxlifetime', $config['lifetime']);
    }

    /**
     * 设置会话Cookie参数
     *
     * @param array $config 会话配置
     */
    private static function setSessionCookieParams($config)
    {
        $path = $config['path'];

        // 如果是靶场路径，使用更宽泛的路径以确保Cookie可以正常设置
        if (strpos($path, '/range/') === 0) {
            $path = '/range/';
        }

        $cookieParams = [
            'lifetime' => $config['lifetime'],
            'path' => $path,
            'domain' => '',
            'secure' => $config['secure'] || (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'),
            'httponly' => $config['httponly'],
            'samesite' => $config['samesite']
        ];

        session_set_cookie_params($cookieParams);
    }

    /**
     * 验证会话完整性（简化版，仅检查会话是否存在）
     *
     * @return bool 会话是否有效
     */
    public static function validateSession()
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    /**
     * 销毁当前会话
     */
    public static function destroySession()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $_SESSION = [];

        $cookieParams = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $cookieParams['path'],
            $cookieParams['domain'],
            $cookieParams['secure'],
            $cookieParams['httponly']
        );

        session_destroy();
        self::$currentSessionType = null;
    }

    /**
     * 设置Cookie（带路径隔离）
     *
     * @param string $name Cookie名称
     * @param string $value Cookie值
     * @param int $lifetime 生存时间（秒）
     * @param string $path Cookie路径（可选，默认使用当前会话路径）
     * @return bool 设置是否成功
     */
    public static function setSecureCookie($name, $value, $lifetime = 3600, $path = null)
    {
        if ($path === null && self::$currentSessionType === 'range') {
            $path = dirname($_SERVER['SCRIPT_NAME']) . '/';

            if (strpos($path, '/range/') === 0) {
                $path = '/range/';
            }
        } elseif ($path === null) {
            $path = '/';
        }

        $options = [
            'expires' => time() + $lifetime,
            'path' => $path,
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'httponly' => true,
            'samesite' => 'Strict'
        ];

        return setcookie($name, $value, $options);
    }

    /**
     * 获取当前会话类型
     *
     * @return string|null 会话类型
     */
    public static function getCurrentSessionType()
    {
        return self::$currentSessionType;
    }

    /**
     * 生成访客唯一标识
     *
     * @return string 访客ID
     */
    public static function getVisitorId()
    {
        if (!isset($_SESSION['heasec_visitor_id'])) {
            $_SESSION['heasec_visitor_id'] = uniqid('visitor_', true);
        }

        return $_SESSION['heasec_visitor_id'];
    }

    /**
     * 生成靶场秘密字符串
     *
     * @param int $length 字符串长度
     * @return string 秘密字符串
     */
    public static function generateSecret($length = 20)
    {
        $charset = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charsetLength = strlen($charset);
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $charset[mt_rand(0, $charsetLength - 1)];
        }

        return $randomString;
    }

    /**
     * 获取或生成会话中的秘密字符串
     *
     * @param int $length 秘密字符串长度
     * @return string 秘密字符串
     */
    public static function getSecret($length = 20)
    {
        if (isset($_SESSION['heasec_secret']) && !empty($_SESSION['heasec_secret'])) {
            return $_SESSION['heasec_secret'];
        }

        $secret = self::generateSecret($length);
        $_SESSION['heasec_secret'] = $secret;

        return $secret;
    }

    /**
     * 设置靶场会话路径的便捷方法
     *
     * 根据靶场代码从当前访问路径中自动截取到靶场根目录作为会话路径
     *
     * @param string $rangeCode 靶场代码
     * @return bool 初始化是否成功
     */
    public static function autoInitRangeSession($rangeCode)
    {
        $requestPath = $_SERVER['SCRIPT_NAME'];

        // 查找模式：/$rangeCode/
        $searchPattern = '/' . $rangeCode . '/';
        $pos = strpos($requestPath, $searchPattern);

        if ($pos === false) {
            $searchPattern = '/' . $rangeCode;
            $pos = strpos($requestPath, $searchPattern);

            if ($pos === false) {
                $currentPath = dirname($requestPath) . '/';
            } else {
                $currentPath = substr($requestPath, 0, $pos + strlen($searchPattern)) . '/';
            }
        } else {
            $currentPath = substr($requestPath, 0, $pos + strlen($searchPattern));
        }

        if (substr($currentPath, -1) !== '/') {
            $currentPath .= '/';
        }

        return self::initRangeSession($currentPath, $rangeCode);
    }
}

/**
 * 快速初始化靶场会话的便捷函数
 *
 * @param string $rangeCode 靶场代码
 * @return bool 初始化是否成功
 */
function HeaSec_InitRangeSession($rangeCode)
{
    return HeaSec_SessionManager::autoInitRangeSession($rangeCode);
}

/**
 * 快速验证会话的便捷函数
 *
 * @return bool 会话是否有效
 */
function HeaSec_ValidateSession()
{
    return HeaSec_SessionManager::validateSession();
}

/**
 * 销毁当前靶场会话的便捷函数
 *
 * @return void
 */
function HeaSec_DestroyCurrentRangeSession()
{
    HeaSec_SessionManager::destroySession();
}

/**
 * 快速获取访客ID的便捷函数
 *
 * @return string 访客ID
 */
function HeaSec_GetVisitorId()
{
    return HeaSec_SessionManager::getVisitorId();
}

/**
 * 快速获取秘密的便捷函数
 *
 * @param int $length 秘密长度
 * @return string 秘密字符串
 */
function HeaSec_GetSecret($length = 20)
{
    return HeaSec_SessionManager::getSecret($length);
}