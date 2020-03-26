1、composer update

2、如果是部署在云服务器上，则需要修改 im\public\js\webim.js 中，下面这行代码：

```php
var config = {
  server : 'ws://tt.haveyb.com:9501'
};
```

3、执行 `php im_server.php` 运行起服务端

4、浏览器访问 http://tt.haveyb.com/im/public/index.html

5、演示效果

![](./public/images/example.png)

6、原项目地址：https://github.com/moell-peng/webim

