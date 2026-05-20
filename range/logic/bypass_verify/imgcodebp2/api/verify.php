<?php
/**
 * HeaSec天积安全团队 - 验证接口（）
 * 版本: v1.0.0
 * 创建日期: 2026-01-20
 * 团队: 天积安全 (HeavenlySecret)
 *
 */

// 设置响应头
header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec ImgCodeBP2 Verify API v1.0.0');

// 设置公共组件路径
$commonBasePath = '../../../../common/';

// 引入会话管理组件
require_once $commonBasePath . 'includes/session_manager.php';
HeaSec_InitRangeSession('imgcodebp2');

// 引入假验证码生成器（本靶场本地副本）
require_once '../includes/FakeCaptchaGenerator.php';

// 引入数据库组件
require_once $commonBasePath . 'includes/database.php';

// 引入靶场公共配置
require_once '../includes/config.php';

/**
 * 识别绕过类型
 * @param array $data POST数据
 * @return string|null 绕过类型（empty/missing/wildcard）或null（正常验证）
 */
function detectBypassType($data)
{
    // 1. 检查验证码字段是否存在（绕过方式2：字段不存在）
    if (!isset($data['captcha'])) {
        return 'missing';
    }

    $captcha = trim($data['captcha']);

    // 2. 检查验证码是否为空（绕过方式1：空值）
    if ($captcha === '') {
        return 'empty';
    }

    // 3. 检查验证码是否为通配符*（绕过方式3：通配符）
    if ($captcha === '*') {
        return 'wildcard';
    }

    // 正常验证流程
    return null;
}

/**
 * 记录绕过方式到数据库
 * @param string $bypassType 绕过类型
 * @return bool 是否记录成功
 */
function recordBypassType($bypassType)
{
    try {
        $db = heasec_db('heasec_logic');

        $sql = "INSERT INTO heasec_imgcodebp2_records (bypass_type, success_count, last_success_at)
                VALUES (?, 1, NOW())
                ON DUPLICATE KEY UPDATE
                success_count = success_count + 1,
                last_success_at = NOW()";

        $stmt = $db->prepare($sql);
        return $stmt->execute([$bypassType]);
    } catch (Exception $e) {
        error_log('[HeaSec] RecordBypass error: ' . $e->getMessage());
        return false;
    }
}

// 获取POST数据
$input = file_get_contents('php://input');
$data = json_decode($input, true);
if (!$data) {
    $data = $_POST;
}

// 获取参数
$username = isset($data['username']) ? trim($data['username']) : '';

// 初始化响应
$response = array(
    'success' => false,
    'bypass_type' => null,
    'message' => ''
);


$bypassType = detectBypassType($data);

if ($bypassType !== null) {
    // 发现绕过方式，验证通过
    $response['success'] = true;
    $response['bypass_type'] = $bypassType;
    $response['message'] = '验证通过（发现绕过：' . getImgCodeBP2BypassTypeName($bypassType) . '）';

    // 记录到数据库
    recordBypassType($bypassType);
} else {
    // 正常验证流程
    $captcha = trim($data['captcha']);

    // 使用假验证码生成器进行验证
    $generator = new FakeCaptchaGenerator();
    if ($generator->verify('imgcodebp2_captcha', $captcha, false)) {
        $response['success'] = true;
        $response['message'] = '验证通过（正常输入）';
    } else {
        $response['success'] = false;
        $response['message'] = '验证码错误';
    }
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>