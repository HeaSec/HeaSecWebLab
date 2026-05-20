-- HeaSec天积安全团队 - 图片验证码绕过2靶场 数据库初始化脚本
-- 版本: v1.0.0
-- 创建日期: 2026-01-20
-- 团队: 天积安全 (HeavenlySecret)
-- 数据库: heasec_logic (与其他逻辑漏洞靶场共用)
-- 表前缀: heasec_imgcodebp2_

-- 创建数据库（如果不存在）
CREATE DATABASE IF NOT EXISTS `heasec_logic` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 使用数据库
USE `heasec_logic`;

-- 删除已存在的表（确保重置到初始状态）
DROP TABLE IF EXISTS `heasec_imgcodebp2_records`;

-- 创建绕过记录表
CREATE TABLE IF NOT EXISTS `heasec_imgcodebp2_records` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `bypass_type` VARCHAR(20) NOT NULL COMMENT '绕过类型（empty/missing/wildcard）',
    `success_count` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '成功次数',
    `last_success_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '最后成功时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_bypass_type` (`bypass_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='图片验证码绕过2靶场-参数篡改记录表';
