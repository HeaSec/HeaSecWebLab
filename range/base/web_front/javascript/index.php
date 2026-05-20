<?php
/**
 * HeaSec天积安全团队 - JavaScript语言基础靶场
 * 版本: v1.0.0
 * 创建日期: 2025-12-16
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec JavaScript语言基础 Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = 'JavaScript语言基础靶场';
$rangeName = 'JavaScript语言基础';
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

<!-- 引入自定义样式 -->
<link rel="stylesheet" href="./css/style.css">

<!-- 引入Prism.js CSS -->
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>assets/css/prism-tomorrow.min.css" />

<!-- 引入星星系统组件的CSS样式 -->
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>components/star-system/css/heasec-congrats-modal.css">

<!-- 靶场主要内容 -->
<div class="heasec-container">

    <!-- 区域1: JavaScript基本语法展示 -->
    <div class="collapsible-section expanded" id="section1">
        <div class="collapsible-header" onclick="toggleSection('section1')">
            <span class="toggle-text">
                <i class="fa fa-code"></i> JavaScript基本语法
            </span>
            <div class="toggle-btn"></div>
        </div>
        <div class="collapsible-content">
            <div class="chapter-title">
                <i class="fa fa-sitemap"></i> JavaScript语言的基本语法
            </div>
            <div class="chapter-description">
                JavaScript是一种用于网页交互的脚本语言。它可以让网页动态响应、验证表单、更新内容，而不需要重新加载页面。
            </div>

            <div class="js-syntax-display">
                <h4><i class="fa fa-file-code-o"></i> JavaScript代码结构</h4>
                <pre><code class="language-javascript">// 这是JavaScript代码的基本结构示例

/*
 * 多行注释可以跨越多行
 * 用于详细说明代码的功能
 */

// 1. 变量声明 - JavaScript有三种声明方式
var oldVariable = "传统变量";  // 函数作用域
let modernVariable = "现代变量"; // 块级作用域
const constantValue = "常量值";   // 不可重新赋值

// 2. 数据类型 - JavaScript有7种基本数据类型
var number = 25;                    // 数字类型
var text = "Hello, World!";        // 字符串类型
var isTrue = true;                  // 布尔类型
var nothing;                        // 未定义类型
var emptyValue = null;              // 空值类型

// 3. 数组和对象 - 复合数据类型
var fruits = ["苹果", "香蕉", "橙子"];     // 数组
var person = {                       // 对象
    name: "张三",
    age: 30,
    city: "北京"
};

// 4. 类型检测
var typeOfNumber = typeof number;       // 检测变量类型
console.log("数字类型：" + typeOfNumber);

// 5. 运算符 - 算术、比较、逻辑运算符
var sum = 10 + 5;        // 算术运算：加法
var isEqual = (10 === "10"); // 比较运算：严格等于
var andResult = true && false;    // 逻辑运算：与

// 5. 控制语句 - 条件语句和循环
if (score >= 90) {
    console.log("优秀");
} else if (score >= 80) {
    console.log("良好");
} else {
    console.log("及格");
}

for (var i = 0; i < 5; i++) {
    console.log("循环次数：" + i);
}

// 6. 函数定义和调用
function greet(name) {
    return "你好，" + name + "！";
}

var message = greet("张三");
console.log(message);  // 输出：你好，张三！

console.log("[HeaSec] JavaScript基本语法示例");</code></pre>

                <div class="syntax-explanation">
                    <h5><i class="fa fa-info-circle"></i> 📌 基本规则说明：</h5>
                    <ul>
                        <li><strong>语句</strong>：以分号结束（可选但推荐）</li>
                        <li><strong>注释</strong>：单行注释用<code>//</code>，多行注释用<code>/* */</code></li>
                        <li><strong>大小写敏感</strong>：<code>myVariable</code>和<code>myvariable</code>是不同的变量</li>
                        <li><strong>代码块</strong>：使用花括号<code>{}</code>包裹</li>
                    </ul>
                </div>

                <h4><i class="fa fa-file-code-o"></i> JavaScript在HTML中的引入方式</h4>
                <pre><code class="language-markup">// 1. 内联脚本 - 直接在HTML文件中使用script标签
&lt;script&gt;
    alert("页面加载完成！");
&lt;/script&gt;

// 2. 外部脚本 - 引入单独的.js文件
&lt;script src="js/myScript.js"&gt;&lt;/script&gt;

// 3. 事件处理器 - 在HTML元素的事件属性中编写
&lt;button onclick="alert('按钮被点击了！')"&gt;点击我&lt;/button&gt;</code></pre>

                <div class="syntax-explanation">
                    <h5><i class="fa fa-info-circle"></i> 📌 引入方式说明：</h5>
                    <ul>
                        <li><strong>内联脚本</strong>：代码直接写在HTML中，适合小段脚本</li>
                        <li><strong>外部脚本</strong>：代码独立存储，可缓存、便于维护</li>
                        <li><strong>事件处理器</strong>：直接绑定到HTML元素，响应特定事件</li>
                        <li><strong>加载顺序</strong>：脚本通常放在body结束标签前，确保DOM加载完成</li>
                    </ul>

                    <h5><i class="fa fa-lightbulb-o"></i> 💡 最佳实践：</h5>
                    <ul>
                        <li>使用外部脚本便于代码复用和维护</li>
                        <li>给script标签添加defer或async属性优化加载</li>
                        <li>避免在HTML中直接编写复杂的JavaScript代码</li>
                    </ul>
                </div>

                <h4><i class="fa fa-file-code-o"></i> JavaScript基本输出方式</h4>
                <pre><code class="language-javascript">// 1. 控制台输出 - console.log()
console.log("这是一条普通日志");
console.warn("这是一条警告信息");
console.error("这是一条错误信息");

// 2. 页面输出 - document.write()
document.write("&lt;h1&gt;欢迎学习JavaScript&lt;/h1&gt;");

// 3. 弹窗输出 - alert()
alert("操作成功！");

// 4. 确认对话框 - confirm()
var result = confirm("确定要删除吗？");

// 5. 输入对话框 - prompt()
var name = prompt("请输入您的姓名：", "访客");</code></pre>

                <div class="syntax-explanation">
                    <h5><i class="fa fa-info-circle"></i> 📌 输出方式说明：</h5>
                    <ul>
                        <li><strong>console.log()</strong>：在控制台输出，用于调试</li>
                        <li><strong>document.write()</strong>：直接写入页面，会覆盖现有内容</li>
                        <li><strong>alert()</strong>：警告弹窗，阻塞执行</li>
                        <li><strong>confirm()</strong>：确认弹窗，返回布尔值</li>
                        <li><strong>prompt()</strong>：输入弹窗，返回用户输入</li>
                    </ul>

                    <h5><i class="fa fa-exclamation-triangle"></i> ⚠️ 注意事项：</h5>
                    <ul>
                        <li>document.write()在页面加载完成后使用会覆盖整个页面</li>
                        <li>alert/confirm/prompt会阻塞页面执行，影响用户体验</li>
                        <li>生产环境应避免使用alert，使用更友好的提示方式</li>
                        <li>console.log仅在开发调试时使用</li>
                    </ul>
                </div>
                <h4><i class="fa fa-code-branch"></i> JavaScript控制语句</h4>
                <pre><code class="language-javascript">// JavaScript控制语句示例

// switch-case语句
var day = 3;
var dayName;
switch (day) {
    case 1: dayName = "星期一"; break;
    case 2: dayName = "星期二"; break;
    case 3: dayName = "星期三"; break;
    default: dayName = "未知";
}
console.log("今天是：" + dayName);

// while循环
var count = 0;
while (count < 3) {
    console.log("while循环：" + count);
    count++;
}

// do-while循环
var num = 0;
do {
    console.log("do-while循环：" + num);
    num++;
} while (num < 3);

console.log("[HeaSec] JavaScript控制语句示例");</code></pre>

                <div class="syntax-explanation">
                    <h5><i class="fa fa-info-circle"></i> 📌 控制语句要点：</h5>
                    <ul>
                        <li><strong>switch-case</strong>：适合多值判断，记得break</li>
                        <li><strong>while</strong>：先判断后执行，可能一次都不执行</li>
                        <li><strong>do-while</strong>：先执行后判断，至少执行一次</li>
                    </ul>
                </div>

                <h4><i class="fa fa-cogs"></i> JavaScript函数</h4>
                <pre><code class="language-javascript">// JavaScript函数进阶示例

// 函数表达式
var add = function(a, b) {
    return a + b;
};
console.log("5 + 3 = " + add(5, 3));

// 带默认参数的函数
function sayHello(name) {
    name = name || "访客";  // 兼容的默认参数写法
    console.log("欢迎，" + name);
}
sayHello();
sayHello("李四");

// 返回多个值的函数
function getUserInfo() {
    return {
        name: "王五",
        age: 30,
        city: "北京"
    };
}
var user = getUserInfo();
console.log(user.name + "来自" + user.city);

console.log("[HeaSec] JavaScript函数示例");</code></pre>

                <div class="syntax-explanation">
                    <h5><i class="fa fa-info-circle"></i> 📌 函数特性说明：</h5>
                    <ul>
                        <li><strong>函数表达式</strong>：将函数赋值给变量</li>
                        <li><strong>默认参数</strong>：使用 || 操作符设置默认值</li>
                        <li><strong>返回对象</strong>：可以返回包含多个值的对象</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- 区域2: DOM修改不同标签和位置 -->
    <div class="collapsible-section" id="section2">
        <div class="collapsible-header" onclick="toggleSection('section2')">
            <span class="toggle-text">
                <i class="fa fa-edit"></i> DOM修改不同标签和位置
            </span>
            <div class="toggle-btn"></div>
        </div>
        <div class="collapsible-content">
            <div class="chapter-title">
                <i class="fa fa-code"></i> DOM修改练习
            </div>
            <div class="chapter-description">
                学习如何使用JavaScript修改不同类型的HTML元素和内容，包括div、p、img、input等元素的修改方法。
            </div>

            <?php
            echo renderCodeEditorLazy([
                'cardTitle' => 'DOM修改不同标签和位置练习',
                'cardIcon' => 'fa fa-edit',
                'height' => '600px',
                'defaultCode' => [
                    'html' => $chapterCodes['dom_modification']['html'],
                    'css' => $chapterCodes['dom_modification']['css'],
                    'javascript' => $chapterCodes['dom_modification']['javascript']
                ],
                'runButtonText' => '运行代码',
                'clearButtonText' => '清空',
                'resetButtonText' => '重置'
            ]);
            ?>
        </div>
    </div>

    <!-- 区域3: DOM选择器使用 -->
    <div class="collapsible-section" id="section3">
        <div class="collapsible-header" onclick="toggleSection('section3')">
            <span class="toggle-text">
                <i class="fa fa-search"></i> DOM选择器使用
            </span>
            <div class="toggle-btn"></div>
        </div>
        <div class="collapsible-content">
            <div class="chapter-title">
                <i class="fa fa-crosshairs"></i> DOM选择器练习
            </div>
            <div class="chapter-description">
                学习JavaScript中不同的DOM选择器使用方法，包括getElementById、getElementsByClassName、querySelector等。
            </div>

            <?php
            echo renderCodeEditorLazy([
                'cardTitle' => 'DOM选择器使用练习',
                'cardIcon' => 'fa fa-search',
                'height' => '550px',
                'defaultCode' => [
                    'html' => $chapterCodes['dom_selectors']['html'],
                    'css' => $chapterCodes['dom_selectors']['css'],
                    'javascript' => $chapterCodes['dom_selectors']['javascript']
                ],
                'runButtonText' => '运行代码',
                'clearButtonText' => '清空',
                'resetButtonText' => '重置'
            ]);
            ?>
        </div>
    </div>

    <!-- 区域4: 事件与弹窗 -->
    <div class="collapsible-section" id="section4">
        <div class="collapsible-header" onclick="toggleSection('section4')">
            <span class="toggle-text">
                <i class="fa fa-mouse-pointer"></i> 事件与弹窗
            </span>
            <div class="toggle-btn"></div>
        </div>
        <div class="collapsible-content">
            <div class="chapter-title">
                <i class="fa fa-hand-pointer-o"></i> 事件与弹窗练习
            </div>
            <div class="chapter-description">
                学习JavaScript中不同类型弹窗的使用和常见事件处理，包括鼠标事件、键盘事件、表单事件等。
            </div>

            <?php
            echo renderCodeEditorLazy([
                'cardTitle' => '事件与弹窗练习',
                'cardIcon' => 'fa fa-mouse-pointer',
                'height' => '650px',
                'defaultCode' => [
                    'html' => $chapterCodes['events_popups']['html'],
                    'css' => $chapterCodes['events_popups']['css'],
                    'javascript' => $chapterCodes['events_popups']['javascript']
                ],
                'runButtonText' => '运行代码',
                'clearButtonText' => '清空',
                'resetButtonText' => '重置'
            ]);
            ?>
        </div>
    </div>

    <!-- 区域5: BOM操作 -->
    <div class="collapsible-section" id="section5">
        <div class="collapsible-header" onclick="toggleSection('section5')">
            <span class="toggle-text">
                <i class="fa fa-window-maximize"></i> BOM操作
            </span>
            <div class="toggle-btn"></div>
        </div>
        <div class="collapsible-content">
            <div class="chapter-title">
                <i class="fa fa-desktop"></i> BOM操作练习
            </div>
            <div class="chapter-description">
                学习浏览器对象模型（BOM）的常用操作，包括窗口操作、地址栏操作、历史记录操作和屏幕信息获取。
            </div>

            <?php
            echo renderCodeEditorLazy([
                'cardTitle' => 'BOM操作练习',
                'cardIcon' => 'fa fa-window-maximize',
                'height' => '600px',
                'defaultCode' => [
                    'html' => $chapterCodes['bom_operations']['html'],
                    'css' => $chapterCodes['bom_operations']['css'],
                    'javascript' => $chapterCodes['bom_operations']['javascript']
                ],
                'runButtonText' => '运行代码',
                'clearButtonText' => '清空',
                'resetButtonText' => '重置'
            ]);
            ?>
        </div>
    </div>

    <!-- 区域6: Fetch API操作 -->
    <div class="collapsible-section" id="section6">
        <div class="collapsible-header" onclick="toggleSection('section6')">
            <span class="toggle-text">
                <i class="fa fa-exchange"></i> Fetch API操作
            </span>
            <div class="toggle-btn"></div>
        </div>
        <div class="collapsible-content">
            <div class="chapter-title">
                <i class="fa fa-refresh"></i> Fetch API操作练习
            </div>
            <div class="chapter-description">
                学习现代Fetch API的使用，包括发送GET和POST请求，处理Promise响应等。Fetch
                API是XMLHttpRequest的现代替代方案，提供了更强大和灵活的网络请求功能。本靶场提供了fetch_server.php服务端脚本用于处理请求。
            </div>

            <?php
            echo renderCodeEditorLazy([
                'cardTitle' => 'Fetch API操作练习',
                'cardIcon' => 'fa fa-exchange',
                'height' => '650px',
                'defaultCode' => [
                    'html' => $chapterCodes['ajax_operations']['html'],
                    'css' => $chapterCodes['ajax_operations']['css'],
                    'javascript' => $chapterCodes['ajax_operations']['javascript']
                ],
                'runButtonText' => '运行代码',
                'clearButtonText' => '清空',
                'resetButtonText' => '重置'
            ]);
            ?>
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
                如果你已经掌握了JavaScript的基本语法，包括DOM操作、事件处理、BOM操作和Fetch API使用，可以点击下方的"我已掌握"按钮来标记你的学习成果。
            </div>

            <!-- 我已掌握按钮区域 -->
            <div class="mastery-button-container">
                <button type="button" class="heasec-mastery-btn" id="javascriptMasteryBtn"
                    onclick="showMasteryCongrats()">
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
<script src="<?php echo $commonBasePath; ?>assets/js/prism.min.js"></script>
<script src="<?php echo $commonBasePath; ?>assets/js/prism-clike.min.js"></script>
<script src="<?php echo $commonBasePath; ?>assets/js/prism-markup.min.js"></script>
<script src="<?php echo $commonBasePath; ?>assets/js/prism-javascript.min.js"></script>

<!-- 引入页面JavaScript文件 -->
<script src="js/main.js?v=v1.1.0"></script>

<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>