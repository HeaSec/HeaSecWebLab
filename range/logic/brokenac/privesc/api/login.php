<?php
/**
 * HeaSec天积安全团队 - 越权访问综合实战登录接口
 * 版本: v1.0.0
 * 团队: 天积安全 (HeavenlySecret)
 */

define('HEASEC_RANGE_ACCESS', true);
$commonBasePath = '../../../../common/';
require_once __DIR__ . '/../includes/bootstrap.php';

privesc_handle_api(function () {
    privesc_require_method('POST');

    $pdo = privesc_get_pdo();
    privesc_ensure_seed_data($pdo);

    $request = privesc_get_request_data();
    $username = privesc_get_string($request, 'username');
    $password = privesc_get_string($request, 'password');

    if ($username === '' || $password === '') {
        privesc_json_error('请输入用户名和密码');
    }

    $user = privesc_fetch_user_by_username($pdo, $username);
    if (!$user || (string) $user['password'] !== $password) {
        privesc_json_error('用户名或密码错误', 401);
    }

    if ((int) $user['status'] !== 1) {
        privesc_json_error('账号已停用，无法登录', 403);
    }

    privesc_login_user($user);

    privesc_json_success('登录成功');
});
