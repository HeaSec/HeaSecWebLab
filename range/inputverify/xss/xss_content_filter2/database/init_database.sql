-- heasec_inputverify 数据库初始化脚本
-- 创建时间: 2026-01-12
-- 更新时间: 2026-01-12
-- 靶场: XSS标签与事件组合学习靶场
-- 说明: 记录用户成功使用的标签和事件

-- 创建数据库（如果不存在）
CREATE DATABASE IF NOT EXISTS `heasec_inputverify` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 使用数据库
USE `heasec_inputverify`;

-- 删除已存在的表（确保重置到初始状态）
DROP TABLE IF EXISTS `heasec_xss_content_filter2_records`;
DROP TABLE IF EXISTS `heasec_xss_content_filter2_events`;
DROP TABLE IF EXISTS `heasec_xss_content_filter2_tags`;

-- 标签记录表
CREATE TABLE IF NOT EXISTS `heasec_xss_content_filter2_tags` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `tag_name` varchar(50) NOT NULL COMMENT '标签名称',
    `success_count` int(11) NOT NULL DEFAULT '1' COMMENT '成功次数',
    `first_success_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '首次成功时间',
    `last_success_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后成功时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_tag` (`tag_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='XSS标签记录表';

-- 事件记录表
CREATE TABLE IF NOT EXISTS `heasec_xss_content_filter2_events` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `event_name` varchar(50) NOT NULL COMMENT '事件名称',
    `success_count` int(11) NOT NULL DEFAULT '1' COMMENT '成功次数',
    `first_success_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '首次成功时间',
    `last_success_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后成功时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_event` (`event_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='XSS事件记录表';
