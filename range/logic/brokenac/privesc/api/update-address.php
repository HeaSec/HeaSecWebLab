<?php
/**
 * HeaSec天积安全团队 - 越权访问综合实战更新地址接口
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

    $addressId = privesc_get_string($request, 'address_id');
    $address = privesc_get_string($request, 'address');

    if (!privesc_is_valid_address_id($addressId)) {
        privesc_json_error('地址编号格式错误');
    }

    if ($address === '' || mb_strlen($address, 'UTF-8') > 255) {
        privesc_json_error('地址内容不能为空且长度不能超过 255 个字符');
    }

    $targetAddress = privesc_fetch_address_by_address_id($pdo, $addressId);
    if (!$targetAddress) {
        privesc_json_error('地址不存在', 404);
    }

    privesc_update_address($pdo, $addressId, $address);

    privesc_json_success('地址更新成功');
});
