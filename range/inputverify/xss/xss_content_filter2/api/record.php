<?php
/**
 * HeaSec天积安全团队 - XSS标签与事件组合学习靶场 - 成就记录API
 * 版本: v1.1.0
 * 创建日期: 2026-01-12
 * 更新日期: 2026-02-11
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 说明: 记录用户成功使用的标签和事件到数据库，并返回完整的成就数据供前端异步刷新
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec XSS Content Filter2 Record API v1.1.0');
header('Content-Type: application/json; charset=utf-8');

// 只允许POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => '[HeaSec] 只允许POST请求']);
    exit;
}

try {
    // 引入数据库连接
    $commonBasePath = '../../../../common/';
    require_once $commonBasePath . 'includes/database.php';
    $db = heasec_db('heasec_inputverify');

    // 获取POST数据
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // 验证数据格式
    if (!isset($data['tags']) || !isset($data['events'])) {
        throw new Exception('Invalid data format');
    }

    $tags = isset($data['tags']) ? $data['tags'] : [];
    $events = isset($data['events']) ? $data['events'] : [];

    // 记录标签
    foreach ($tags as $tag) {
        if (empty($tag)) {
            continue;
        }

        $stmt = $db->prepare("
            INSERT INTO heasec_xss_content_filter2_tags (tag_name, success_count)
            VALUES (:tag, 1)
            ON DUPLICATE KEY UPDATE
                success_count = success_count + 1,
                last_success_at = CURRENT_TIMESTAMP
        ");
        $stmt->execute(['tag' => $tag]);
    }

    // 记录事件
    foreach ($events as $event) {
        if (empty($event)) {
            continue;
        }

        $stmt = $db->prepare("
            INSERT INTO heasec_xss_content_filter2_events (event_name, success_count)
            VALUES (:event, 1)
            ON DUPLICATE KEY UPDATE
                success_count = success_count + 1,
                last_success_at = CURRENT_TIMESTAMP
        ");
        $stmt->execute(['event' => $event]);
    }

    // 获取统计信息
    $stmt = $db->query("SELECT COUNT(*) as count FROM heasec_xss_content_filter2_tags");
    $tagCount = intval($stmt->fetch(PDO::FETCH_ASSOC)['count']);

    $stmt = $db->query("SELECT COUNT(*) as count FROM heasec_xss_content_filter2_events");
    $eventCount = intval($stmt->fetch(PDO::FETCH_ASSOC)['count']);

    // 计算星星数量（从高到低判断）
    $starCount = 0;
    if ($eventCount >= 5) {
        $starCount = 3;  // 5个不同事件
    } elseif ($tagCount >= 5) {
        $starCount = 2;  // 5个不同标签
    } elseif ($tagCount + $eventCount > 0) {
        $starCount = 1;  // 首次触发
    }

    // 获取标签记录列表
    $stmt = $db->query("SELECT tag_name as name, success_count as count FROM heasec_xss_content_filter2_tags ORDER BY success_count DESC");
    $tagRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 获取事件记录列表
    $stmt = $db->query("SELECT event_name as name, success_count as count FROM heasec_xss_content_filter2_events ORDER BY success_count DESC");
    $eventRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 计算进度提示
    $tagHint = ($tagCount < 5) ? '还差 ' . (5 - $tagCount) . ' 个不同的标签获得一颗星星' : '恭喜！您标签已收集完成';
    $eventHint = ($eventCount < 5) ? '还差 ' . (5 - $eventCount) . ' 个不同的事件获得一颗星星' : '恭喜！您事件已收集完成';
    $progressHint = ($starCount == 0 && $tagCount == 0 && $eventCount == 0) ? '成功触发任意 XSS 即可获得第1颗星（首次触发）' : '';

    // 返回学习状态，配合前端恭喜组件
    $learningStatus = ($starCount >= 3) ? '已掌握' : '学习中';

    echo json_encode([
        'success' => true,
        'message' => '[HeaSec] 成就记录成功',
        'star_count' => $starCount,
        'tag_count' => $tagCount,
        'event_count' => $eventCount,
        'tag_records' => $tagRecords,
        'event_records' => $eventRecords,
        'tag_hint' => $tagHint,
        'event_hint' => $eventHint,
        'progress_hint' => $progressHint,
        'learning_status' => $learningStatus
    ]);

} catch (Exception $e) {
    error_log('[HeaSec] Record error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '[HeaSec] 服务器内部错误',
        'error' => $e->getMessage()
    ]);
}
?>