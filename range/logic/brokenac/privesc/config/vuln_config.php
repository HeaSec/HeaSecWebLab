<?php
/**
 * HeaSec天积安全团队 - 越权访问综合实战漏洞配置
 * 配置格式: [URL, [[参数名, 位置], ...], 漏洞类型, 分数]
 */

return [
    'vulns' => [
        ['/api/register.php', [['type', 'POST']], '权限提升', 250],
        ['/api/change-password.php', [['user_hash', 'POST']], '水平越权', 200],
        ['/api/update-profile.php', [['role', 'POST'], ['cookie:type', 'HEAD']], '权限提升', 200],
        ['/api/get-user-info.php', [['username', 'GET']], '水平越权', 100],
        ['/api/update-address.php', [['address_id', 'POST']], '水平越权', 100],
        ['/api/delete-avatar.php', [['filename', 'POST']], '水平越权', 100],
        ['/api/get-user-list.php', [], '垂直越权', 100],
        ['/api/toggle-user-status.php', [['user_id', 'POST'], ['cookie:type', 'HEAD']], '垂直越权', 150],
    ],
    'maxScore' => 1200,
];
