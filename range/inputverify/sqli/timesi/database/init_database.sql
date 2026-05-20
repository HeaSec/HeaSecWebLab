-- ============================================================
-- HeaSec天积安全团队 - 时间盲注靶场 - 数据库初始化脚本
-- 版本: v2.0.0
-- 数据库: heasec_sqli（与报错注入靶场共享）
-- 表名前缀: heasec_timesi_
-- 说明: 本脚本可重复执行（使用 DROP TABLE IF EXISTS）
--       不影响同数据库下其他靶场的表
-- ============================================================

CREATE DATABASE IF NOT EXISTS heasec_sqli DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_general_ci;
USE heasec_sqli;

-- -----------------------------------------------------------
-- 服务资产表（靶场业务数据）
-- -----------------------------------------------------------
DROP TABLE IF EXISTS heasec_timesi_services;
CREATE TABLE IF NOT EXISTS `heasec_timesi_services` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `service_name` VARCHAR(100) NOT NULL COMMENT '服务名称',
    `service_type` VARCHAR(50) NOT NULL COMMENT '服务类型',
    `status` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '服务状态：1正常 0异常',
    `port` INT UNSIGNED NOT NULL COMMENT '服务端口',
    `description` VARCHAR(255) DEFAULT NULL COMMENT '服务描述',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='时间盲注靶场-服务资产表';

INSERT INTO `heasec_timesi_services` (`service_name`, `service_type`, `status`, `port`, `description`) VALUES
('天积Web应用防火墙', '安全服务', 1, 8080, '提供Web应用安全防护'),
('天积日志分析平台', '分析服务', 1, 9200, '日志收集和分析'),
('天积漏洞扫描引擎', '扫描服务', 1, 4443, '自动化漏洞扫描'),
('天积资产管理系统', '管理服务', 1, 3306, 'IT资产统一管理'),
('天积威胁情报中心', '情报服务', 0, 6379, '威胁情报收集与分析');

-- -----------------------------------------------------------
-- 成就记录表（全局共享模式，无会话依赖）
-- -----------------------------------------------------------
DROP TABLE IF EXISTS heasec_timesi_achievements;
CREATE TABLE IF NOT EXISTS `heasec_timesi_achievements` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `function_name` VARCHAR(50) NOT NULL COMMENT '延迟技术标识（sleep/benchmark/cartesian/other）',
    `success_count` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '成功触发次数',
    `first_success_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '首次成功时间',
    `last_success_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '最后成功时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_function` (`function_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='时间盲注靶场-成就记录表（全局共享）';

-- -----------------------------------------------------------
-- 字符串函数记录表（全局共享模式，无会话依赖）
-- -----------------------------------------------------------
DROP TABLE IF EXISTS heasec_timesi_string_functions;
CREATE TABLE IF NOT EXISTS `heasec_timesi_string_functions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `function_name` VARCHAR(50) NOT NULL COMMENT '字符串函数标识（mid/left/right/ascii/ord/char/hex/conv/instr/locate/position/lpad/rpad/replace/insert/trim/other等）',
    `success_count` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '成功触发次数',
    `first_success_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '首次成功时间',
    `last_success_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '最后成功时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_function` (`function_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='时间盲注靶场-字符串函数记录表（全局共享）';
