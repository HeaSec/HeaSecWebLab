<?php
/**
 * HeaSec天积安全团队 - 文件目录执行权限绕过靶场 - 关卡初始化接口
 * 版本: v1.0.0
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 此接口用于在跳转到下一关前初始化关卡状态并重置exec/images目录
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 文件目录执行权限绕过 Range v1.0.0');
header('Content-Type: application/json; charset=utf-8');

// 公共组件路径
$commonBasePath = '../../../../../common/';

define('HEASEC_RANGE_ACCESS', true);

// 引入会话管理
require_once $commonBasePath . 'includes/session_manager.php';
HeaSec_InitRangeSession('filedirectory');
HeaSec_ValidateSession();

// 获取目标关卡
$input = json_decode(file_get_contents('php://input'), true);
$targetLevel = isset($input['targetLevel']) ? (int)$input['targetLevel'] : 0;

// 验证目标关卡
if ($targetLevel < 1 || $targetLevel > 3) {
    echo json_encode([
        'success' => false,
        'message' => '无效的目标关卡'
    ]);
    exit;
}

// 检查前置关卡是否已通过（除了第一关）
if ($targetLevel > 1) {
    $prevLevelKey = 'filedirectory_level' . ($targetLevel - 1) . '_passed';
    if (!isset($_SESSION[$prevLevelKey]) || !$_SESSION[$prevLevelKey]) {
        echo json_encode([
            'success' => false,
            'message' => '请先通过前一关'
        ]);
        exit;
    }
}

// images目录路径
$imagesDir = dirname(__DIR__) . '/exec/images/';
$execDir = dirname(__DIR__) . '/exec/';

// 创建exec目录（如果不存在）
if (!file_exists($execDir)) {
    mkdir($execDir, 0755, true);
}

// 创建images目录（如果不存在）
if (!file_exists($imagesDir)) {
    mkdir($imagesDir, 0755, true);
}

// 重置images目录：删除用户上传的文件（保留.htaccess）
if (file_exists($imagesDir)) {
    $files = glob($imagesDir . '*');
    foreach ($files as $file) {
        if (is_file($file)) {
            $basename = basename($file);
            if ($basename !== '.htaccess') {
                @unlink($file);
            }
        }
    }
}

// 重置exec目录：删除用户上传的文件（保留secret.php和.htaccess）
if (file_exists($execDir)) {
    $files = glob($execDir . '*');
    foreach ($files as $file) {
        if (is_file($file)) {
            $basename = basename($file);
            // 只保留secret.php和.htaccess
            if ($basename !== 'secret.php' && $basename !== '.htaccess') {
                @unlink($file);
            }
        }
    }
}

// 恢复原始的.htaccess文件（禁止PHP执行）
$htaccessFile = $imagesDir . '.htaccess';
$htaccessContent = '# 禁止PHP执行 - phpStudy兼容配置
<FilesMatch "\.php$">
    php_flag engine off
</FilesMatch>

<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>';
file_put_contents($htaccessFile, $htaccessContent);

// 生成当前关卡的通关密码
// 使用generateSecret直接生成新密码，避免动态会话获取的问题
$secretKey = 'filedirectory_level' . $targetLevel . '_secret';
$_SESSION[$secretKey] = HeaSec_SessionManager::generateSecret(20);
$secret = $_SESSION[$secretKey];

// 创建或更新exec/secret.php文件（硬编码密码）
$secretFile = $execDir . 'secret.php';
$secretContent = '<?php
/**
 * HeaSec天积安全团队 - 秘密文件
 * 此文件只能在服务器端访问
 */
defined("HEASEC_RANGE_ACCESS") or die("Direct access not allowed");
echo "' . $secret . '";
?>';

$result = file_put_contents($secretFile, $secretContent);

if ($result === false) {
    echo json_encode([
        'success' => false,
        'message' => '初始化关卡失败：无法写入密码文件'
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => '关卡初始化成功',
    'level' => $targetLevel
]);
?>
