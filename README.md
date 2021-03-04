# Dcat Admin AccessLogs Extension
该功能主要记录前台访问的记录，在Dcat Admin后台中会自动生成菜单模块

## 使用方法
在需要记录的路由中加入路由中间件名：access.log
   
    Route::group(['middleware'=>['access.log']],function (){
        Route::get('/', "IndexController@index");
    });


使用队列方式进行记录

    php artisan queue:work --queue=access

队列具体使用方式请参考
https://learnku.com/docs/laravel/5.5/queues/1324


