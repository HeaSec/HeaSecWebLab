-- heasec_inputverify 数据库初始化脚本
-- 创建时间: 2026-04-17
-- 靶场: 文件包含进阶
-- 说明: 此脚本可用于重置数据库到初始状态

-- 创建数据库（如果不存在）
CREATE DATABASE IF NOT EXISTS `heasec_inputverify` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 使用数据库
USE `heasec_inputverify`;

-- 删除已存在的表（确保重置到初始状态）
DROP TABLE IF EXISTS `heasec_fiadv_achievements`;
DROP TABLE IF EXISTS `heasec_fiadv_targets`;

-- 目标字符串存储表（全局共享，仅存一条记录）
CREATE TABLE IF NOT EXISTS `heasec_fiadv_targets` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `target_value` VARCHAR(64) NOT NULL COMMENT '目标字符串（I_love_heasec_ + 20位随机字符，共34位）',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='文件包含进阶靶场目标字符串表（全局共享，仅存一条记录）';

-- 成就记录表（全局共享）
CREATE TABLE IF NOT EXISTS `heasec_fiadv_achievements` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `protocol` VARCHAR(20) NOT NULL COMMENT '协议标识（file/php_input/data/zip/phar）',
    `success_count` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '成功次数',
    `first_success_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '首次成功时间',
    `last_success_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '最后成功时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_protocol` (`protocol`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='文件包含进阶靶场成就记录表（全局共享）';
