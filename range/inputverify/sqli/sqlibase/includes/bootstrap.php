<?php
/**
 * HeaSec天积安全团队 - SQL注入基础靶场公共引导
 * 版本: v1.0.0
 * 团队: 天积安全 (HeavenlySecret)
 */

if (!defined('HEASEC_RANGE_ACCESS')) {
    define('HEASEC_RANGE_ACCESS', true);
}

$sqlibaseCommonBasePath = isset($commonBasePath) ? $commonBasePath : '../../../../common/';

require_once $sqlibaseCommonBasePath . 'includes/session_manager.php';
require_once $sqlibaseCommonBasePath . 'includes/HeaSec_Database.php';
require_once $sqlibaseCommonBasePath . 'components/vuln-card/includes/HeaSec_VulnManager.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/response.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/vuln-records.php';
require_once __DIR__ . '/data-init.php';

HeaSec_InitRangeSession('sqlibase');
HeaSec_ValidateSession();
