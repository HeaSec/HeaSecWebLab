<?php
/**
 * HeaSec天积安全团队 - 第二关：验证密码接口
 * 版本: v1.0.0
 * 创建日期: 2026-01-17
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec API v1.0.0');

// 只允许POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(array(
        'success' => false,
        'message' => ' 只允许POST请求'
    ), JSON_UNESCAPED_UNICODE);
    exit;
}

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 设置公共组件的基础路径
$commonBasePath = '../../../../../common/';

// 引入会话管理组件
require_once $commonBasePath . 'includes/session_manager.php';
HeaSec_InitRangeSession('bvbase');

// 引入数据库组件
require_once $commonBasePath . 'includes/HeaSec_Database.php';

// 引入学习状态更新组件
require_once $commonBasePath . 'includes/HeaSec_LearningStatusUpdater.php';

// 获取POST数据
$input = file_get_contents('php://input');
$data = json_decode($input, true);
if (!$data) {
    $data = $_POST;
}

// 获取参数
$code = isset($data['code']) ? trim($data['code']) : '';


// 当前关卡
$level = 2;

// 初始化响应
$response = array(
    'success' => false,
    'passed' => false,
    'message' => ''
);

try {
    if (empty($code)) {
        throw new Exception('请输入通关密码');
    }



    // 获取数据库连接
    $pdo = HeaSec_Database::getConnection('heasec_logic');

    // 根据验证码查询有效记录
    $stmt = $pdo->prepare("SELECT * FROM heasec_bvbase_codes WHERE level = ? AND code = ? AND expires_at > NOW() LIMIT 1");
    $stmt->execute(array($level, $code));
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$record) {
        throw new Exception('验证码不存在或已过期，请重新获取');
    }



    // 检查手机号是否为指定领奖手机号
    if ($record['phone'] !== '13866668888') {
        $response['success'] = true;
        $response['passed'] = false; // 不算通关
        $response['message'] = '验证成功，但手机号不符合通关要求，请使用正确的手机号（13866668888）验证码';
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 通关成功，更新学习状态：从"待学习"更新为"学习中"
    HeaSec_UpdateLearningStatusIfNeeded('bvbase');

    $response['success'] = true;
    $response['passed'] = true;
    $response['message'] = '领奖成功，恭喜通关！';

} catch (Exception $e) {
    $response['success'] = false;
    $response['passed'] = false;
    $response['message'] = $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);

?>