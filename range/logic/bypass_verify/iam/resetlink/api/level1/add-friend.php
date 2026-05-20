<?php
/**
 * HeaSec天积安全团队 - 密码重置凭证可猜测靶场 - 添加好友API（第一关）
 * 版本: v1.0.0
 * 创建日期: 2026-01-22
 * 团队: 天积安全 (HeavenlySecret)
 */

// 禁用错误输出到页面
ini_set('display_errors', 0);
error_reporting(E_ALL);

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);
$commonBasePath = '../../../../../../common/';

// 引入会话管理组件（必须在session_start之前）
require_once $commonBasePath . 'includes/session_manager.php';

// 初始化靶场会话
HeaSec_InitRangeSession('resetlink');

// 设置响应头
header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec 密码重置凭证可猜测 Range v1.0.0');

// 引入数据库组件
require_once $commonBasePath . 'includes/HeaSec_Database.php';

// 获取请求数据
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// 验证登录状态
if (!isset($_SESSION['resetlink_level1_logged_in']) || $_SESSION['resetlink_level1_logged_in'] !== true) {
    echo json_encode([
        'success' => false,
        'message' => '请先登录'
    ]);
    exit;
}

// 获取当前用户
$currentUser = isset($_SESSION['resetlink_level1_user']) ? $_SESSION['resetlink_level1_user'] : null;

if (!$currentUser) {
    echo json_encode([
        'success' => false,
        'message' => '用户未登录或session已过期'
    ]);
    exit;
}

if (!isset($currentUser['id'])) {
    echo json_encode([
        'success' => false,
        'message' => '用户数据不完整：缺少ID字段'
    ]);
    exit;
}

if ($currentUser['is_admin'] == 1) {
    echo json_encode([
        'success' => false,
        'message' => '无权添加好友'
    ]);
    exit;
}

// 获取操作类型和好友账号
$action = isset($data['action']) ? trim($data['action']) : '';
$friendUsername = isset($data['username']) ? trim($data['username']) : '';

if (empty($action)) {
    echo json_encode([
        'success' => false,
        'message' => '缺少操作类型'
    ]);
    exit;
}

if (empty($friendUsername)) {
    echo json_encode([
        'success' => false,
        'message' => '请输入好友账号'
    ]);
    exit;
}

// 不能添加自己
if ($friendUsername === $currentUser['username']) {
    echo json_encode([
        'success' => false,
        'message' => '不能添加自己为好友'
    ]);
    exit;
}

try {
    $pdo = HeaSec_Database::getConnection('heasec_logic');

    // 搜索操作
    if ($action === 'search') {
        // 查找好友账号
        $stmt = $pdo->prepare("SELECT username, user_id, phone FROM heasec_resetlink_users WHERE level = 1 AND username = ?");
        $stmt->execute([$friendUsername]);
        $friendInfo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$friendInfo) {
            echo json_encode([
                'success' => false,
                'message' => '账号不存在'
            ]);
            exit;
        }

        // 检查是否已添加好友
        $stmt = $pdo->prepare("SELECT friend_added FROM heasec_resetlink_users WHERE level = 1 AND id = ?");
        $stmt->execute([$currentUser['id']]);
        $userRecord = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($userRecord && $userRecord['friend_added'] == 1) {
            echo json_encode([
                'success' => false,
                'message' => '您已添加过好友，无法再次添加'
            ]);
            exit;
        }

        // 返回好友信息用于确认
        echo json_encode([
            'success' => true,
            'action' => 'search',
            'message' => '找到用户：' . htmlspecialchars($friendInfo['username']) . '，请确认添加',
            'friend' => [
                'username' => $friendInfo['username'],
                'user_id' => $friendInfo['user_id'],
                'phone' => $friendInfo['phone']
            ]
        ]);
        exit;
    }

    // 确认添加操作
    if ($action === 'add') {
        // 检查是否已添加好友
        $stmt = $pdo->prepare("SELECT friend_added FROM heasec_resetlink_users WHERE level = 1 AND id = ?");
        $stmt->execute([$currentUser['id']]);
        $userRecord = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($userRecord && $userRecord['friend_added'] == 1) {
            echo json_encode([
                'success' => false,
                'message' => '您已添加过好友，无法再次添加'
            ]);
            exit;
        }

        // 查找好友账号
        $stmt = $pdo->prepare("SELECT username, user_id, phone FROM heasec_resetlink_users WHERE level = 1 AND username = ?");
        $stmt->execute([$friendUsername]);
        $friendInfo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$friendInfo) {
            echo json_encode([
                'success' => false,
                'message' => '账号不存在'
            ]);
            exit;
        }

        // 更新用户记录，标记已添加好友
        $stmt = $pdo->prepare("UPDATE heasec_resetlink_users SET friend_added = 1, friend_username = ?, updated_at = CURRENT_TIMESTAMP WHERE level = 1 AND id = ?");
        $stmt->execute([$friendUsername, $currentUser['id']]);

        // 更新session
        $_SESSION['resetlink_level1_user']['friend_added'] = 1;
        $_SESSION['resetlink_level1_user']['friend_username'] = $friendUsername;

        echo json_encode([
            'success' => true,
            'action' => 'add',
            'message' => '添加好友成功！页面即将刷新...',
            'refresh' => true
        ]);
        exit;
    }

    // 无效的操作类型
    echo json_encode([
        'success' => false,
        'message' => '无效的操作类型'
    ]);

} catch (PDOException $e) {
    error_log('[HeaSec] Database error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '系统错误，请稍后重试'
    ]);
}
