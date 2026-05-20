<?php
/**
 * HeaSec天积安全团队 - XXE基础靶场公共函数
 * 版本: v1.0.0
 * 创建日期: 2026-04-09
 * 团队: 天积安全 (HeavenlySecret)
 */

/**
 * 生成secret.ini文件（如不存在则创建）
 * 文件包含大量注释和模拟配置项，密钥放在文件末尾
 * INI格式不含<>字符，XXE可直接使用file://协议读取
 * @param string $filePath secret.ini文件路径
 * @param bool $compact 是否生成精简版（单行，无换行符，用于OOB外带场景）
 * @return void
 */
function generateSecretFile($filePath, $compact = false) {
    if (file_exists($filePath)) {
        return;
    }

    $dir = dirname($filePath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $passcode = '';
    for ($i = 0; $i < 20; $i++) {
        $passcode .= $chars[mt_rand(0, strlen($chars) - 1)];
    }

    // 精简模式：单行INI文件，无换行符，确保OOB外带不会因换行符断裂URL
    if ($compact) {
        $content = 'secret_passcode=' . $passcode;
        file_put_contents($filePath, $content);
        return;
    }

    $content  = "; ============================================================\n";
    $content .= "; 天积数据平台 - 系统核心配置文件\n";
    $content .= "; HeaSec Security - HeavenlySecret\n";
    $content .= "; 版本: 1.0.0\n";
    $content .= "; ============================================================\n";
    $content .= ";\n";
    $content .= "; [重要提示]\n";
    $content .= "; 本文件包含系统运行所需的核心配置参数\n";
    $content .= "; 请勿手动修改此文件，除非您完全了解相关配置项的作用\n";
    $content .= "; 修改任何配置项前，请先备份此文件\n";
    $content .= ";\n";
    $content .= "; 配置格式说明：\n";
    $content .= "; - 所有以分号(;)开头的行均为注释行\n";
    $content .= "; - 配置项格式为: 参数名 = 参数值\n";
    $content .= "; - 字符串值推荐使用双引号包裹\n";
    $content .= "; - 修改配置后需要重启相关服务才能生效\n";
    $content .= "; - 空行会被自动忽略\n";
    $content .= ";\n";
    $content .= "; ============================================================\n";
    $content .= "; 系统基础配置节\n";
    $content .= "; ============================================================\n";
    $content .= "\n";
    $content .= "[system]\n";
    $content .= "; 系统名称，用于日志和界面显示\n";
    $content .= "system_name = \"天积数据平台\"\n";
    $content .= "; 当前系统版本号\n";
    $content .= "system_version = \"1.0.0\"\n";
    $content .= "; 开发团队名称\n";
    $content .= "developer = \"HeaSec\"\n";
    $content .= "; 团队口号\n";
    $content .= "slogan = \"日积寸功，乐享安全\"\n";
    $content .= "; 系统语言\n";
    $content .= "language = \"zh-CN\"\n";
    $content .= "; 系统时区\n";
    $content .= "timezone = \"Asia/Shanghai\"\n";
    $content .= "\n";
    $content .= "; ============================================================\n";
    $content .= "; 数据库连接配置节\n";
    $content .= "; ============================================================\n";
    $content .= "\n";
    $content .= "[database]\n";
    $content .= "; 数据库主机地址\n";
    $content .= "db_host = \"127.0.0.1\"\n";
    $content .= "; 数据库端口号\n";
    $content .= "db_port = \"3306\"\n";
    $content .= "; 数据库名称\n";
    $content .= "db_name = \"heasec_platform\"\n";
    $content .= "; 数据库连接用户名\n";
    $content .= "db_user = \"heasec_user\"\n";
    $content .= "; 数据库连接密码（已加密存储）\n";
    $content .= "db_pass = \"****************\"\n";
    $content .= "; 数据库字符集\n";
    $content .= "db_charset = \"utf8mb4\"\n";
    $content .= "; 数据库表名前缀\n";
    $content .= "db_prefix = \"heasec_\"\n";
    $content .= "; 连接池大小\n";
    $content .= "pool_size = \"10\"\n";
    $content .= "; 连接超时时间（秒）\n";
    $content .= "connect_timeout = \"30\"\n";
    $content .= "\n";
    $content .= "; ============================================================\n";
    $content .= "; 安全配置节\n";
    $content .= "; ============================================================\n";
    $content .= "\n";
    $content .= "[security]\n";
    $content .= "; 会话超时时间（秒）\n";
    $content .= "session_timeout = \"3600\"\n";
    $content .= "; 最大登录尝试次数\n";
    $content .= "max_login_attempts = \"5\"\n";
    $content .= "; 密码加密算法\n";
    $content .= "encryption_algorithm = \"AES-256-CBC\"\n";
    $content .= "; API接口认证密钥\n";
    $content .= "api_key = \"sk-heasec-2026-xxxxxxxxxxxxxxxx\"\n";
    $content .= "; CSRF令牌有效期（秒）\n";
    $content .= "csrf_token_lifetime = \"7200\"\n";
    $content .= "; XSS过滤级别\n";
    $content .= "xss_filter_level = \"strict\"\n";
    $content .= "; SQL注入防护模式\n";
    $content .= "sql_injection_guard = \"enabled\"\n";
    $content .= "\n";
    $content .= "; ============================================================\n";
    $content .= "; 应用功能配置节\n";
    $content .= "; ============================================================\n";
    $content .= "\n";
    $content .= "[application]\n";
    $content .= "; 是否启用调试模式\n";
    $content .= "debug_mode = \"false\"\n";
    $content .= "; 日志记录级别（debug/info/warning/error）\n";
    $content .= "log_level = \"warning\"\n";
    $content .= "; 日志文件存储路径\n";
    $content .= "log_path = \"/var/log/heasec/\"\n";
    $content .= "; 文件上传最大尺寸（MB）\n";
    $content .= "max_upload_size = \"10\"\n";
    $content .= "; 允许的文件上传类型\n";
    $content .= "allowed_types = \"xml,csv,json\"\n";
    $content .= "; 数据导入批次大小\n";
    $content .= "import_batch_size = \"100\"\n";
    $content .= "; 数据验证严格模式\n";
    $content .= "strict_validation = \"true\"\n";
    $content .= "\n";
    $content .= "; ============================================================\n";
    $content .= "; 缓存配置节\n";
    $content .= "; ============================================================\n";
    $content .= "\n";
    $content .= "[cache]\n";
    $content .= "; 缓存驱动类型（file/redis/memcached）\n";
    $content .= "cache_driver = \"file\"\n";
    $content .= "; 缓存默认有效期（秒）\n";
    $content .= "default_ttl = \"3600\"\n";
    $content .= "; 缓存文件存储路径\n";
    $content .= "cache_path = \"/tmp/heasec/cache/\"\n";
    $content .= "; 是否启用缓存前缀\n";
    $content .= "prefix_enabled = \"true\"\n";
    $content .= "\n";
    $content .= "; ============================================================\n";
    $content .= "; 通关验证密钥配置节\n";
    $content .= "; ============================================================\n";
    $content .= "\n";
    $content .= "[passcode]\n";
    $content .= "; 通关验证密钥（请勿泄露）\n";
    $content .= "secret_passcode = \"" . $passcode . "\"\n";

    file_put_contents($filePath, $content);
}

/**
 * 从secret.ini文件中提取通关密码
 * @param string $filePath secret.ini文件路径
 * @return string|null 通关密码，文件不存在或格式错误返回null
 */
function extractPasscode($filePath) {
    if (!file_exists($filePath)) {
        return null;
    }
    $config = parse_ini_file($filePath, true);
    if (isset($config['passcode']['secret_passcode'])) {
        return $config['passcode']['secret_passcode'];
    }
    // 精简模式（无section）直接读取顶级key
    if (isset($config['secret_passcode'])) {
        return $config['secret_passcode'];
    }
    return null;
}

/**
 * 获取指定关卡的秘密文件路径
 * @param int $level 关卡编号
 * @return string secret.ini文件绝对路径
 */
function getSecretFilePath($level) {
    $basePath = dirname(__DIR__);
    switch ($level) {
        case 2:
            return $basePath . '/config/level2/secret.ini';
        case 3:
            return $basePath . '/config/level3/secret.ini';
        default:
            return $basePath . '/config/secret.ini';
    }
}

/**
 * 获取指定关卡的数据文件路径
 * @param int $level 关卡编号
 * @return string 数据文件绝对路径
 */
function getDataFilePath($level) {
    $basePath = dirname(__DIR__);
    return $basePath . '/data/level' . $level . '.json';
}

/**
 * 确保导入数据文件存在
 * @param string $filePath 数据文件路径
 * @return void
 */
function ensureDataFile($filePath) {
    if (!file_exists($filePath)) {
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($filePath, json_encode([], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
}

/**
 * 追加导入数据
 * @param string $filePath 数据文件路径
 * @param array $product 商品数据
 * @return void
 */
function appendImportedData($filePath, $product) {
    $data = json_decode(file_get_contents($filePath), true);
    if (!is_array($data)) {
        $data = [];
    }
    $product['import_time'] = date('Y-m-d H:i:s');
    $data[] = $product;
    file_put_contents($filePath, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

/**
 * 获取导入数据
 * @param string $filePath 数据文件路径
 * @return array 商品数据数组
 */
function getImportedData($filePath) {
    if (!file_exists($filePath)) {
        return [];
    }
    $data = json_decode(file_get_contents($filePath), true);
    return is_array($data) ? $data : [];
}

/**
 * 清空导入数据
 * @param string $filePath 数据文件路径
 * @return void
 */
function clearImportedData($filePath) {
    file_put_contents($filePath, json_encode([], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

/**
 * 发送JSON响应
 * @param bool $success 是否成功
 * @param string $message 消息
 * @param array $data 额外数据
 */
function sendJsonResponse($success, $message, $data = []) {
    header('Content-Type: application/json; charset=utf-8');
    header('X-HeavenlySecret: HeaSec XXEBase Range v1.0.0');

    $response = [
        'success' => $success,
        'message' => $message
    ];

    if (!empty($data)) {
        $response = array_merge($response, $data);
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}
