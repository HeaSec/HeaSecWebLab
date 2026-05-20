-- HeaSec天积安全团队 - 越权访问综合实战数据库初始化脚本
-- 版本: v1.0.0
-- 团队: 天积安全 (HeavenlySecret)

CREATE DATABASE IF NOT EXISTS `heasec_logic` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `heasec_logic`;

DROP TABLE IF EXISTS `heasec_privesc_star_status`;
DROP TABLE IF EXISTS `heasec_privesc_vuln_records`;
DROP TABLE IF EXISTS `heasec_privesc_address`;
DROP TABLE IF EXISTS `heasec_privesc_users`;

CREATE TABLE IF NOT EXISTS `heasec_privesc_users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `username` VARCHAR(50) NOT NULL COMMENT '用户名（唯一）',
    `password` VARCHAR(100) NOT NULL COMMENT '密码（明文存储，教学用途）',
    `name` VARCHAR(50) DEFAULT NULL COMMENT '姓名',
    `phone` VARCHAR(20) DEFAULT NULL COMMENT '手机号',
    `role` TINYINT NOT NULL DEFAULT 0 COMMENT '角色（0=普通用户，2=管理员）',
    `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态（1=正常，0=停用）',
    `avatar` VARCHAR(255) DEFAULT NULL COMMENT '头像文件名',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_username` (`username`),
    UNIQUE KEY `idx_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='越权访问综合实战用户表';

CREATE TABLE IF NOT EXISTS `heasec_privesc_address` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `address_id` VARCHAR(20) NOT NULL COMMENT '地址ID（格式ADDR_XXXX）',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `address` VARCHAR(255) NOT NULL COMMENT '地址内容',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_address_id` (`address_id`),
    UNIQUE KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='越权访问综合实战地址表';

CREATE TABLE IF NOT EXISTS `heasec_privesc_vuln_records` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `vuln_id` VARCHAR(100) NOT NULL COMMENT '漏洞标识',
    `score` INT NOT NULL COMMENT '漏洞得分',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '提交时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_vuln_id` (`vuln_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='越权访问综合实战漏洞记录表（全局共享）';

-- 创建星星状态记录表（全局单条记录）
CREATE TABLE IF NOT EXISTS `heasec_privesc_star_status` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `unlocked_stars` INT NOT NULL DEFAULT 0 COMMENT '已解锁的星星数量',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='越权访问综合实战星星状态表（全局共享）';

-- 初始化星星状态记录（确保存在一条记录）
INSERT IGNORE INTO `heasec_privesc_star_status` (`id`, `unlocked_stars`) VALUES (1, 0);
