# mysqli

由于环境不支持 pdo_msyql,用mysqli模拟pdo_mysql



## 安装

```
composer require dungang/yii2-mysqli
```

## 使用

修改数据配置文件 config/db.php

> 'pdoClass'=> 'dungang\mysqli\PDO_Mysql',

```
return [
    'class' => 'yii\db\Connection',
    'pdoClass'=> 'dungang\mysqli\PDO_Mysql',
    'username' => 'root',
    'password' => 'root',
    'charset' => 'utf8',
    'tablePrefix' => 'vint_',
    'dsn' => 'mysql:host=localhost;port=3306;dbname=db_test',
];
```

## 协议

The MIT License (MIT)

Copyright (c) 2017 dungang

Permission is hereby granted, free of charge, to any person obtaining a copy of
this software and associated documentation files (the "Software"), to deal in
the Software without restriction, including without limitation the rights to
use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
the Software, and to permit persons to whom the Software is furnished to do so,
subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.