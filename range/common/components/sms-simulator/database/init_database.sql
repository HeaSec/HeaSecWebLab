-- ========================================
-- HeaSec天积安全团队 - 手机短信模拟器数据库初始化脚本
-- SMS Simulator Database Initialization Script
-- 版本: v1.0.0
-- 创建日期: 2026-01-06
-- 团队: 天积安全 (HeavenlySecret)
-- 数据库: heasec_common
--
-- 功能说明:
--   - 创建手机号注册表（heasec_sms_simulator）
--   - 创建短信发送日志表（heasec_sms_log）
--   - 创建短信收件箱表（heasec_sms_message）
--   - 初始化默认手机号：13866668888
-- ========================================

-- ========================================
-- 0. 创建数据库（如果不存在）
-- ========================================
CREATE DATABASE IF NOT EXISTS `heasec_common` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 使用数据库
USE `heasec_common`;

-- ========================================
-- 1. 删除现有表（如果存在）
-- ========================================

DROP TABLE IF EXISTS `heasec_sms_message`;
DROP TABLE IF EXISTS `heasec_sms_log`;
DROP TABLE IF EXISTS `heasec_sms_simulator`;

-- ========================================
-- 2. 创建表结构
-- ========================================

-- 手机号注册表
CREATE TABLE IF NOT EXISTS `heasec_sms_simulator` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `phone_number` varchar(20) NOT NULL COMMENT '手机号码',
    `is_default` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否为默认手机号：1是，0否',
    `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态：1启用，0禁用',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_phone_number` (`phone_number`),
    KEY `idx_is_default` (`is_default`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='手机短信模拟器-手机号注册表';

-- 短信发送日志表
CREATE TABLE IF NOT EXISTS `heasec_sms_log` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `phone_number` varchar(20) NOT NULL COMMENT '目标手机号码',
    `sender` varchar(100) NOT NULL COMMENT '发送者标识（靶场名称或系统标识）',
    `message_content` text NOT NULL COMMENT '短信内容',
    `send_status` varchar(20) NOT NULL DEFAULT '未发送' COMMENT '发送状态：已发送、未发送',
    `detail_info` varchar(255) DEFAULT NULL COMMENT '详细信息（未发送原因或其他说明）',
    `ip_address` varchar(45) DEFAULT NULL COMMENT '发送者IP地址',
    `user_agent` varchar(500) DEFAULT NULL COMMENT '发送者浏览器标识',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '发送时间',
    PRIMARY KEY (`id`),
    KEY `idx_phone_number` (`phone_number`),
    KEY `idx_send_status` (`send_status`),
    KEY `idx_sender` (`sender`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='手机短信模拟器-短信发送日志表';

-- 短信收件箱表
CREATE TABLE IF NOT EXISTS `heasec_sms_message` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `simulator_id` int(11) NOT NULL COMMENT '关联heasec_sms_simulator表的ID',
    `phone_number` varchar(20) NOT NULL COMMENT '接收手机号码',
    `sender` varchar(100) NOT NULL COMMENT '发送者标识（靶场名称或系统标识）',
    `message_content` text NOT NULL COMMENT '短信内容',
    `is_read` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否已读：1已读，0未读',
    `read_at` timestamp NULL DEFAULT NULL COMMENT '阅读时间',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '接收时间',
    PRIMARY KEY (`id`),
    KEY `idx_simulator_id` (`simulator_id`),
    KEY `idx_phone_number` (`phone_number`),
    KEY `idx_is_read` (`is_read`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='手机短信模拟器-短信收件箱表';

-- ========================================
-- 3. 插入初始化数据
-- ========================================

-- 插入默认手机号
INSERT INTO `heasec_sms_simulator` (`phone_number`, `is_default`, `status`) VALUES
('13866668888', 1, 1);

-- ========================================
-- 4. 完成初始化
-- ========================================

-- 数据库初始化完成
-- HeaSec天积安全团队 - SMS Simulator
-- 初始化完成时间: CURRENT_TIMESTAMP
