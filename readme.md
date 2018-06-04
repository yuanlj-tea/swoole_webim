1.先将client目录放置在您的web服务器下，打开client/static/js/init.js 文件，将该文件的配置修改成自己的域名或者IP。

2.打开server目录，首先将rooms目录以及其子目录权限设为777，确保该目录可写。将client/uploads目录设置为777可写。

3.修改server/config.inc.php 文件。修改DOMAIN和redis配置。

> define("DOMAIN","[http://192.168.79.206.:8081](http://192.168.79.206:8081/)");


4.命令行执行 ：

> /usr/local/php/bin/php /path/server/hsw_server.php

