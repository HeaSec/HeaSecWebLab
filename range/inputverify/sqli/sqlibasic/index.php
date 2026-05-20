<?php
/**
 * HeaSec天积安全团队 - SQL注入基础靶场
 * 版本: v1.0.0
 * 创建日期: 2026-04-01
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 功能：帮助用户学习理解SQL注入漏洞的原理和利用方法
 * 包含5个可折叠区域：原理讲解、SQL调试、注入练习、拓展知识、学习完成
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec SQL注入基础 Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = 'SQL注入基础靶场';
$rangeName = 'SQL注入基础';
$showVersion = false;
$showResetButton = true;
$version = 'v1.0.0';

// 数据库配置
$useDatabase = true;
$databaseName = 'heasec_sqlbase';
$initSqlFile = __DIR__ . '/database/init_database.sql';

// 设置公共组件的基础路径（从靶场目录到range/common/的相对路径）
$commonBasePath = '../../../common/';

// 定义常量允许访问公共组件
define('HEASEC_RANGE_ACCESS', true);

// 引入公共头部
require_once $commonBasePath . 'includes/header.php';
?>

<!--+引入自定义样式 -->
<link rel="stylesheet" href="./css/style.css">

<!--+引入星星系统组件的CSS样式 -->
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>components/star-system/css/heasec-congrats-modal.css">

<!--+靶场主要内容 -->
<div class="heasec-container">

    <!--+=====================================================
         区域1: SQL漏洞原理及利用
         ===================================================== -->
    <div class="collapsible-section expanded" id="section1">
        <div class="collapsible-header" onclick="toggleSection('section1')">
            <span class="toggle-text">
                <i class="fa fa-shield"></i> SQL漏洞原理及利用
            </span>
            <div class="toggle-btn"></div>
        </div>
        <div class="collapsible-content">
            <div class="chapter-title">
                <i class="fa fa-lightbulb-o"></i> SQL注入漏洞原理
            </div>

            <!--+SQL前后端交互架构图 -->
            <div class="architecture-container">
                <h4 class="sub-section-title">1. SQL语句前后端交互逻辑</h4>
                <p class="principle-intro">
                    在WEB应用中，用户的数据通过<strong>前端表单</strong>提交，经由<strong>HTTP请求</strong>传输到后端服务器，后端程序将用户输入<strong>拼接到SQL语句</strong>中执行查询。当用户输入包含恶意SQL代码时，就产生了<strong>SQL注入漏洞</strong>。
                </p>

                <!-- 三层架构示意图 -->
                <div class="interaction-diagram">
                    <!-- 客户端 -->
                    <div class="diagram-node client-node">
                        <div class="node-icon">💻</div>
                        <div class="node-name">客户端<br><small>(表示层)</small></div>
                        <div class="node-tech-stack">
                            <span class="tech-tag">HTML</span>
                            <span class="tech-tag">表单</span>
                            <span class="tech-tag">JS</span>
                        </div>
                        <div class="node-examples">用户输入数据</div>
                    </div>

                    <!-- 交互：客户端 <-> 应用服务器 -->
                    <div class="diagram-flow">
                        <div class="flow-step request-step">
                            <div class="step-label">HTTP 请求</div>
                            <div class="step-arrow right-arrow">
                                <div class="arrow-line"></div>
                                <div class="arrow-head"></div>
                            </div>
                            <div class="step-detail">携带用户输入</div>
                        </div>
                        <div class="flow-step response-step">
                            <div class="step-arrow left-arrow">
                                <div class="arrow-head"></div>
                                <div class="arrow-line"></div>
                            </div>
                            <div class="step-label">HTTP 响应</div>
                            <div class="step-detail">返回查询结果</div>
                        </div>
                    </div>

                    <!-- 应用服务器 -->
                    <div class="diagram-node server-node injection-point">
                        <div class="node-icon">⚙️</div>
                        <div class="node-name">应用服务器<br><small>(逻辑层)</small></div>
                        <div class="node-tech-stack">
                            <span class="tech-tag server">PHP</span>
                            <span class="tech-tag server">SQL拼接</span>
                        </div>
                        <div class="node-examples">执行SQL查询</div>
                    </div>

                    <!-- 交互：应用服务器 <-> 数据库服务器 -->
                    <div class="diagram-flow">
                        <div class="flow-step request-step">
                            <div class="step-label">SQL 查询</div>
                            <div class="step-arrow right-arrow">
                                <div class="arrow-line" style="background: linear-gradient(90deg, #ed8936, #9f7aea);"></div>
                                <div class="arrow-head" style="border-left-color: #9f7aea;"></div>
                            </div>
                            <div class="step-detail">拼接后的SQL</div>
                        </div>
                        <div class="flow-step response-step">
                            <div class="step-arrow left-arrow">
                                <div class="arrow-head" style="border-right-color: #ed8936;"></div>
                                <div class="arrow-line" style="background: linear-gradient(90deg, #4A90E2, #ed8936);"></div>
                            </div>
                            <div class="step-label">数据结果</div>
                            <div class="step-detail">查询返回</div>
                        </div>
                    </div>

                    <!-- 数据库服务器 -->
                    <div class="diagram-node database-node">
                        <div class="node-icon">🗄️</div>
                        <div class="node-name">数据库<br><small>(数据层)</small></div>
                        <div class="node-tech-stack">
                            <span class="tech-tag db">MySQL</span>
                            <span class="tech-tag db">PDO</span>
                        </div>
                        <div class="node-examples">存储用户数据</div>
                    </div>
                </div>

                <!-- 架构详情 -->
                <div class="principle-features">
                    <div class="feature-item">
                        <div class="feature-title">💻 客户端 (表示层)</div>
                        <div class="feature-desc">用户直接交互的界面。用户在<strong>表单</strong>中输入用户名、密码等数据，前端通过JavaScript进行简单的格式校验后，将数据打包成<strong>HTTP请求</strong>发送给服务器。</div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-title">⚠️ 应用服务器 (逻辑层) - 注入点</div>
                        <div class="feature-desc">后端程序接收用户输入后，<strong style="color:#e53e3e;">直接将数据拼接到SQL语句中</strong>。这是SQL注入漏洞产生的根本原因。安全的做法是使用<strong>预处理语句(Prepared Statement)</strong>。</div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-title">🗄️ 数据库 (数据层)</div>
                        <div class="feature-desc">数据库接收并执行SQL语句，返回查询结果。如果SQL语句被注入，攻击者可以<strong>获取敏感数据</strong>、<strong>修改数据</strong>甚至<strong>控制服务器</strong>。</div>
                    </div>
                </div>

                <!--+正常查询 vs 注入查询 动态流程对比 -->
                <h4 class="sub-section-title">2. 正常查询 vs SQL注入 动态流程对比</h4>
                <p class="principle-intro">
                    点击下方按钮，观察<strong>正常查询</strong>与<strong>SQL注入查询</strong>在三层架构中的数据流动差异。系统会自动播放对应的流程动画并展示真实的查询结果。
                </p>

                <!--+模拟查询表单 -->
                <div class="vf-sim-form">
                    <div class="vf-sim-label"><i class="fa fa-search"></i> 模拟用户查询 — 点击下方按钮选择查询方式</div>
                    <!-- 模拟查询输入框 -->
                    <input type="text" id="simQueryInput" class="vf-querybar-input" readonly placeholder="请输入商品ID，点击下方按钮模拟查询..." value="">
                    <div class="vf-sim-buttons">
                        <button class="vf-sim-toggle vf-toggle-safe" id="btnNormal" onclick="switchFlow('normal')">
                            <i class="fa fa-check-circle"></i> 正常查询: id=1
                        </button>
                        <button class="vf-sim-toggle vf-toggle-danger" id="btnInject" onclick="switchFlow('inject')">
                            <i class="fa fa-exclamation-triangle"></i> SQL注入: 1 OR 1=1
                        </button>
                    </div>
                    <div id="simStatus" class="vf-sim-status"></div>
                </div>

                <!--+图1: 正常SQL查询流程 -->
                <div class="vf-card vf-card-safe vf-hidden" id="flowNormal">
                    <div class="vf-card-header">
                        <span class="vf-badge vf-badge-safe"><i class="fa fa-check-circle"></i> 正常查询流程</span>
                        <span class="vf-subtitle" id="normalSubtitle">用户输入: id = 1</span>
                        <button class="vf-play-btn vf-play-safe" onclick="playFlowAnimation('normal')">
                            <i class="fa fa-play"></i> 播放动画
                        </button>
                    </div>
                    <div class="vf-steps">
                        <!-- Step 1: 客户端 -->
                        <div class="vf-step" data-step="1">
                            <div class="vf-step-indicator">
                                <div class="vf-step-dot"></div>
                                <div class="vf-step-line"></div>
                            </div>
                            <div class="vf-step-content">
                                <div class="vf-step-node">
                                    <span class="vf-node-icon">💻</span>
                                    <span class="vf-node-title">Step 1 · 客户端发送请求</span>
                                </div>
                                <div class="vf-data-bubble vf-bubble-request">
                                    <div class="vf-bubble-label">HTTP 请求</div>
                                    <code id="normalRequest">GET /api/product.php?id=<strong>1</strong></code>
                                </div>
                            </div>
                        </div>
                        <!-- Step 2: 服务器 -->
                        <div class="vf-step" data-step="2">
                            <div class="vf-step-indicator">
                                <div class="vf-step-dot"></div>
                                <div class="vf-step-line"></div>
                            </div>
                            <div class="vf-step-content">
                                <div class="vf-step-node">
                                    <span class="vf-node-icon">⚙️</span>
                                    <span class="vf-node-title">Step 2 · 服务器拼接SQL语句</span>
                                </div>
                                <div class="vf-data-bubble vf-bubble-sql">
                                    <div class="vf-bubble-label">SQL拼接结果</div>
                                    <code id="normalSql">SELECT id, name, price, description<br>FROM heasec_sqlbase_products<br>WHERE id = <strong class="vf-text-safe">1</strong></code>
                                    <!-- 漏洞原因说明 -->
                                    <div class="vf-cause-note vf-cause-safe" id="normalCause"></div>
                                </div>
                            </div>
                        </div>
                        <!-- Step 3: 数据库 -->
                        <div class="vf-step" data-step="3">
                            <div class="vf-step-indicator">
                                <div class="vf-step-dot"></div>
                                <div class="vf-step-line"></div>
                            </div>
                            <div class="vf-step-content">
                                <div class="vf-step-node">
                                    <span class="vf-node-icon">🗄️</span>
                                    <span class="vf-node-title">Step 3 · 数据库执行查询</span>
                                </div>
                                <div class="vf-data-bubble vf-bubble-db">
                                    <div class="vf-bubble-label" id="normalDbLabel">执行结果: 匹配 1 条记录</div>
                                    <code id="normalDbCode">MySQL: WHERE id = 1 → 命中 1 行</code>
                                </div>
                            </div>
                        </div>
                        <!-- Step 4: 返回结果 -->
                        <div class="vf-step" data-step="4">
                            <div class="vf-step-indicator">
                                <div class="vf-step-dot vf-dot-end"></div>
                            </div>
                            <div class="vf-step-content">
                                <div class="vf-step-node">
                                    <span class="vf-node-icon">📋</span>
                                    <span class="vf-node-title">Step 4 · 返回查询结果</span>
                                </div>
                                <div class="vf-data-bubble vf-bubble-result vf-result-safe">
                                    <div class="vf-bubble-label" id="normalResultLabel">✅ 正常返回 1 条数据</div>
                                    <div id="normalResultTable">
                                        <table class="vf-result-table">
                                            <thead><tr><th>id</th><th>name</th><th>price</th><th>description</th></tr></thead>
                                            <tbody id="normalResultBody"><tr><td>1</td><td>Apple</td><td>$5.00</td><td>Fresh red apple</td></tr></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!--+图2: SQL注入查询流程 -->
                <div class="vf-card vf-card-danger vf-hidden" id="flowInject">
                    <div class="vf-card-header">
                        <span class="vf-badge vf-badge-danger"><i class="fa fa-exclamation-triangle"></i> SQL注入查询流程</span>
                        <span class="vf-subtitle" id="injectSubtitle">用户输入: id = 1 OR 1=1</span>
                        <button class="vf-play-btn vf-play-danger" onclick="playFlowAnimation('inject')">
                            <i class="fa fa-play"></i> 播放动画
                        </button>
                    </div>
                    <div class="vf-steps">
                        <!-- Step 1: 客户端 -->
                        <div class="vf-step" data-step="1">
                            <div class="vf-step-indicator">
                                <div class="vf-step-dot vf-dot-danger"></div>
                                <div class="vf-step-line vf-line-danger"></div>
                            </div>
                            <div class="vf-step-content">
                                <div class="vf-step-node">
                                    <span class="vf-node-icon">💻</span>
                                    <span class="vf-node-title">Step 1 · 客户端发送请求</span>
                                </div>
                                <div class="vf-data-bubble vf-bubble-request vf-bubble-danger">
                                    <div class="vf-bubble-label">⚠️ HTTP 请求（含恶意参数）</div>
                                    <code id="injectRequest">GET /api/product.php?id=<strong class="vf-text-danger">1+OR+1%3D1</strong></code>
                                </div>
                            </div>
                        </div>
                        <!-- Step 2: 服务器 -->
                        <div class="vf-step" data-step="2">
                            <div class="vf-step-indicator">
                                <div class="vf-step-dot vf-dot-danger"></div>
                                <div class="vf-step-line vf-line-danger"></div>
                            </div>
                            <div class="vf-step-content">
                                <div class="vf-step-node">
                                    <span class="vf-node-icon">⚠️</span>
                                    <span class="vf-node-title">Step 2 · 服务器拼接SQL语句 <span class="vf-tag-danger">危险!</span></span>
                                </div>
                                <div class="vf-data-bubble vf-bubble-sql vf-bubble-danger-sql">
                                    <div class="vf-bubble-label">🔴 SQL拼接结果（注入成功）</div>
                                    <code id="injectSql">SELECT id, name, price, description<br>FROM heasec_sqlbase_products<br>WHERE id = <strong class="vf-text-danger">1 OR 1=1</strong></code>
                                    <!-- 漏洞原因说明 -->
                                    <div class="vf-cause-note vf-cause-danger" id="injectCause"></div>
                                </div>
                            </div>
                        </div>
                        <!-- Step 3: 数据库 -->
                        <div class="vf-step" data-step="3">
                            <div class="vf-step-indicator">
                                <div class="vf-step-dot vf-dot-danger"></div>
                                <div class="vf-step-line vf-line-danger"></div>
                            </div>
                            <div class="vf-step-content">
                                <div class="vf-step-node">
                                    <span class="vf-node-icon">🗄️</span>
                                    <span class="vf-node-title">Step 3 · 数据库执行查询</span>
                                </div>
                                <div class="vf-data-bubble vf-bubble-db vf-bubble-danger-db">
                                    <div class="vf-bubble-label" id="injectDbLabel">🚨 执行结果: 匹配全部 5 条记录!</div>
                                    <code id="injectDbCode">MySQL: WHERE id = 1 OR 1=1 → <strong>条件永远为真</strong>，命中 5 行</code>
                                </div>
                            </div>
                        </div>
                        <!-- Step 4: 返回结果 -->
                        <div class="vf-step" data-step="4">
                            <div class="vf-step-indicator">
                                <div class="vf-step-dot vf-dot-danger vf-dot-end"></div>
                            </div>
                            <div class="vf-step-content">
                                <div class="vf-step-node">
                                    <span class="vf-node-icon">🚨</span>
                                    <span class="vf-node-title">Step 4 · 返回查询结果 <span class="vf-tag-danger">数据泄露!</span></span>
                                </div>
                                <div class="vf-data-bubble vf-bubble-result vf-result-danger">
                                    <div class="vf-bubble-label" id="injectResultLabel">🔴 全部 5 条数据泄露!</div>
                                    <div id="injectResultTable">
                                        <table class="vf-result-table">
                                            <thead><tr><th>id</th><th>name</th><th>price</th><th>description</th></tr></thead>
                                            <tbody id="injectResultBody">
                                                <tr><td>1</td><td>Apple</td><td>$5.00</td><td>Fresh red apple</td></tr>
                                                <tr><td>2</td><td>Banana</td><td>$3.00</td><td>Yellow banana from tropical</td></tr>
                                                <tr><td>3</td><td>Orange</td><td>$4.00</td><td>Sweet orange</td></tr>
                                                <tr><td>4</td><td>Grape</td><td>$8.00</td><td>Purple grape</td></tr>
                                                <tr><td>5</td><td>Watermelon</td><td>$15.00</td><td>Big watermelon</td></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!--+注入类型说明 -->
            <div class="code-display-section">
                <h4><i class="fa fa-tags"></i> SQL注入类型</h4>

                <table class="compare-table">
                    <thead>
                        <tr>
                            <th>类型</th>
                            <th>特征</th>
                            <th>示例</th>
                            <th>判断方法</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>数字型注入</strong></td>
                            <td>注入点不需要引号闭合</td>
                            <td><code>id=1 OR 1=1</code></td>
                            <td>
                                点击下方按钮体验判断过程：
                                <div class="vf-bool-btns">
                                    <button class="vf-bool-toggle vf-bool-false" id="btnBoolTrue" onclick="testBoolInject('1 AND 1=1')">
                                        测试：1 AND 1=1
                                    </button>
                                    <button class="vf-bool-toggle vf-bool-false" id="btnBoolFalse" onclick="testBoolInject('1 AND 1=2')">
                                        测试：1 AND 1=2
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!--+布尔盲注判断结果展示区 -->
                <div class="vf-bool-result vf-hidden" id="boolResultBox">
                    <div class="vf-bool-result-header">
                        <span class="vf-bool-result-badge" id="boolResultBadge"></span>
                        <span class="vf-bool-result-sql" id="boolResultSql"></span>
                    </div>
                    <div class="vf-bool-result-body" id="boolResultBody"></div>
                    <div class="vf-bool-result-footer" id="boolResultFooter"></div>
                </div>

                <table class="compare-table" style="margin-top: 15px;">
                    <thead>
                        <tr>
                            <th>类型</th>
                            <th>特征</th>
                            <th>示例</th>
                            <th>判断方法</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>字符型注入</strong></td>
                            <td>注入点需要引号闭合</td>
                            <td><code>name=' OR '1'='1</code></td>
                            <td>
                                点击下方按钮体验判断过程：
                                <div class="vf-bool-btns">
                                    <button class="vf-bool-toggle vf-bool-false" id="btnCharQuote" onclick="testCharInject('quote')">
                                        测试：'
                                    </button>
                                    <button class="vf-bool-toggle vf-bool-false" id="btnCharComment" onclick="testCharInject('comment')">
                                        测试：'--
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!--+字符型注入判断结果展示区 -->
                <div class="vf-bool-result vf-hidden" id="charResultBox">
                    <div class="vf-bool-result-header">
                        <span class="vf-bool-result-badge" id="charResultBadge"></span>
                        <span class="vf-bool-result-sql" id="charResultSql"></span>
                    </div>
                    <div class="vf-bool-result-body" id="charResultBody"></div>
                    <div class="vf-bool-result-footer" id="charResultFooter"></div>
                </div>

                <table class="compare-table" style="margin-top: 15px;">
                    <thead>
                        <tr>
                            <th>注入方式</th>
                            <th>特点</th>
                            <th>适用场景</th>
                            <th>演示</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>回显注入</strong></td>
                            <td>页面直接显示查询结果</td>
                            <td>页面有数据展示位置</td>
                            <td>
                                <div class="vf-bool-btns">
                                    <button class="vf-bool-toggle vf-bool-false" id="btnEchoUnion" onclick="testEchoInject('union')">
                                        测试：UNION回显
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>报错注入</strong></td>
                            <td>通过错误信息获取数据</td>
                            <td>页面显示SQL错误信息</td>
                            <td>
                                <div class="vf-bool-btns">
                                    <button class="vf-bool-toggle vf-bool-false" id="btnErrorExtract" onclick="testErrorInject('extractvalue')">
                                        测试：报错注入
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>布尔盲注</strong></td>
                            <td>无回显，通过条件判断</td>
                            <td>页面无数据显示，但有响应差异</td>
                            <td>
                                <div class="vf-bool-btns">
                                    <button class="vf-bool-toggle vf-bool-false" id="btnBlindTrue" onclick="testBlindInject('1 AND 1=1')">
                                        测试：1 AND 1=1
                                    </button>
                                    <button class="vf-bool-toggle vf-bool-false" id="btnBlindFalse" onclick="testBlindInject('1 AND 1=2')">
                                        测试：1 AND 1=2
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>时间盲注</strong></td>
                            <td>通过响应时间差异判断</td>
                            <td>页面无数据显示，也无错误信息</td>
                            <td>
                                <div class="vf-bool-btns">
                                    <button class="vf-bool-toggle vf-bool-false" id="btnTimeShort" onclick="testTimeBlindInject('short')">
                                        测试：SLEEP(2)
                                    </button>
                                    <button class="vf-bool-toggle vf-bool-false" id="btnTimeLong" onclick="testTimeBlindInject('long')">
                                        测试：SLEEP(10)
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!--+回显注入结果展示区 -->
                <div class="vf-bool-result vf-hidden" id="echoResultBox">
                    <div class="vf-bool-result-header">
                        <span class="vf-bool-result-badge" id="echoResultBadge"></span>
                        <span class="vf-bool-result-sql" id="echoResultSql"></span>
                    </div>
                    <div class="vf-bool-result-body" id="echoResultBody"></div>
                    <div class="vf-bool-result-footer" id="echoResultFooter"></div>
                </div>

                <!--+报错注入结果展示区 -->
                <div class="vf-bool-result vf-hidden" id="errorResultBox">
                    <div class="vf-bool-result-header">
                        <span class="vf-bool-result-badge" id="errorResultBadge"></span>
                        <span class="vf-bool-result-sql" id="errorResultSql"></span>
                    </div>
                    <div class="vf-bool-result-body" id="errorResultBody"></div>
                    <div class="vf-bool-result-footer" id="errorResultFooter"></div>
                </div>

                <!--+布尔盲注结果展示区 -->
                <div class="vf-bool-result vf-hidden" id="blindResultBox">
                    <div class="vf-bool-result-header">
                        <span class="vf-bool-result-badge" id="blindResultBadge"></span>
                        <span class="vf-bool-result-sql" id="blindResultSql"></span>
                    </div>
                    <div class="vf-bool-result-body" id="blindResultBody"></div>
                    <div class="vf-bool-result-footer" id="blindResultFooter"></div>
                </div>

                <!--+时间盲注结果展示区 -->
                <div class="vf-bool-result vf-hidden" id="timeBlindResultBox">
                    <div class="vf-bool-result-header">
                        <span class="vf-bool-result-badge" id="timeBlindResultBadge"></span>
                        <span class="vf-bool-result-sql" id="timeBlindResultSql"></span>
                    </div>
                    <div class="vf-bool-result-body" id="timeBlindResultBody"></div>
                    <div class="vf-bool-result-footer" id="timeBlindResultFooter"></div>
                </div>
            </div>

            <!--+注入后果 -->
            <div class="code-display-section">
                <h4><i class="fa fa-skull-crossbones"></i> SQL注入可能造成的危害</h4>
                <div class="vf-harm-grid">
                    <div class="vf-harm-card">
                        <div class="vf-harm-icon">🔓</div>
                        <div class="vf-harm-title">数据泄露</div>
                        <div class="vf-harm-desc">攻击者可通过SQL注入读取数据库中任意表的数据，包括用户账号密码、个人隐私信息、企业商业机密等敏感数据</div>
                    </div>
                    <div class="vf-harm-card">
                        <div class="vf-harm-icon">🎭</div>
                        <div class="vf-harm-title">身份伪造</div>
                        <div class="vf-harm-desc">利用注入绕过登录验证，无需密码即可冒充任意用户（包括管理员）登录系统</div>
                    </div>
                    <div class="vf-harm-card">
                        <div class="vf-harm-icon">✏️</div>
                        <div class="vf-harm-title">数据篡改</div>
                        <div class="vf-harm-desc">通过UPDATE/DELETE语句修改或删除数据库中的数据，造成业务数据丢失或被篡改</div>
                    </div>
                    <div class="vf-harm-card">
                        <div class="vf-harm-icon">⬆️</div>
                        <div class="vf-harm-title">权限提升</div>
                        <div class="vf-harm-desc">利用数据库内置函数（如LOAD_FILE()、INTO OUTFILE）读写服务器文件系统，逐步获取更高权限</div>
                    </div>
                    <div class="vf-harm-card">
                        <div class="vf-harm-icon">💀</div>
                        <div class="vf-harm-title">系统控制</div>
                        <div class="vf-harm-desc">在特定条件下，通过数据库功能执行操作系统命令，最终完全控制服务器</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!--+=====================================================
         区域2: SQL语句调试功能
         ===================================================== -->
    <div class="collapsible-section" id="section2">
        <div class="collapsible-header" onclick="toggleSection('section2')">
            <span class="toggle-text">
                <i class="fa fa-terminal"></i> SQL语句调试
            </span>
            <div class="toggle-btn"></div>
        </div>
        <div class="collapsible-content">
            <div class="chapter-title">
                <i class="fa fa-bug"></i> SQL语句调试功能
            </div>
            <div class="chapter-description">
                在这个区域，你可以输入用户名或邮箱进行精确搜索，系统会显示实际执行的SQL语句。尝试使用下方的注入语句来获取更多信息。
            </div>

            <!--+搜索表单 -->
            <form id="sqlDebugForm" class="sql-debug-form">
                <input type="text" id="debugKeyword" name="keyword" placeholder="输入搜索内容（如：admin）">
                <button type="submit"><i class="fa fa-search"></i> 搜索</button>
            </form>

            <!--+调试信息显示区 -->
            <div id="debugInfo"></div>

            <!--+查询结果显示区 -->
            <div id="debugResult"></div>

            <!--+注入语句示例 -->
            <div class="payload-examples">
                <div class="payload-card" data-payload="admin" onclick="SQLDebug.copyPayload(this.getAttribute('data-payload'))">
                    <strong>正常查询</strong>
                    <div class="payload-code-row">
                        <code>admin</code>
                        <button class="copy-btn" data-payload="admin" onclick="copyFromDataAttr(this, event)" title="复制到剪贴板">
                            <i class="fa fa-copy"></i>
                        </button>
                    </div>
                    <div class="payload-desc">查询admin用户信息（点击卡片填入输入框）</div>
                </div>
                <div class="payload-card" data-payload="' OR '1'='1" onclick="SQLDebug.copyPayload(this.getAttribute('data-payload'))">
                    <strong>字符型注入基础</strong>
                    <div class="payload-code-row">
                        <code>' OR '1'='1</code>
                        <button class="copy-btn" data-payload="' OR '1'='1" onclick="copyFromDataAttr(this, event)" title="复制到剪贴板">
                            <i class="fa fa-copy"></i>
                        </button>
                    </div>
                    <div class="payload-desc">绕过登录验证的经典Payload（点击卡片填入输入框）</div>
                </div>
                <div class="payload-card" data-payload="' UNION SELECT user(),database(),version(),4 --+ " onclick="SQLDebug.copyPayload(this.getAttribute('data-payload'))">
                    <strong>联合查询注入</strong>
                    <div class="payload-code-row">
                        <code>' UNION SELECT user(),database(),version(),4 --+ </code>
                        <button class="copy-btn" data-payload="' UNION SELECT user(),database(),version(),4 --+ " onclick="copyFromDataAttr(this, event)" title="复制到剪贴板">
                            <i class="fa fa-copy"></i>
                        </button>
                    </div>
                    <div class="payload-desc">获取数据库系统信息（点击卡片填入输入框）</div>
                </div>
                <div class="payload-card" data-payload="' UNION SELECT 1,table_name,3,4 FROM information_schema.tables WHERE table_schema=database() --+ " onclick="SQLDebug.copyPayload(this.getAttribute('data-payload'))">
                    <strong>获取表名</strong>
                    <div class="payload-code-row">
                        <code>' UNION SELECT 1,table_name,3,4 FROM information_schema.tables WHERE table_schema=database() --+ </code>
                        <button class="copy-btn" data-payload="' UNION SELECT 1,table_name,3,4 FROM information_schema.tables WHERE table_schema=database() --+ " onclick="copyFromDataAttr(this, event)" title="复制到剪贴板">
                            <i class="fa fa-copy"></i>
                        </button>
                    </div>
                    <div class="payload-desc">查询当前数据库的所有表（点击卡片填入输入框）</div>
                </div>
            </div>
        </div>
    </div>

    <!--+=====================================================
         区域3: SQL注入尝试区域
         ===================================================== -->
    <div class="collapsible-section" id="section3">
        <div class="collapsible-header" onclick="toggleSection('section3')">
            <span class="toggle-text">
                <i class="fa fa-flask"></i> SQL注入尝试
            </span>
            <div class="toggle-btn"></div>
        </div>
        <div class="collapsible-content">
            <div class="chapter-title">
                <i class="fa fa-graduation-cap"></i> SQL注入实践练习
            </div>
            <div class="chapter-description">
                在这个区域，你可以选择不同的注入场景，学习如何判断注入点类型并体验完整的数据提取过程。
            </div>

            <!--+场景选择器 -->
            <div class="scenario-selector">
                <button class="scenario-btn active" data-scenario="numeric" onclick="SQLPractice.switchScenario('numeric')">
                    <i class="fa fa-hashtag"></i> 数字型注入
                </button>
                <button class="scenario-btn" data-scenario="single_quote" onclick="SQLPractice.switchScenario('single_quote')">
                    <i class="fa fa-quote-right"></i> 字符型（单引号）
                </button>
                <button class="scenario-btn" data-scenario="double_quote" onclick="SQLPractice.switchScenario('double_quote')">
                    <i class="fa fa-quote-left"></i> 字符型（双引号）
                </button>
            </div>

            <!--+场景提示 -->
            <div id="scenarioHint" class="info-box">
                <i class="fa fa-lightbulb-o"></i> 当前场景：数字型注入。参数不需要引号闭合，直接输入数字或注入语句。
            </div>

            <!--+注入表单 -->
            <form id="sqlPracticeForm" class="sql-debug-form">
                <input type="text" id="practiceInput" name="input" placeholder="输入测试内容（如：1 或 1 OR 1=1）">
                <button type="submit"><i class="fa fa-play"></i> 执行测试</button>
            </form>

            <!--+调试信息 -->
            <div id="practiceDebugInfo"></div>

            <!--+结果显示 -->
            <div id="practiceResult"></div>

            <!--+常用Payload参考 -->
            <div class="payload-examples">
                <div class="payload-card" data-payload="1" onclick="SQLPractice.usePayload(this.getAttribute('data-payload'))">
                    <strong>正常查询</strong>
                    <div class="payload-code-row">
                        <code>1</code>
                        <button class="copy-btn" data-payload="1" onclick="copyFromDataAttr(this, event)" title="复制到剪贴板">
                            <i class="fa fa-copy"></i>
                        </button>
                    </div>
                    <div class="payload-desc">查询ID为1的产品（点击卡片填入输入框）</div>
                </div>
                <div class="payload-card" data-payload="1 OR 1=1" onclick="SQLPractice.usePayload(this.getAttribute('data-payload'))">
                    <strong>数字型注入</strong>
                    <div class="payload-code-row">
                        <code>1 OR 1=1</code>
                        <button class="copy-btn" data-payload="1 OR 1=1" onclick="copyFromDataAttr(this, event)" title="复制到剪贴板">
                            <i class="fa fa-copy"></i>
                        </button>
                    </div>
                    <div class="payload-desc">返回所有记录（点击卡片填入输入框）</div>
                </div>
                <div class="payload-card" data-payload="' OR '1'='1" onclick="SQLPractice.usePayload(this.getAttribute('data-payload'))">
                    <strong>单引号注入</strong>
                    <div class="payload-code-row">
                        <code>' OR '1'='1</code>
                        <button class="copy-btn" data-payload="' OR '1'='1" onclick="copyFromDataAttr(this, event)" title="复制到剪贴板">
                            <i class="fa fa-copy"></i>
                        </button>
                    </div>
                    <div class="payload-desc">适用于单引号字符型（点击卡片填入输入框）</div>
                </div>
                <div class="payload-card" data-payload='" OR "1"="1' onclick="SQLPractice.usePayload(this.getAttribute('data-payload'))">
                    <strong>双引号注入</strong>
                    <div class="payload-code-row">
                        <code>&quot; OR &quot;1&quot;=&quot;1</code>
                        <button class="copy-btn" data-payload='" OR "1"="1' onclick="copyFromDataAttr(this, event)" title="复制到剪贴板">
                            <i class="fa fa-copy"></i>
                        </button>
                    </div>
                    <div class="payload-desc">适用于双引号字符型（点击卡片填入输入框）</div>
                </div>
            </div>

            <!--+数据提取流程说明 -->
            <div class="code-display-section">
                <h4><i class="fa fa-route"></i> 完整数据提取流程</h4>
                <div id="extractionSteps">
                    <!-- 由JavaScript动态生成，根据不同场景显示不同的payload -->
                </div>
            </div>
        </div>
    </div>

    <!--+=====================================================
         区域4: 拓展知识讲解
         ===================================================== -->
    <div class="collapsible-section" id="section4">
        <div class="collapsible-header" onclick="toggleSection('section4')">
            <span class="toggle-text">
                <i class="fa fa-rocket"></i> 拓展知识讲解
            </span>
            <div class="toggle-btn"></div>
        </div>
        <div class="collapsible-content">
            <div class="chapter-title">
                <i class="fa fa-star"></i> 高级SQL注入技术
            </div>

            <!--+堆叠注入 -->
            <div class="code-display-section">
                <h4><i class="fa fa-layer-group"></i> 堆叠注入（Stacked Injection）</h4>

                <div class="info-box">
                    <h5><i class="fa fa-info-circle"></i> 概念</h5>
                    <p>堆叠注入是指在一次SQL注入中执行多条SQL语句。通过分号<code>;</code>分隔，可以在一条SQL语句后追加任意SQL语句。</p>
                </div>

                <pre class="static-code-block"><code><span class="sql-comment">原始SQL：</span>
<span class="sql-keyword">SELECT</span> * <span class="sql-keyword">FROM</span> <span class="sql-identifier">users</span> <span class="sql-keyword">WHERE</span> <span class="sql-identifier">id</span> = <span class="sql-number">1</span>

<span class="sql-comment">注入后：</span>
<span class="sql-keyword">SELECT</span> * <span class="sql-keyword">FROM</span> <span class="sql-identifier">users</span> <span class="sql-keyword">WHERE</span> <span class="sql-identifier">id</span> = <span class="sql-number">1</span>; <span class="sql-keyword">DROP TABLE</span> <span class="sql-identifier">users</span>;--</code></pre>

                <div class="warning-box">
                    <h5><i class="fa fa-exclamation-triangle"></i> 条件限制</h5>
                    <ul>
                        <li>数据库必须支持多语句执行</li>
                        <li>PHP的mysqli_multi_query()函数或PDO配置允许</li>
                        <li>MySQL默认支持，但PHP代码通常使用单语句执行函数</li>
                    </ul>
                </div>
            </div>

            <!--+二次注入 -->
            <div class="code-display-section">
                <h4><i class="fa fa-sync"></i> 二次注入</h4>

                <div class="info-box">
                    <h5><i class="fa fa-info-circle"></i> 概念</h5>
                    <p>二次注入是指恶意数据先被存储到数据库中，在后续使用时被当作SQL代码执行。</p>
                </div>

                <pre class="static-code-block"><code><span class="sql-comment">// 步骤1：注册时（安全存储）</span>
$username = $_POST[<span class="sql-string">'username'</span>]; <span class="sql-comment">// admin'--</span>
$pdo->query(<span class="sql-string">"INSERT INTO users (username) VALUES ('$username')"</span>);
<span class="sql-comment">// 存储的数据：admin'--</span>

<span class="sql-comment">// 步骤2：修改密码时（二次注入）</span>
$username = $_SESSION[<span class="sql-string">'username'</span>]; <span class="sql-comment">// 从数据库读取：admin'--</span>
$pdo->query(<span class="sql-string">"UPDATE users SET password='$newpass' WHERE username='$username'"</span>);
<span class="sql-comment">// 实际执行：UPDATE users SET password='xxx' WHERE username='admin'--'</span></code></pre>
            </div>

            <!--+宽字节注入 -->
            <div class="code-display-section">
                <h4><i class="fa fa-language"></i> 宽字节注入</h4>

                <div class="info-box">
                    <h5><i class="fa fa-info-circle"></i> 概念</h5>
                    <p>当数据库使用GBK编码时，攻击者可以利用宽字节特性绕过转义。</p>
                </div>

                <pre class="static-code-block"><code><span class="sql-comment">// PHP代码使用addslashes转义</span>
$id = addslashes($_GET[<span class="sql-string">'id'</span>]);
<span class="sql-comment">// 输入：運' 转义后：運\'</span>
<span class="sql-comment">// 在GBK编码下，運\会被当作一个汉字，从而吃掉转义符</span>

<span class="sql-comment">输入：?id=運' UNION SELECT 1,2,3--</span>
<span class="sql-comment">转义后：運\' UNION SELECT 1,2,3--</span>
<span class="sql-comment">GBK解析：[運\] ' UNION SELECT 1,2,3--</span></code></pre>
            </div>

            <!--+防御措施 -->
            <div class="code-display-section">
                <h4><i class="fa fa-shield-alt"></i> SQL注入防御措施</h4>

                <div class="success-box">
                    <h5><i class="fa fa-check-circle"></i> 最佳防御实践</h5>
                    <ul>
                        <li><strong>使用预处理语句</strong>：最有效的防御方法</li>
                        <li><strong>输入验证</strong>：对用户输入进行严格的类型和格式验证</li>
                        <li><strong>最小权限原则</strong>：数据库用户应仅具有必要的最小权限</li>
                        <li><strong>错误信息处理</strong>：不要在生产环境显示详细错误信息</li>
                        <li><strong>WAF防护</strong>：使用Web应用防火墙</li>
                    </ul>
                </div>

                <pre class="static-code-block"><code><span class="sql-comment">// 安全代码示例：使用PDO预处理</span>
$stmt = $pdo->prepare(<span class="sql-string">"SELECT * FROM users WHERE id = ? AND username = ?"</span>);
$stmt->execute([$id, $username]);

<span class="sql-comment">// 或者使用命名参数</span>
$stmt = $pdo->prepare(<span class="sql-string">"SELECT * FROM users WHERE id = :id"</span>);
$stmt->bindParam(<span class="sql-string">':id'</span>, $id, PDO::PARAM_INT);
$stmt->execute();</code></pre>
            </div>
        </div>
    </div>

    <!--+=====================================================
         区域5: 学习完成区
         ===================================================== -->
    <div class="collapsible-section" id="section5">
        <div class="collapsible-header" onclick="toggleSection('section5')">
            <span class="toggle-text">
                <i class="fa fa-graduation-cap"></i> 学习完成
            </span>
            <div class="toggle-btn"></div>
        </div>
        <div class="collapsible-content">
            <div class="chapter-title">
                <i class="fa fa-trophy"></i> 恭喜你完成SQL注入基础学习！
            </div>
            <div class="chapter-description">
                如果你已经掌握了SQL注入的基本原理、类型判断和数据提取方法，可以点击下方按钮标记学习完成。
            </div>

            <div class="mastery-section">
                <div class="mastery-button-container">
                    <button type="button" class="heasec-mastery-btn" id="sqlMasteryBtn" onclick="showMasteryCongrats()">
                        <i class="fa fa-check-circle"></i>
                        我已掌握
                    </button>
                </div>
                <p class="mastery-intro">完成本靶场学习后，你应该理解了：</p>
                <div class="mastery-grid">
                    <div class="mastery-card">
                        <div class="mastery-card-icon">📖</div>
                        <div class="mastery-card-title">漏洞原理</div>
                        <div class="mastery-card-desc">理解SQL注入漏洞的产生原理，掌握前后端数据交互中用户输入被拼接进SQL语句导致的安全风险</div>
                    </div>
                    <div class="mastery-card">
                        <div class="mastery-card-icon">🔍</div>
                        <div class="mastery-card-title">类型判断</div>
                        <div class="mastery-card-desc">能够区分数字型注入和字符型注入，掌握通过引号、注释符等判断注入点类型的方法</div>
                    </div>
                    <div class="mastery-card">
                        <div class="mastery-card-icon">🎯</div>
                        <div class="mastery-card-title">数据提取</div>
                        <div class="mastery-card-desc">掌握使用UNION注入获取数据的方法，了解回显注入、报错注入、盲注等不同数据提取技术</div>
                    </div>
                    <div class="mastery-card">
                        <div class="mastery-card-icon">🛡️</div>
                        <div class="mastery-card-title">防御方法</div>
                        <div class="mastery-card-desc">理解预处理语句（Prepared Statement）等SQL注入防御方法的原理和重要性</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!--+引入星星系统组件的JavaScript -->
<script src="<?php echo $commonBasePath; ?>components/star-system/js/heasec-congrats-modal.js"></script>

<!--+配置变量 -->
<script>
window.heasecConfig = {
    commonBasePath: '<?php echo $commonBasePath; ?>',
    apiBasePath: './api/'
};
</script>

<!--+引入页面JavaScript文件 -->
<script src="js/main.js?v=v1.2.0"></script>

<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>
