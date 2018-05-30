## 安装说明  
上传文件至API服务器下`client`目录  
重命名`config.php.demo`为`config.php`  
输入WHMCS服务器配置信息及客户端信息  
whmcs配置文件可见WHMCS安装目录下`configuration.php`
## 测试
访问`https://domain/client/server.php?token=秘钥&databaseName=数据库名&method=getUsers`  
部署成功则返回json
## 配置文件结构
|参数名|描述|
|:-|:-|
|db_hostname|见WHMCS的configuration.php文件|
|db_...|同上|
|cc_encryption_hash|同上|
|app_website|WHMCS主站地址|
|app_background|客户端背景图|
|app_title|客户端标题|