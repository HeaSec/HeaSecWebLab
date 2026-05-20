<?php
/**
 * HeaSec天积安全团队 - 越权访问综合实战修改密码接口
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

    privesc_require_login($pdo);
    $request = privesc_get_request_data();

    $userHash = privesc_get_string($request, 'user_hash');
    $newPassword = privesc_get_string($request, 'new_password');
    $confirmPassword = privesc_get_string($request, 'confirm_password');

    if ($userHash === '' || !preg_match('/^[a-f0-9]{64}$/i', $userHash)) {
        privesc_json_error('用户标识无效');
    }

    if ($newPassword !== $confirmPassword) {
        privesc_json_error('两次输入的密码不一致');
    }

    if (!privesc_is_valid_password($newPassword)) {
        privesc_json_error('密码长度应为 3-50 位');
    }

    $targetUser = privesc_fetch_user_by_hash($pdo, $userHash);
    if (!$targetUser) {
        privesc_json_error('未找到对应用户', 404);
    }

    privesc_update_password($pdo, $targetUser['id'], $newPassword);

    privesc_json_success('密码修改成功');
});
