<?php

/**
 * User: Bulbul Mahmud Nito
 * Date: 11/02/2020
 */

namespace App\Repositories;

use App\Models\BusinessInternet;
use App\Models\TagCategory;

class BusinessInternetRepository extends BaseRepository {

    public $modelName = BusinessInternet::class;

    public function getInternetPackageList($homeShow = 0) {

        $internet = $this->model->where('status', 1)
            ->whereNotNull('product_code')
            ->where('product_code', '!=', "")
            ->orderBy('sort');

        if ($homeShow == 1) {
            $internet->where('home_show', 1);
        }

        $packages = $internet->get();

        $data = [];
        $count = 0;
        foreach ($packages as $p) {

            $dataVol = $p->data_volume;
            if ($p->volume_data_unit == "GB") {
                $dataVol = $p->data_volume * 1024;
            }

            $data[$count]['id'] = $p->id;
            $data[$count]['type_en'] = $p->type;
            $data[$count]['type_bn'] = $p->type == 'Prepaid' ? 'প্রিপেইড' : 'পোস্টপেইড';;
            $data[$count]['product_code'] = $p->product_code;
            $data[$count]['internet_volume_mb'] = $dataVol;
            $data[$count]['volume_data_unit'] = $p->volume_data_unit;
            $data[$count]['validity_days'] = $p->validity;
            $data[$count]['validity_unit'] = $p->validity_unit;
            $data[$count]['price_tk'] = $p->mrp;
            $data[$count]['tag_category_id'] = $p->tag_id;
            $data[$count]['ussd_en'] = $p->activation_ussd_code;
            $data[$count]['balance_check_ussd_code'] = $p->balance_check_ussd_code;
            $data[$count]['page_header'] = $p->page_header;
            $data[$count]['page_header_bn'] = $p->page_header_bn;
            $data[$count]['schema_markup'] = $p->schema_markup;
            $data[$count]['url_slug'] = $p->url_slug;
            $data[$count]['url_slug_bn'] = $p->url_slug_bn;
            $data[$count]['likes'] = $p->likes;
            $count++;
        }

        return $data;
    }

    public function getInternetPackageDetails($internetSlug) {

        $internet = $this->model->where('url_slug', $internetSlug)->orWhere('url_slug_bn', $internetSlug)->first();




        $dataVol = $internet->data_volume;
        if ($internet->volume_data_unit == "GB") {
            $dataVol = $internet->data_volume * 1024;
        }

        $data['id'] = $internet->id;
        $data['type'] = $internet->type;
        $data['product_code'] = $internet->product_code;
        $data['product_name'] = $internet->product_name;
        $data['internet_volume_mb'] = $dataVol;
        $data['volume_data_unit'] = $internet->volume_data_unit;
        $data['validity_days'] = $internet->validity;
        $data['validity_unit'] = $internet->validity_unit;
        $data['price_tk'] = $internet->mrp;
        $data['activation_ussd_code'] = $internet->activation_ussd_code;
        $data['balance_check_ussd_code'] = $internet->balance_check_ussd_code;
        $data['banner_photo'] = $internet->banner_photo == "" ? "" : config('filesystems.image_host_url') . $internet->banner_photo;
        $data['banner_photo_mobile'] = $internet->banner_image_mobile == "" ? "" : config('filesystems.image_host_url') . $internet->banner_image_mobile;
        $data['alt_text'] = $internet->alt_text;
        $data['package_details_en'] = $internet->package_details_en;
        $data['package_details_bn'] = $internet->package_details_bn;
        $data['banner_photo'] = $internet->banner_photo != "" ? config('filesystems.image_host_url') . $internet->banner_photo : "";
        $data['alt_text'] = $internet->alt_text;
        $data['url_slug'] = $internet->url_slug;
        $data['url_slug_bn'] = $internet->url_slug_bn;
        $data['page_header'] = $internet->page_header;
        $data['page_header_bn'] = $internet->page_header_bn;
        $data['schema_markup'] = $internet->schema_markup;
        $data['likes'] = $internet->likes;

        $data['tag_en'] = "";
        $data['tag_bn'] = "";
        $data['tag_color'] = "";
        $tags = TagCategory::where("id", $internet->tag_id)->first();
        if (!empty($tags)) {
            $data['tag_en'] = $tags->name_en;
            $data['tag_bn'] = $tags->name_bn;
            $data['tag_color'] = $tags->tag_color;
        }



        $count = 0;
        $relatedProduct = $this->model->whereIn("id", array($internet->related_product))->get();
        $data['related_product'] = [];
        foreach ($relatedProduct as $rp) {

            $rpDataVol = $rp->data_volume;
            if ($rp->volume_data_unit == "GB") {
                $rpDataVol = $rp->data_volume * 1024;
            }

            $data['related_product'][$count]['id'] = $rp->id;
            $data['related_product'][$count]['type'] = $rp->type;
            $data['related_product'][$count]['product_code'] = $rp->product_code;
            $data['related_product'][$count]['product_name'] = $rp->product_name;
            $data['related_product'][$count]['internet_volume_mb'] = $rpDataVol;
            $data['related_product'][$count]['volume_data_unit'] = $rp->volume_data_unit;
            $data['related_product'][$count]['validity_days'] = $rp->validity;
            $data['related_product'][$count]['validity_unit'] = $rp->validity_unit;
            $data['related_product'][$count]['price_tk'] = $rp->mrp;
            $data['related_product'][$count]['activation_ussd_code'] = $rp->activation_ussd_code;
            $data['related_product'][$count]['balance_check_ussd_code'] = $rp->balance_check_ussd_code;
            $data['related_product'][$count]['url_slug'] = $rp->url_slug;
            $data['related_product'][$count]['url_slug_bn'] = $rp->url_slug_bn;
            $data['related_product'][$count]['likes'] = $rp->likes;

            $data['related_product'][$count]['tag_en'] = "";
            $data['related_product'][$count]['tag_bn'] = "";
            $data['related_product'][$count]['tag_color'] = "";
            $rpTags = TagCategory::where("id", $rp->tag_id)->first();
            if (!empty($rpTags)) {
                $data['related_product'][$count]['tag_en'] = $rpTags->name_en;
                $data['related_product'][$count]['tag_bn'] = $rpTags->name_bn;
                $data['related_product'][$count]['tag_color'] = $rpTags->tag_color;
            }

            $count++;
        }

        return $data;
    }

    public function internetLike($internetId) {

        $internet = $this->model->findOrFail($internetId);
        $likes = $internet->likes + 1;
        $internet->likes = $likes;
        $internet->save();
        $data['likes'] = $likes;
        return $data;
    }

}
