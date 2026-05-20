<?php
/**
 * HeaSec天积安全团队 - 短信记录管理标签页模板
 * Tab Template: SMS Management
 * 版本: v1.0.0
 * 创建日期: 2026-01-06
 * 团队: 天积安全 (HeavenlySecret)
 */
?>
<!-- 短信管理工具栏 -->
<div class="heasec-sms-toolbar">
    <div class="toolbar-left">
        <div class="phone-selector">
            <label for="phoneSearchInput">
                <i class="fa fa-phone"></i>
                选择手机号:
            </label>
            <div class="searchable-select" id="phoneSearchableSelect">
                <input type="text" id="phoneSearchInput" class="form-control search-input" placeholder="搜索手机号..." autocomplete="off">
                <button type="button" class="select-toggle" id="phoneSelectToggle">
                    <i class="fa fa-chevron-down"></i>
                </button>
                <div class="select-dropdown" id="phoneDropdown" style="display: none;">
                    <div class="dropdown-list" id="phoneDropdownList">
                        <!-- 手机号列表将在这里动态生成 -->
                    </div>
                </div>
            </div>
        </div>
        <button class="btn btn-primary btn-mark-all-read" id="btnMarkAllRead" disabled>
            <i class="fa fa-envelope-open"></i>
            全部已读
        </button>
        <button class="btn btn-danger btn-batch-delete-sms" id="btnBatchDeleteSms" disabled>
            <i class="fa fa-trash"></i>
            批量删除
        </button>
    </div>
    <div class="toolbar-right">
        <div class="sms-stats" id="smsStats">
            <span class="stat-item">
                <i class="fa fa-envelope"></i>
                总计: <strong id="totalCount">0</strong>
            </span>
            <span class="stat-item">
                <i class="fa fa-envelope-o"></i>
                未读: <strong id="unreadCount">0</strong>
            </span>
        </div>
        <button class="btn btn-secondary btn-refresh-sms" id="btnRefreshSms">
            <i class="fa fa-refresh"></i>
            刷新
        </button>
    </div>
</div>

<!-- 短信列表容器（Android风格） -->
<div class="heasec-sms-list-container" id="smsListContainer">
    <div class="loading-indicator" id="smsLoadingIndicator">
        <i class="fa fa-spinner fa-spin"></i>
        正在加载短信列表...
    </div>

    <div class="empty-message" id="smsEmptyMessage" style="display: none;">
        <i class="fa fa-inbox"></i>
        <p>暂无短信记录</p>
    </div>

    <div class="sms-list" id="smsList">
        <!-- 短信项目将在这里动态生成 -->
    </div>
</div>

<!-- 短信详情模态框 -->
<div class="heasec-sms-modal" id="smsDetailModal" style="display: none;">
    <div class="modal-overlay"></div>
    <div class="modal-container sms-detail-container">
        <div class="modal-header">
            <h3 class="modal-title">短信详情</h3>
            <button class="modal-close" id="closeSmsDetailModal">
                <i class="fa fa-times"></i>
            </button>
        </div>
        <div class="modal-content">
            <div class="sms-detail-view">
                <div class="sms-detail-header">
                    <div class="sender-info">
                        <i class="fa fa-user"></i>
                        <span id="smsSender">未知发送者</span>
                    </div>
                    <div class="time-info">
                        <i class="fa fa-clock-o"></i>
                        <span id="smsTime">--</span>
                    </div>
                </div>
                <div class="sms-detail-body">
                    <div class="sms-bubble">
                        <p id="smsContent">短信内容</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary modal-cancel" id="cancelSmsDetailModal">
                <i class="fa fa-check"></i>
                关闭
            </button>
        </div>
    </div>
</div>
