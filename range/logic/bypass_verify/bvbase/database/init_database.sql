-- heasec_logic 数据库初始化脚本
-- 创建时间: 2026-01-17
-- 靶场: 基础流程绕过
-- 说明: 此脚本可用于重置数据库到初始状态

-- 创建数据库（如果不存在）
CREATE DATABASE IF NOT EXISTS `heasec_logic` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 使用数据库
USE `heasec_logic`;

-- =============================================
-- 基础流程绕过靶场表结构
-- 表前缀: heasec_bvbase_
-- =============================================

-- 删除旧表（如果存在）
DROP TABLE IF EXISTS `heasec_bvbase_codes`;

-- 验证码表
CREATE TABLE IF NOT EXISTS `heasec_bvbase_codes` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `phone` VARCHAR(11) NOT NULL COMMENT '手机号',
  `code` VARCHAR(20) NOT NULL COMMENT '验证码（20位随机字符串）',
  `level` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '关卡(1/2/3)',
  `is_used` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否已使用(0:未使用,1:已使用)',
  `created_at` DATETIME NOT NULL COMMENT '创建时间',
  `expires_at` DATETIME NOT NULL COMMENT '过期时间',
  PRIMARY KEY (`id`),
  KEY `idx_phone_level` (`phone`, `level`),
  KEY `idx_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='验证码表';

