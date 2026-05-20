<?php
/**
 * HeaSec天积安全团队 - 条件竞争上传靶场 - 关卡初始化接口
 * 版本: v1.0.0
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 此接口用于在跳转到下一关前初始化关卡状态
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 条件竞争上传 Range v1.0.0');
header('Content-Type: application/json; charset=utf-8');

// 公共组件路径
$commonBasePath = '../../../../../common/';

define('HEASEC_RANGE_ACCESS', true);

// 引入会话管理
require_once $commonBasePath . 'includes/session_manager.php';
HeaSec_InitRangeSession('racecondition');
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
    $prevLevelKey = 'racecondition_level' . ($targetLevel - 1) . '_passed';
    if (!isset($_SESSION[$prevLevelKey]) || !$_SESSION[$prevLevelKey]) {
        echo json_encode([
            'success' => false,
            'message' => '请先通过前一关'
        ]);
        exit;
    }
}

// images目录路径
$imagesDir = dirname(__DIR__) . '/images/';

// 创建images目录（如果不存在）
if (!file_exists($imagesDir)) {
    mkdir($imagesDir, 0755, true);
}

// 重置images目录：删除用户上传的文件（保留secret.php和.htaccess）
if (file_exists($imagesDir)) {
    $files = glob($imagesDir . '*');
    foreach ($files as $file) {
        if (is_file($file)) {
            $basename = basename($file);
            if ($basename !== 'secret.php' && $basename !== '.htaccess') {
                @unlink($file);
            }
        }
    }
}

// 生成当前关卡的通关密码
// 使用generateSecret直接生成新密码，避免HeaSec_GetSecret的缓存机制
$secretKey = 'racecondition_level' . $targetLevel . '_secret';
$_SESSION[$secretKey] = HeaSec_SessionManager::generateSecret(20);
$secret = $_SESSION[$secretKey];

// 创建或更新secret.php文件
$secretFile = $imagesDir . 'secret.php';
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
