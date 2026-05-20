<?php
/**
 * HeaSec天积安全团队 - SQL注入基础靶场通用辅助
 * 版本: v1.0.0
 * 团队: 天积安全 (HeavenlySecret)
 */

/**
 * 获取靶场配置。
 *
 * @return array
 */
function sqlibase_get_config()
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
function sqlibase_table($key)
{
    $config = sqlibase_get_config();
    return isset($config['tables'][$key]) ? $config['tables'][$key] : '';
}

/**
 * HTML 转义。
 *
 * @param mixed $value 原始值
 * @return string
 */
function sqlibase_h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * 获取请求数据。
 *
 * @return array
 */
function sqlibase_get_request_data()
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
function sqlibase_get_string(array $source, $key, $default = '')
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
function sqlibase_get_int(array $source, $key, $default = 0)
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
function sqlibase_get_query_param($key, $default = '')
{
    return isset($_GET[$key]) ? trim((string) $_GET[$key]) : $default;
}

/**
 * 校验用户名。
 *
 * @param string $username 用户名
 * @return bool
 */
function sqlibase_is_valid_username($username)
{
    return (bool) preg_match('/^[A-Za-z0-9_]{4,20}$/', $username);
}

/**
 * 校验密码。
 *
 * @param string $password 密码
 * @return bool
 */
function sqlibase_is_valid_password($password)
{
    $length = strlen($password);
    return $length >= 6 && $length <= 20;
}

/**
 * 校验姓名。
 *
 * @param string $name 姓名
 * @return bool
 */
function sqlibase_is_valid_name($name)
{
    $length = mb_strlen($name, 'UTF-8');
    return $length >= 1 && $length <= 50;
}

/**
 * 获取截图目录。
 *
 * @return string
 */
function sqlibase_get_screenshot_directory()
{
    $config = sqlibase_get_config();
    return rtrim($config['upload']['absolute_dir'], '/\\') . DIRECTORY_SEPARATOR;
}

/**
 * 确保截图目录存在。
 */
function sqlibase_ensure_screenshot_directory()
{
    $directory = sqlibase_get_screenshot_directory();
    if (!is_dir($directory)) {
        mkdir($directory, 0777, true);
    }
}

/**
 * 获取上传文件扩展名。
 *
 * @param string $mimeType MIME 类型
 * @return string
 */
function sqlibase_get_extension_by_mime($mimeType)
{
    switch ($mimeType) {
        case 'image/jpeg':
            return 'jpg';
        case 'image/png':
            return 'png';
        case 'image/gif':
            return 'gif';
        default:
            return '';
    }
}

/**
 * 生成唯一截图文件名。
 *
 * @param string $extension 扩展名
 * @return string
 */
function sqlibase_generate_screenshot_filename($extension)
{
    return time() . '_' . random_int(1000, 9999) . '.' . strtolower($extension);
}

/**
 * 校验上传文件。
 *
 * @param array $file $_FILES 中的文件项
 * @return string|null 错误信息，null 表示通过
 */
function sqlibase_validate_upload_file($file)
{
    $config = sqlibase_get_config();

    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return '请选择要上传的文件';
    }

    if ($file['size'] > $config['upload']['max_size']) {
        return '文件大小不能超过2MB';
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $config['upload']['allowed_mime'], true)) {
        return '仅支持 JPG、PNG、GIF 格式的图片';
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $config['upload']['extensions'], true)) {
        return '文件扩展名不允许';
    }

    return null;
}
