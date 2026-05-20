-- heasec_base 数据库初始化脚本 - URL任意跳转靶场
-- 创建时间: 2026-04-03
-- 靶场: URL任意跳转 (Open Redirect)
-- 说明: 此脚本可用于重置数据库到初始状态

CREATE DATABASE IF NOT EXISTS `heasec_base` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `heasec_base`;

-- 删除已存在的表（确保重置到初始状态）
DROP TABLE IF EXISTS `heasec_urlredirect_achievements`;
DROP TABLE IF EXISTS `heasec_urlredirect_requests`;
DROP TABLE IF EXISTS `heasec_urlredirect_users`;

-- 创建用户表
CREATE TABLE IF NOT EXISTS `heasec_urlredirect_users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `username` VARCHAR(50) NOT NULL COMMENT '用户名',
    `password` VARCHAR(255) NOT NULL COMMENT '密码（bcrypt哈希）',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='URL任意跳转靶场用户表';

-- 创建请求记录表
CREATE TABLE IF NOT EXISTS `heasec_urlredirect_requests` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `raw_url` VARCHAR(2000) NOT NULL COMMENT '原始URL',
    `parsed_host` VARCHAR(255) DEFAULT NULL COMMENT '解析后的目标域名',
    `is_valid` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否达成成就（1=通过白名单检查且目标为百度域名）',
    `bypass_type` VARCHAR(50) DEFAULT NULL COMMENT '绕过方式类型',
    `bypass_desc` VARCHAR(255) DEFAULT NULL COMMENT '绕过方式描述',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '提交时间',
    PRIMARY KEY (`id`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_is_valid` (`is_valid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='URL任意跳转靶场请求记录表';

-- 创建成就记录表
CREATE TABLE IF NOT EXISTS `heasec_urlredirect_achievements` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `bypass_type` VARCHAR(50) NOT NULL COMMENT '绕过方式类型',
    `bypass_desc` VARCHAR(255) NOT NULL COMMENT '绕过方式描述',
    `bypass_example` VARCHAR(500) DEFAULT NULL COMMENT '绕过示例URL',
    `applicable_scene` VARCHAR(255) DEFAULT NULL COMMENT '适用场景',
    `first_success_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '首次成功时间',
    `success_count` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '成功次数',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_type` (`user_id`, `bypass_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='URL任意跳转靶场成就记录表';

-- 插入测试用户数据（密码为123456的bcrypt哈希）
INSERT INTO `heasec_urlredirect_users` (`username`, `password`) VALUES
('heasec', '$2y$10$GoWhb7hvx1mQroiInHAV3OYpZ6tJuat28z2bdO1hAd205Q1AZdck6');
