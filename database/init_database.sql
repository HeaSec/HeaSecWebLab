-- ========================================
-- HeaSec天积安全团队 - 网址导航系统数据库初始化脚本
-- Database Initialization Script for Navigation CMS
-- 版本: v10.0.0 - 与xlsx数据同步 - 与xlsx数据同步 - 数据库同步版本
-- 创建日期: 2026-02-05
-- 更新日期: 2026-05-07
-- 团队: 天积安全 (HeavenlySecret)
-- 更新说明:
--   - 基于当前数据库状态生成
--   - all_categories 表包含21条记录
--   - links 表包含85条记录
--   - 所有 learning_status 字段重置为 '待学习'
-- ========================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ========================================
-- 1. 删除旧表（如果存在）
-- ========================================

DROP TABLE IF EXISTS `links`;
DROP TABLE IF EXISTS `links_backup`;
DROP TABLE IF EXISTS `third_level_categories`;
DROP TABLE IF EXISTS `subcategories`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `all_categories`;
DROP TABLE IF EXISTS `heasec_batchreg_users`;
DROP TABLE IF EXISTS `admin_users`;
DROP TABLE IF EXISTS `heasec_team_info`;
DROP TABLE IF EXISTS `heasec_sms_log`;
DROP TABLE IF EXISTS `heasec_sms_message`;
DROP TABLE IF EXISTS `heasec_sms_simulator`;

-- ========================================
-- 2. 创建表结构
-- ========================================

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `idx_admin_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员用户表';

CREATE TABLE `heasec_batchreg_users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '用户名',
  `nickname` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '昵称',
  `password` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '密码',
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '手机号',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '注册时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_username` (`username`),
  UNIQUE KEY `idx_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='批量注册靶场用户表';

CREATE TABLE `heasec_team_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `team_name` varchar(50) NOT NULL,
  `team_en_name` varchar(50) NOT NULL,
  `team_abbr` varchar(10) NOT NULL,
  `team_slogan` varchar(100) NOT NULL,
  `version` varchar(20) NOT NULL,
  `build` varchar(10) DEFAULT NULL,
  `security_level` tinyint(1) DEFAULT '1',
  `status` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='HeaSec团队信息表';

CREATE TABLE `all_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) DEFAULT NULL,
  `level` tinyint(4) NOT NULL COMMENT '1:一级 2:二级 3:三级',
  `name` varchar(255) NOT NULL,
  `description` text,
  `code` varchar(100) DEFAULT NULL,
  `sort_order` decimal(10,2) DEFAULT '0.00',
  `status` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_level` (`level`),
  KEY `idx_sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='统一分类表';

CREATE TABLE `links` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `description` text,
  `code` varchar(50) DEFAULT NULL,
  `difficulty` enum('基础','进阶','拓展','实战') NOT NULL DEFAULT '基础',
  `url` varchar(500) NOT NULL,
  `category_id` int(11) DEFAULT NULL COMMENT '关联all_categories.id',
  `sort_order` decimal(10,2) NOT NULL DEFAULT '0.00',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  `learning_status` enum('待学习','学习中','已掌握') NOT NULL DEFAULT '待学习',
  `max_stars` int(11) NOT NULL DEFAULT '0',
  `earned_stars` int(11) NOT NULL DEFAULT '0',
  `max_achievements` int(11) NOT NULL DEFAULT '0',
  `earned_achievements` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_links_code` (`code`),
  KEY `idx_links_category_id` (`category_id`),
  KEY `idx_links_sort_order` (`sort_order`),
  KEY `idx_links_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='链接资源表';

CREATE TABLE `heasec_sms_simulator` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `phone_number` varchar(20) NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `phone_number` (`phone_number`),
  KEY `idx_is_default` (`is_default`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='短信模拟器表';

CREATE TABLE `heasec_sms_message` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `simulator_id` int(11) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `sender` varchar(100) NOT NULL,
  `message_content` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_simulator_id` (`simulator_id`),
  KEY `idx_phone_number` (`phone_number`),
  KEY `idx_is_read` (`is_read`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='短信消息表';

CREATE TABLE `heasec_sms_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `phone_number` varchar(20) NOT NULL,
  `sender` varchar(100) NOT NULL,
  `message_content` text NOT NULL,
  `send_status` varchar(20) NOT NULL DEFAULT '未发送',
  `detail_info` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_phone_number` (`phone_number`),
  KEY `idx_sender` (`sender`),
  KEY `idx_send_status` (`send_status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='短信日志表';

-- ========================================
-- 3. 插入初始化数据
-- ========================================

INSERT INTO `heasec_team_info` (`id`, `team_name`, `team_en_name`, `team_abbr`, `team_slogan`, `version`, `build`, `security_level`, `status`, `created_at`, `updated_at`) VALUES
(1, '天积安全', 'HeavenlySecret', 'HeaSec', '日积寸功，乐享安全', 'v3.3.0', '20251214', 1, 1, '2026-01-09 11:55:59', NULL);

INSERT INTO `admin_users` (`id`, `username`, `password`, `email`, `last_login`, `status`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$gTqlmOjOKKsQJAR4ms0w3uY8HU2prWwaMyxuTwRgLZn8oJKW/pO2C', 'admin@example.com', NULL, 1, '2026-01-09 11:55:59', NULL);

INSERT INTO `heasec_sms_simulator` (`id`, `phone_number`, `is_default`, `status`, `created_at`, `updated_at`) VALUES
(1, '13866668888', 1, 1, '2026-01-12 13:23:05', NULL);

-- 插入分类数据 (all_categories) - 21条记录
INSERT INTO `all_categories` (`id`, `parent_id`, `level`, `name`, `description`, `code`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES
(1101, NULL, 1, 'WEB安全基础知识', 'Web安全的基础知识和学习资源，请点击左侧二级分类开始学习', 'base', 10.00, 1, NOW(), NULL),
(1102, NULL, 1, '输入验证类漏洞', '学习各种输入验证相关的安全漏洞，请点击左侧二级分类开始学习', 'inputverify', 20.00, 1, NOW(), NULL),
(1103, NULL, 1, '业务逻辑类漏洞', '学习各种业务逻辑类漏洞，请点击左侧二级分类开始学习', 'logic', 30.00, 1, NOW(), NULL),
(1104, NULL, 1, '综合实战', '学习面对各类业务系统开展实战安全测试', 'pentest', 40.00, 1, NOW(), NULL),
(2101, 1101, 2, 'HTTP协议基础', '学习HTTP协议的基础知识和相关安全技术', 'http', 10.00, 1, NOW(), NULL),
(2102, 1101, 2, '网站前端代码基础', '学习HTML、JavaScript、CSS等前端基础知识和相关安全技术', 'web_front', 20.00, 1, NOW(), NULL),
(2103, 1101, 2, '服务端语言基础', '学习PHP、SQL等服务端语言的基础知识和相关安全技术', 'server', 30.00, 1, NOW(), NULL),
(2201, 1102, 2, '文件上传', '学习文件上传相关的安全技术', 'upload', 10.00, 1, NOW(), NULL),
(2202, 1102, 2, '命令执行', '学习命令执行/代码执行相关的安全技术', 'rce', 20.00, 1, NOW(), NULL),
(2203, 1102, 2, '跨站脚本注入', '学习跨站脚本注入相关的安全技术', 'xss', 30.00, 1, NOW(), NULL),
(2204, 1102, 2, 'SQL注入', '学习SQL注入相关的安全技术', 'sqli', 40.00, 1, NOW(), NULL),
(2205, 1102, 2, 'XML相关漏洞', '学习XML注入相关的安全技术', 'xml', 50.00, 1, NOW(), NULL),
(2206, 1102, 2, '反序列化相关漏洞', '学习反序列化相关的安全技术', 'deser', 60.00, 1, NOW(), NULL),
(2301, 1103, 2, '绕过验证', '学习绕过验证相关的安全技术', 'bypass_verify', 10.00, 1, NOW(), NULL),
(2302, 1103, 2, '越权访问', '学习越权访问相关的安全技术', 'brokenac', 20.00, 1, NOW(), NULL),
(2303, 1103, 2, '交易篡改', '学习交易篡改相关的安全技术', 'trntamp', 30.00, 1, NOW(), NULL),
(3101, 2201, 3, '文件类型校验绕过', '学习如何绕过文件类型校验', 'type', 10.00, 1, NOW(), NULL),
(3102, 2201, 3, '文件内容校验绕过', '学习如何绕过文件内容校验', 'content', 20.00, 1, NOW(), NULL),
(3103, 2201, 3, '其他场景校验绕过', '学习其他文件上传相关攻防技术', 'other', 30.00, 1, NOW(), NULL),
(3201, 2301, 3, '身份管理类功能', '学习身份管理类功能的绕过验证机制', 'iam', 10.00, 1, NOW(), NULL),
(3202, 2301, 3, 'JWT验证', '学习jwt验证相关的漏洞和攻击技术', 'jwt', 20.00, 1, NOW(), NULL);

-- 插入链接数据 (links) - 85条记录, 所有learning_status字段均为'待学习'
INSERT INTO `links` (`id`, `title`, `description`, `code`, `difficulty`, `url`, `category_id`, `sort_order`, `status`, `created_at`, `updated_at`, `learning_status`, `max_stars`, `earned_stars`, `max_achievements`, `earned_achievements`) VALUES
(1, 'HTTP协议解析', '学习理解HTTP协议的基础知识', 'httpxyjx', '基础', './range/base/http/httpxyjx', 2101, 10.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(2, 'HTTP Accept-Language', '尝试抓包跟踪HTTP请求和响应，理解Accept-Language请求头的作用', 'httpal', '进阶', './range/base/http/httpal', 2101, 20.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(3, 'HTTP user-agent', '尝试抓包跟踪HTTP请求和响应，理解user-agent请求头的作用', 'httpua', '进阶', './range/base/http/httpua', 2101, 30.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(4, 'HTTP Cookie', '尝试抓包跟踪HTTP请求和响应，理解cookie请求头的作用', 'httpck', '进阶', './range/base/http/httpck', 2101, 40.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(5, 'HTTP 代理IP请求头', '尝试抓包跟踪HTTP请求和响应，理解代理IP相关请求头的作用', 'httpxff', '进阶', './range/base/http/httpxff', 2101, 50.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(6, 'CRLF注入', '学习CRLF注入的漏洞利用', 'crlf', '实战', './range/base/http/crlf/index.php?username=heasec', 2101, 60.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(7, 'HTML语言基础', '了解HTML的基本语法、常用标签、属性等', 'html', '基础', './range/base/web_front/html', 2102, 10.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(8, 'HTML前端校验绕过', '尝试绕过HTML前端的校验机制', 'htmlbypass', '进阶', './range/base/web_front/htmlbypass', 2102, 20.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(9, 'JavaScript基础', '了解JavaScript的基本语法', 'javascript', '基础', './range/base/web_front/javascript', 2102, 30.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(10, 'JavaScript 绕过', '学习如何分析JavaScript脚本并绕过前端限制', 'jsbypass', '进阶', './range/base/web_front/jsbypass', 2102, 40.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(11, '服务端语言基础', '学习PHP和SQL的基础知识', 'php_sql', '基础', './range/base/server/php_sql', 2103, 10.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(12, '目录浏览', '学习目录浏览漏洞的利用', 'dirlist', '基础', './range/base/server/dirlist/dirlist.php', 2103, 20.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(13, '路径穿越', '学习路径穿越漏洞的基本原理和利用', 'pathtrvl', '进阶', './range/base/server/pathtrvl', 2103, 30.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(14, '暴力破解基础', '尝试通过抓包工具自动化提交HTTP请求来进行暴力破解攻击', 'brute', '实战', './range/base/server/brute', 2103, 40.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(15, 'URL任意跳转', '学习URL任意跳转漏洞的原理和利用', 'urlredirect', '实战', './range/base/server/urlredirect', 2103, 50.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(16, 'SSRF漏洞', '学习SSRF漏洞的原理和利用', 'ssrf', '实战', './range/base/server/ssrf', 2103, 60.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(17, '文件仅前端文件提示校验', '尝试上传木马文件，理解文件上传漏洞的基础概念。', 'upload_base', '基础', './range/inputverify/upload/type/upload_base', 3101, 10.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(18, '文件前端JavaScript校验', '尝试绕过依赖前端Js机制的文件上传校验，理解"前端Js"校验的局限性。', 'jsverify', '进阶', './range/inputverify/upload/type/jsverify', 3101, 20.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(19, '文件Content-Type校验', '尝试绕过依赖Content-Type机制的文件上传校验，理解"Content-Type"校验的局限性。', 'ctbypass', '进阶', './range/inputverify/upload/type/ctbypass', 3101, 30.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(20, '文件拓展名黑名单校验', '尝试绕过文件拓展名黑名单上传木马文件', 'blacklist', '进阶', './range/inputverify/upload/type/blacklist', 3101, 40.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(21, '文件拓展名白名单校验', '尝试绕过依赖白名单校验但忽略解析漏洞的文件上传系统，理解白名单校验下解析漏洞会导致的危害。', 'whitelist', '进阶', './range/inputverify/upload/type/whitelist', 3101, 50.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(22, '多文件上传校验缺陷', '尝试通过多文件校验缺陷上传木马文件', 'multifile', '进阶', './range/inputverify/upload/type/multifile/', 3101, 60.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(23, '图片文件头校验', '尝试绕过依赖文件头校验但忽略内容拼接的文件上传系统，理解仅文件头校验的局限性。', 'fileheader', '进阶', './range/inputverify/upload/content/fileheader', 3102, 10.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(24, '文件上传WAF对抗校验', '学习文件上传过程中WAF对抗绕过', 'antiwaf', '进阶', './range/inputverify/upload/content/antiwaf', 3102, 20.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(25, '文件目录执行权限绕过校验', '学习文件上传过程中目录执行权限的绕过', 'fileDirectory', '进阶', './range/inputverify/upload/other/fileDirectory', 3103, 10.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(26, '条件竞争上传绕过校验', '学习利用条件竞争方法绕过文件上传校验机制', 'racecondition', '进阶', './range/inputverify/upload/other/racecondition', 3103, 20.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(27, '文件上传综合对抗', '综合利用文件上传绕过技巧上传恶意文件', 'upload_Comprehensive', '实战', './range/inputverify/upload/other/upload_Comprehensive', 3103, 30.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(28, '回显型命令注入', '学习回显型命令注入的相关技术', 'echo_rce', '基础', './range/inputverify/rce/echo_rce', 2202, 10.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(29, '无回显命令注入', '学习无回显命令注入的相关技术', 'blind_rce', '进阶', './range/inputverify/rce/blind_rce', 2202, 20.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(30, '代码注入', '学习代码注入的相关技术', 'code_inj', '进阶', './range/inputverify/rce/code_inj', 2202, 30.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(31, '命令执行实战', '综合利用各种命令执行技术完成实战目标', 'rceadv', '实战', './range/inputverify/rce/rceadv', 2202, 40.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(32, '文件包含基础', '学习文件包含基础', 'lfibase', '基础', './range/inputverify/rce/lfibase', 2202, 50.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(33, '文件包含进阶', '学习文件包含的多种协议利用', 'fiadv', '拓展', './range/inputverify/rce/fiadv', 2202, 60.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(34, '反序列化基础', '学习反序列化基础知识', 'dsbasic', '基础', './range/inputverify/deser/dsbasic', 2206, 10.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(35, '反序列化练习', '练习反序列化的漏洞利用', 'deserbase', '进阶', './range/inputverify/deser/deserbase', 2206, 20.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(36, '反序列化实战', '综合利用各种反序列化技术完成实战目标', 'deseradv', '实战', './range/inputverify/deser/deseradv', 2206, 30.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(37, 'XSS基础分类', '学习反射型、存储型、DOM型三种XSS的基本原理与触发机制', 'xssbasic', '进阶', './range/inputverify/xss/xssbasic', 2203, 10.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(38, '注入Script标签', '绕过不同防御机制注入标签', 'xss_content_filter', '进阶', './range/inputverify/xss/xss_content_filter', 2203, 20.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(39, '标签与事件', '在黑名单限制下探索多种HTML标签与事件处理器组合', 'xss_content_filter2', '进阶', './range/inputverify/xss/xss_content_filter2', 2203, 30.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(40, 'HTML属性注入', '在HTML属性上下文中绕过字符过滤实现注入', 'html_context_filter', '进阶', './range/inputverify/xss/html_context_filter', 2203, 40.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(41, 'JS上下文逃逸', '利用JavaScript字符串拼接不当实现代码逃逸', 'js_context_filter', '进阶', './range/inputverify/xss/js_context_filter', 2203, 50.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(42, '文件相关XSS', '探究文件上传及在线预览功能中潜藏的XSS风险', 'filexss', '进阶', './range/inputverify/xss/filexss', 2203, 60.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(43, 'XSS实战利用', '模拟网页篡改、Cookie盗取、XSS蠕虫传播等真实攻击场景', 'xss_exploit', '实战', './range/inputverify/xss/xss_exploit', 2203, 70.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(44, 'XSS实战利用', '学习XSS与CSRF漏洞结合的利用', 'xss_exploit_csrf', '实战', './range/inputverify/xss/xss_exploit_csrf', 2203, 80.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(45, 'SQL注入基础', '学习SQL注入的基本原理', 'sqlibasic', '基础', './range/inputverify/sqli/sqlibasic', 2204, 10.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(46, 'SQL注入练习', '尝试发现不同的SQL注入漏洞', 'sqlibase', '基础', './range/inputverify/sqli/sqlibase', 2204, 20.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(47, 'SQL盲注', '学习基础的SQL盲注技巧', 'blindinj', '进阶', './range/inputverify/sqli/blindinj', 2204, 30.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(48, '报错注入', '学习不同的SQL报错注入语句', 'errsi', '进阶', './range/inputverify/sqli/errsi', 2204, 40.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(49, '时间盲注', '学习不同的时间盲注语句', 'timesi', '进阶', './range/inputverify/sqli/timesi', 2204, 50.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(50, '不同语句注入', '学习在不同类型的语句和位置进行注入', 'cuosi', '进阶', './range/inputverify/sqli/cuosi', 2204, 60.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(52, '特殊字符过滤', '学习如何绕过特殊字符过滤进行注入', 'symbol', '实战', './range/inputverify/sqli/symbol', 2204, 70.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(53, '关键字过滤', '学习如何绕过关键字过滤进行注入', 'kwbpsi', '实战', './range/inputverify/sqli/kwbpsi', 2204, 80.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(51, '盲注进阶', '学习如何绕过过滤机制进行盲注', 'bsiadv', '实战', './range/inputverify/sqli/bsiadv', 2204, 90.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(54, '特殊的SQL注入', '学习特殊的SQL注入技巧', 'specsi', '实战', './range/inputverify/sqli/specsi', 2204, 100.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(55, 'SQL注入综合实战', '学习综合使用多种技巧完成SQL注入', 'mixedsi', '实战', './range/inputverify/sqli/mixedsi', 2204, 110.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(56, 'XXE基础漏洞', '学习XXE漏洞的基本利用方式', 'xxebase', '进阶', './range/inputverify/xml/xxebase', 2205, 10.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(57, 'XXE绕过', '学习XXE漏洞的利用和绕过方式', 'xxebypass', '进阶', './range/inputverify/xml/xxebypass', 2205, 20.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(58, 'SOAP与XML', '学习SOAP下的XML注入', 'soapxml', '拓展', './range/inputverify/xml/soapxml', 2205, 30.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(59, '基础流程绕过', '学习验证机制和要执行的操作没有在同一个请求流程时的绕过方法', 'bvbase', '基础', './range/logic/bypass_verify/bvbase', 2301, 10.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(60, '图片验证码绕过1', '学习图片验证码绕过的基础方法', 'imgcodebp1', '进阶', './range/logic/bypass_verify/imgcodebp1', 2301, 20.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(61, '图片验证码绕过2', '学习通过参数篡改的方式绕过图片验证码', 'imgcodebp2', '进阶', './range/logic/bypass_verify/imgcodebp2', 2301, 30.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(62, '短信验证码绕过', '学习通过篡改接收方的方式绕过短信验证码', 'smsbypass', '进阶', './range/logic/bypass_verify/smsbypass', 2301, 40.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(63, '用户枚举', '学习用户枚举漏洞的利用', 'userenum', '基础', './range/logic/bypass_verify/iam/userenum', 3201, 5.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(64, '会话安全', '学习常见的会话安全问题', 'session', '进阶', './range/logic/bypass_verify/iam/session', 3201, 6.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(65, '密码重置凭证可猜测', '学习绕过基于密码重置链接的密码重置机制', 'resetlink', '进阶', './range/logic/bypass_verify/iam/resetlink', 3201, 10.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(66, '密码重置之会话覆盖', '学习通过会话覆盖攻击绕过密码重置限制', 'sessionoverride', '进阶', './range/logic/bypass_verify/iam/sessionoverride', 3201, 20.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(67, '暴力破解之前端加密', '学习在各种前端加密的情况下完成暴力破解操作', 'bruteenc', '进阶', './range/logic/bypass_verify/iam/bruteenc', 3201, 25.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(68, '密码重置之流程绕过', '学习在没有可用账号的情况下，通过流程绕过的方式实现任意账号密码重置', 'resetstepbp', '实战', './range/logic/bypass_verify/iam/resetstepbp', 3201, 30.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(69, '批量注册', '综合运用所学技巧，尝试绕过验证机制批量注册账号', 'batchreg', '实战', './range/logic/bypass_verify/iam/batchreg', 3201, 40.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(70, '用户覆盖', '尝试绕过注册环节的验证机制，覆盖已注册用户账号密码', 'useroverride', '实战', './range/logic/bypass_verify/iam/useroverride', 3201, 50.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(71, 'JWT基础', '学习JWT验证的基础知识', 'jwtbasic', '基础', './range/logic/bypass_verify/jwt/jwtbasic', 3202, 10.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(72, 'JWT简单漏洞', '学习JWT的简单漏洞利用', 'jwtvul', '基础', './range/logic/bypass_verify/jwt/jwtvul', 3202, 20.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(73, 'JWT签名算法绕过', '学习JWT签名算法绕过相关的攻击技术', 'jwtalg', '进阶', './range/logic/bypass_verify/jwt/jwtalg', 3202, 30.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(74, 'JWT密钥注入', '学习JWT秘钥注入相关的攻击技术', 'jwtkey', '实战', './range/logic/bypass_verify/jwt/jwtkey', 3202, 40.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(75, '水平越权基础', '学习基础的水平越权技术', 'idref', '基础', './range/logic/brokenac/idref', 2302, 10.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(76, '垂直越权基础', '学习基础的垂直越权', 'vertbp', '基础', './range/logic/brokenac/vertbp', 2302, 20.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(77, '未授权访问', '学习常见的未授权访问漏洞', 'noauth', '进阶', './range/logic/brokenac/noauth', 2302, 30.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(78, '文件越权访问', '学习文件越权访问技术', 'filebac', '进阶', './range/logic/brokenac/filebac', 2302, 40.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(79, '越权访问综合实战', '综合学习到的各种越权访问技术进行实战漏洞挖掘', 'privesc', '实战', './range/logic/brokenac/privesc', 2302, 50.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(80, '金额篡改', '学习金额篡改的基础技术', 'amttamp', '基础', './range/logic/trntamp/amttamp', 2303, 10.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(81, '异常数据', '学习异常数据攻击', 'anomdata', '基础', './range/logic/trntamp/anomdata', 2303, 20.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(82, '重放攻击', '学习重放攻击技术', 'replay', '进阶', './range/logic/trntamp/replay', 2303, 30.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(83, '三方支付漏洞', '学习常见的第三方支付漏洞利用', '3rdpay', '进阶', './range/logic/trntamp/3rdpay', 2303, 40.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(84, '优惠滥用', '学习优惠机制相关漏洞利用', 'discount', '实战', './range/logic/trntamp/discount', 2303, 50.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0),
(85, '商城系统综合实战', '综合运用所学技巧，对商城系统进行实战漏洞挖掘', 'shop', '实战', './range/pentest/shop', 1104, 10.00, 1, NOW(), NULL, '待学习', 0, 0, 0, 0);

SET FOREIGN_KEY_CHECKS = 1;
-- 数据库初始化完成
