<?php
/**
 * HeaSec天积安全团队 - SQL注入基础靶场配置
 * 版本: v1.0.0
 * 团队: 天积安全 (HeavenlySecret)
 */

return [
    'version' => 'v1.0.0',
    'range_code' => 'sqlibase',
    'database' => 'heasec_sqlinject',
    'tables' => [
        'users' => 'heasec_sqlibase_users',
        'categories' => 'heasec_sqlibase_categories',
        'articles' => 'heasec_sqlibase_articles',
        'feedback' => 'heasec_sqlibase_feedback',
        'preferences' => 'heasec_sqlibase_preferences',
        'visit_logs' => 'heasec_sqlibase_visit_logs',
        'vuln_records' => 'heasec_sqlibase_vuln_records',
        'star_status' => 'heasec_sqlibase_star_status',
    ],
    'cookie' => [
        'name' => 'heasec_user_token',
        'default' => 'default',
        'path' => '',
        'lifetime' => 86400 * 30,
        'samesite' => 'Lax',
    ],
    'upload' => [
        'allowed_mime' => ['image/jpeg', 'image/png', 'image/gif'],
        'extensions' => ['jpg', 'jpeg', 'png', 'gif'],
        'max_size' => 2 * 1024 * 1024,
        'relative_dir' => 'uploads/screenshots/',
        'absolute_dir' => dirname(__DIR__) . '/uploads/screenshots/',
    ],
    'ui' => [
        'title' => 'SQL注入漏洞挖掘',
        'star_count' => 3,
        'score_thresholds' => [400, 800, 1200],
        'star_titles' => ['注入初探者', '注入猎手', '注入专家'],
        'max_score' => 1200,
        'vuln_types' => ['数字型(无闭合)', '数字型(括号闭合)', '字符型(单引号闭合)', '字符型(双引号闭合)', '字符型(单引号加括号闭合)','字符型(双引号加括号闭合)', "字符型(%'闭合)", "其他类型"],
    ],
];
