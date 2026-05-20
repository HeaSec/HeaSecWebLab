<?php
/**
 * HeaSec天积安全团队 - 越权访问综合实战获取地址接口
 * 版本: v1.0.0
 * 团队: 天积安全 (HeavenlySecret)
 */

define('HEASEC_RANGE_ACCESS', true);
$commonBasePath = '../../../../common/';
require_once __DIR__ . '/../includes/bootstrap.php';

privesc_handle_api(function () {
    privesc_require_method('GET');

    $pdo = privesc_get_pdo();
    privesc_ensure_seed_data($pdo);

    $currentUser = privesc_require_login($pdo);
    $address = privesc_fetch_address_by_user_id($pdo, $currentUser['id']);

    $addresses = [];
    if ($address) {
        $addresses[] = [
            'address_id' => $address['address_id'],
            'address' => $address['address'],
        ];
    }

    privesc_json_success('', [
        'addresses' => $addresses,
    ]);
});
