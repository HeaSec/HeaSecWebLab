<?php
/**
 * HeaSec天积安全团队 - 越权访问综合实战切换用户状态接口
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

    $currentUser = privesc_require_login($pdo);

    $cookieType = isset($_COOKIE['type']) ? trim((string) $_COOKIE['type']) : '';
    if ($cookieType !== '2') {
        privesc_json_error('权限不足，无法执行此操作');
    }

    $request = privesc_get_request_data();
    $userId = privesc_get_int($request, 'user_id');

    if ($userId <= 0) {
        privesc_json_error('用户编号无效');
    }

    $targetUser = privesc_fetch_user_by_id($pdo, $userId);
    if (!$targetUser) {
        privesc_json_error('用户不存在', 404);
    }

    $newStatus = privesc_toggle_user_status($pdo, $userId);
    if ($newStatus < 0) {
        privesc_json_error('状态切换失败');
    }

    if ((int) $currentUser['id'] === $userId && $newStatus !== 1) {
        privesc_logout_user();
    }

    privesc_json_success('状态切换成功');
});
