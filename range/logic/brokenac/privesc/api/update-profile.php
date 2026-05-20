<?php
/**
 * HeaSec天积安全团队 - 越权访问综合实战更新资料接口
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
    $request = privesc_get_request_data();

    $name = privesc_get_string($request, 'name');
    $phone = privesc_get_string($request, 'phone');
    $roleValue = isset($request['role']) ? (int) $request['role'] : null;

    if ($name === '' || mb_strlen($name, 'UTF-8') > 50) {
        privesc_json_error('姓名不能为空且长度不能超过 50 个字符');
    }

    if (!privesc_is_valid_phone($phone)) {
        privesc_json_error('手机号格式不正确');
    }

    $cookieType = isset($_COOKIE['type']) ? trim((string) $_COOKIE['type']) : '';
    if ($roleValue !== null) {
        if (!privesc_is_valid_role($roleValue)) {
            privesc_json_error('角色类型无效');
        }

        if ($cookieType === '2') {
            privesc_update_profile($pdo, $currentUser['id'], $name, $phone, $roleValue);
        } else {
            privesc_update_profile($pdo, $currentUser['id'], $name, $phone);
        }
    } else {
        privesc_update_profile($pdo, $currentUser['id'], $name, $phone);
    }

    $updatedUser = privesc_fetch_user_by_id($pdo, $currentUser['id']);
    if ($updatedUser) {
        privesc_sync_type_cookie($updatedUser);
    }

    privesc_json_success('更新成功');
});
