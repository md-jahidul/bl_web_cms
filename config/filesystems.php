<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => env('FILESYSTEM_DRIVER', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Default Cloud Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Many applications store files both locally and in the cloud. For this
    | reason, you may specify a default "cloud" driver here. This driver
    | will be bound as the Cloud disk implementation in the container.
    |
    */

    'cloud' => env('FILESYSTEM_CLOUD', 's3'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been setup for each driver as an example of the required options.
    |
    | Supported Drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],
        'internal' => [
            'driver' => 'local',
            'root' => env('UPLOAD_BASE_PATH', '/src/uploads'),
            'url' => env('APP_URL') . '/uploads',
            'visibility' => 'public',
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
        ],

    ],
    'image_host_url' => env('IMAGE_HOST_URL', 'http://172.16.8.160:8443/uploads/'),
    'image_host' => env('IMAGE_HOST', 'http://172.16.8.160:8443'),

    // File Location
    'moduleType' => [
        'OfferCategory' => [
            'image_name_en' => 'banner_name',
            'image_name_bn' => 'banner_name_bn',
            'exact_path_web' => 'banner_image_url',
            'exact_path_mobile' => 'banner_image_mobile',
            'model' => 'offer-category'
        ],
        'RoamingCategory' => [
            'image_name_en' => 'banner_name',
            'image_name_bn' => 'banner_name_bn',
            'exact_path_web' => 'banner_web',
            'exact_path_mobile' => 'banner_mobile',
            'model' => 'roaming-category'
        ],
        'RoamingOtherOffer' => [
            'image_name_en' => 'banner_name',
            'image_name_bn' => 'banner_name_bn',
            'exact_path_web' => 'banner_web',
            'exact_path_mobile' => 'banner_mobile',
            'model' => 'roaming-other-offer'
        ],
        'RoamingInfo' => [
            'image_name_en' => 'banner_name',
            'image_name_bn' => 'banner_name_bn',
            'exact_path_web' => 'banner_web',
            'exact_path_mobile' => 'banner_mobile',
            'model' => 'roaming-info-tips'
        ],
        'AppServiceTab' => [
            'image_name_en' => 'banner_name',
            'image_name_bn' => 'banner_name_bn',
            'exact_path_web' => 'banner_image_url',
            'exact_path_mobile' => 'banner_image_mobile',
            'model' => 'app-service-tab',
        ],
        'AppServiceProduct' => [
            'image_name_en' => 'product_img_en',
            'image_name_bn' => 'product_img_bn',
            'exact_path_web' => 'product_img_url',
            'exact_path_mobile' => 'product_img_url',
            'model' => 'app-service-product',
        ],
        'AppServiceProductDetail' => [
            'image_name_en' => 'banner_name',
            'image_name_bn' => 'banner_name_bn',
            'exact_path_web' => 'image',
            'exact_path_mobile' => 'banner_image_mobile',
            'model' => 'app-service-product-detail',
        ],
        'BusinessCategory' => [
            'image_name_en' => 'banner_name',
            'image_name_bn' => 'banner_name_bn',
            'exact_path_web' => 'banner_photo',
            'exact_path_mobile' => 'banner_image_mobile',
            'model' => 'business-category',
        ],
        'BusinessNews' => [
            'image_name_en' => 'image_name_en',
            'image_name_bn' => 'image_name_en',
            'exact_path_web' => 'image_url',
            'exact_path_mobile' => 'image_url',
            'model' => 'business-news',
        ],
        'BusinessHomeBanner' => [
            'image_name_en' => 'image_name_en',
            'image_name_bn' => 'image_name_bn',
            'exact_path_web' => 'image_name',
            'exact_path_mobile' => 'image_name_mobile',
            'model' => 'business-home-banner',
        ],
        'BusinessPackages' => [
            'image_name_en' => 'card_banner_name_en',
            'image_name_bn' => 'card_banner_name_bn',
            'exact_path_web' => 'card_banner_web',
            'exact_path_mobile' => 'card_banner_mobile',
            'model' => 'business-package',
        ],
        'BusinessOthers' => [
            'image_name_en' => 'banner_name',
            'image_name_bn' => 'banner_name_bn',
            'exact_path_web' => 'banner_photo',
            'exact_path_mobile' => 'banner_image_mobile',
            'model' => 'business-others',
        ]
    ],

    'modelList' => [
        'offer-category' => 'OfferCategory',
        'roaming-category' => 'RoamingCategory',
        'roaming-other-offer' => 'RoamingOtherOffer',
        'roaming-info-tips' => 'RoamingInfo',
        'app-service-product' => 'AppServiceProduct',
        'app-service-tab' => 'AppServiceTab',
        'business-category' => 'BusinessCategory',
        'business-news' => 'BusinessNews',
        'business-home-banner' => 'BusinessHomeBanner',
        'business-others' => 'BusinessOthers'
    ]
];
