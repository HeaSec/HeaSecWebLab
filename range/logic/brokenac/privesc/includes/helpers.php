<?php
/**
 * HeaSec天积安全团队 - 越权访问综合实战通用辅助
 * 版本: v1.0.0
 * 团队: 天积安全 (HeavenlySecret)
 */

/**
 * 获取靶场配置。
 *
 * @return array
 */
function privesc_get_config()
{
    static $config = null;

    if ($config === null) {
        $config = require __DIR__ . '/../config/app.php';
    }

    return $config;
}

/**
 * 获取数据库表名。
 *
 * @param string $key 表标识
 * @return string
 */
function privesc_table($key)
{
    $config = privesc_get_config();
    return isset($config['tables'][$key]) ? $config['tables'][$key] : '';
}

/**
 * HTML 转义。
 *
 * @param mixed $value 原始值
 * @return string
 */
function privesc_h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * 获取请求数据。
 *
 * @return array
 */
function privesc_get_request_data()
{
    static $requestData = null;

    if ($requestData !== null) {
        return $requestData;
    }

    $contentType = isset($_SERVER['CONTENT_TYPE']) ? strtolower(trim($_SERVER['CONTENT_TYPE'])) : '';

    if (strpos($contentType, 'application/json') !== false) {
        $rawBody = file_get_contents('php://input');
        $decoded = json_decode($rawBody, true);
        $requestData = is_array($decoded) ? $decoded : [];
        return $requestData;
    }

    $requestData = !empty($_POST) ? $_POST : [];
    return $requestData;
}

/**
 * 获取字符串参数。
 *
 * @param array $source 数据源
 * @param string $key 参数名
 * @param string $default 默认值
 * @return string
 */
function privesc_get_string(array $source, $key, $default = '')
{
    if (!isset($source[$key])) {
        return $default;
    }

    return trim((string) $source[$key]);
}

/**
 * 获取整数参数。
 *
 * @param array $source 数据源
 * @param string $key 参数名
 * @param int $default 默认值
 * @return int
 */
function privesc_get_int(array $source, $key, $default = 0)
{
    if (!isset($source[$key]) || $source[$key] === '') {
        return $default;
    }

    return (int) $source[$key];
}

/**
 * 获取查询字符串参数。
 *
 * @param string $key 参数名
 * @param string $default 默认值
 * @return string
 */
function privesc_get_query_param($key, $default = '')
{
    return isset($_GET[$key]) ? trim((string) $_GET[$key]) : $default;
}

/**
 * 校验用户名。
 *
 * @param string $username 用户名
 * @return bool
 */
function privesc_is_valid_username($username)
{
    return (bool) preg_match('/^[A-Za-z0-9_]{3,20}$/', $username);
}

/**
 * 校验密码。
 *
 * @param string $password 密码
 * @return bool
 */
function privesc_is_valid_password($password)
{
    $length = strlen($password);
    return $length >= 3 && $length <= 50;
}

/**
 * 校验手机号。
 *
 * @param string $phone 手机号
 * @return bool
 */
function privesc_is_valid_phone($phone)
{
    if ($phone === '') {
        return true;
    }

    return (bool) preg_match('/^[0-9\-+\s]{6,20}$/', $phone);
}

/**
 * 校验角色。
 *
 * @param int $role 角色值
 * @return bool
 */
function privesc_is_valid_role($role)
{
    return in_array((int) $role, [0, 2], true);
}

/**
 * 获取角色名称。
 *
 * @param int $role 角色值
 * @return string
 */
function privesc_get_role_name($role)
{
    $config = privesc_get_config();
    return isset($config['roles'][(int) $role]) ? $config['roles'][(int) $role] : '未知角色';
}

/**
 * 校验地址标识。
 *
 * @param string $addressId 地址标识
 * @return bool
 */
function privesc_is_valid_address_id($addressId)
{
    return (bool) preg_match('/^ADDR_\d{4}$/', $addressId);
}

/**
 * 校验头像文件名。
 *
 * @param string $filename 文件名
 * @return bool
 */
function privesc_is_valid_avatar_filename($filename)
{
    return (bool) preg_match('/^\d{10}\.(png|jpg|jpeg)$/i', $filename);
}

/**
 * 获取头像目录。
 *
 * @return string
 */
function privesc_get_avatar_directory()
{
    $config = privesc_get_config();
    return rtrim($config['upload']['absolute_dir'], '/\\') . DIRECTORY_SEPARATOR;
}

/**
 * 确保头像目录存在。
 */
function privesc_ensure_avatar_directory()
{
    $directory = privesc_get_avatar_directory();
    if (!is_dir($directory)) {
        mkdir($directory, 0777, true);
    }
}

/**
 * 获取头像相对地址。
 *
 * @param string|null $filename 文件名
 * @return string
 */
function privesc_get_avatar_url($filename)
{
    if (empty($filename)) {
        return '';
    }

    $config = privesc_get_config();
    $absolutePath = privesc_get_avatar_directory() . basename($filename);

    if (!is_file($absolutePath)) {
        return '';
    }

    return $config['upload']['relative_dir'] . rawurlencode(basename($filename));
}

/**
 * 获取上传文件扩展名。
 *
 * @param string $mimeType MIME 类型
 * @return string
 */
function privesc_get_extension_by_mime($mimeType)
{
    switch ($mimeType) {
        case 'image/png':
            return 'png';
        case 'image/jpeg':
            return 'jpg';
        default:
            return '';
    }
}

/**
 * 生成随机字符串。
 *
 * @param string $characters 字符集
 * @param int $length 长度
 * @return string
 */
function privesc_random_from_charset($characters, $length)
{
    $maxIndex = strlen($characters) - 1;
    $value = '';

    for ($i = 0; $i < $length; $i++) {
        $value .= $characters[random_int(0, $maxIndex)];
    }

    return $value;
}

/**
 * 生成管理员随机密码。
 *
 * @param int $length 长度
 * @return string
 */
function privesc_generate_admin_password($length = 10)
{
    $lower = 'abcdefghijklmnopqrstuvwxyz';
    $upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $digits = '0123456789';
    $special = '!@#$%^&*';
    $all = $lower . $upper . $digits . $special;

    $password = [
        $lower[random_int(0, strlen($lower) - 1)],
        $upper[random_int(0, strlen($upper) - 1)],
        $digits[random_int(0, strlen($digits) - 1)],
        $special[random_int(0, strlen($special) - 1)],
    ];

    while (count($password) < $length) {
        $password[] = $all[random_int(0, strlen($all) - 1)];
    }

    shuffle($password);
    return implode('', $password);
}

/**
 * 设置类型 Cookie。
 *
 * @param string|null $value Cookie 值
 */
function privesc_set_type_cookie($value)
{
    $config = privesc_get_config();
    $cookieConfig = $config['cookie'];
    $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    $cookiePath = isset($cookieConfig['path']) ? trim((string) $cookieConfig['path']) : '';

    if ($cookiePath === '') {
        $scriptPath = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', (string) $_SERVER['SCRIPT_NAME']) : '';
        $scriptDir = $scriptPath !== '' ? str_replace('\\', '/', dirname($scriptPath)) : '';

        if (substr($scriptDir, -4) === '/api') {
            $scriptDir = substr($scriptDir, 0, -4);
        }

        $scriptDir = rtrim($scriptDir, '/');
        $cookiePath = $scriptDir === '' || $scriptDir === '.' ? '/' : $scriptDir . '/';
    }

    $options = [
        'expires' => $value === null ? time() - 3600 : time() + (int) $cookieConfig['lifetime'],
        'path' => $cookiePath,
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => false,
        'samesite' => $cookieConfig['samesite'],
    ];

    setcookie($cookieConfig['type_name'], $value === null ? '' : $value, $options);

    if ($value === null) {
        unset($_COOKIE[$cookieConfig['type_name']]);
    } else {
        $_COOKIE[$cookieConfig['type_name']] = $value;
    }
}

/**
 * 生成唯一头像文件名。
 *
 * @param string $extension 扩展名
 * @return string
 */
function privesc_generate_avatar_filename($extension)
{
    $directory = privesc_get_avatar_directory();
    $timestamp = time();
    $extension = strtolower($extension);

    do {
        $filename = $timestamp . '.' . $extension;
        $timestamp++;
    } while (file_exists($directory . $filename));

    return $filename;
}

/**
 * 规范化漏洞 URL。
 * 委托给 HeaSec_VulnManager 静态方法
 *
 * @param string $url 原始 URL
 * @return string
 */
function privesc_normalize_vuln_url($url)
{
    return HeaSec_VulnManager::normalizeVulnUrl($url);
}

/**
 * 对漏洞参数进行规范化排序。
 * 委托给 HeaSec_VulnManager 静态方法
 *
 * @param array $params 参数列表
 * @return array
 */
function privesc_normalize_vuln_params(array $params)
{
    return HeaSec_VulnManager::normalizeVulnParams($params);
}

/**
 * 构建漏洞唯一键。
 * 委托给 HeaSec_VulnManager 静态方法
 *
 * @param string $url URL
 * @param string $type 漏洞类型
 * @param array $params 参数列表
 * @return string
 */
function privesc_build_vuln_key($url, $type, array $params)
{
    return HeaSec_VulnManager::buildVulnKey($url, $type, $params);
}

/**
 * 获取当前会话的漏洞记录键（已废弃）。
 *
 * @return string 固定返回 'global'
 * @deprecated 漏洞记录已改为全局共享，不再区分会话
 */
function privesc_get_session_record_key()
{
    return 'global';
}
