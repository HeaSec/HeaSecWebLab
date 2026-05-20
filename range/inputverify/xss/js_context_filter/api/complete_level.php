<?php
/**
 * HeaSec JavaScript上下文XSS过滤靶场 - 通关API
 * 版本: v1.0.0
 * 创建日期: 2025-01-29
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec JS上下文XSS通关API v1.0.0');
header('Content-Type: application/json; charset=utf-8');

// 只允许POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => '[HeaSec] 只允许POST请求'
    ]);
    exit;
}

// 检查关卡参数
if (!isset($_POST['level']) || empty($_POST['level'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => '[HeaSec] 缺少关卡参数'
    ]);
    exit;
}

$level = intval($_POST['level']);

// 验证关卡编号
if ($level < 1 || $level > 3) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => '[HeaSec] 无效的关卡编号'
    ]);
    exit;
}

try {
    // 引入数据库连接
    $commonBasePath = '../../../../common/';
    require_once  $commonBasePath . 'includes/database.php';
    require_once __DIR__ . '/../includes/HeaSec_SessionManager.php';

    // 获取数据库连接
    $db = heasec_db('heasec_inputverify');

    // 初始化会话管理器
    HeaSec_SessionManager::init($db);

    // 标记关卡为完成
    HeaSec_SessionManager::completeLevel($level);

    // 获取星星数量
    $starCount = HeaSec_SessionManager::getStarCount();

    // 更新学习状态
    require_once $commonBasePath . 'includes/HeaSec_LearningStatusUpdater.php';
    if ($level === 1 || $level === 2) {
        HeaSec_UpdateLearningStatus('js_context_filter', '学习中');
    } elseif ($level === 3) {
        HeaSec_UpdateLearningStatus('js_context_filter', '已掌握');
    }

    // 检查是否三关全部完成
    $allCompleted = HeaSec_SessionManager::isLevelCompleted(1) && 
                    HeaSec_SessionManager::isLevelCompleted(2) && 
                    HeaSec_SessionManager::isLevelCompleted(3);

    echo json_encode([
        'success' => true,
        'message' => '[HeaSec] 关卡' . $level . '通关成功',
        'level' => $level,
        'star_count' => $starCount,
        'all_levels_completed' => $allCompleted
    ]);

} catch (Exception $e) {
    error_log('[HeaSec] 通关API错误: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '[HeaSec] 服务器内部错误'
    ]);
}
?>
