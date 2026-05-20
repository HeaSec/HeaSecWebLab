-- SQL注入基础靶场 - 数据库初始化脚本
-- 数据库：heasec_sqlinject
-- 注意：仅操作 heasec_sqlibase_ 前缀的表，不影响其他靶场数据

CREATE DATABASE IF NOT EXISTS `heasec_sqlinject` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `heasec_sqlinject`;

-- 删除旧表（按依赖倒序）
DROP TABLE IF EXISTS `heasec_sqlibase_star_status`;
DROP TABLE IF EXISTS `heasec_sqlibase_vuln_records`;
DROP TABLE IF EXISTS `heasec_sqlibase_visit_logs`;
DROP TABLE IF EXISTS `heasec_sqlibase_feedback`;
DROP TABLE IF EXISTS `heasec_sqlibase_preferences`;
DROP TABLE IF EXISTS `heasec_sqlibase_articles`;
DROP TABLE IF EXISTS `heasec_sqlibase_categories`;
DROP TABLE IF EXISTS `heasec_sqlibase_users`;

-- 用户表
CREATE TABLE IF NOT EXISTS `heasec_sqlibase_users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(50) NOT NULL COMMENT '用户名',
    `password` VARCHAR(100) NOT NULL COMMENT '密码（明文，教学用途）',
    `name` VARCHAR(50) NOT NULL COMMENT '姓名',
    `role` ENUM('admin','editor','user') NOT NULL DEFAULT 'user' COMMENT '角色',
    `status` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '状态：1启用 0禁用',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户表';

-- 资讯分类表
CREATE TABLE IF NOT EXISTS `heasec_sqlibase_categories` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL COMMENT '分类名称',
    `description` VARCHAR(200) DEFAULT NULL COMMENT '分类描述',
    `sort_order` INT NOT NULL DEFAULT 0 COMMENT '排序权重',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='资讯分类表';

-- 资讯表
CREATE TABLE IF NOT EXISTS `heasec_sqlibase_articles` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(200) NOT NULL COMMENT '标题',
    `content` TEXT NOT NULL COMMENT '内容',
    `category_id` INT UNSIGNED NOT NULL COMMENT '分类ID',
    `author_id` INT UNSIGNED NOT NULL COMMENT '作者ID',
    `status` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '状态：1发布 0草稿',
    `view_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '浏览量',
    `publish_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '发布时间',
    PRIMARY KEY (`id`),
    KEY `idx_category` (`category_id`),
    KEY `idx_author` (`author_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='资讯表';

-- 意见反馈表
CREATE TABLE IF NOT EXISTS `heasec_sqlibase_feedback` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `category_id` INT UNSIGNED NOT NULL COMMENT '分类ID',
    `content` TEXT NOT NULL COMMENT '反馈内容',
    `screenshot` VARCHAR(255) DEFAULT NULL COMMENT '截图路径',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='意见反馈表';

-- 用户偏好设置表
CREATE TABLE IF NOT EXISTS `heasec_sqlibase_preferences` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `pref_key` VARCHAR(100) NOT NULL COMMENT '偏好标识',
    `per_page` INT UNSIGNED NOT NULL DEFAULT 10 COMMENT '每页显示条数',
    `theme` VARCHAR(20) NOT NULL DEFAULT 'blue' COMMENT '主题',
    `language` VARCHAR(10) NOT NULL DEFAULT 'zh-CN' COMMENT '语言',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_pref_key` (`pref_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户偏好设置表';

-- 访问日志表
CREATE TABLE IF NOT EXISTS `heasec_sqlibase_visit_logs` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_agent` VARCHAR(500) NOT NULL COMMENT 'User-Agent',
    `visit_count` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '访问次数',
    `first_visit` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '首次访问',
    `last_visit` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '最近访问',
    PRIMARY KEY (`id`),
    KEY `idx_ua` (`user_agent`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='访问日志表';

-- 漏洞记录表
CREATE TABLE IF NOT EXISTS `heasec_sqlibase_vuln_records` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `vuln_id` VARCHAR(100) NOT NULL COMMENT '漏洞标识',
    `score` INT NOT NULL COMMENT '漏洞得分',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '提交时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_vuln_id` (`vuln_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='SQL注入基础靶场漏洞记录表（全局共享）';

-- 星星状态表
CREATE TABLE IF NOT EXISTS `heasec_sqlibase_star_status` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `unlocked_stars` INT NOT NULL DEFAULT 0 COMMENT '已解锁的星星数量',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='SQL注入基础靶场星星状态表（全局共享）';

-- 用户数据
INSERT INTO `heasec_sqlibase_users` (`username`, `password`, `name`, `role`, `status`) VALUES
('admin',  'admin123',  '林启航', 'admin',  1),
('editor', 'editor123', '周思远', 'editor', 1),
('user',   'user123',   '陈晓明', 'user',   1);

-- 分类数据
INSERT INTO `heasec_sqlibase_categories` (`name`, `description`, `sort_order`) VALUES
('技术前沿', '最新技术动态与前沿研究',     1),
('安全资讯', '网络安全事件与防护技术资讯', 2),
('产品动态', '天积安全产品发布与更新动态', 3);

-- 资讯数据
INSERT INTO `heasec_sqlibase_articles` (`title`, `content`, `category_id`, `author_id`, `status`, `view_count`, `publish_date`) VALUES
('AI驱动的自动化安全测试工具发布', '天积安全正式发布新一代AI驱动的自动化安全测试平台，该平台集成了机器学习算法，能够智能识别Web应用中的安全漏洞。测试结果显示，相比传统扫描工具，新平台的漏洞检出率提升了45%，误报率降低了60%。', 1, 1, 1, 328, '2026-04-15 09:00:00'),
('2026年Q1网络安全威胁态势报告', '根据天积安全实验室的监测数据，2026年第一季度SQL注入攻击仍占据Web攻击事件的32%，其次是XSS跨站脚本攻击占21%。报告建议企业加强输入验证和参数化查询的使用。', 2, 1, 1, 512, '2026-04-10 14:30:00'),
('云原生架构安全最佳实践指南', '本文详细介绍了在云原生环境下保障应用安全的关键措施，包括容器安全、微服务间通信加密、API网关安全策略等。特别强调了在CI/CD流程中集成安全扫描的重要性。', 1, 2, 1, 256, '2026-04-08 10:15:00'),
('供应链安全攻击防护方案', '近期频发的供应链攻击事件引起了行业广泛关注。天积安全推出了一套完整的供应链安全检测与防护方案，涵盖第三方组件漏洞扫描、依赖关系分析和实时威胁监控。', 2, 1, 1, 189, '2026-04-05 16:45:00'),
('HeaSec漏洞扫描引擎v3.2更新说明', 'HeaSec漏洞扫描引擎v3.2版本正式发布，新增了对GraphQL注入、Server-Side Request Forgery等新型漏洞的检测能力，扫描性能提升了30%。', 3, 2, 1, 421, '2026-04-03 11:00:00'),
('Web应用防火墙规则优化实战', '本文分享了在实际生产环境中优化WAF规则的实战经验，包括如何平衡安全防护与业务可用性，以及如何通过自定义规则有效防护SQL注入和XSS攻击。', 2, 2, 1, 167, '2026-03-28 09:30:00'),
('零信任架构落地实施案例分析', '某大型金融机构采用天积安全零信任架构解决方案的实施案例。项目历时6个月，覆盖了2000+员工终端和500+应用系统，实施后内部安全事件减少了78%。', 1, 1, 1, 345, '2026-03-25 15:20:00'),
('移动应用安全加固方案发布', '天积安全推出面向移动应用的安全加固方案，支持Android和iOS平台，提供代码混淆、反调试、完整性校验等多层次防护能力。', 3, 2, 1, 203, '2026-03-20 10:00:00');

-- 偏好设置数据
INSERT INTO `heasec_sqlibase_preferences` (`pref_key`, `per_page`, `theme`, `language`) VALUES
('default', 10, 'blue', 'zh-CN');

-- 访问日志数据
INSERT INTO `heasec_sqlibase_visit_logs` (`user_agent`, `visit_count`, `first_visit`, `last_visit`) VALUES
('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 15, '2026-04-01 08:30:00', '2026-04-22 17:45:00'),
('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)', 8, '2026-04-10 14:20:00', '2026-04-21 09:15:00'),
('python-requests/2.28.0', 3, '2026-04-15 22:10:00', '2026-04-20 11:30:00');

-- 星星状态数据
INSERT IGNORE INTO `heasec_sqlibase_star_status` (`id`, `unlocked_stars`) VALUES
(1, 0);
