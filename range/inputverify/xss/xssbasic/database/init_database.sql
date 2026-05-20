-- heasec_inputverify 数据库初始化脚本
-- 创建时间: 2025-12-13 19:11:10
-- 靶场: 基础分类学习
-- 说明: 此脚本可用于重置数据库到初始状态

-- 创建数据库（如果不存在）
CREATE DATABASE IF NOT EXISTS `heasec_inputverify` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 使用数据库
USE `heasec_inputverify`;

-- 删除已存在的表（确保重置到初始状态）
DROP TABLE IF EXISTS `heasec_xssbasic_records`;
DROP TABLE IF EXISTS `heasec_xssbasic_progress`;

-- 创建靶场表结构
-- heasec_xssbasic_records (成就记录表)
CREATE TABLE IF NOT EXISTS `heasec_xssbasic_records` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `achievement` varchar(50) NOT NULL COMMENT '成就名称',
                    `success_count` int(11) NOT NULL DEFAULT '0' COMMENT '成功次数',
                    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
                    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
                    `last_success_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后成功时间',
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `uk_achievement` (`achievement`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='成就记录表';

-- heasec_xssbasic_progress (关卡进度表)
CREATE TABLE IF NOT EXISTS `heasec_xssbasic_progress` (
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

-- 删除已存在的表（确保重置到初始状态）
DROP TABLE IF EXISTS `heasec_xssbasic_messages`;

-- 创建留言板表 (用于第二关存储型XSS)
-- heasec_xssbasic_messages
CREATE TABLE IF NOT EXISTS `heasec_xssbasic_messages` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `content` text NOT NULL COMMENT '留言内容',
                    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='留言板表';

-- 注：删除了旧版的 heasec_xssbasic_learning_progress 表

