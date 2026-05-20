<?php
/**
 * HeaSec天积安全团队 - 服务端脚本基础靶场
 * 版本: v1.0.0
 * 创建日期: 2025-12-30
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 服务端脚本基础 Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = '服务端脚本基础靶场';
$rangeName = '服务端脚本基础';
$showVersion = false;
$showResetButton = true;
$version = 'v1.0.0';

// 设置数据库相关变量（用于公共组件识别）
$initSqlFile = 'database/init_database.sql';
$databaseName = 'heasec_base';
$useDatabase = true;

// 设置公共组件的基础路径
$commonBasePath = '../../../common/';

// 定义常量允许访问公共组件
define('HEASEC_RANGE_ACCESS', true);

// ==================== 自定义重置处理逻辑 ====================
// 在引入公共header之前处理重置请求，以便添加清理上传目录的功能
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && ($_GET['action'] === 'reset' || $_GET['action'] === 'init')) {
    header('Content-Type: application/json');

    try {
        // 引入数据库组件
        require_once $commonBasePath . 'includes/HeaSec_Database.php';

        // 1. 清理上传目录
        $uploadsDir = __DIR__ . '/examples/uploads';
        $cleanedFiles = 0;

        if (is_dir($uploadsDir)) {
            // 递归删除uploads目录下的所有文件
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($uploadsDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($iterator as $file) {
                if ($file->isDir()) {
                    // 删除空目录
                    @rmdir($file->getPathname());
                } else {
                    // 删除文件
                    if (@unlink($file->getPathname())) {
                        $cleanedFiles++;
                    }
                }
            }
        }

        // 2. 执行数据库重置（使用公共组件的数据库类）
        $initSqlFile = 'database/init_database.sql';

        if (file_exists($initSqlFile)) {
            $pdo = HeaSec_Database::getServerConnection();
            $sqlContent = file_get_contents($initSqlFile);

            // 移除注释并分割SQL语句
            $sqlContent = preg_replace('/--.*$/m', '', $sqlContent);
            $sqlContent = preg_replace('/\/\*.*?\*\//s', '', $sqlContent);
            $sqlStatements = array_filter(array_map('trim', explode(';', $sqlContent)));

            $pdo->beginTransaction();

            foreach ($sqlStatements as $sql) {
                if (!empty($sql)) {
                    try {
                        $pdo->exec($sql);
                    } catch (Exception $e) {
                        error_log('[HeaSec] 执行SQL语句失败: ' . $e->getMessage() . '; SQL: ' . $sql);
                    }
                }
            }

            $pdo->commit();
        }

        $action = $_GET['action'];
        $message = ($action === 'init') ? "数据库初始化成功，清理了 {$cleanedFiles} 个文件" : "重置成功，清理了 {$cleanedFiles} 个文件";
        echo json_encode(['success' => true, 'message' => $message]);

    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['success' => false, 'message' => '[HeaSec] ' . $e->getMessage()]);
    }
    exit;
}

// ==================== 动态读取示例文件内容的辅助函数 ====================
/**
 * 读取文件内容并进行HTML实体编码，用于data-code属性
 * @param string $filename 示例文件名
 * @return string HTML实体编码后的文件内容
 */
function getEncodedExampleCode($filename)
{
    $filepath = __DIR__ . '/examples/' . $filename;
    if (file_exists($filepath)) {
        $content = file_get_contents($filepath);
        // 进行HTML实体编码
        $encoded = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
        // 将换行符转换为 \n 字符串（JavaScript会将其转换回实际换行）
        $encoded = str_replace("\r\n", "\n", $encoded); // 统一换行符
        $encoded = str_replace("\n", "\\n", $encoded);  // 转换为 \n 字符
        return $encoded;
    }
    return '';
}

// 引入公共头部
require_once $commonBasePath . 'includes/header.php';
?>

<!-- 引入自定义样式 -->
<link rel="stylesheet" href="./css/style.css?v=v1.0.4">

<!-- 引入Prism.js CSS -->
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>assets/css/prism-tomorrow.min.css" />

<!-- 引入星星系统组件的CSS样式 -->
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>components/star-system/css/heasec-congrats-modal.css">

<!-- 靶场主要内容 -->
<div class="heasec-container">

    <!-- 区域0: 服务端基本工作原理 -->
    <div class="collapsible-section expanded" id="section0">
        <div class="collapsible-header" onclick="toggleSection('section0')">
            <span class="toggle-text">
                <i class="fa fa-server"></i> 服务端基本工作原理（动态页面生成）
            </span>
            <div class="toggle-btn"></div>
        </div>
        <div class="collapsible-content">
            <div class="chapter-title">
                <i class="fa fa-sitemap"></i> 动态页面的数据流向
            </div>
            <div class="chapter-description">
                与只包含固定HTML/CSS的静态网页不同，现代Web应用的核心是<strong>动态生成</strong>内容。服务端脚本（如PHP）可以根据用户的实时请求、数据库中的最新数据，动态"拼装"出HTML页面并返回给用户。以下展示了典型的三层架构中，处理一个查询请求的完整动态数据流闭环。
            </div>

            <div class="architecture-container">
                <!-- 动态数据流交互示意图 -->
                <div class="interaction-diagram">
                    <!-- 客户端 -->
                    <div class="diagram-node client-node" style="width: 150px;">
                        <div class="node-layer-label"><small>表示层</small></div>
                        <div class="node-icon">💻</div>
                        <div class="node-name">客户端浏览器</div>
                        <div class="node-tech-stack">
                            <span class="tech-tag">HTML</span>
                            <span class="tech-tag">CSS</span>
                            <span class="tech-tag">JS</span>
                        </div>
                        <div class="node-examples" style="margin-top: 15px;">
                            <div style="margin-bottom: 5px; color: #4a5568; font-weight: 600;">解析渲染展示表单：</div>
                            <table class="mini-render-table">
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Age</th>
                                </tr>
                                <tr>
                                    <td>1</td>
                                    <td>张三</td>
                                    <td>25</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- 交互：客户端 <-> 应用服务器 -->
                    <div class="diagram-flow">
                        <div class="flow-step request-step">
                            <div class="step-label-wrapper">
                                <div class="step-number inline-step">1</div>
                                <div class="step-label">HTTP 请求 (例如: ?id=1)</div>
                            </div>
                            <div class="step-detail">通过URL或表单提交参数</div>
                            <div class="step-arrow right-arrow">
                                <div class="arrow-line"></div>
                                <div class="arrow-head"></div>
                            </div>
                        </div>
                        <div class="flow-step response-step">
                            <div class="step-arrow left-arrow">
                                <div class="arrow-head"></div>
                                <div class="arrow-line"></div>
                            </div>
                            <div class="step-label-wrapper">
                                <div class="step-number inline-step">4</div>
                                <div class="step-label">HTTP 响应 (原生HTML代码)</div>
                            </div>
                            <div class="step-detail">
                                <div class="step-code-snippet">
                                    <div>&lt;table&gt;</div>
                                    <div style="padding-left: 10px;">
                                        &lt;tr&gt;&lt;th&gt;ID&lt;/th&gt;&lt;th&gt;User&lt;/th&gt;&lt;th&gt;Age&lt;/th&gt;&lt;/tr&gt;
                                    </div>
                                    <div style="padding-left: 10px;">
                                        &lt;tr&gt;&lt;td&gt;1&lt;/td&gt;&lt;td&gt;张三&lt;/td&gt;&lt;td&gt;25&lt;/td&gt;&lt;/tr&gt;
                                    </div>
                                    <div>&lt;/table&gt;</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 应用服务器 -->
                    <div class="diagram-node server-node">
                        <div class="node-layer-label"><small>逻辑层</small></div>
                        <div class="node-icon">⚙️</div>
                        <div class="node-name">Web应用服务器</div>
                        <div class="node-tech-stack">
                            <span class="tech-tag server">PHP引擎</span>
                            <span class="tech-tag server">Apache/Nginx</span>
                        </div>
                        <div class="node-examples" style="margin-top: 15px;">
                            <div style="margin-bottom: 5px; color: #4a5568; font-weight: 600;">执行业务逻辑计算并将数据填入HTML模板（渲染）
                            </div>
                        </div>
                    </div>

                    <!-- 交互：应用服务器 <-> 数据库服务器 -->
                    <div class="diagram-flow">
                        <div class="flow-step request-step">
                            <div class="step-label-wrapper">
                                <div class="step-number inline-step">2</div>
                                <div class="step-label">构造并发送 SQL 查询</div>
                            </div>
                            <div class="step-detail">SELECT * FROM users WHERE id=1</div>
                            <div class="step-arrow right-arrow">
                                <div class="arrow-line" style="background: linear-gradient(90deg, #ed8936, #9f7aea);">
                                </div>
                                <div class="arrow-head" style="border-left-color: #9f7aea;"></div>
                            </div>
                        </div>

                        <div class="flow-step response-step">
                            <div class="step-arrow left-arrow">
                                <div class="arrow-head" style="border-right-color: #ed8936;"></div>
                                <div class="arrow-line" style="background: linear-gradient(90deg, #4A90E2, #ed8936);">
                                </div>
                            </div>
                            <div class="step-label-wrapper">
                                <div class="step-number inline-step">3</div>
                                <div class="step-label">返回 数据结果集</div>
                            </div>
                            <div class="step-detail">如: ["name"=>"张三", "age"=>25]</div>
                        </div>
                    </div>

                    <!-- 数据库服务器 -->
                    <div class="diagram-node database-node">
                        <div class="node-layer-label"><small>数据层</small></div>
                        <div class="node-icon">🗄️</div>
                        <div class="node-name">数据库服务器</div>
                        <div class="node-tech-stack">
                            <span class="tech-tag db">MySQL</span>
                            <span class="tech-tag db">SQL Server</span>
                        </div>
                        <div class="node-examples" style="margin-top: 15px;">
                            <div style="margin-bottom: 5px; color: #4a5568; font-weight: 600;">执行查询并检索磁盘数据</div>
                        </div>
                    </div>
                </div>

                <div class="section-separator" style="margin: 35px 0;"></div>

                <h4 class="sub-section-title">静态网页 vs 动态网页 核心对比</h4>

                <table class="comparison-table">
                    <thead>
                        <tr>
                            <th width="15%">特性</th>
                            <th width="42%">静态网页 (仅HTML/CSS/JS)</th>
                            <th width="43%">动态网页 (结合PHP/数据库)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>内容生成方式</strong></td>
                            <td>事先写好的固定文件（如 .html），服务器直接读取并原样返回。</td>
                            <td class="highlight">请求时由服务端脚本（如 .php）实时执行计算和拼接后，生成全新的HTML返回。</td>
                        </tr>
                        <tr>
                            <td><strong>数据存储</strong></td>
                            <td>数据直接硬编码（Hardcode）写死在HTML文件的标签内容中。</td>
                            <td class="highlight">数据主要存储在数据库（如MySQL）中，实现了数据与展示层代码的分离。</td>
                        </tr>
                        <tr>
                            <td><strong>交互性与个性化</strong></td>
                            <td>所有访问者看到的内容完全相同，功能有限。</td>
                            <td class="highlight">可以实现用户登录、发帖留言等功能，不同用户访问同一网址可以看到根据自身权限和请求参数定做的特定内容。</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 区域1: PHP基本语法展示 -->
    <div class="collapsible-section" id="section1">
        <div class="collapsible-header" onclick="toggleSection('section1')">
            <span class="toggle-text">
                <i class="fa fa-code"></i> PHP基本语法
            </span>
            <div class="toggle-btn"></div>
        </div>
        <div class="collapsible-content">
            <div class="chapter-title">
                <i class="fa fa-sitemap"></i> PHP语言的基本语法
            </div>
            <div class="chapter-description">
                PHP（Hypertext Preprocessor，超文本预处理器）是一种广泛使用的开源服务端脚本语言，特别适合Web开发。PHP代码在服务器上执行，生成HTML发送给客户端浏览器。
            </div>

            <div class="php-syntax-display">
                <h4><i class="fa fa-file-code-o"></i> PHP代码结构</h4>
                <pre><code class="language-php">&lt;?php
    // 这是一个单行注释
    $message = "Hello, World!"; // 声明变量并赋值
    echo $message; // 输出内容

    /*
    这是一个多行注释
    可以跨越多行
    用于详细说明代码
    */
?&gt;</code></pre>

                <div class="syntax-explanation">
                    <h5><i class="fa fa-info-circle"></i> 📌 基本规则说明：</h5>
                    <ul>
                        <li><strong>开始标签</strong>：<code>&lt;?php</code> 标记PHP代码的开始</li>
                        <li><strong>结束标签</strong>：<code>?&gt;</code> 标记PHP代码的结束（在纯PHP文件中通常省略）</li>
                        <li><strong>语句</strong>：每条语句以分号结束（必须）</li>
                        <li><strong>注释</strong>：单行注释用 <code>//</code>，多行注释用 <code>/* */</code></li>
                        <li><strong>变量</strong>：以美元符号 <code>$</code> 开头</li>
                        <li><strong>大小写敏感</strong>：变量名区分大小写，函数名不区分大小写</li>
                    </ul>
                </div>

                <h4><i class="fa fa-file-code-o"></i> PHP在HTML中的嵌入方式</h4>
                <pre><code class="language-php">&lt;!DOCTYPE html&gt;
&lt;html&gt;
&lt;head&gt;
    &lt;title&gt;PHP与HTML混合&lt;/title&gt;
&lt;/head&gt;
&lt;body&gt;
    &lt;?php
        $name = "张三";
        $fileContent = "这是文件内容";
        $isLoggedIn = true;
    ?&gt;

    &lt;!-- 方式1: 简单输出 --&gt;
    &lt;p&gt;欢迎，&lt;?php echo $name; ?&gt;!&lt;/p&gt;

    &lt;!-- 方式2: 短标签输出（echo的简写） --&gt;
    &lt;p&gt;当前时间：&lt;?= date('Y-m-d H:i:s') ?&gt;&lt;/p&gt;

    &lt;!-- 方式3: 条件输出语法 --&gt;
    &lt;?php if ($fileContent !== ''): ?&gt;
        &lt;div class="file-content"&gt;
            &lt;h3&gt;文件内容&lt;/h3&gt;
            &lt;p&gt;&lt;?php echo htmlspecialchars($fileContent); ?&gt;&lt;/p&gt;
        &lt;/div&gt;
    &lt;?php else: ?&gt;
        &lt;p&gt;文件内容为空&lt;/p&gt;
    &lt;?php endif; ?&gt;

    &lt;!-- 方式4: 条件输出（省略else） --&gt;
    &lt;?php if ($isLoggedIn): ?&gt;
        &lt;a href="#"&gt;退出登录&lt;/a&gt;
    &lt;?php endif; ?&gt;
&lt;/body&gt;
&lt;/html&gt;</code></pre>

                <div class="syntax-explanation">
                    <h5><i class="fa fa-info-circle"></i> 📌 嵌入方式说明：</h5>
                    <ul>
                        <li><strong>灵活嵌入</strong>：PHP可以在HTML的任意位置嵌入</li>
                        <li><strong>标准标签</strong>：<code>&lt;?php ?&gt;</code> 是标准的PHP标签</li>
                        <li><strong>短标签输出</strong>：<code>&lt;?= ?&gt;</code> 等同于 <code>&lt;?php echo ?&gt;</code></li>
                        <li><strong>条件输出语法</strong>：<code>&lt;?php if (): ?&gt; ... &lt;?php endif; ?&gt;当满足条件时执行相关代码，可用于输出也可仅执行代码块</code>
                        </li>
                    </ul>
                </div>

                <h4><i class="fa fa-cogs"></i> PHP变量和数据类型</h4>
                <pre><code class="language-php">&lt;?php
    // 变量声明
    $name = "张三";      // 字符串
    $age = 25;          // 整数
    $height = 1.75;     // 浮点数
    $isStudent = true;  // 布尔值

    // 数组
    $fruits = array("苹果", "香蕉", "橙子");

    // 关联数组
    $person = array(
        "name" => "李四",
        "age" => 30,
        "city" => "北京"
    );

    // 输出变量
    echo $name . " 今年 " . $age . " 岁";
?&gt;</code></pre>

                <div class="syntax-explanation">
                    <h5><i class="fa fa-info-circle"></i> 📌 变量说明：</h5>
                    <ul>
                        <li><strong>变量名</strong>：必须以 <code>$</code> 开头，区分大小写</li>
                        <li><strong>数据类型</strong>：字符串、整数、浮点数、布尔值、数组、对象、NULL</li>
                        <li><strong>数组</strong>：可以使用 <code>array()</code> 或 <code>[]</code> 创建</li>
                    </ul>
                </div>

                <h4><i class="fa fa-code-branch"></i> PHP控制语句</h4>
                <pre><code class="language-php">&lt;?php
    // if-else条件语句
    $score = 85;

    if ($score >= 90) {
        echo "优秀";
    } elseif ($score >= 80) {
        echo "良好";  // 执行这个
    } elseif ($score >= 60) {
        echo "及格";
    } else {
        echo "不及格";
    }

    // for循环
    for ($i = 0; $i < 5; $i++) {
        echo "循环次数：" . $i . "&lt;br&gt;";
    }

    // foreach循环（遍历数组）
    $fruits = array("苹果", "香蕉", "橙子");
    foreach ($fruits as $fruit) {
        echo $fruit . "&lt;br&gt;";
    }

    // 三元运算符（简化if-else）
    $age = 20;
    $status = ($age >= 18) ? "成年人" : "未成年人";
    echo $status . "&lt;br&gt;"; // 输出：成年人

    // 三元运算符嵌套
    $score = 75;
    $grade = ($score >= 90) ? "优秀" : (($score >= 60) ? "及格" : "不及格");
    echo $grade . "&lt;br&gt;"; // 输出：及格

    // 三元运算符简写（PHP 5.3+）
    $username = "张三";
    echo $username ?: "游客"; // 如果$username为空，则输出"游客"
?&gt;</code></pre>

                <div class="syntax-explanation">
                    <h5><i class="fa fa-info-circle"></i> 📌 控制语句要点：</h5>
                    <ul>
                        <li><strong>条件语句</strong>：<code>if-elseif-else</code> 用于多条件判断</li>
                        <li><strong>循环语句</strong>：<code>for</code> 用于已知次数，<code>foreach</code> 专门用于遍历数组</li>
                        <li><strong>三元运算符</strong>：<code>条件 ? 真值 : 假值</code> 用于简化简单的if-else语句</li>
                        <li><strong>三元运算符简写</strong>：<code>$var ?: 默认值</code> 当变量为空时使用默认值（PHP 5.3+）</li>
                        <li><strong>循环控制</strong>：<code>break</code> 退出循环，<code>continue</code> 跳过当前迭代</li>
                        <li><strong>注意</strong>：三元运算符可嵌套，但嵌套过多会影响代码可读性</li>
                    </ul>
                </div>

                <h4><i class="fa fa-cogs"></i> PHP函数</h4>
                <pre><code class="language-php">&lt;?php
    // 函数定义
    function greet($name) {
        return "你好，" . $name . "！";
    }

    // 函数调用
    $message = greet("张三");
    echo $message . "&lt;br&gt;"; // 输出：你好，张三！

    // 带多个参数的函数
    function calculateArea($width, $height) {
        return $width * $height;
    }

    $area = calculateArea(10, 5);
    echo "矩形面积：" . $area;
?&gt;</code></pre>

                <div class="syntax-explanation">
                    <h5><i class="fa fa-info-circle"></i> 📌 函数特性说明：</h5>
                    <ul>
                        <li><strong>函数定义</strong>：使用 <code>function</code> 关键字定义</li>
                        <li><strong>参数传递</strong>：默认是值传递</li>
                        <li><strong>返回值</strong>：使用 <code>return</code> 语句返回</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- 区域2: SQL基本语法展示 -->
    <div class="collapsible-section" id="section2">
        <div class="collapsible-header" onclick="toggleSection('section2')">
            <span class="toggle-text">
                <i class="fa fa-database"></i> SQL基本语法
            </span>
            <div class="toggle-btn"></div>
        </div>
        <div class="collapsible-content">
            <div class="chapter-title">
                <i class="fa fa-sitemap"></i> SQL语言的基本语法
            </div>
            <div class="chapter-description">
                SQL（Structured Query Language，结构化查询语言）是一种用于管理关系型数据库的标准语言。SQL可以用于查询、插入、更新和删除数据，以及创建和管理数据库对象。
            </div>

            <div class="sql-syntax-display">
                <h4><i class="fa fa-file-code-o"></i> SQL代码结构</h4>
                <pre><code class="language-sql">-- 这是一个单行注释
SELECT username, email FROM users WHERE age > 18; -- 查询语句

/*
这是一个多行注释
可以跨越多行
用于详细说明SQL语句
*/

INSERT INTO users (username, email, age) VALUES ('张三', 'zhangsan@example.com', 25);

UPDATE users SET age = 26 WHERE id = 1;

DELETE FROM users WHERE id = 1;</code></pre>

                <div class="syntax-explanation">
                    <h5><i class="fa fa-info-circle"></i> 📌 基本规则说明：</h5>
                    <ul>
                        <li><strong>语句结束</strong>：每条SQL语句以分号结束（必须）</li>
                        <li><strong>大小写</strong>：SQL关键字不区分大小写，但习惯上大写</li>
                        <li><strong>注释</strong>：单行注释用 <code>--</code>，多行注释用 <code>/* */</code></li>
                        <li><strong>字符串</strong>：使用单引号包裹</li>
                    </ul>
                </div>

                <h4><i class="fa fa-table"></i> 创建表</h4>
                <pre><code class="language-sql">-- 创建表
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT '用户ID',
    username VARCHAR(50) NOT NULL UNIQUE COMMENT '用户名',
    email VARCHAR(100) NOT NULL COMMENT '邮箱',
    age INT DEFAULT 18 COMMENT '年龄',
    status TINYINT DEFAULT 1 COMMENT '状态：1正常 0禁用',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户表';</code></pre>

                <div class="syntax-explanation">
                    <h5><i class="fa fa-info-circle"></i> 📌 数据类型说明：</h5>
                    <ul>
                        <li><strong>INT</strong>：整数类型</li>
                        <li><strong>VARCHAR(n)</strong>：变长字符串，n为最大长度</li>
                        <li><strong>TIMESTAMP</strong>：时间戳类型</li>
                        <li><strong>TINYINT</strong>：小整数，常用于状态字段</li>
                        <li><strong>AUTO_INCREMENT</strong>：自增主键</li>
                    </ul>
                </div>

                <h4><i class="fa fa-search"></i> SELECT查询语句</h4>
                <pre><code class="language-sql">-- 查询所有字段
SELECT * FROM users;

-- 查询指定字段
SELECT username, email FROM users;

-- WHERE条件查询
SELECT * FROM users WHERE age > 18;

-- 多条件查询（AND/OR）
SELECT * FROM users WHERE age >= 18 AND city = '北京';

-- ORDER BY排序
SELECT * FROM users ORDER BY age DESC; -- 降序

-- LIMIT限制结果数量
SELECT * FROM users LIMIT 5; -- 前5条</code></pre>

                <div class="syntax-explanation">
                    <h5><i class="fa fa-info-circle"></i> 📌 SELECT要点：</h5>
                    <ul>
                        <li><strong>*</strong>：表示所有字段</li>
                        <li><strong>WHERE</strong>：指定查询条件</li>
                        <li><strong>ORDER BY</strong>：排序，ASC升序，DESC降序</li>
                        <li><strong>LIMIT</strong>：限制返回结果数量</li>
                    </ul>
                </div>

                <h4><i class="fa fa-plus"></i> INSERT插入语句</h4>
                <pre><code class="language-sql">-- 插入指定字段（推荐）
INSERT INTO users (username, email, age) VALUES ('李四', 'lisi@example.com', 30);

-- 插入多条记录
INSERT INTO users (username, email, age) VALUES
    ('王五', 'wangwu@example.com', 28),
    ('赵六', 'zhaoliu@example.com', 35);</code></pre>

                <div class="syntax-explanation">
                    <h5><i class="fa fa-info-circle"></i> 📌 INSERT要点：</h5>
                    <ul>
                        <li><strong>字符串</strong>：使用单引号包裹</li>
                        <li><strong>数值</strong>：不需要引号</li>
                        <li><strong>自增字段</strong>：插入NULL或省略</li>
                    </ul>
                </div>

                <h4><i class="fa fa-edit"></i> UPDATE更新语句</h4>
                <pre><code class="language-sql">-- 更新单个字段
UPDATE users SET age = 26 WHERE id = 1;

-- 更新多个字段
UPDATE users SET age = 27, city = '深圳' WHERE id = 1;

-- 基于条件更新
UPDATE users SET status = 0 WHERE age < 18;</code></pre>

                <div class="syntax-explanation">
                    <h5><i class="fa fa-exclamation-triangle"></i> ⚠️ 重要提示：</h5>
                    <ul>
                        <li><strong>永远不要省略WHERE子句</strong>，否则会更新所有记录</li>
                        <li>更新前建议先用SELECT验证条件</li>
                    </ul>
                </div>

                <h4><i class="fa fa-trash"></i> DELETE删除语句</h4>
                <pre><code class="language-sql">-- 删除指定记录
DELETE FROM users WHERE id = 1;

-- 基于条件删除
DELETE FROM users WHERE age < 18;</code></pre>

                <div class="syntax-explanation">
                    <h5><i class="fa fa-exclamation-triangle"></i> ⚠️ 重要提示：</h5>
                    <ul>
                        <li><strong>永远不要省略WHERE子句</strong>，否则会删除所有记录</li>
                        <li>删除前建议先用SELECT验证条件</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- 区域3: PHP表单处理 -->
    <div class="collapsible-section" id="section3">
        <div class="collapsible-header" onclick="toggleSection('section3')">
            <span class="toggle-text">
                <i class="fa fa-list-alt"></i> PHP表单处理
            </span>
            <div class="toggle-btn"></div>
        </div>
        <div class="collapsible-content">
            <div class="chapter-title">
                <i class="fa fa-code"></i> PHP表单处理示例
            </div>
            <div class="chapter-description">
                学习PHP如何接收和处理GET/POST请求，包括超全局变量$_GET和$_POST的使用，以及基本的数据验证和安全处理。
            </div>

            <div class="code-preview-container">
                <!-- 代码展示区域 (65%) -->
                <div class="code-display">
                    <div class="code-display-header">
                        <span>原始代码</span>
                        <button onclick="toggleFullscreen('section3')">
                            <i class="fa fa-search-plus"></i> 放大
                        </button>
                    </div>
                    <div class="code-content" data-code="<?php echo getEncodedExampleCode('form.php'); ?>"
                        data-language="php">
                        <div
                            style="display: flex; align-items: center; justify-content: center; height: 100%; color: #888;">
                            <i class="fa fa-spinner fa-spin" style="margin-right: 10px;"></i> 点击展开区域加载代码
                        </div>
                    </div>
                </div>

                <!-- 预览效果区域 (35%) -->
                <div class="preview-display">
                    <div class="preview-header">
                        <span>预览效果</span>
                        <div>
                            <button onclick="openPopup('preview3')">
                                <i class="fa fa-external-link"></i> 弹出
                            </button>
                            <button onclick="refreshPreview('preview3')">
                                <i class="fa fa-refresh"></i> 刷新
                            </button>
                        </div>
                    </div>
                    <iframe src="examples/form.php" id="preview3"></iframe>
                </div>
            </div>
        </div>
    </div>

    <!-- 区域4: PHP文件处理 -->
    <div class="collapsible-section" id="section4">
        <div class="collapsible-header" onclick="toggleSection('section4')">
            <span class="toggle-text">
                <i class="fa fa-folder"></i> PHP文件处理
            </span>
            <div class="toggle-btn"></div>
        </div>
        <div class="collapsible-content">
            <div class="chapter-title">
                <i class="fa fa-code"></i> PHP文件处理示例
            </div>
            <div class="chapter-description">
                学习PHP的文件操作，包括文件上传、创建、读取、下载和删除等常用功能。
            </div>

            <div class="code-preview-container">
                <!-- 代码展示区域 (65%) -->
                <div class="code-display">
                    <div class="code-display-header">
                        <span>原始代码</span>
                        <button onclick="toggleFullscreen('section4')">
                            <i class="fa fa-search-plus"></i> 放大
                        </button>
                    </div>
                    <div class="code-content" data-code="<?php echo getEncodedExampleCode('file.php'); ?>"
                        data-language="php">
                        <div
                            style="display: flex; align-items: center; justify-content: center; height: 100%; color: #888;">
                            <i class="fa fa-spinner fa-spin" style="margin-right: 10px;"></i> 点击展开区域加载代码
                        </div>
                    </div>
                </div>

                <!-- 预览效果区域 (35%) -->
                <div class="preview-display">
                    <div class="preview-header">
                        <span>预览效果</span>
                        <div>
                            <button onclick="openPopup('preview4')">
                                <i class="fa fa-external-link"></i> 弹出
                            </button>
                            <button onclick="refreshPreview('preview4')">
                                <i class="fa fa-refresh"></i> 刷新
                            </button>
                        </div>
                    </div>
                    <iframe src="examples/file.php" id="preview4"></iframe>
                </div>
            </div>
        </div>
    </div>

    <!-- 区域5: PHP数据库处理 -->
    <div class="collapsible-section" id="section5">
        <div class="collapsible-header" onclick="toggleSection('section5')">
            <span class="toggle-text">
                <i class="fa fa-database"></i> PHP数据库处理
            </span>
            <div class="toggle-btn"></div>
        </div>
        <div class="collapsible-content">
            <div class="chapter-title">
                <i class="fa fa-code"></i> PHP数据库处理示例
            </div>
            <div class="chapter-description">
                学习PHP使用PDO（PHP Data Objects）进行数据库操作，包括数据库连接、插入、查询、更新和删除数据的完整流程。
            </div>

            <div class="code-preview-container">
                <!-- 代码展示区域 (65%) -->
                <div class="code-display">
                    <div class="code-display-header">
                        <span>原始代码</span>
                        <button onclick="toggleFullscreen('section5')">
                            <i class="fa fa-search-plus"></i> 放大
                        </button>
                    </div>
                    <div class="code-content" data-code="<?php echo getEncodedExampleCode('database.php'); ?>"
                        data-language="php">
                        <div
                            style="display: flex; align-items: center; justify-content: center; height: 100%; color: #888;">
                            <i class="fa fa-spinner fa-spin" style="margin-right: 10px;"></i> 点击展开区域加载代码
                        </div>
                    </div>
                </div>

                <!-- 预览效果区域 (35%) -->
                <div class="preview-display">
                    <div class="preview-header">
                        <span>预览效果</span>
                        <div>
                            <button onclick="openPopup('preview5')">
                                <i class="fa fa-external-link"></i> 弹出
                            </button>
                            <button onclick="refreshPreview('preview5')">
                                <i class="fa fa-refresh"></i> 刷新
                            </button>
                        </div>
                    </div>
                    <iframe src="examples/database.php" id="preview5"></iframe>
                </div>
            </div>
        </div>
    </div>

    <!-- 区域6: PHP Cookie和Session处理 -->
    <div class="collapsible-section" id="section6">
        <div class="collapsible-header" onclick="toggleSection('section6')">
            <span class="toggle-text">
                <i class="fa fa-shield"></i> PHP Cookie和Session处理
            </span>
            <div class="toggle-btn"></div>
        </div>
        <div class="collapsible-content">
            <div class="chapter-title">
                <i class="fa fa-code"></i> PHP Cookie和Session处理示例
            </div>
            <div class="chapter-description">
                学习PHP的Session管理和Cookie使用，包括用户登录、登出、"记住我"功能的完整实现。
            </div>

            <div class="code-preview-container">
                <!-- 代码展示区域 (65%) -->
                <div class="code-display">
                    <div class="code-display-header">
                        <span>原始代码</span>
                        <button onclick="toggleFullscreen('section6')">
                            <i class="fa fa-search-plus"></i> 放大
                        </button>
                    </div>
                    <div class="code-content" data-code="<?php echo getEncodedExampleCode('session.php'); ?>"
                        data-language="php">
                        <div
                            style="display: flex; align-items: center; justify-content: center; height: 100%; color: #888;">
                            <i class="fa fa-spinner fa-spin" style="margin-right: 10px;"></i> 点击展开区域加载代码
                        </div>
                    </div>
                </div>

                <!-- 预览效果区域 (35%) -->
                <div class="preview-display">
                    <div class="preview-header">
                        <span>预览效果</span>
                        <div>
                            <button onclick="openPopup('preview6')">
                                <i class="fa fa-external-link"></i> 弹出
                            </button>
                            <button onclick="refreshPreview('preview6')">
                                <i class="fa fa-refresh"></i> 刷新
                            </button>
                        </div>
                    </div>
                    <iframe src="examples/session.php" id="preview6"></iframe>
                </div>
            </div>
        </div>
    </div>

    <!-- 区域7: 学习完成确认 -->
    <div class="collapsible-section" id="section7">
        <div class="collapsible-header" onclick="toggleSection('section7')">
            <span class="toggle-text">
                <i class="fa fa-graduation-cap"></i> 学习完成
            </span>
            <div class="toggle-btn"></div>
        </div>
        <div class="collapsible-content">
            <div class="chapter-description">
                如果你已经掌握了服务端脚本的基本语法，包括PHP的表单处理、文件处理、数据库操作以及Cookie和Session管理，可以点击下方的"我已掌握"按钮来标记你的学习成果。
            </div>

            <!-- 我已掌握按钮区域 -->
            <div class="mastery-button-container">
                <button type="button" class="heasec-mastery-btn" id="phpSqlMasteryBtn" onclick="showMasteryCongrats()">
                    <i class="fa fa-check-circle"></i>
                    我已掌握
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 引入星星系统组件的JavaScript -->
<script src="<?php echo $commonBasePath; ?>components/star-system/js/heasec-congrats-modal.js"></script>

<!-- 配置变量 -->
<script>
    window.heasecConfig = {
        commonBasePath: '<?php echo $commonBasePath; ?>'
    };
</script>

<!-- 引入Prism.js -->
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>assets/css/prism-line-numbers.min.css" />
<script src="<?php echo $commonBasePath; ?>assets/js/prism.min.js"></script>
<script src="<?php echo $commonBasePath; ?>assets/js/prism-clike.min.js"></script>
<script src="<?php echo $commonBasePath; ?>assets/js/prism-markup.min.js"></script>
<script src="<?php echo $commonBasePath; ?>assets/js/prism-markup-templating.min.js"></script>
<script src="<?php echo $commonBasePath; ?>assets/js/prism-php.min.js"></script>
<script src="<?php echo $commonBasePath; ?>assets/js/prism-sql.min.js"></script>
<script src="<?php echo $commonBasePath; ?>assets/js/prism-line-numbers.min.js"></script>

<!-- 引入页面JavaScript文件 -->
<script src="js/main.js?v=v1.0.2"></script>

<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>