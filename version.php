<?php

return [
    '1.0.0' => [
        '前端訪問記錄',
        '前端訪問記錄功能',
        'create_access_logs_table.php'
    ],

    '1.0.1' => [
        '前端訪問記錄',
        '去除设置按钮',
    ],

    '1.0.2' => [
        '中间件注册',
        '代码优化更新',
    ],

    '1.1.0' => [
        '去除在路由中中间件',
        '避免新应用未安装本扩展前台页面访问异常',
        '可在扩展中心设置过滤无须记录的路径',
        '修复访问记录排序问题'
    ],
    '1.2.0' => [
        '增加查询索引,优化查询',
        '默认查询当天',
        'change_created_at_key_into_jou_access_logs.php'
    ],
    '1.2.1' => [
        '增加IP查询索引，优化查询',
        'change_ip_key_into_jou_access_logs.php'
    ],
    '1.2.2' => [
        '二级域名记录失效问题修复'
    ],
    '2.0.0' => [
        '增加更多的统计',
        //'change_created_at_key_into_jou_access_logs.php',
    ],
    '2.0.1' => [
        '增加地区字段',
        'add_ipcounty_into_jou_access_logs.php',
    ]
];
