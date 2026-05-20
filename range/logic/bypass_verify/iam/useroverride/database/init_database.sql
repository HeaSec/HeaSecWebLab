-- ============================================================
-- HeaSec天积安全团队 - 用户覆盖靶场数据库初始化脚本
-- 版本: v1.0.0
-- 创建日期: 2026-02-25
-- 团队: 天积安全 (HeavenlySecret)
-- ============================================================

-- 创建数据库（如果不存在）
CREATE DATABASE IF NOT EXISTS `heasec_logic` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 使用数据库
USE `heasec_logic`;

-- 删除已存在的表（确保重置到初始状态）
DROP TABLE IF EXISTS `heasec_useroverride_users`;

-- 创建用户表
CREATE TABLE IF NOT EXISTS `heasec_useroverride_users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(50) NOT NULL COMMENT '用户名',
    `phone` VARCHAR(20) NOT NULL COMMENT '手机号',
    `password` VARCHAR(100) NOT NULL COMMENT '密码',
    `is_admin` TINYINT NOT NULL DEFAULT 0 COMMENT '是否是管理员（0:否，1:是）',
    `secret` VARCHAR(50) DEFAULT NULL COMMENT '秘密字符串（仅管理员有）',
    `login_attempts` INT NOT NULL DEFAULT 0 COMMENT '登录错误次数',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_username` (`username`),
    UNIQUE KEY `idx_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户覆盖靶场用户表';

-- 注意：用户数据将在首次访问靶场时由PHP代码自动生成
-- 目标用户: wangdajie, 手机号: 11005911234
-- 干扰用户: zhangdajie, leidajie, chendajie (手机号随机)
-- 管理员: 随机用户名, 随机手机号(1100591xxxx)
