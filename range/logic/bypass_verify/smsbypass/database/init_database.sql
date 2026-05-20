-- heasec_logic 数据库初始化脚本
-- 创建时间: 2026-01-21
-- 靶场: 短信验证码绕过 - 验证码接收方篡改
-- 说明: 此脚本可用于重置数据库到初始状态

-- 创建数据库（如果不存在）
CREATE DATABASE IF NOT EXISTS `heasec_logic` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 使用数据库
USE `heasec_logic`;

-- 删除已存在的表（确保重置到初始状态）
DROP TABLE IF EXISTS `heasec_smsbypass_records`;
DROP TABLE IF EXISTS `heasec_smsbypass_codes`;

-- 创建验证码存储表
CREATE TABLE IF NOT EXISTS `heasec_smsbypass_codes` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `code` VARCHAR(6) NOT NULL COMMENT '验证码',
    `sent_phones` TEXT COMMENT '发送的手机号列表(JSON)',
    `request_params` TEXT COMMENT '原始请求参数(JSON)',
    `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态：1=有效，0=失效/已使用',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='短信验证码绕过靶场-验证码存储表';

-- 创建篡改记录表
CREATE TABLE IF NOT EXISTS `heasec_smsbypass_records` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `bypass_type` VARCHAR(30) NOT NULL COMMENT '篡改类型（direct_replace/array_injection/parameter_pollution）',
    `success_count` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '成功次数',
    `last_success_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '最后成功时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_bypass_type` (`bypass_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='短信验证码绕过靶场-验证码接收方篡改记录表';

-- 确保目标手机号存在于短信模拟器中（用于接收验证码）
-- 需要在heasec_common数据库的heasec_sms_simulator表中预置目标手机号
INSERT INTO `heasec_common`.`heasec_sms_simulator` (`phone_number`, `status`, `is_default`)
SELECT '13866668888', 1, 0
WHERE NOT EXISTS (SELECT 1 FROM `heasec_common`.`heasec_sms_simulator` WHERE `phone_number` = '13866668888');
