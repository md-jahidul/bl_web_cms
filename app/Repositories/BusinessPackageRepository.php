<?php

/**
 * User: Bulbul Mahmud Nito
 * Date: 11/02/2020
 */

namespace App\Repositories;

use App\Models\BusinessPackages;

class BusinessPackageRepository extends BaseRepository {

    public $modelName = BusinessPackages::class;

    public function getPackageList($homeShow = 0) {
        $packages = $this->model->orderBy('sort')->where('status', 1);
        if ($homeShow == 1) {
            $packages->where('home_show', $homeShow);
        }

        $packageData = $packages->select('id',
                                'name',
                                'name_bn',
                                'card_banner_web',
                                'card_banner_mobile',
                                'card_banner_name_en',
                                'card_banner_name_bn',
                                'card_banner_alt_text',
                                'card_banner_alt_text_bn',
                                'short_details',
                                'short_details_bn',
                                'page_header',
                                'page_header_bn',
                                'schema_markup',
                                'url_slug',
                                'url_slug_bn')->get();

        return $packageData;
    }

    public function getPackageById($packageSlug) {
        $package = $this->model->select('id', 'name', 'name_bn', 'banner_photo', 'banner_image_mobile',
                                    'banner_name', 'banner_name_bn', 'alt_text',
                                    'alt_text_bn', 'short_details', 'short_details_bn', 'main_details',
                                    'main_details_bn', 'offer_details', 'offer_details_bn', 'url_slug', 'url_slug_bn',
                                    'page_header', 'page_header_bn', 'schema_markup')
                                ->where('url_slug', $packageSlug)->orWhere('url_slug_bn', $packageSlug)->first();

        return $package;
//        $data = [];
//        if (!empty($package)) {
//            $data['id'] = $package->id;
//            $data['slug'] = 'packages';
//            $data['name_en'] = $package->name;
//            $data['name_bn'] = $package->name_bn;
//            $data['banner_photo'] = $package->banner_photo == "" ? "" : config('filesystems.image_host_url') . $package->banner_photo;
//            $data['banner_photo_mobile'] = $package->banner_image_mobile == "" ? "" : config('filesystems.image_host_url') . $package->banner_image_mobile;
//            $data['alt_text'] = $package->alt_text;
//            $data['short_details_en'] = $package->short_details;
//            $data['short_details_bn'] = $package->short_details_bn;
//            $data['main_details_en'] = $package->main_details;
//            $data['main_details_bn'] = $package->main_details_bn;
//            $data['offer_details_en'] = $package->offer_details;
//            $data['offer_details_bn'] = $package->offer_details_bn;
//            $data['url_slug'] = $package->url_slug;
//            $data['url_slug_bn'] = $package->url_slug_bn;
//            $data['page_header'] = $package->page_header;
//            $data['page_header_bn'] = $package->page_header_bn;
//            $data['schema_markup'] = $package->schema_markup;
//        }
//        return $data;
    }

}
