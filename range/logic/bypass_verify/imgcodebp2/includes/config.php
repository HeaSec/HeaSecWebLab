<?php
/**
 * HeaSec天积安全团队 - 图片验证码绕过2靶场公共配置
 * 版本: v1.0.0
 * 创建日期: 2026-01-20
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 说明：统一定义绕过类型名称映射，遵循DRY原则
 */

// 防止直接访问
if (!defined('HEASEC_RANGE_ACCESS') && !defined('HEASEC_ImgCodeBP2_CONFIG')) {
    define('HEASEC_ImgCodeBP2_CONFIG', true);
}

/**
 * 获取绕过类型名称映射
 * @return array 绕过类型 => 中文名称
 */
function getImgCodeBP2BypassTypeNames() {
    return [
        'empty' => '验证码为空值',
        'missing' => '验证码字段不存在',
        'wildcard' => '验证码使用通配符*'
    ];
}

/**
 * 根据绕过类型获取中文名称
 * @param string $bypassType 绕过类型
 * @return string 中文名称
 */
function getImgCodeBP2BypassTypeName($bypassType) {
    $names = getImgCodeBP2BypassTypeNames();
    return isset($names[$bypassType]) ? $names[$bypassType] : $bypassType;
}
?>
