-- ============================================
-- HeaSec天积安全团队 - 批量注册靶场数据库初始化脚本
-- Batch Registration Range Database Init Script
-- 版本: v1.0.0
-- 创建日期: 2026-02-14
-- 团队: 天积安全 (HeavenlySecret)
-- ============================================
-- 数据库名: heasec_logic (与其他逻辑漏洞靶场共用)
-- 表前缀: heasec_batchreg_
-- ============================================

CREATE DATABASE IF NOT EXISTS `heasec_logic` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `heasec_logic`;

-- 删除已存在的表（确保重置到初始状态）
DROP TABLE IF EXISTS `heasec_batchreg_users`;

-- 创建用户表
CREATE TABLE IF NOT EXISTS `heasec_batchreg_users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(50) NOT NULL COMMENT '用户名',
    `nickname` VARCHAR(50) NOT NULL COMMENT '昵称',
    `password` VARCHAR(100) NOT NULL COMMENT '密码',
    `phone` VARCHAR(20) NOT NULL COMMENT '手机号',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '注册时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_username` (`username`),
    UNIQUE KEY `idx_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='批量注册靶场用户表';

-- 注意：用户数据将在用户注册时动态创建
