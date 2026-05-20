<?php
/**
 * HeaSec天积安全团队 - 成就数据API接口
 * 版本: v1.0.0
 * 创建日期: 2025-12-14
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 成就数据API v1.0.0');
header('Content-Type: application/json; charset=utf-8');

// 只允许GET请求
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => '[HeaSec] 只允许GET请求'
    ]);
    exit;
}

// 检查range_code参数
if (!isset($_GET['range_code']) || empty($_GET['range_code'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => '[HeaSec] 缺少range_code参数'
    ]);
    exit;
}

$rangeCode = $_GET['range_code'];

try {
    // 根据不同的range_code连接相应的数据库
    switch ($rangeCode) {
        case 'xss-xssbasic':
        case 'xss_xssbasic':
            require_once __DIR__ . '/../includes/database.php';
            $db = heasec_db('heasec_inputverify');

            // 获取XSS基础靶场的成就数据
            $sql = "SELECT achievement, success_count FROM heasec_xssbasic_records";
            $stmt = $db->prepare($sql);
            $stmt->execute();
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 计算成就数量
            $achievedCount = 0;
            $achievementDetails = [];

            foreach ($records as $record) {
                if ($record['success_count'] > 0) {
                    $achievedCount++;
                    $achievementDetails[] = [
                        'type' => $record['achievement'],
                        'count' => $record['success_count']
                    ];
                }
            }

            $data = [
                'achievedCount' => $achievedCount,
                'totalStars' => 3,
                'details' => $achievementDetails,
                'learningStatus' => $achievedCount >= 3 ? 'mastered' : 'in_progress'
            ];
            break;

        default:
            throw new Exception('不支持的靶场代码');
    }

    echo json_encode([
        'success' => true,
        'data' => $data,
        'message' => '[HeaSec] 成就数据获取成功'
    ]);

} catch (Exception $e) {
    error_log('[HeaSec] 成就数据API错误: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '[HeaSec] 服务器内部错误'
    ]);
}
?>