<?php
/**
 * HeaSec天积安全团队 - 手机短信模拟器发送接口
 * SMS Simulator Send API
 * 版本: v1.0.0
 * 创建日期: 2026-01-06
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 功能说明:
 *   - 接收靶场发送的短信请求
 *   - 校验手机号格式和短信长度
 *   - 如果手机号已注册，保存到收件箱
 *   - 记录所有发送尝试到日志表
 *
 * 注意：此接口不做安全处理，安全处理由靶场层负责
 */

// 设置响应头
header('Content-Type: application/json; charset=utf-8');
header('HeavenlySecret: HeaSec-API-v1.0.0');

// 定义访问常量并引入数据库组件
define('HEASEC_RANGE_ACCESS', true);
require_once dirname(dirname(dirname(__DIR__))) . '/includes/HeaSec_Database.php';

// 初始化响应数组
$response = array(
    'success' => false,
    'message' => '',
    'data' => null,
    'timestamp' => time()
);

try {
    // 获取POST数据
    $postData = file_get_contents('php://input');
    $requestData = json_decode($postData, true);

    // 如果JSON解析失败，尝试从$_POST获取
    if (empty($requestData)) {
        $requestData = $_POST;
    }

    // 获取参数
    $phone = isset($requestData['phone']) ? trim($requestData['phone']) : '';
    $message = isset($requestData['message']) ? trim($requestData['message']) : '';
    $rangeCode = isset($requestData['range_code']) ? trim($requestData['range_code']) : 'unknown';

    // 基础校验1：检查必填参数
    if (empty($phone)) {
        throw new Exception('手机号不能为空');
    }

    if (empty($message)) {
        throw new Exception('短信内容不能为空');
    }

    // 基础校验2：验证手机号格式（1开头，11位数字）
    // 注意：此处故意放宽校验，以支持靶场测试场景使用非标准手机号
    if (!preg_match('/^1\d{10}$/', $phone)) {
        throw new Exception('手机号格式不正确');
    }

    // 基础校验3：验证短信长度（不超过500个字符）
    if (mb_strlen($message, 'UTF-8') > 500) {
        throw new Exception('短信内容不能超过500个字符');
    }

    // 获取数据库连接
    $pdo = HeaSec_Database::getConnection('heasec_common');

    // 查询手机号是否已注册且启用
    $sql = "SELECT id, status FROM heasec_sms_simulator WHERE phone_number = ? LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array($phone));
    $simulator = $stmt->fetch(PDO::FETCH_ASSOC);

    $actuallySent = false;
    $detailInfo = null;
    $sendStatus = '未发送';

    // 检查手机号是否注册且启用
    if ($simulator && $simulator['status'] == 1) {
        // 手机号有效，保存短信到收件箱
        $sql = "INSERT INTO heasec_sms_message (simulator_id, phone_number, sender, message_content) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array($simulator['id'], $phone, $rangeCode, $message));
        $actuallySent = true;
        $sendStatus = '已发送';
    } else {
        // 手机号未注册或已禁用
        if (!$simulator) {
            $detailInfo = '手机号未注册';
        } else {
            $detailInfo = '手机号已禁用';
        }
    }

    // 记录发送日志（无论成功失败都记录）
    $sql = "INSERT INTO heasec_sms_log (phone_number, sender, message_content, send_status, detail_info, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);

    // 获取客户端信息
    $ipAddress = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';

    $stmt->execute(array(
        $phone,
        $rangeCode,
        $message,
        $sendStatus,
        $detailInfo,
        $ipAddress,
        $userAgent
    ));

    // 获取日志ID
    $logId = $pdo->lastInsertId();

    // 返回成功响应（根据需求，即使未实际发送也返回成功）
    $response['success'] = true;
    $response['message'] = $actuallySent ? '短信发送成功' : '短信发送成功（未注册手机号，短信未保存）';
    $response['data'] = array(
        'phone' => $phone,
        'sent' => $actuallySent,
        'log_id' => $logId
    );

} catch (Exception $e) {
    // 捕获异常并记录失败日志
    $errorMessage = $e->getMessage();

    try {
        // 尝试记录失败日志
        $pdo = HeaSec_Database::getConnection('heasec_common');
        $sql = "INSERT INTO heasec_sms_log (phone_number, sender, message_content, send_status, detail_info, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);

        // 获取客户端信息
        $ipAddress = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
        $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';

        $stmt->execute(array(
            isset($phone) ? $phone : '',
            isset($rangeCode) ? $rangeCode : 'unknown',
            isset($message) ? $message : '',
            '未发送', // 失败状态
            $errorMessage,
            $ipAddress,
            $userAgent
        ));
    } catch (Exception $logException) {
        // 日志记录失败不影响主流程
    }

    // 返回错误信息
    $response['success'] = false;
    $response['message'] = '[HeaSec] ' . $errorMessage;
}

// 输出JSON响应
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
