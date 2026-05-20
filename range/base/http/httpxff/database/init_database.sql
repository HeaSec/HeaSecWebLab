-- heasec_base 数据库初始化脚本
-- 创建时间: 2025-11-07
-- 靶场: HTTP 代理IP请求头
-- 说明: 此脚本可用于重置数据库到初始状态

-- 创建数据库（如果不存在）
CREATE DATABASE IF NOT EXISTS `heasec_base` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 使用数据库
USE `heasec_base`;

-- 删除已存在的表（确保重置到初始状态）
DROP TABLE IF EXISTS `heasec_httpxff_records`;

-- 创建HTTP请求头记录表
CREATE TABLE IF NOT EXISTS `heasec_httpxff_records` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `header_name` varchar(50) NOT NULL COMMENT '请求头名称',
        `success_count` int(11) NOT NULL DEFAULT '1' COMMENT '成功次数',
        `last_success_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '最后成功时间',
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uk_header_name` (`header_name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='HTTP请求头成功记录表';
