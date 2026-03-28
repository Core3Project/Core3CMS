<?php
// Backup Module - One-click database dump
// Adds a "Download Backup" button to the admin dashboard

// Hook into admin dashboard to show backup button
Modules::on('admin_dashboard_after', function() {
    $csrf = Auth::csrf();
    return '<div class="panel"><div class="panel-hd">Database Backup</div><div class="panel-bd">
    <p style="font-size:13px;color:var(--muted,#646970);margin-bottom:12px">Download a full SQL dump of your database. Store it safely.</p>
    <a href="' . adm('backup-download?t=' . $csrf) . '" class="btn btn-outline" style="width:100%;justify-content:center">⬇ Download Backup</a>
    </div></div>';
});
