<?php
/**
 * HeaSec天积安全团队 - 密码重置流程绕过靶场 - 第三关发送重置链接接口
 * 版本: v1.1.0
 * 创建日期: 2026-04-01
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 该接口用于发送密码重置链接到用户关联的手机号
 * 漏洞点：使用 $_SERVER['HTTP_HOST'] 构建重置链接，未做白名单校验
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 密码重置流程绕过 Range v1.1.0');
header('Content-Type: application/json; charset=utf-8');

// 设置公共组件基础路径
$commonBasePath = '../../../../../../common/';
// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 引入会话管理组件
require_once $commonBasePath . 'includes/session_manager.php';

// 初始化靶场会话
HeaSec_InitRangeSession('resetstepbp');

// 验证会话完整性
HeaSec_ValidateSession();

// 引入数据库组件
require_once $commonBasePath . 'includes/HeaSec_Database.php';

// 引入短信发送组件
require_once $commonBasePath . 'components/sms-simulator/includes/HeaSec_SmsSender.php';

try {
    // 漏洞点：将受污染的HOST值保存到独立变量，仅用于构建密码重置链接
    // 正常情况下 $injectedHost 与真实HOST一致，攻击者可通过修改Host头注入恶意域名
    $injectedHost = $_SERVER['HTTP_HOST'];

    // 恢复真实的HOST值，确保所有内部API调用（如短信模拟器）不受HOST注入影响
    // SERVER_NAME 在Apache默认配置下也会受Host头影响，因此直接使用localhost
    $realHost = 'localhost';
    $realPort = $_SERVER['SERVER_PORT'];
    if (!in_array($realPort, ['80', '443'])) {
        $realHost .= ':' . $realPort;
    }
    $_SERVER['HTTP_HOST'] = $realHost;

    // 接收参数
    $input = json_decode(file_get_contents('php://input'), true);
    $username = isset($input['username']) ? trim($input['username']) : '';

    if (empty($username)) {
        echo json_encode([
            'success' => false,
            'message' => '请输入账号'
        ]);
        exit;
    }

    $pdo = HeaSec_Database::getConnection('heasec_logic');

    // 查询账号是否存在（level=3）
    $stmt = $pdo->prepare("SELECT * FROM heasec_resetstepbp_users WHERE level = 3 AND username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode([
            'success' => false,
            'message' => '账号不存在'
        ]);
        exit;
    }

    // 生成32位随机十六进制重置令牌
    $token = bin2hex(random_bytes(16));

    // 使用受污染的HOST构建重置链接（漏洞触发点）
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    // 动态计算靶场根目录的URL路径（从 /api/level3/ 回退到靶场根目录）
    $rangeDir = dirname(dirname(dirname($_SERVER['SCRIPT_NAME'])));
    $baseUrl = $protocol . '://' . $injectedHost . $rangeDir;
    $resetLink = $baseUrl . '/reset/level3-reset.php?token=' . $token;

    // 将令牌信息存入数据库（记录受污染的HOST）
    $expiresAt = date('Y-m-d H:i:s', strtotime('+30 minutes'));
    $stmt = $pdo->prepare("INSERT INTO heasec_resetstepbp_reset_tokens (username, token, host, reset_link, expires_at) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$username, $token, $injectedHost, $resetLink, $expiresAt]);

    // 获取手机号后四位用于提示
    $phoneSuffix = substr($user['phone'], -4);

    // 发送重置链接到账号关联的手机号（使用真实HOST，不受注入影响）
    $smsMessage = "【天积安全】您正在重置密码，请点击以下链接完成操作：{$resetLink}，链接有效期30分钟。如非本人操作，请忽略此短信。";
    HeaSec_SmsSender::send($user['phone'], $smsMessage, 'resetstepbp');

    // 钓鱼模拟检测：检查是否同时满足以下条件
    // 1. 目标账号的 is_admin 字段为 1
    // 2. 请求中的 HOST 头值等于 pentest.heasec.com
    if ($user['is_admin'] == 1 && $injectedHost === 'pentest.heasec.com') {
        // 钓鱼模拟成功：从数据库中读取admin的明文密码
        $adminPassword = $user['password'];

        // 向攻击者可控的手机号发送钓鱼模拟短信（使用真实HOST，不受注入影响）
        $phishingSms = "【天积安全】钓鱼模拟通知：admin的密码为 {$adminPassword}。（这是靶场模拟短信，模拟攻击者通过钓鱼网站获取用户密码的场景。真实攻击中攻击者会在自己控制的钓鱼网站上捕获用户输入的密码或重置token）";
        HeaSec_SmsSender::send('13866668888', $phishingSms, 'resetstepbp');
    }

    echo json_encode([
        'success' => true,
        'message' => "重置链接已发送到尾号{$phoneSuffix}的手机"
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '请求失败，请稍后重试'
    ]);
}
