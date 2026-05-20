<?php
/**
 * HeaSec天积安全团队 - 未授权访问靶场 - 配置初始化
 * 版本: v1.0.0
 * 创建日期: 2026-03-05
 * 团队: 天积安全 (HeavenlySecret)
 */

/**
 * 生成随机字符串
 * @param int $length 字符串长度
 * @return string 随机字符串
 */
function generateNoauthRandomString($length = 10) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $str = '';
    for ($i = 0; $i < $length; $i++) {
        $str .= $chars[mt_rand(0, strlen($chars) - 1)];
    }
    return $str;
}

/**
 * 获取关卡随机路径选项
 * @param int $level 关卡编号
 * @return array 路径选项数组
 */
function getNoauthPathOptions($level) {
    switch ($level) {
        case 1:
            // 第一关：管理页面文件名
            return ['admin.php', 'manager.php', 'system.php', 'console.php', 'panel.php'];
        case 2:
            // 第二关：后台目录名
            return ['administrator', 'manage', 'backend', 'sysadmin', 'webadmin'];
        case 3:
            // 第三关：API接口文件名
            return ['getConfig.php', 'fetchData.php', 'loadSettings.php', 'queryInfo.php', 'readConfig.php'];
        default:
            return [];
    }
}

/**
 * 生成robots.txt文件（第一关专用）
 * 在靶场首次访问或重置后重新生成，只包含当前生效的管理页面路径
 * @param string $randomPath 当前生效的管理页面路径
 * @return bool 是否生成成功
 */
function generateNoauthRobotsTxt($randomPath) {
    $robotsContent = "User-agent: *\n";
    $robotsContent .= "Disallow: /{$randomPath}\n";

    // robots.txt应放置在靶场根目录
    $robotsFile = dirname(__DIR__) . '/robots.txt';

    // 如果文件已存在，先删除（确保重置后能重新生成）
    if (file_exists($robotsFile)) {
        unlink($robotsFile);
    }

    return file_put_contents($robotsFile, $robotsContent) !== false;
}

/**
 * 删除robots.txt文件（重置时调用）
 * @return bool 是否删除成功
 */
function deleteNoauthRobotsTxt() {
    $robotsFile = dirname(__DIR__) . '/robots.txt';

    if (file_exists($robotsFile)) {
        return unlink($robotsFile);
    }
    return true; // 文件不存在也算成功
}

/**
 * 生成第三关API文件
 * 在靶场首次访问或重置后重新生成，只创建当前生效的API文件
 * @param string $randomPath 当前生效的API文件名
 * @return bool 是否生成成功
 */
function generateNoauthLevel3Api($randomPath) {
    $apiDir = dirname(__DIR__) . '/noauth_level3/api';

    // 确保API目录存在
    if (!is_dir($apiDir)) {
        mkdir($apiDir, 0755, true);
    }

    // 先删除所有可能的API文件（确保重置后能重新生成）
    $allApiFiles = ['getConfig.php', 'fetchData.php', 'loadSettings.php', 'queryInfo.php', 'readConfig.php'];
    foreach ($allApiFiles as $file) {
        $filePath = $apiDir . '/' . $file;
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    // 生成当前生效的API文件内容
    $apiContent = <<<'PHP'
<?php
/**
 * HeaSec天积安全团队 - 未授权访问靶场 - 第三关配置接口
 *
 * 此接口返回路由器配置数据
 */

header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec API v1.0.0');

define('HEASEC_RANGE_ACCESS', true);
$commonBasePath = '../../../../../common/';
require_once $commonBasePath . 'includes/session_manager.php';
require_once $commonBasePath . 'includes/HeaSec_Database.php';
require_once __DIR__ . '/../../includes/config-init.php';
require_once __DIR__ . '/../../includes/access-control.php';

HeaSec_InitRangeSession('noauth');

$currentFileName = basename(__FILE__);
$currentLevel = 3;

$pdo = HeaSec_Database::getConnection('heasec_logic');
$config = checkNoauthAccess($currentLevel, $currentFileName, $pdo);

if (!$config) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => '接口不存在']);
    exit;
}

// 生成路由器数据
$routerData = generateRouterData();

$response = [
    'success' => true,
    'data' => [
        'device_name' => $routerData['device_name'],
        'firmware_version' => $routerData['firmware_version'],
        'mac_address' => $routerData['mac_address'],
        'uptime' => $routerData['uptime'],
        'online_devices' => $routerData['online_devices'],
        'wan_status' => $routerData['wan_status'],
        'lan_status' => $routerData['lan_status'],
        'passcode' => $config['passcode']
    ]
];

echo json_encode($response, JSON_UNESCAPED_UNICODE);
PHP;

    // 写入当前生效的API文件
    $apiFile = $apiDir . '/' . $randomPath;
    return file_put_contents($apiFile, $apiContent) !== false;
}

/**
 * 删除第三关所有API文件（重置时调用）
 * @return bool 是否删除成功
 */
function deleteNoauthLevel3Api() {
    $apiDir = dirname(__DIR__) . '/noauth_level3/api';
    $allApiFiles = ['getConfig.php', 'fetchData.php', 'loadSettings.php', 'queryInfo.php', 'readConfig.php'];

    $success = true;
    foreach ($allApiFiles as $file) {
        $filePath = $apiDir . '/' . $file;
        if (file_exists($filePath)) {
            if (!unlink($filePath)) {
                $success = false;
            }
        }
    }
    return $success;
}

/**
 * 初始化关卡配置数据
 * @param int $level 关卡编号
 * @param PDO $pdo 数据库连接
 * @return array 配置数据
 */
function initNoauthLevelConfig($level, $pdo) {
    // 检查是否已初始化
    $stmt = $pdo->prepare("SELECT * FROM heasec_noauth_config WHERE level = ?");
    $stmt->execute([$level]);
    $config = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($config) {
        return $config; // 已初始化，返回现有配置
    }

    // 生成管理员密码（10位随机字符串）
    $adminPassword = generateNoauthRandomString(10);

    // 生成交通密码（20位随机字符串）
    $passcode = generateNoauthRandomString(20);

    // 随机选择路径
    $pathOptions = getNoauthPathOptions($level);
    $randomPath = $pathOptions[array_rand($pathOptions)];

    // 插入配置数据
    $stmt = $pdo->prepare("INSERT INTO heasec_noauth_config
        (level, admin_password, passcode, random_path)
        VALUES (?, ?, ?, ?)");
    $stmt->execute([$level, $adminPassword, $passcode, $randomPath]);

    // 第一关初始化时生成robots.txt文件
    if ($level === 1) {
        generateNoauthRobotsTxt($randomPath);
    }

    // 第三关初始化时生成API文件
    if ($level === 3) {
        generateNoauthLevel3Api($randomPath);
    }

    // 返回新创建的配置
    return [
        'level' => $level,
        'admin_password' => $adminPassword,
        'passcode' => $passcode,
        'random_path' => $randomPath
    ];
}

/**
 * 获取关卡配置（不自动初始化）
 * @param int $level 关卡编号
 * @param PDO $pdo 数据库连接
 * @return array|null 配置数据，未初始化返回null
 */
function getNoauthLevelConfig($level, $pdo) {
    $stmt = $pdo->prepare("SELECT * FROM heasec_noauth_config WHERE level = ?");
    $stmt->execute([$level]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

/**
 * 生成路由器模拟数据
 * @return array 路由器数据
 */
function generateRouterData() {
    $uptime_days = mt_rand(1, 30);
    $uptime_hours = mt_rand(0, 23);
    $uptime_minutes = mt_rand(0, 59);
    $online_devices = mt_rand(3, 8);

    return [
        'device_name' => 'HeaSec-TJRouter-X1000',
        'firmware_version' => 'v2.3.1',
        'mac_address' => '00:1A:2B:3C:4D:5E',
        'uptime' => "{$uptime_days}天 {$uptime_hours}小时 {$uptime_minutes}分钟",
        'online_devices' => $online_devices,
        'wan_status' => '已连接',
        'lan_status' => '已连接'
    ];
}
