<?php
/**
 * HeaSec天积安全团队 - HTML语言基础靶场
 * 版本: v1.0.0
 * 创建日期: 2025-12-02
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec HTML语言基础 Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = 'HTML语言基础靶场';
$rangeName = 'HTML语言基础';
$showVersion = false;
$showResetButton = true;
$version = 'v1.0.0';

// 设置公共组件的基础路径（从靶场目录到range/common/的相对路径）
$commonBasePath = '../../../common/';

// 定义常量允许访问公共组件
define('HEASEC_RANGE_ACCESS', true);

// 引入代码编辑器组件
require_once $commonBasePath . 'components/code-editor/includes/HeaSec_CodeEditor.php';

// 引入章节示例代码
require_once 'chaptercode.php';

// 引入公共头部
require_once $commonBasePath . 'includes/header.php';
?>

<!-- 引入参考靶场的蓝紫渐变样式 -->
<link rel="stylesheet" href="../../http/httpxyjx/css/style_blue_purple_gradient.css">

<!-- 引入自定义样式 -->
<link rel="stylesheet" href="./css/style.css">

<!-- 引入星星系统组件的CSS样式 -->
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>components/star-system/css/heasec-congrats-modal.css">

<!-- 靶场主要内容 -->
<div class="heasec-container">
  
    <!-- 区域1: HTML基本语法展示 -->
    <div class="collapsible-section expanded" id="section1">
        <div class="collapsible-header" onclick="toggleSection('section1')">
            <span class="toggle-text">
                <i class="fa fa-code"></i> HTML基本语法
            </span>
            <div class="toggle-btn"></div>
        </div>
        <div class="collapsible-content">
            <div class="chapter-title">
                <i class="fa fa-sitemap"></i> HTML元素的结构和语法规则
            </div>
            <div class="chapter-description">
                每个HTML元素都由开始标签、属性、元素内容和结束标签组成。以下是HTML语法的基本格式和示例分析。
            </div>

            <div class="code-display-section">
                <h4><i class="fa fa-file-code-o"></i> HTML元素的基本结构</h4>
                <pre class="static-code-block"><code><span class="tag">&lt;标签名</span> <span class="attr-name">属性名</span>=<span class="attr-value">"属性值"</span><span class="tag">&gt;</span><span class="content">元素内容</span><span class="tag">&lt;/标签名&gt;</span></code></pre>

                <div class="composition-explanation">
                    <h5><i class="fa fa-info-circle"></i> 📌 组成部分说明：</h5>
                    <ul>
                        <li><strong>开始标签</strong> <code>&lt;标签名&gt;</code>：定义元素的开始</li>
                        <li><strong>属性</strong> <code>属性名="属性值"</code>：提供元素的额外信息</li>
                        <li><strong>元素内容</strong>：标签之间的文本或其他元素</li>
                        <li><strong>结束标签</strong> <code>&lt;/标签名&gt;</code>：定义元素的结束</li>
                    </ul>
                </div>

                <h4><i class="fa fa-file-code-o"></i> 示例1: 简单的段落元素</h4>
                <pre class="static-code-block"><code><span class="tag">&lt;p&gt;</span><span class="content">这是一个段落</span><span class="tag">&lt;/p&gt;</span></code></pre>

                <div class="example-explanation">
                    <h5><i class="fa fa-search"></i> 解析：</h5>
                    <ul>
                        <li><code>&lt;p&gt;</code> - 开始标签，表示段落开始</li>
                        <li><code>这是一个段落</code> - 元素内容，即显示的文本</li>
                        <li><code>&lt;/p&gt;</code> - 结束标签，表示段落结束</li>
                    </ul>
                </div>

                <h4><i class="fa fa-file-code-o"></i> 示例2: 带属性的链接元素</h4>
                <pre class="static-code-block"><code><span class="tag">&lt;a</span> <span class="attr-name">href</span>=<span class="attr-value">"https://www.example.com"</span> <span class="attr-name">target</span>=<span class="attr-value">"_blank"</span> <span class="attr-name">title</span>=<span class="attr-value">"点击访问示例网站"</span><span class="tag">&gt;</span><span class="content">访问示例网站</span><span class="tag">&lt;/a&gt;</span></code></pre>

                <div class="example-explanation">
                    <h5><i class="fa fa-search"></i> 解析：</h5>
                    <ul>
                        <li><code>&lt;a&gt;</code> - 开始标签，表示链接</li>
                        <li><code>href="https://www.baidu.com"</code> - href属性，指定链接地址</li>
                        <li><code>target="_blank"</code> - target属性，在新窗口打开</li>
                        <li><code>title="点击访问示例网站"</code> - title属性，鼠标悬停时显示提示</li>
                        <li><code>访问示例网站</code> - 链接文本</li>
                        <li><code>&lt;/a&gt;</code> - 结束标签</li>
                    </ul>
                </div>

                <h4><i class="fa fa-file-code-o"></i> 示例3: 自闭合图片元素</h4>
                <pre class="static-code-block"><code><span class="tag">&lt;img</span> <span class="attr-name">src</span>=<span class="attr-value">"logo.png"</span> <span class="attr-name">alt</span>=<span class="attr-value">"网站Logo"</span> <span class="attr-name">width</span>=<span class="attr-value">"200"</span> <span class="attr-name">height</span>=<span class="attr-value">"100"</span><span class="tag">&gt;</span></code></pre>

                <div class="example-explanation">
                    <h5><i class="fa fa-search"></i> 解析：</h5>
                    <ul>
                        <li><code>&lt;img&gt;</code> - 图片开始标签</li>
                        <li><code>src="logo.png"</code> - src属性，指定图片路径</li>
                        <li><code>alt="网站Logo"</code> - alt属性，图片无法显示时的替代文本</li>
                        <li><code>width="200"</code> - width属性，图片宽度</li>
                        <li><code>height="100"</code> - height属性，图片高度</li>
                        <li><strong>注意：</strong> img是自闭合标签，不需要结束标签</li>
                    </ul>
                </div>

                <h4><i class="fa fa-file-code-o"></i> 语法规则总结</h4>
                <pre class="static-code-block"><code><span class="comment">&lt;!-- 1. 标签名小写 --&gt;</span>
<span class="tag">&lt;p&gt;</span><span class="content">正确</span><span class="tag">&lt;/p&gt;</span>      <span class="comment">&lt;!-- ✅ 推荐 --&gt;</span>
<span class="tag">&lt;P&gt;</span><span class="content">也可以</span><span class="tag">&lt;/P&gt;</span>    <span class="comment">&lt;!-- ⚠️ 不推荐 --&gt;</span>

<span class="comment">&lt;!-- 2. 属性值用引号包围 --&gt;</span>
<span class="tag">&lt;div</span> <span class="attr-name">class</span>=<span class="attr-value">"container"</span><span class="tag">&gt;</span><span class="content">正确</span><span class="tag">&lt;/div&gt;</span>      <span class="comment">&lt;!-- ✅ 推荐 --&gt;</span>
<span class="tag">&lt;div</span> <span class="attr-name">class</span>=<span class="attr-value">container</span><span class="tag">&gt;</span><span class="content">不推荐</span><span class="tag">&lt;/div&gt;</span>     <span class="comment">&lt;!-- ⚠️ 可能出错 --&gt;</span>

<span class="comment">&lt;!-- 3. 正确嵌套 --&gt;</span>
<span class="tag">&lt;div&gt;&lt;p&gt;</span><span class="content">正确的嵌套</span><span class="tag">&lt;/p&gt;&lt;/div&gt;</span>           <span class="comment">&lt;!-- ✅ 正确 --&gt;</span>
<span class="tag">&lt;div&gt;&lt;p&gt;</span><span class="content">错误的嵌套</span><span class="tag">&lt;/div&gt;&lt;/p&gt;</span>           <span class="comment">&lt;!-- ❌ 错误 --&gt;</span>

<span class="comment">&lt;!-- 4. 自闭合标签 --&gt;</span>
<span class="tag">&lt;br&gt;</span> <span class="tag">&lt;img</span> <span class="attr-name">src</span>=<span class="attr-value">"photo.jpg"</span><span class="tag">&gt;</span> <span class="tag">&lt;hr&gt;</span>       <span class="comment">&lt;!-- ✅ HTML5中可以直接使用 --&gt;</span>
<span class="tag">&lt;br /&gt;</span> <span class="tag">&lt;img</span> <span class="attr-name">src</span>=<span class="attr-value">"photo.jpg"</span> <span class="tag">/&gt;</span> <span class="tag">&lt;hr /&gt;</span> <span class="comment">&lt;!-- ✅ XHTML风格，也可以使用 --&gt;</span></code></pre>

                <h4><i class="fa fa-file-code-o"></i> 属性的使用</h4>
                <pre class="static-code-block"><code><span class="comment">&lt;!-- 常见属性示例 --&gt;</span>
<span class="tag">&lt;div</span> <span class="attr-name">id</span>=<span class="attr-value">"main-content"</span> <span class="attr-name">class</span>=<span class="attr-value">"container"</span> <span class="attr-name">data-role</span>=<span class="attr-value">"page"</span><span class="tag">&gt;</span>
    <span class="tag">&lt;input</span> <span class="attr-name">type</span>=<span class="attr-value">"text"</span> <span class="attr-name">name</span>=<span class="attr-value">"username"</span> <span class="attr-name">required</span> <span class="attr-name">disabled</span><span class="tag">&gt;</span>
    <span class="tag">&lt;button</span> <span class="attr-name">type</span>=<span class="attr-value">"submit"</span> <span class="attr-name">onclick</span>=<span class="attr-value">"submitForm()"</span><span class="tag">&gt;</span><span class="content">提交</span><span class="tag">&lt;/button&gt;</span>
<span class="tag">&lt;/div&gt;</span></code></pre>

                <div class="example-explanation">
                    <h5><i class="fa fa-search"></i> 属性的基本规则：</h5>
                    <ul>
                        <li><strong>属性名</strong>：小写，使用连字符分隔（如 <code>data-id</code>）</li>
                        <li><strong>属性值</strong>：必须用引号包围（单引号或双引号）</li>
                        <li><strong>布尔属性</strong>：可以只写属性名，不写值（如 <code>disabled</code>）</li>
                    </ul>
                </div>

                <h4><i class="fa fa-file-code-o"></i> 注释的使用</h4>
                <pre class="static-code-block"><code><span class="comment">&lt;!-- 这是一个单行注释 --&gt;</span>
<span class="comment">&lt;!--
    这是一个多行注释
    可以跨越多行
    用于详细说明
--&gt;</span>
<span class="tag">&lt;p&gt;</span><span class="content">这段文字会被正常显示</span><span class="tag">&lt;/p&gt;</span> <span class="comment">&lt;!-- 行尾注释 --&gt;</span></code></pre>

                <div class="example-explanation">
                    <h5><i class="fa fa-search"></i> 注释的使用说明：</h5>
                    <ul>
                        <li><strong>单行注释</strong>：<code>&lt;!-- 注释内容 --&gt;</code></li>
                        <li><strong>多行注释</strong>：可以跨越多行，用于详细说明</li>
                        <li><strong>行尾注释</strong>：在代码行末添加注释，说明该行代码作用</li>
                        <li><strong>注意</strong>：HTML注释不会在浏览器中显示</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- 区域2: HTML文档结构 -->
    <div class="collapsible-section" id="section2">
        <div class="collapsible-header" onclick="toggleSection('section2')">
            <span class="toggle-text">
                <i class="fa fa-file-code-o"></i> HTML文档结构
            </span>
            <div class="toggle-btn"></div>
        </div>
        <div class="collapsible-content">
            <div class="chapter-title">
                <i class="fa fa-sitemap"></i> HTML文档结构练习
            </div>
            <div class="chapter-description">
                学习HTML文档的基础结构，包括文档类型声明、字符编码、页面标题等核心元素。
            </div>

            <?php
            echo renderCodeEditorLazy([
                'cardTitle' => 'HTML文档结构练习',
                'cardIcon' => 'fa fa-file-code-o',
                'height' => '500px',
                'defaultLanguage' => 'html',
                'defaultCode' => [
                    'html' => $chapterCodes['html_structure']['html'],
                    'css' => $chapterCodes['html_structure']['css'],
                    'javascript' => $chapterCodes['html_structure']['javascript']
                ],
                'runButtonText' => '运行代码',
                'clearButtonText' => '清空',
                'resetButtonText' => '重置'
            ]);
            ?>
        </div>
    </div>

    <!-- 区域3: 文本标签 -->
    <div class="collapsible-section" id="section3">
        <div class="collapsible-header" onclick="toggleSection('section3')">
            <span class="toggle-text">
                <i class="fa fa-font"></i> 文本标签
            </span>
            <div class="toggle-btn"></div>
        </div>
        <div class="collapsible-content">
            <div class="chapter-title">
                <i class="fa fa-text-width"></i> 文本标签练习
            </div>
            <div class="chapter-description">
                学习HTML中常用的文本相关标签，包括标题、段落、换行和分割线，以及各种文本格式化标签。
            </div>

            <?php
            echo renderCodeEditorLazy([
                'cardTitle' => '文本标签练习',
                'cardIcon' => 'fa fa-font',
                'height' => '600px',
                'defaultLanguage' => 'html',
                'defaultCode' => [
                    'html' => $chapterCodes['text_tags']['html'],
                    'css' => $chapterCodes['text_tags']['css'],
                    'javascript' => $chapterCodes['text_tags']['javascript']
                ],
                'runButtonText' => '运行代码',
                'clearButtonText' => '清空',
                'resetButtonText' => '重置'
            ]);
            ?>
        </div>
    </div>

    <!-- 区域4: 链接和图片 -->
    <div class="collapsible-section" id="section4">
        <div class="collapsible-header" onclick="toggleSection('section4')">
            <span class="toggle-text">
                <i class="fa fa-link"></i> 链接和图片
            </span>
            <div class="toggle-btn"></div>
        </div>
        <div class="collapsible-content">
            <div class="chapter-title">
                <i class="fa fa-image"></i> 链接和图片练习
            </div>
            <div class="chapter-description">
                学习HTML中常用的链接和图片标签的各种用法，包括基本超链接、图片显示、图片链接等。
            </div>

            <?php
            echo renderCodeEditorLazy([
                'cardTitle' => '链接和图片练习',
                'cardIcon' => 'fa fa-link',
                'height' => '550px',
                'defaultLanguage' => 'html',
                'defaultCode' => [
                    'html' => $chapterCodes['links_images']['html'],
                    'css' => $chapterCodes['links_images']['css'],
                    'javascript' => $chapterCodes['links_images']['javascript']
                ],
                'runButtonText' => '运行代码',
                'clearButtonText' => '清空',
                'resetButtonText' => '重置'
            ]);
            ?>
        </div>
    </div>

    <!-- 区域5: 表格标签 -->
    <div class="collapsible-section" id="section5">
        <div class="collapsible-header" onclick="toggleSection('section5')">
            <span class="toggle-text">
                <i class="fa fa-table"></i> 表格标签
            </span>
            <div class="toggle-btn"></div>
        </div>
        <div class="collapsible-content">
            <div class="chapter-title">
                <i class="fa fa-th-list"></i> 表格标签练习
            </div>
            <div class="chapter-description">
                学习表格的完整结构，包括表头、主体、底部，以及表格的基本属性设置。
            </div>

            <?php
            echo renderCodeEditorLazy([
                'cardTitle' => '表格标签练习',
                'cardIcon' => 'fa fa-table',
                'height' => '600px',
                'defaultLanguage' => 'html',
                'defaultCode' => [
                    'html' => $chapterCodes['tables']['html'],
                    'css' => $chapterCodes['tables']['css'],
                    'javascript' => $chapterCodes['tables']['javascript']
                ],
                'runButtonText' => '运行代码',
                'clearButtonText' => '清空',
                'resetButtonText' => '重置'
            ]);
            ?>
        </div>
    </div>

    <!-- 区域6: 表单基础 -->
    <div class="collapsible-section" id="section6">
        <div class="collapsible-header" onclick="toggleSection('section6')">
            <span class="toggle-text">
                <i class="fa fa-wpforms"></i> 表单基础
            </span>
            <div class="toggle-btn"></div>
        </div>
        <div class="collapsible-content">
            <div class="chapter-title">
                <i class="fa fa-check-square-o"></i> 表单基础练习
            </div>
            <div class="chapter-description">
                学习表单的基本结构和各种输入类型，包括文本、密码、数字、邮箱、日期、文件上传等。
            </div>

            <?php
            echo renderCodeEditorLazy([
                'cardTitle' => '表单基础练习',
                'cardIcon' => 'fa fa-wpforms',
                'height' => '700px',
                'defaultLanguage' => 'html',
                'defaultCode' => [
                    'html' => $chapterCodes['forms']['html'],
                    'css' => $chapterCodes['forms']['css'],
                    'javascript' => $chapterCodes['forms']['javascript']
                ],
                'runButtonText' => '运行代码',
                'clearButtonText' => '清空',
                'resetButtonText' => '重置'
            ]);
            ?>
        </div>
    </div>

    <!-- 区域7: CSS基础（拓展） -->
    <div class="collapsible-section" id="section7">
        <div class="collapsible-header" onclick="toggleSection('section7')">
            <span class="toggle-text">
                <i class="fa fa-css3"></i> CSS基础（拓展）
            </span>
            <div class="toggle-btn"></div>
        </div>
        <div class="collapsible-content">
            <div class="chapter-title">
                <i class="fa fa-paint-brush"></i> CSS与HTML结合练习
            </div>
            <div class="chapter-description">
                学习CSS与HTML结合的三种方式（内联样式、内部样式表、外部样式表），以及常用的CSS选择器、盒子模型、定位和布局等样式优化技巧。
            </div>

            <div class="code-display-section">
                <h4><i class="fa fa-file-code-o"></i> CSS规则的基本结构和引入方式</h4>
                <pre class="static-code-block"><code><span class="selector">选择器</span> <span class="brace">{</span>
    <span class="property">属性名</span><span class="punctuation">:</span> <span class="value">属性值</span><span class="punctuation">;</span>
    <span class="property">属性名</span><span class="punctuation">:</span> <span class="value">属性值</span><span class="punctuation">;</span>
<span class="brace">}</span></code></pre>

                <div class="composition-explanation">
                    <h5><i class="fa fa-info-circle"></i> 📌 组成部分说明：</h5>
                    <ul>
                        <li><strong>选择器</strong>：指定要应用样式的HTML元素</li>
                        <li><strong>属性名</strong>：要设置的CSS属性（如color、font-size等）</li>
                        <li><strong>属性值</strong>：属性的具体值</li>
                        <li><strong>声明块</strong>：用花括号<code>{}</code>包围的属性声明集合</li>
                    </ul>
                </div>

                <h4><i class="fa fa-file-code-o"></i> CSS的三种引入方式</h4>
                <pre class="static-code-block"><code><span class="comment">&lt;!-- 方式1: 内联样式 --&gt;</span>
<span class="tag">&lt;p</span> <span class="attr-name">style</span>=<span class="attr-value">"color: red; font-size: 16px;"</span><span class="tag">&gt;</span><span class="content">内联样式</span><span class="tag">&lt;/p&gt;</span>

<span class="comment">&lt;!-- 方式2: 内部样式表 --&gt;</span>
<span class="tag">&lt;style&gt;</span>
<span class="selector">p</span> <span class="brace">{</span>
    <span class="property">color</span><span class="punctuation">:</span> <span class="value">blue</span><span class="punctuation">;</span>
<span class="brace">}</span>
<span class="tag">&lt;/style&gt;</span>

<span class="comment">&lt;!-- 方式3: 外部样式表 --&gt;</span>
<span class="tag">&lt;link</span> <span class="attr-name">rel</span>=<span class="attr-value">"stylesheet"</span> <span class="attr-name">href</span>=<span class="attr-value">"styles.css"</span><span class="tag">&gt;</span></code></pre>

                <div class="composition-explanation">
                    <h5><i class="fa fa-info-circle"></i> 📌 引入方式说明：</h5>
                    <ul>
                        <li><strong>内联样式</strong>：直接在元素的<code>style</code>属性中写CSS，优先级最高</li>
                        <li><strong>内部样式表</strong>：在HTML文档的<code>&lt;style&gt;</code>标签中写CSS</li>
                        <li><strong>外部样式表</strong>：将CSS写在单独的.css文件中，通过<code>&lt;link&gt;</code>标签引入，推荐使用</li>
                    </ul>
                </div>

                <h4><i class="fa fa-file-code-o"></i> 示例1: 基本的CSS规则</h4>
                <pre class="static-code-block"><code><span class="selector">p</span> <span class="brace">{</span>
    <span class="property">color</span><span class="punctuation">:</span> <span class="value">red</span><span class="punctuation">;</span>
    <span class="property">font-size</span><span class="punctuation">:</span> <span class="value">16px</span><span class="punctuation">;</span>
    <span class="property">text-align</span><span class="punctuation">:</span> <span class="value">center</span><span class="punctuation">;</span>
<span class="brace">}</span></code></pre>

                <div class="example-explanation">
                    <h5><i class="fa fa-search"></i> 解析：</h5>
                    <ul>
                        <li><code>p</code> - 元素选择器，选择所有段落元素</li>
                        <li><code>color: red;</code> - 设置文字颜色为红色</li>
                        <li><code>font-size: 16px;</code> - 设置字体大小为16像素</li>
                        <li><code>text-align: center;</code> - 设置文本居中对齐</li>
                    </ul>
                </div>

                <h4><i class="fa fa-file-code-o"></i> 示例2: 类选择器和ID选择器</h4>
                <pre class="static-code-block"><code><span class="comment">/* 类选择器 */</span>
<span class="selector">.highlight</span> <span class="brace">{</span>
    <span class="property">background-color</span><span class="punctuation">:</span> <span class="value">yellow</span><span class="punctuation">;</span>
    <span class="property">font-weight</span><span class="punctuation">:</span> <span class="value">bold</span><span class="punctuation">;</span>
<span class="brace">}</span>

<span class="comment">/* ID选择器 */</span>
<span class="selector">#main-title</span> <span class="brace">{</span>
    <span class="property">color</span><span class="punctuation">:</span> <span class="value">blue</span><span class="punctuation">;</span>
    <span class="property">font-size</span><span class="punctuation">:</span> <span class="value">24px</span><span class="punctuation">;</span>
<span class="brace">}</span>

<span class="comment">/* 属性选择器 */</span>
<span class="selector">input[type="text"]</span> <span class="brace">{</span>
    <span class="property">border</span><span class="punctuation">:</span> <span class="value">1px solid #ccc</span><span class="punctuation">;</span>
    <span class="property">padding</span><span class="punctuation">:</span> <span class="value">5px</span><span class="punctuation">;</span>
<span class="brace">}</span></code></pre>

                <div class="example-explanation">
                    <h5><i class="fa fa-search"></i> 选择器说明：</h5>
                    <ul>
                        <li><code>.highlight</code> - 类选择器，选择<code>class="highlight"</code>的元素</li>
                        <li><code>#main-title</code> - ID选择器，选择<code>id="main-title"</code>的元素</li>
                        <li><code>input[type="text"]</code> - 属性选择器，选择type属性为text的input元素</li>
                    </ul>
                </div>

                <h4><i class="fa fa-file-code-o"></i> CSS常用属性</h4>
                <pre class="static-code-block"><code><span class="selector">.box</span> <span class="brace">{</span>
    <span class="comment">/* 文字属性 */</span>
    <span class="property">color</span><span class="punctuation">:</span> <span class="value">#333</span><span class="punctuation">;</span>                 <span class="comment">/* 文字颜色 */</span>
    <span class="property">font-size</span><span class="punctuation">:</span> <span class="value">14px</span><span class="punctuation">;</span>              <span class="comment">/* 字体大小 */</span>
    <span class="property">font-weight</span><span class="punctuation">:</span> <span class="value">bold</span><span class="punctuation">;</span>            <span class="comment">/* 字体粗细 */</span>
    <span class="property">text-align</span><span class="punctuation">:</span> <span class="value">center</span><span class="punctuation">;</span>         <span class="comment">/* 文本对齐 */</span>

    <span class="comment">/* 背景属性 */</span>
    <span class="property">background-color</span><span class="punctuation">:</span> <span class="value">#f5f5f5</span><span class="punctuation">;</span>     <span class="comment">/* 背景颜色 */</span>
    <span class="property">background-image</span><span class="punctuation">:</span> <span class="value">url('bg.png')</span><span class="punctuation">;</span> <span class="comment">/* 背景图片 */</span>

    <span class="comment">/* 边框属性 */</span>
    <span class="property">border</span><span class="punctuation">:</span> <span class="value">1px solid #ddd</span><span class="punctuation">;</span>        <span class="comment">/* 边框 */</span>
    <span class="property">border-radius</span><span class="punctuation">:</span> <span class="value">5px</span><span class="punctuation">;</span>          <span class="comment">/* 圆角 */</span>

    <span class="comment">/* 尺寸属性 */</span>
    <span class="property">width</span><span class="punctuation">:</span> <span class="value">200px</span><span class="punctuation">;</span>                <span class="comment">/* 宽度 */</span>
    <span class="property">height</span><span class="punctuation">:</span> <span class="value">100px</span><span class="punctuation">;</span>               <span class="comment">/* 高度 */</span>

    <span class="comment">/* 内外边距 */</span>
    <span class="property">padding</span><span class="punctuation">:</span> <span class="value">10px</span><span class="punctuation">;</span>               <span class="comment">/* 内边距 */</span>
    <span class="property">margin</span><span class="punctuation">:</span> <span class="value">20px</span><span class="punctuation">;</span>                <span class="comment">/* 外边距 */</span>
<span class="brace">}</span></code></pre>

                <h4><i class="fa fa-file-code-o"></i> CSS注释的使用</h4>
                <pre class="static-code-block"><code><span class="comment">/* 这是一个单行注释 */</span>

<span class="comment">/*
    这是一个多行注释
    可以跨越多行
    用于详细说明CSS代码的作用
*/</span>

<span class="selector">p</span> <span class="brace">{</span>
    <span class="property">color</span><span class="punctuation">:</span> <span class="value">red</span><span class="punctuation">;</span> <span class="comment">/* 行尾注释 */</span>
<span class="brace">}</span></code></pre>
            </div>

            <?php
            echo renderCodeEditorLazy([
                'cardTitle' => 'CSS与HTML结合练习',
                'cardIcon' => 'fa fa-css3',
                'height' => '700px',
                'defaultLanguage' => 'html',
                'defaultCode' => [
                    'html' => $chapterCodes['css_html_combined']['html'],
                    'css' => $chapterCodes['css_html_combined']['css'],
                    'javascript' => $chapterCodes['css_html_combined']['javascript']
                ],
                'runButtonText' => '运行代码',
                'clearButtonText' => '清空',
                'resetButtonText' => '重置'
            ]);
            ?>
        </div>
    </div>

    <!-- 区域8: 学习完成确认 -->
    <div class="collapsible-section" id="section8">
        <div class="collapsible-header" onclick="toggleSection('section8')">
            <span class="toggle-text">
                <i class="fa fa-graduation-cap"></i> 学习完成
            </span>
            <div class="toggle-btn"></div>
        </div>
        <div class="collapsible-content">
 
            <div class="chapter-description">
                如果你已经掌握了HTML的基本语法，包括文档结构、文本标签、链接图片、表格和表单的使用，可以点击下方的"我已掌握"按钮来标记你的学习成果。
            </div>

            <!-- 我已掌握按钮区域 -->

                <div class="mastery-button-container">
                    <button type="button" class="heasec-mastery-btn" id="htmlMasteryBtn" onclick="showMasteryCongrats()">
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

<!-- 引入页面JavaScript文件 -->
<script src="js/main.js?v=v1.1.0"></script>

<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>