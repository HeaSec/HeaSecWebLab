-- heasec_inputverify 数据库初始化脚本
-- 创建时间: 2026-01-14 09:25:44
-- 靶场: HTML上下文过滤绕过
-- 说明: 此脚本可用于重置数据库到初始状态

-- 创建数据库（如果不存在）
CREATE DATABASE IF NOT EXISTS `heasec_inputverify` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 使用数据库
USE `heasec_inputverify`;

-- 删除已存在的表（确保重置到初始状态）
DROP TABLE IF EXISTS `heasec_html_context_filter_records`;
DROP TABLE IF EXISTS `heasec_html_context_filter_progress`;
DROP TABLE IF EXISTS `heasec_html_context_filter_access_logs`;

-- 创建靶场表结构
-- heasec_html_context_filter_records (成就记录表)
CREATE TABLE IF NOT EXISTS `heasec_html_context_filter_records` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `achievement` varchar(50) NOT NULL COMMENT '成就名称',
    `success_count` int(11) NOT NULL DEFAULT '0' COMMENT '成功次数',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    `last_success_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后成功时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_achievement` (`achievement`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='成就记录表';

-- heasec_html_context_filter_progress (关卡进度表)
CREATE TABLE IF NOT EXISTS `heasec_html_context_filter_progress` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `session_id` varchar(128) NOT NULL COMMENT '会话ID',
    `current_level` tinyint(4) NOT NULL DEFAULT '1' COMMENT '当前关卡（1, 2, 3）',
    `level1_completed` tinyint(1) NOT NULL DEFAULT '0' COMMENT '第一关是否完成',
    `level2_completed` tinyint(1) NOT NULL DEFAULT '0' COMMENT '第二关是否完成',
    `level3_completed` tinyint(1) NOT NULL DEFAULT '0' COMMENT '第三关是否完成',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_session_id` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='关卡进度表';

-- heasec_html_context_filter_access_logs (访客访问日志表)
CREATE TABLE IF NOT EXISTS `heasec_html_context_filter_access_logs` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `session_id` varchar(128) NOT NULL COMMENT '会话ID',
    `ip_address` varchar(45) NOT NULL COMMENT '来源IP',
    `request_page` varchar(255) NOT NULL COMMENT '请求页面',
    `referer` text COMMENT '来源页面URL',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '访问时间',
    PRIMARY KEY (`id`),
    KEY `idx_session_id` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='访客访问日志表';
