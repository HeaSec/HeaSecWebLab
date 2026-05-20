-- ========================================
-- HeaSec天积安全团队 - 文件上传黑名单绕过靶场数据库初始化脚本
-- 版本: v1.0.0
-- 创建日期: 2025-12-08
-- 更新日期: 2026-03-21
-- 团队: 天积安全 (HeavenlySecret)
-- 靶场: 文件上传黑名单绕过 (File Upload Blacklist Bypass)
-- 数据库: heasec_inputverify (与其他输入验证靶场共用)
-- ========================================

-- 创建数据库（如果不存在）
CREATE DATABASE IF NOT EXISTS `heasec_inputverify` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 使用数据库
USE `heasec_inputverify`;

-- 删除已存在的表（确保重置到初始状态）
DROP TABLE IF EXISTS `heasec_blacklist_bypass_records`;
DROP TABLE IF EXISTS `heasec_blacklist_success_log`;
DROP VIEW IF EXISTS `v_achievement_stats`;
DROP VIEW IF EXISTS `v_detailed_records`;

-- 创建文件上传黑名单绕过记录表
CREATE TABLE IF NOT EXISTS `heasec_blacklist_bypass_records` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `bypass_type` varchar(50) NOT NULL COMMENT '绕过类型：大小写绕过、非常规后缀名绕过、截断绕过',
    `filename` varchar(255) NOT NULL COMMENT '上传的文件名',
    `extension` varchar(20) NOT NULL COMMENT '文件扩展名',
    `file_size` int(11) NOT NULL DEFAULT '0' COMMENT '文件大小（字节）',
    `success_count` int(11) NOT NULL DEFAULT '0' COMMENT '成功使用次数',
    `last_success_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '最后成功时间',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_bypass_type` (`bypass_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='文件上传黑名单绕过成功记录表';

-- 插入基础绕过类型记录（确保三种绕过类型都存在，便于成就系统统计）
INSERT IGNORE INTO `heasec_blacklist_bypass_records`
    (`bypass_type`, `filename`, `extension`, `success_count`) VALUES
    ('大小写绕过', '未发现', 'php', 0),
    ('非常规后缀名绕过', '未发现', 'php5', 0),
    ('截断绕过', '未发现', 'php', 0);

-- 创建成功记录表（用于记录每次成功的绕过尝试）
CREATE TABLE IF NOT EXISTS `heasec_blacklist_success_log` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `bypass_type` varchar(50) NOT NULL COMMENT '绕过类型',
    `filename` varchar(255) NOT NULL COMMENT '上传的文件名',
    `file_size` int(11) NOT NULL DEFAULT '0' COMMENT '文件大小（字节）',
    `ip_address` varchar(45) NOT NULL COMMENT '客户端IP地址',
    `user_agent` varchar(500) DEFAULT NULL COMMENT '用户代理字符串',
    `success_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '成功时间',
    PRIMARY KEY (`id`),
    KEY `idx_bypass_type` (`bypass_type`),
    KEY `idx_success_at` (`success_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='文件上传绕过成功日志表';

-- 显示初始化完成信息
SELECT '文件上传黑名单绕过靶场数据库初始化完成' as message,
       'HeaSec天积安全团队' as team,
       'v1.0.0' as version;
