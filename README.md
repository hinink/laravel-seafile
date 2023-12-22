## Flysystem Adapter for SeaFile

### Requirement

```bash
PHP >= 7.4
Laravel >= 6

```

### Installation

```bash
composer require "hinink/laravel-seafile"

```

### Usages

> SeaFile Api Doc

- [Upload Usage](https://download.seafile.com/published/web-api/v2.1/file-upload.md#user-content-Update%20File)

```php
    $storage = Storage::disk('seafile');
    
    $storage->put('save file path', 'contents');
    
    $storage->putFileAs('save path', 'file', 'save name');
    
    $storage->getMetadata('file path');
    
    $storage->getSize('file path');
    
    $storage->getTimestamp('file path');
    
    $storage->getMimetype('file path');
    
    $storage->listContents('path');
    
    $storage->directories('path');
    
    $storage->files('path');
    
    $storage->exists('file path');
    
    $storage->makeDirectory('dir name');
    
    $storage->delete('file path');
    
    $storage->deleteDir('dir name');
    
    $storage->getUploadUrl();       
                  
    
    $storage->rename('file path', 'new name')  
       
    $storage->url('file path');        # Disposable 
    $storage->url(['url'=>'file path','cache'=>true]);  # Valid for one hour

```

#### Config filesystems.php add

```php

'disks'=>[
     ....
     
    'seafile' => [
        'driver'   => 'seaFile',                    # 必须
        'server'   => env('SEAFILE_SERVER', ''),    # 必须
        'username' => env('SEAFILE_USER', ''),      # 
        'password' => env('SEAFILE_PASSWORD', ''),
        'token'    => env('SEAFILE_TOKEN', ''),     # 
        'repo_id'  => env('SEAFILE_REPO_ID', ''),   # 必须	    	
    ],
    
   ...
]
```
> Token or username password method

#### Direct file transmission

1. getUploadUrl

```php
    $upload_url = Storage::disk('seafile')->getUploadUrl();

```

2. upload file

```php
    post $upload_url
    url params: ret-json=1  返回 json    
    body params
         'file': (filename, fileobj),
         'parent_dir':  保存的父级地址 固定值 '/'
         'relative_path': 文件保存路径
         'replace': 1,0  是否覆盖
    
    respon
        json [{"name": "1.mp4", "id": "4f5022acac09f3112c02f07ee09d8d093064e4ad", "size": 302668280}]
        str  4f5022acac09f3112c02f07ee09d8d093064e4ad        
        
```

## Refer To

- [overtrue/flysystem-qiniu](https://github.com/overtrue/flysystem-qiniu)
- [rsd/seafile-php-sdk](https://github.com/Schmidt-DevOps/Seafile-PHP-SDK)

### License

```php
MIT
```
