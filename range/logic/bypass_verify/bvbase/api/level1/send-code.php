<?php
/**
 * HeaSec天积安全团队 - 第一关：发送验证码接口
 * 版本: v1.0.0
 * 创建日期: 2026-01-17
 * 团队: 天积安全 (HeavenlySecret)
 *

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

// 引入数据库组件
require_once $commonBasePath . 'includes/HeaSec_Database.php';

// 引入短信发送组件
require_once $commonBasePath . 'components/sms-simulator/includes/HeaSec_SmsSender.php';

/**
 * 生成20位随机验证码（数字+字母）
 */
function generateCode($length = 20)
{
    $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $code = '';
    $charsLen = strlen($chars);
    for ($i = 0; $i < $length; $i++) {
        $code .= $chars[mt_rand(0, $charsLen - 1)];
    }
    return $code;
}

// 获取POST数据
$input = file_get_contents('php://input');
$data = json_decode($input, true);
if (!$data) {
    $data = $_POST;
}

// 获取参数
$phone = isset($data['phone']) ? trim($data['phone']) : '';

// 当前关卡
$level = 1;

// 初始化响应
$response = array(
    'success' => false,
    'message' => ''
);

try {

    if (!preg_match('/^1\d{10}$/', $phone)) {
        throw new Exception('手机号格式不正确');
    }

    // 获取数据库连接
    $pdo = HeaSec_Database::getConnection('heasec_logic');

    // 生成验证码
    $code = generateCode(20);

    // 删除该手机号该关卡的旧验证码
    $stmt = $pdo->prepare("DELETE FROM heasec_bvbase_codes WHERE phone = ? AND level = ?");
    $stmt->execute(array($phone, $level));

    // 存储新验证码（10分钟有效期）
    $stmt = $pdo->prepare("INSERT INTO heasec_bvbase_codes (phone, code, level, is_used, created_at, expires_at) VALUES (?, ?, ?, 0, NOW(), DATE_ADD(NOW(), INTERVAL 10 MINUTE))");
    $stmt->execute(array($phone, $code, $level));

    // 构造短信内容
    $message = '恭喜您获得第一关领奖资格，您的验证码是 ' . $code . '，10分钟内有效。';

    // 调用短信发送组件
    $result = HeaSec_SmsSender::send($phone, $message, 'bvbase_level1');

    if ($result['success']) {
        // $maskedPhone = substr($phone, 0, 3) . '****' . substr($phone, 7); // 不再脱敏
        $response['success'] = true;
        // 使用 \n 进行换行，前端js会将 \n 转换为 <br>，**内容**转换为加粗
        $response['message'] = "恭喜您获得第一关领奖资格！\n验证码已发送到您的手机号(可使用顶部区域短信模拟器查看)：**{$phone}**\n请在礼品兑换表单中输入验证码，验证码10分钟内有效，同一关卡同一手机号只有最后一次下发的验证码有效。";
    } else {
        throw new Exception('短信发送失败: ' . (isset($result['message']) ? $result['message'] : '未知错误'));
    }

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = ' ' . $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>