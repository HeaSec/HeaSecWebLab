-- ============================================================
-- HeaSec天积安全团队 - 文件相关XSS靶场 数据库初始化脚本
-- 版本: v1.0.0
-- 创建日期: 2026-03-03
-- 更新日期: 2026-03-21
-- 团队: 天积安全 (HeavenlySecret)
-- 数据库名: heasec_inputverify (与其他输入验证靶场共用)
-- ============================================================

-- 创建数据库（如果不存在）
CREATE DATABASE IF NOT EXISTS `heasec_inputverify` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 使用数据库
USE `heasec_inputverify`;

-- 删除已存在的表（确保重置到初始状态）
DROP TABLE IF EXISTS `heasec_filexss_records`;
DROP TABLE IF EXISTS `heasec_filexss_progress`;

-- 创建成就记录表
CREATE TABLE IF NOT EXISTS `heasec_filexss_records` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `achievement` varchar(50) NOT NULL COMMENT '成就名称（level1, level2, level3）',
    `success_count` int(11) NOT NULL DEFAULT '0' COMMENT '成功次数',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    `last_success_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '最后成功时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_achievement` (`achievement`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='文件相关XSS成就记录表';

-- 创建关卡进度表（如果不存在）
CREATE TABLE IF NOT EXISTS `heasec_filexss_progress` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `session_id` varchar(128) NOT NULL COMMENT '会话ID',
    `current_level` tinyint(4) NOT NULL DEFAULT '1' COMMENT '当前关卡（1, 2, 3）',
    `level1_completed` tinyint(1) NOT NULL DEFAULT '0' COMMENT '第一关是否完成（0:未完成, 1:已完成）',
    `level2_completed` tinyint(1) NOT NULL DEFAULT '0' COMMENT '第二关是否完成',
    `level3_completed` tinyint(1) NOT NULL DEFAULT '0' COMMENT '第三关是否完成',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_session_id` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='文件相关XSS关卡进度表';
