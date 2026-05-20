<?php
/**
 * HeaSec天积安全团队 - XSS标签与事件组合学习靶场
 * 版本: v1.1.0
 * 创建日期: 2026-01-12
 * 更新日期: 2026-02-11
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 说明: 这是一个独立的XSS标签与事件组合学习靶场，帮助用户探索各种HTML标签和事件处理器的XSS组合方式
 * v1.1.0: 表单提交改为AJAX方式，避免XSS payload反复触发
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec XSS标签与事件组合学习 Range v1.1.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = 'XSS标签与事件组合学习靶场';
$rangeName = '标签与事件';
$showVersion = false;
$showResetButton = true;
$version = 'v1.1.0';

// 设置公共组件的基础路径（从靶场目录到range/common/的相对路径）
$commonBasePath = '../../../common/';

// 设置重置功能相关变量
$initSqlFile = __DIR__ . '/database/init_database.sql';
$databaseName = 'heasec_inputverify';
$useDatabase = true;

// 引入公共头部
require_once $commonBasePath . 'includes/header.php';

// 数据库初始化
try {
    require_once $commonBasePath . 'includes/database.php';
    $db = heasec_db('heasec_inputverify');
    // 测试连接
    $db->query('SELECT 1');
} catch (Exception $e) {
    error_log('[HeaSec] Database error: ' . $e->getMessage());
}

// 获取成就统计（仅用于初始渲染）
$starCount = 0;
$tagCount = 0;
$eventCount = 0;
$records = [];

try {
    // 统计标签数量
    $stmt = $db->query("SELECT COUNT(*) as count FROM heasec_xss_content_filter2_tags");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $tagCount = intval($result['count']);

    // 统计事件数量
    $stmt = $db->query("SELECT COUNT(*) as count FROM heasec_xss_content_filter2_events");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $eventCount = intval($result['count']);

    // 计算星星数量（从高到低判断）
    if ($eventCount >= 5) {
        $starCount = 3;  // 5个不同事件
    } elseif ($tagCount >= 5) {
        $starCount = 2;  // 5个不同标签
    } elseif ($tagCount + $eventCount > 0) {
        $starCount = 1;  // 首次触发
    }

    // 获取标签记录
    $stmt = $db->query("SELECT tag_name as name, success_count FROM heasec_xss_content_filter2_tags ORDER BY success_count DESC");
    $tagRecords = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $tagRecords[] = ['name' => $row['name'], 'count' => $row['success_count']];
    }

    // 获取事件记录
    $stmt = $db->query("SELECT event_name as name, success_count FROM heasec_xss_content_filter2_events ORDER BY success_count DESC");
    $eventRecords = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $eventRecords[] = ['name' => $row['name'], 'count' => $row['success_count']];
    }

    // 计算进度提示（分别计算）
    $progressHint = '';
    $tagHint = '';
    $eventHint = '';

    // 只有在未获得对应成就时才显示提示
    if ($tagCount < 5) {
        $needed = 5 - $tagCount;
        $tagHint = "还差 {$needed} 个不同的标签获得一颗星星";
    } else {
        $tagHint = "恭喜！您标签已收集完成";
    }

    if ($eventCount < 5) {
        $needed = 5 - $eventCount;
        $eventHint = "还差 {$needed} 个不同的事件获得一颗星星";
    } else {
        $eventHint = "恭喜！您事件已收集完成";
    }

    // 只有在完全没有任何成就时，才显示主进度提示
    if ($starCount == 0 && $tagCount == 0 && $eventCount == 0) {
        $progressHint = '成功触发任意 XSS 即可获得第1颗星（首次触发）';
    }

    // 构建分组记录
    $recordGroups = [];

    // 始终显示两个分组，即使为空也显示占位
    $recordGroups[] = [
        'title' => '已发现的标签',
        'records' => $tagRecords,
        'hint' => $tagHint,
        'icon' => 'fa-code',
        'headerLabel' => '标签'
    ];

    $recordGroups[] = [
        'title' => '已发现的事件',
        'records' => $eventRecords,
        'hint' => $eventHint,
        'icon' => 'fa-bolt',
        'headerLabel' => '事件'
    ];

} catch (Exception $e) {
    error_log('[HeaSec] Database error: ' . $e->getMessage());
    $starCount = 0;
    $recordGroups = [];
}

// 引入星星系统组件资源（CSS）
require_once $commonBasePath . 'components/star-system/includes/HeaSec_StarSystem.php';
echo HeaSec_StarSystem::renderAssets($commonBasePath, ['js' => false]);

// 引入成就卡片公共组件
require_once $commonBasePath . 'components/achievement-card/includes/HeaSec_AchievementCard.php';
?>

<!-- 引入统一样式文件 -->
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
<!-- 引入站点特定样式 -->
<link rel="stylesheet" href="css/style.css">

<!-- XSS弹窗检测系统 -->
<script>
    (function () {
        'use strict';

        // 保存原始alert函数
        var originalAlert = window.alert;

        var hasPassed = false;

        // 暴露重置函数，供AJAX表单提交时重置状态
        window._heasecResetXSSFlag = function () {
            hasPassed = false;
        };

        console.log('[HeaSec XSS] 弹窗检测系统已初始化');

        // 提取HTML中的标签和事件
        function extractTagsAndEvents(html) {
            var tags = [];
            var events = [];

            // 提取所有标签名
            var tagMatch = html.match(/<(\w+)/g);
            if (tagMatch) {
                tagMatch.forEach(function (match) {
                    var tagName = match.replace(/</g, '').toLowerCase();
                    if (tags.indexOf(tagName) === -1) {
                        tags.push(tagName);
                    }
                });
            }

            // 提取所有事件
            var eventMatch = html.match(/on\w+/gi);
            if (eventMatch) {
                eventMatch.forEach(function (match) {
                    var eventName = match.toLowerCase();
                    if (events.indexOf(eventName) === -1) {
                        events.push(eventName);
                    }
                });
            }

            return { tags: tags, events: events };
        }

        // 发送记录到服务器，并异步刷新成就UI
        function sendRecord(tags, events) {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'api/record.php', true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            console.log('[HeaSec XSS] 成就记录成功:', response);

                            // 使用返回数据异步更新成就卡片
                            updateAchievementCard(response);

                                // 延迟触发成就更新事件
                                setTimeout(function () {
                                    document.dispatchEvent(new CustomEvent('heasec:starUnlocked', {
                                        detail: {
                                            starCount: response.star_count,
                                            learningStatus: response.learning_status,
                                            timestamp: Date.now()
                                        }
                                    }));
                                    console.log('[HeaSec XSS] 成就更新事件已触发，当前状态:', response.learning_status);
                                }, 300);
                        } else {
                            console.log('[HeaSec XSS] 成就记录失败:', response.message);
                        }
                    } catch (e) {
                        console.log('[HeaSec XSS] API响应解析失败:', e);
                    }
                }
            };
            xhr.send(JSON.stringify({
                tags: tags,
                events: events
            }));
        }

        // 异步更新成就卡片UI（不刷新页面）
        function updateAchievementCard(data) {
            // 更新星星系统 - 使用组件实例API
            var starContainer = document.querySelector('.heasec-achievement-card .heasec-star-system');
            if (starContainer && starContainer._heasecStarInstance) {
                // 使用组件提供的 API 更新星星状态，它会自动处理样式、图标源和动画
                starContainer._heasecStarInstance.unlockMultipleStars(data.star_count, true);
            } else if (starContainer) {
                // 降级处理：如果没有实例，尝试手动更新
                var stars = starContainer.querySelectorAll('.heasec-star');
                for (var i = 0; i < stars.length; i++) {
                    if (i < data.star_count) {
                        stars[i].classList.add('achieved');
                        stars[i].classList.remove('locked');
                        
                        // 尝试更新图标
                        var img = stars[i].querySelector('img');
                        if (img && img.src.indexOf('star-gray.svg') !== -1) {
                            img.src = img.src.replace('star-gray.svg', 'star-gold.svg');
                        }
                    }
                }
            }

            // 更新成就卡片的data-config中的achievedCount（供恭喜模块读取）
            var card = document.querySelector('.heasec-achievement-card');
            if (card) {
                var configStr = card.getAttribute('data-config');
                if (configStr) {
                    try {
                        var config = JSON.parse(configStr);
                        config.achievedCount = data.star_count;
                        card.setAttribute('data-config', JSON.stringify(config));
                    } catch (e) {
                        console.log('[HeaSec XSS] 更新data-config失败:', e);
                    }
                }
            }

            // 更新记录列表
            var recordPanels = document.querySelectorAll('.heasec-achievement-card .tech-info-panel');

            // 找到标签和事件的记录面板（跳过前面的非记录面板）
            var tagPanel = null;
            var eventPanel = null;

            // 查找包含"已发现的标签"和"已发现的事件"的面板
            for (var p = 0; p < recordPanels.length; p++) {
                var h4 = recordPanels[p].querySelector('h4');
                if (h4) {
                    var text = h4.textContent.trim();
                    if (text.indexOf('已发现的标签') !== -1) {
                        tagPanel = recordPanels[p];
                    } else if (text.indexOf('已发现的事件') !== -1) {
                        eventPanel = recordPanels[p];
                    }
                }
            }

            // 更新标签面板
            if (tagPanel && data.tag_records) {
                updateRecordPanel(tagPanel, data.tag_records, data.tag_hint);
            }

            // 更新事件面板
            if (eventPanel && data.event_records) {
                updateRecordPanel(eventPanel, data.event_records, data.event_hint);
            }

            // 更新主进度提示（仅在初始零成就时存在）
            // 通过查找直接子级的 tech-info-panel 中 color 为 #0056b3 的 alert-info 来定位
            var allAlerts = document.querySelectorAll('.heasec-achievement-card > .tech-card-body > .tech-info-panel > .alert-info');
            for (var a = 0; a < allAlerts.length; a++) {
                var alertEl = allAlerts[a];
                if (alertEl.style.color === '#0056b3' || alertEl.style.color === 'rgb(0, 86, 179)') {
                    if (data.progress_hint) {
                        alertEl.innerHTML = '<i class="fa fa-info-circle"></i> ' + data.progress_hint;
                        alertEl.parentNode.style.display = '';
                    } else {
                        alertEl.parentNode.style.display = 'none';
                    }
                    break;
                }
            }
        }

        // 更新单个记录面板
        function updateRecordPanel(panel, records, hint) {
            // 更新提示
            var hintEl = panel.querySelector('.alert-info');
            if (hintEl && hint) {
                hintEl.querySelector('span').textContent = hint;
            }

            // 更新记录列表
            var grid = panel.querySelector('.info-grid');
            if (!grid) return;

            // 保留header行，清除记录行
            var headerRow = grid.querySelector('.info-item');
            grid.innerHTML = '';
            if (headerRow) {
                grid.appendChild(headerRow);
            }

            if (records.length === 0) {
                var emptyDiv = document.createElement('div');
                emptyDiv.className = 'info-item';
                emptyDiv.innerHTML = '<span class="info-label">暂无记录</span><span class="info-value"></span>';
                grid.appendChild(emptyDiv);
            } else {
                records.forEach(function (record) {
                    var div = document.createElement('div');
                    div.className = 'info-item';
                    div.innerHTML = '<span class="info-label" style="font-size: 13px;">' +
                        escapeHtml(record.name) + '：</span>' +
                        '<span class="info-value"><span class="badge badge-success" style="font-size: 11px;">' +
                        record.count + '</span></span>';
                    grid.appendChild(div);
                });
            }
        }

        // HTML转义辅助函数
        function escapeHtml(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // 添加提示消息到页面
        function addPageMessage(message, isSuccess) {
            var xssForm = document.getElementById('xssForm');
            if (!xssForm) return;

            // 移除旧的提示消息
            var oldMsg = document.getElementById('xss-detection-message');
            if (oldMsg) {
                oldMsg.remove();
            }

            // 创建新的提示消息
            var msgDiv = document.createElement('div');
            msgDiv.id = 'xss-detection-message';
            msgDiv.className = 'alert ' + (isSuccess ? 'alert-success' : 'alert-danger');
            msgDiv.style.marginTop = '15px';
            msgDiv.innerHTML = '<div><i class="fa fa-' + (isSuccess ? 'check-circle' : 'exclamation-triangle') + '"></i><strong>' + message + '</strong></div>';

            // 插入到表单后面
            xssForm.parentNode.insertBefore(msgDiv, xssForm.nextSibling);
        }

        // 重写alert函数
        window.alert = function (message) {
            console.log('[HeaSec XSS] 拦截到alert:', message);

            var stack = '';
            try {
                throw new Error();
            } catch (e) {
                stack = e.stack || '';
            }

            console.log('[HeaSec XSS] 调用堆栈:', stack);

            // 分析堆栈判断是否为控制台调用
            var isConsole = false;
            if (stack.indexOf('index.php') === -1 && (stack.indexOf('<anonymous>') !== -1 || stack.indexOf('VM') !== -1 || stack.indexOf('debugger') !== -1)) {
                isConsole = true;
                console.log('[HeaSec XSS] 检测到控制台直接调用');
            }

            // 检查xss-test-area内是否存在元素
            var testArea = document.getElementById('xss-test-area');
            var isValidXSS = false;

            if (testArea) {
                // 检查测试区域是否有内容
                var hasContent = testArea.innerHTML.trim() !== '';
                console.log('[HeaSec XSS] hasContent:', hasContent);

                if (hasContent && !isConsole) {
                    isValidXSS = true;
                }
            }

            console.log('[HeaSec XSS] 检测结果 - isValidXSS:', isValidXSS);

            // 如果不是有效的XSS，显示错误但仍执行弹窗
            if (!isValidXSS) {
                var errorMsg = isConsole
                    ? '检测到控制台执行，请在正确的位置输入'
                    : '检测到控制台执行，请在正确的位置输入';

                console.log('[HeaSec XSS] ' + errorMsg);
                addPageMessage(errorMsg, false);
                return originalAlert.apply(this, arguments);
            }

            // 成功实现XSS注入
            if (hasPassed) {
                return originalAlert.apply(this, arguments);
            }

            hasPassed = true;

            // 从输入框获取原始输入
            var xssInput = document.getElementById('xss_code');
            var originalInput = xssInput ? xssInput.value.trim() : '';

            // 提取标签和事件（使用原始输入而不是innerHTML）
            var result = extractTagsAndEvents(originalInput);

            console.log('[HeaSec XSS] 原始输入:', originalInput);
            console.log('[HeaSec XSS] 检测到标签:', result.tags);
            console.log('[HeaSec XSS] 检测到事件:', result.events);

            // 发送记录（会异步更新成就UI）
            sendRecord(result.tags, result.events);

            var successMsg = '成功触发XSS！已记录使用的标签和事件';
            console.log('[HeaSec XSS] ' + successMsg);
            addPageMessage(successMsg, true);

            // 不再刷新页面，成就UI通过异步更新

            // 调用原始弹窗
            return originalAlert.apply(this, arguments);
        };

        console.log('[HeaSec XSS] 弹窗拦截函数已设置');
    })();
</script>

<!-- 靶场主要内容 -->
<div class="range-container">
    <!-- 关卡区域 -->
    <div class="level-section">
        <div class="tech-container">
            <div class="tech-card">
                <div class="tech-card-header">
                    <h3>
                        <i class="fa fa-code"></i>
                        标签与事件
                    </h3>
                    <p class="level-description">"粗心的程序员修复了xss，来找找有多少种方法可以再触发弹窗吧"</p>
                </div>
                <div class="tech-card-body">
                    <!-- 关卡提示 -->
                    <div class="level-tip">
                        <i class="fa fa-lightbulb-o"></i>
                        <strong>通关条件：</strong>请使用标签与事件组合触发 alert 弹窗
                    </div>

                    <!-- XSS代码输入表单（改为AJAX提交，不刷新页面） -->
                    <form class="tech-form" id="xssForm" onsubmit="return false;">
                        <div class="search-container">
                            <div class="search-input-wrapper">
                                <input type="text" id="xss_code" name="xss_code" class="search-input"
 required
                                    autocomplete="off" <?php if (isset($_POST['xss_code']))
                                        echo 'value="' . htmlspecialchars($_POST['xss_code']) . '"'; ?>>
                                <button type="submit" class="search-submit-btn" name="submit">
                                    <i class="fa fa-search"></i>
                                    搜索
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- 测试结果区域（通过JS动态渲染） -->
                    <div id="search-result-area"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- 成就系统卡片 - 使用公共组件 -->
    <?php
    echo renderAchievementCard([
        'achievedCount' => $starCount,
        'titles' => ['首次触发', '标签大师', '事件专家'],
        'recordGroups' => $recordGroups,
        'progressHint' => $progressHint,
        'rangeCode' => 'xss_content_filter2',

        // 恭喜功能配置：启用学习状态更新
        'congratsConfig' => [
            'messages' => [
                'partial' => '你已经掌握了 %d/3 种技能！继续努力，获得更多的成就！',
                'complete' => '太棒了！你已经掌握了所有%d种技能，成为了真正的安全大师！'
            ],
            'updateLearningStatus' => true,
            'updateStatusApiUrl' => $commonBasePath . 'api/update-learning-status.php',
            'nextRangeApiUrl' => $commonBasePath . 'api/next-range.php',
            'enableNextRangeButton' => true
        ]
    ], $commonBasePath);
    ?>


<!-- 引入模态框组件脚本 -->
<script src="js/modal.js?v=<?php echo $version; ?>"></script>

<!-- AJAX表单提交与结果渲染 -->
<script>
    (function () {
        'use strict';

        var xssForm = document.getElementById('xssForm');
        var xssCodeInput = document.getElementById('xss_code');
        var submitBtn = xssForm ? xssForm.querySelector('[type="submit"]') : null;
        var resultArea = document.getElementById('search-result-area');

        if (!xssForm) return;

        // 表单提交处理（AJAX方式）
        xssForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var code = xssCodeInput.value.trim();

            // 空输入检查
            if (!code) {
                HeaSecModal.showError('输入错误', '请输入搜索内容');
                xssCodeInput.focus();
                return false;
            }

            // 重置hasPassed标志（允许新的payload被检测）
            // 通过重新设置闭包变量来实现
            window._heasecResetXSSFlag && window._heasecResetXSSFlag();

            // 显示提交中状态
            if (submitBtn) {
                submitBtn.disabled = true;
                var originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 搜索中...';
            }

            // AJAX提交到filter.php
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'api/filter.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4) {
                    // 恢复按钮状态
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText || '<i class="fa fa-search"></i> 搜索';
                    }

                    if (xhr.status === 200) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            renderFilterResult(response);
                        } catch (err) {
                            console.log('[HeaSec XSS] 响应解析失败:', err);
                        }
                    } else {
                        console.log('[HeaSec XSS] 请求失败，状态码:', xhr.status);
                    }
                }
            };
            xhr.send('xss_code=' + encodeURIComponent(code));
        });

        // 渲染过滤结果到页面
        function renderFilterResult(data) {
            if (!data.has_input) {
                resultArea.innerHTML = '';
                return;
            }

            var html = '<div class="search-result">';
            html += '<div class="search-result-title">';
            html += '<i class="fa fa-search"></i> 您搜索了：';
            html += '</div>';

            if (data.is_blocked) {
                // 被拦截
                html += '<div class="alert alert-danger" style="margin-top: 15px;">';
                html += '<div><i class="fa fa-times"></i> ';
                html += '<strong>' + data.blocked_reason + '</strong>';
                html += '</div></div>';
            } else {
                // 通过过滤，直接输出（这里就是XSS注入点）
                html += '<div id="xss-test-area" style="margin-top: 15px;">';
                html += data.filtered_code;
                html += '</div>';
            }

            html += '</div>';
            resultArea.innerHTML = html;
        }

        // 输入框焦点高亮
        if (xssCodeInput) {
            xssCodeInput.addEventListener('focus', function () {
                this.closest('.search-input-wrapper').style.borderColor = '#007bff';
                this.closest('.search-input-wrapper').style.background = 'white';
            });

            xssCodeInput.addEventListener('blur', function () {
                this.closest('.search-input-wrapper').style.borderColor = '#dee2e6';
                this.closest('.search-input-wrapper').style.background = '#f8f9fa';
            });
        }
    })();
</script>

<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>