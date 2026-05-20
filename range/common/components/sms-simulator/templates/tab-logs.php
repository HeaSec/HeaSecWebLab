<?php
/**
 * HeaSec天积安全团队 - 短信发送日志管理标签页模板
 * Tab Template: SMS Log Management
 * 版本: v1.0.0
 * 创建日期: 2026-01-06
 * 团队: 天积安全 (HeavenlySecret)
 */
?>
<!-- 日志管理工具栏 -->
<div class="heasec-sms-toolbar">
    <div class="toolbar-left">
        <div class="search-group">
            <input type="text" id="logSearchPhone" class="form-control search-input" placeholder="搜索手机号...">
            <input type="text" id="logSearchSender" class="form-control search-input" placeholder="搜索发送者...">
            <select id="logSearchStatus" class="form-control search-select">
                <option value="">全部状态</option>
                <option value="已发送">已发送</option>
                <option value="未发送">未发送</option>
            </select>
            <button class="btn btn-primary btn-search-log" id="btnSearchLog">
                <i class="fa fa-search"></i>
                搜索
            </button>
            <button class="btn btn-secondary btn-reset-search" id="btnResetSearch">
                <i class="fa fa-refresh"></i>
                重置
            </button>
        </div>
    </div>
    <div class="toolbar-right">
        <button class="btn btn-danger btn-batch-delete-log" id="btnBatchDeleteLog" disabled>
            <i class="fa fa-trash"></i>
            批量删除
        </button>
        <button class="btn btn-warning btn-clear-log" id="btnClearLog">
            <i class="fa fa-trash-o"></i>
            清空日志
        </button>
        <button class="btn btn-secondary btn-refresh-log" id="btnRefreshLog">
            <i class="fa fa-refresh"></i>
            刷新
        </button>
    </div>
</div>

<!-- 日志列表表格 -->
<div class="heasec-sms-table-container">
    <table class="heasec-sms-table" id="logsTable">
        <thead>
            <tr>
                <th>
                    <input type="checkbox" id="selectAllLogs" />
                </th>
                <th>手机号</th>
                <th>发送者</th>
                <th>短信内容</th>
                <th>状态</th>
                <th>详细信息</th>
                <th>IP地址</th>
                <th>发送时间</th>
            </tr>
        </thead>
        <tbody id="logsTableBody">
            <tr class="loading-row">
                <td colspan="8">
                    <div class="loading-indicator">
                        <i class="fa fa-spinner fa-spin"></i>
                        正在加载日志列表...
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</div>

<!-- 分页控件 -->
<div class="heasec-sms-pagination" id="logPagination">
    <div class="pagination-info">
        共 <strong id="logTotalCount">0</strong> 条记录
    </div>
    <div class="pagination-controls" id="logPaginationControls">
        <!-- 分页按钮将动态生成 -->
    </div>
</div>
