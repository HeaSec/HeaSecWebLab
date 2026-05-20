<?php
/**
 * HeaSec天积安全团队 - 暴力破解前端加密靶场登录接口
 * 版本: v1.0.0
 * 创建日期: 2026-02-25
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec BruteEnc API v1.0.0');

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 设置公共组件路径
$commonBasePath = '../../../../../common/';

// 引入公共组件
require_once $commonBasePath . 'includes/HeaSec_Database.php';
require_once $commonBasePath . 'includes/HeaSec_LearningStatusUpdater.php';

// 初始化响应
$response = [
    'success' => false,
    'message' => ''
];

try {
    // 获取请求数据
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // 参数验证
    if (empty($data['username']) || empty($data['password']) || empty($data['level'])) {
        throw new Exception('参数不完整');
    }

    $username = trim($data['username']);
    $encryptedPassword = trim($data['password']);
    $level = intval($data['level']);

    // 验证关卡编号
    if ($level < 1 || $level > 3) {
        throw new Exception('无效的关卡编号');
    }

    // 获取数据库连接
    $pdo = HeaSec_Database::getConnection('heasec_logic');

    // 查询用户
    $stmt = $pdo->prepare("SELECT id, username, password FROM heasec_bruteenc_users WHERE username = ? AND level = ? LIMIT 1");
    $stmt->execute([$username, $level]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('登录失败，账号或密码错误');
    }

    // 根据关卡进行密码验证
    $passwordHash = '';
    switch ($level) {
        case 1:
            // 第一关：直接比对SHA256哈希
            $passwordHash = $encryptedPassword;
            break;

        case 2:
            // 第二关：AES解密后计算SHA256
            $decryptedPassword = aesDecrypt($encryptedPassword);
            if ($decryptedPassword === false) {
                throw new Exception('登录失败，账号或密码错误');
            }
            $passwordHash = hash('sha256', $decryptedPassword);
            break;

        case 3:
            // 第三关：RSA解密后计算SHA256
            $decryptedPassword = rsaDecrypt($encryptedPassword);
            if ($decryptedPassword === false) {
                throw new Exception('登录失败，账号或密码错误');
            }
            $passwordHash = hash('sha256', $decryptedPassword);
            break;

        default:
            throw new Exception('无效的关卡编号');
    }

    // 验证密码哈希
    if ($passwordHash !== $user['password']) {
        throw new Exception('登录失败，账号或密码错误');
    }

    // 登录成功
    // 第一关和第二关更新学习状态为"学习中"
    if ($level < 3) {
        HeaSec_UpdateLearningStatusIfNeeded('bruteenc');
    }

    $response['success'] = true;
    $response['message'] = $level === 3 ? '太棒了！登录成功，你已经完成所有关卡' : '太棒了！登录成功，点击按钮进入下一关';

    // 第三关返回showCongrats标志
    if ($level === 3) {
        $response['showCongrats'] = true;
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

// 返回JSON响应
echo json_encode($response, JSON_UNESCAPED_UNICODE);

/**
 * 第二关解密处理
 *
 * @param string $ciphertext 加密数据
 * @return string|false 解密结果
 */
function aesDecrypt($ciphertext) {
    $keyBase64 = 'SGVhU2VjQUVTSklBTUk2Ng==';
    $ivBase64 = 'SGVhU2VjQUVTSXNDb29sMQ==';

    $key = base64_decode($keyBase64);
    $iv = base64_decode($ivBase64);

    $decrypted = openssl_decrypt(base64_decode($ciphertext), 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv);

    return $decrypted;
}

/**
 * 第三关解密处理
 *
 * @param string $ciphertext 加密数据
 * @return string|false 解密结果
 */
function rsaDecrypt($ciphertext) {
    $privateKeyPath = __DIR__ . '/../keys/private.pem';

    if (!file_exists($privateKeyPath)) {
        return false;
    }

    $privateKey = file_get_contents($privateKeyPath);

    if (!openssl_private_decrypt(base64_decode($ciphertext), $decrypted, $privateKey)) {
        return false;
    }

    return $decrypted;
}
