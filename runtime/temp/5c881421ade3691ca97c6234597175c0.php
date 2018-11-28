<?php if (!defined('THINK_PATH')) exit(); /*a:3:{s:52:"D:\www\h18/application/admin\view\collect\union.html";i:1537517863;s:50:"D:\www\h18\application\admin\view\public\head.html";i:1537345858;s:50:"D:\www\h18\application\admin\view\public\foot.html";i:1536224796;}*/ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <title><?php echo $title; ?></title>
    <link rel="stylesheet" href="/static/layui/css/layui.css">
    <link rel="stylesheet" href="/static/css/admin_style.css">
    <script type="text/javascript" src="/static/js/jquery.js"></script>
    <script type="text/javascript" src="/static/layui/layui.js"></script>
    <script>
        var ROOT_PATH="",ADMIN_PATH="<?php echo $_SERVER['SCRIPT_NAME']; ?>", MAC_VERSION='v10';
    </script>
</head>
<body>

<style>
	.table tr:hover td{ background: #8bb2fd;}
</style>

<div class="page-container p10">

    <div class="my-toolbar-box">
        <div class="layui-btn-group">
            <?php if($collect_break_vod != ''): ?>
            <a href="<?php echo url('load'); ?>?flag=vod" class="layui-btn layui-btn-danger ">【进入视频断点采集】</a>
            <?php endif; if($collect_break_art != ''): ?>
            <a href="<?php echo url('load'); ?>?flag=art" class="layui-btn layui-btn-danger ">【进入文章断点采集】</a>
            <?php endif; ?>
            </div>
    </div>
    <hr>

    <script src="/static/js/caiji.js" charset="utf-8"></script>

</div>

<script type="text/javascript" src="/static/js/admin_common.js"></script>
<script type="text/javascript">
    layui.use(['laypage', 'layer'], function() {
        var laypage = layui.laypage
                , layer = layui.layer;


    });
</script>
</body>
</html>