-- heasec_base 数据库初始化脚本
-- 创建时间: 2026-04-06
-- 靶场: SSRF漏洞
-- 团队: 天积安全 (HeavenlySecret)
-- 说明: 此脚本可用于重置数据库到初始状态

-- 创建数据库（如果不存在）
CREATE DATABASE IF NOT EXISTS `heasec_base` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 使用数据库
USE `heasec_base`;

-- --------------------------------------------------------
-- SSRF靶场秘密存储表
-- --------------------------------------------------------
DROP TABLE IF EXISTS `heasec_ssrf_secrets`;
CREATE TABLE IF NOT EXISTS `heasec_ssrf_secrets` (
    `session_id` VARCHAR(64) NOT NULL COMMENT '会话ID',
    `secret_value` VARCHAR(20) NOT NULL COMMENT '秘密字符串',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (`session_id`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='SSRF靶场秘密存储表';

-- --------------------------------------------------------
-- SSRF靶场步骤进度表
-- --------------------------------------------------------
DROP TABLE IF EXISTS `heasec_ssrf_progress`;
CREATE TABLE IF NOT EXISTS `heasec_ssrf_progress` (
    `session_id` VARCHAR(64) NOT NULL COMMENT '会话ID',
    `current_step` TINYINT NOT NULL DEFAULT 1 COMMENT '当前步骤（1-4）',
    `step1_completed` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '第一步是否完成',
    `step2_completed` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '第二步是否完成',
    `step3_completed` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '第三步是否完成',
    `step4_completed` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '第四步是否完成',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='SSRF靶场步骤进度表';

-- --------------------------------------------------------
-- SSRF靶场端口探测记录表
-- --------------------------------------------------------
DROP TABLE IF EXISTS `heasec_ssrf_ports`;
CREATE TABLE IF NOT EXISTS `heasec_ssrf_ports` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `session_id` VARCHAR(64) NOT NULL COMMENT '会话ID',
    `target_host` VARCHAR(255) NOT NULL COMMENT '目标主机',
    `port` INT UNSIGNED NOT NULL COMMENT '端口号',
    `is_open` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否开放(1=开放)',
    `probed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '探测时间',
    PRIMARY KEY (`id`),
    INDEX `idx_session_host` (`session_id`, `target_host`),
    INDEX `idx_port` (`port`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='SSRF靶场端口探测记录表';

-- --------------------------------------------------------
-- SSRF靶场请求日志表
-- --------------------------------------------------------
DROP TABLE IF EXISTS `heasec_ssrf_logs`;
CREATE TABLE IF NOT EXISTS `heasec_ssrf_logs` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `session_id` VARCHAR(64) NOT NULL COMMENT '会话ID',
    `protocol` VARCHAR(20) NOT NULL COMMENT '使用的协议(http/file/dict/gopher)',
    `target_url` VARCHAR(2000) NOT NULL COMMENT '目标URL',
    `response_preview` VARCHAR(1000) DEFAULT NULL COMMENT '响应摘要',
    `is_success` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否成功',
    `step_completed` TINYINT(1) DEFAULT NULL COMMENT '触发的步骤完成(1-4)',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '请求时间',
    PRIMARY KEY (`id`),
    INDEX `idx_session_protocol` (`session_id`, `protocol`),
    INDEX `idx_step` (`step_completed`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='SSRF靶场请求日志表';
