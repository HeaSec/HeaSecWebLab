<?php
/**
 * HeaSec天积安全团队 - 用户枚举靶场登录接口
 * 版本: v1.0.0
 * 创建日期: 2026-02-27
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec UserEnum API v1.0.0');

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
    'code' => 0,
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
    $password = trim($data['password']);
    $level = intval($data['level']);

    // 验证关卡编号
    if ($level < 1 || $level > 3) {
        throw new Exception('无效的关卡编号');
    }

    // 获取数据库连接
    $pdo = HeaSec_Database::getConnection('heasec_logic');

    // 查询用户
    $stmt = $pdo->prepare("SELECT id, username, password FROM heasec_userenum_users WHERE username = ? AND level = ? LIMIT 1");
    $stmt->execute([$username, $level]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // 根据关卡进行不同的响应策略（用户枚举漏洞的核心）
    switch ($level) {
        case 1:
            // 第一关：前端提示差异 - 直接暴露用户是否存在
            if (!$user) {
                // 用户不存在
                $response['code'] = 1001;
                $response['message'] = '用户名不存在';
                break;
            }

            // 验证密码（明文比对）
            if ($password !== $user['password']) {
                $response['code'] = 1002;
                $response['message'] = '密码错误';
                break;
            }

            // 登录成功 - 判断是否为测试账号
            $isTestAccount = ($username === '13866668888');
            if ($isTestAccount) {
                // 测试账号登录成功，但不触发通关
                $response['success'] = true;
                $response['code'] = 0;
                $response['message'] = '测试账号登录成功！但这不是目标账号，请尝试枚举出其他账号。';
                $response['isTestAccount'] = true;
            } else {
                // 目标账号登录成功，触发通关
                HeaSec_UpdateLearningStatusIfNeeded('userenum');
                $response['success'] = true;
                $response['code'] = 0;
                $response['message'] = '登录成功，点击按钮进入下一关';
                $response['isTestAccount'] = false;
            }
            break;

        case 2:
            // 第二关：状态码差异 - 前端提示统一，但code不同
            if (!$user) {
                // 用户不存在
                $response['code'] = 2001;
                $response['message'] = '密码错误或用户名不存在';
                break;
            }

            // 验证密码（明文比对）
            if ($password !== $user['password']) {
                $response['code'] = 2002;
                $response['message'] = '密码错误或用户名不存在';
                break;
            }

            // 登录成功 - 判断是否为测试账号
            $isTestAccount = ($username === '13866668888');
            if ($isTestAccount) {
                // 测试账号登录成功，但不触发通关
                $response['success'] = true;
                $response['code'] = 0;
                $response['message'] = '测试账号登录成功！但这不是目标账号，请尝试枚举出其他账号。';
                $response['isTestAccount'] = true;
            } else {
                // 目标账号登录成功，触发通关
                HeaSec_UpdateLearningStatusIfNeeded('userenum');
                $response['success'] = true;
                $response['code'] = 0;
                $response['message'] = '登录成功，点击按钮进入下一关';
                $response['isTestAccount'] = false;
            }
            break;

        case 3:
            // 第三关：Cookie计数器 - 响应完全一致，但Cookie不同
            if (!$user) {
                // 用户不存在 - 不设置Cookie
                $response['code'] = 3001;
                $response['message'] = '密码错误或用户名不存在';
                break;
            }

            // 验证密码（明文比对）
            if ($password !== $user['password']) {
                // 密码错误 - 设置/更新Cookie
                $errCount = isset($_COOKIE['login_err_count']) ? intval($_COOKIE['login_err_count']) : 0;
                $errCount++;
                setcookie('login_err_count', $errCount, time() + 3600, '/');

                $response['code'] = 3001;
                $response['message'] = '密码错误或用户名不存在';
                break;
            }

            // 登录成功 - 清除Cookie
            setcookie('login_err_count', '', time() - 3600, '/');

            // 判断是否为测试账号
            $isTestAccount = ($username === '13866668888');
            if ($isTestAccount) {
                // 测试账号登录成功，但不触发通关
                $response['success'] = true;
                $response['code'] = 0;
                $response['message'] = '测试账号登录成功！但这不是目标账号，请尝试枚举出其他账号。';
                $response['isTestAccount'] = true;
            } else {
                // 目标账号登录成功，触发通关
                HeaSec_UpdateLearningStatusIfNeeded('userenum');
                $response['success'] = true;
                $response['code'] = 0;
                $response['message'] = '登录成功';
                $response['showCongrats'] = true;
                $response['isTestAccount'] = false;
            }
            break;

        default:
            throw new Exception('无效的关卡编号');
    }

} catch (Exception $e) {
    $response['code'] = 500;
    $response['message'] = $e->getMessage();
}

// 返回JSON响应
echo json_encode($response, JSON_UNESCAPED_UNICODE);
