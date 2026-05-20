-- heasec_inputverify 数据库初始化脚本
-- 创建时间: 2026-04-23
-- 靶场: 命令执行实战靶场
-- 说明: 此脚本可用于重置数据库到初始状态

-- 创建数据库（如果不存在）
CREATE DATABASE IF NOT EXISTS `heasec_inputverify` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 使用数据库
USE `heasec_inputverify`;

-- 删除已存在的表（确保重置到初始状态）
DROP TABLE IF EXISTS `heasec_rceadv_achievements`;

-- 成就记录表（全局共享模式）
CREATE TABLE IF NOT EXISTS `heasec_rceadv_achievements` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `achievement_type` VARCHAR(20) NOT NULL COMMENT '成就类型（reverse_shell/create_user/open_port）',
    `detail` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '成就详情（如IP:PORT）',
    `success_count` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '成功验证次数',
    `first_success_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '首次成功时间',
    `last_success_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '最后成功时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_achievement_type` (`achievement_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='命令执行实战靶场成就记录表（全局共享）';
