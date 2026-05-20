-- heasec_inputverify 数据库初始化脚本
-- 创建时间: 2026-01-12 10:49:02
-- 靶场: WAF内容校验
-- 说明: 此脚本可用于重置数据库到初始状态

-- 创建数据库（如果不存在）
CREATE DATABASE IF NOT EXISTS `heasec_inputverify` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 使用数据库
USE `heasec_inputverify`;

-- 删除已存在的表（确保重置到初始状态）
DROP TABLE IF EXISTS `heasec_antiwaf_records`;

-- 创建靶场表结构
-- heasec_antiwaf_records
CREATE TABLE IF NOT EXISTS `heasec_antiwaf_records` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `achievement` varchar(50) NOT NULL COMMENT '成就名称',
                    `success_count` int(11) NOT NULL DEFAULT '0' COMMENT '成功次数',
                    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
                    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
                    `last_success_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后成功时间',
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `uk_achievement` (`achievement`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='成就记录表';

-- 靶场其他表结构请在此处添加
-- 建议使用表前缀: heasec_antiwaf_
