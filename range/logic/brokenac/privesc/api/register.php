<?php
/**
 * HeaSec天积安全团队 - 越权访问综合实战注册接口
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
    $type = isset($request['type']) ? (int) $request['type'] : 0;
    $name = privesc_get_string($request, 'name');
    $phone = privesc_get_string($request, 'phone');

    if (!privesc_is_valid_username($username)) {
        privesc_json_error('用户名格式不正确');
    }

    if (!privesc_is_valid_password($password)) {
        privesc_json_error('密码长度应为 3-50 位');
    }

    if (!in_array($type, [0, 2], true)) {
        privesc_json_error('无效的角色类型');
    }

    if (empty($name)) {
        privesc_json_error('姓名不能为空');
    }

    if (empty($phone)) {
        privesc_json_error('手机号不能为空');
    }

    if (!privesc_is_valid_phone($phone)) {
        privesc_json_error('手机号格式不正确');
    }

    if (privesc_fetch_user_by_username($pdo, $username)) {
        privesc_json_error('用户名已存在');
    }

    if (privesc_fetch_user_by_phone($pdo, $phone)) {
        privesc_json_error('手机号已被使用');
    }

    $config = privesc_get_config();
    $defaultUser = $config['default_user'];

    $userId = privesc_create_user($pdo, [
        'username' => $username,
        'password' => $password,
        'name' => $name,
        'phone' => $phone,
        'role' => $type,
        'status' => 1,
        'avatar' => null,
    ]);

    privesc_create_address($pdo, $userId, $defaultUser['address']);

    privesc_json_success('注册成功', [], 201);
});
