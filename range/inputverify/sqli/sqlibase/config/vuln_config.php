<?php
/**
 * HeaSec天积安全团队 - SQL注入基础靶场漏洞验证配置
 * 版本: v1.0.0
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 每条规则：[URL路径, [[参数名, 参数位置], ...], 漏洞类型, 分值]
 * 参数位置：GET / POST / HEAD（HEAD包含Cookie和HTTP头部）
 */

return [
    'vulns' => [
        ['/api/get-article.php',       [['id', 'GET']],               '数字型(无闭合)',             200],
        ['/api/login.php',             [['username', 'POST']],        '字符型(单引号闭合)',          200],
        ['/api/submit-feedback.php',   [['category_id', 'POST']],     '数字型(括号闭合)',            200],
        ['/api/search-articles.php',   [['category', 'POST']],        '字符型(双引号闭合)',          200],
        ['/api/get-preferences.php',   [['heasec_user_token', 'HEAD']], '字符型(单引号加括号闭合)',  200],
        ['/api/log-visit.php',         [['User-Agent', 'HEAD']],      "字符型(%'闭合)",              200],
    ],
    'maxScore' => 1200,
];
