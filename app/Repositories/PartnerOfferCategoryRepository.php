<?php

/**
 * Created by PhpStorm.
 * User: BS23
 * Date: 27-Aug-19
 * Time: 3:51 PM
 */

namespace App\Repositories;

use App\Models\PartnerCategory;

class PartnerOfferCategoryRepository extends BaseRepository
{
    public $modelName = PartnerCategory::class;

    public function loyaltyCatOffers($page, $elg, $cat, $area, $searchStr)
    {
        $actualPage = $page - 1;
        $limit =9;
        $offset = $actualPage * $limit;
        $offers =  $this->model->where('status', 1)
            ->whereHas('partnerOffers')
            ->select(
                'id',
                'name_en',
                'name_bn',
                'url_slug_en',
                'url_slug_bn',
                'page_header',
                'page_header_bn',
                'schema_markup'
            )
            ->withCount(['partnerOffers'])
            ->with(['partnerOffers' => function ($q) use ($elg, $area, $searchStr, $offset, $limit) {
                $q->where('is_active', 1);

                $q->select(
                    'id',
                    'partner_id',
                    'partner_category_id',
                    'loyalty_tier_id',
                    'card_img',
                    'validity_en',
                    'validity_bn',
                    'btn_text_en',
                    'btn_text_bn',
                    'url_slug',
                    'url_slug_bn',
                    'page_header',
                    'page_header_bn',
                    'schema_markup',
                    'other_attributes'
                )
                    ->with(['partner' => function ($q) use ($searchStr) {
                        $q->select('id', 'company_name_en', 'company_name_bn');
                        if ($searchStr != "") {
                            $q->whereRaw("company_name_en Like '%$searchStr%'");
                            $q->whereRaw("company_name_bn Like '%$searchStr%'");
                        }
                    }])
                    ->whereHas('partner', function ($q) use ($searchStr) {
                        if ($searchStr != "") {
                            $q->whereRaw("company_name_en Like '%$searchStr%'");
                            $q->whereRaw("company_name_bn Like '%$searchStr%'");
                        }
                    });
                if ($elg != "") {
                    $q->where('loyalty_tier_id', $elg);
                }
                if ($area != "") {
                    $q->where('area_id', $area);
                }
                $q->offset($offset)->limit($limit);
            }]);
        if (
            $cat != ""
        ) {
            $offers->where('id', $cat);
        }
        $res = $offers
            ->get();

        return $res;
    }

    public function loyaltyCatOffersCount( $elg, $cat, $area, $searchStr)
    {
        $offers =  $this->model->where('status', 1)
        ->whereHas('partnerOffers')
        ->select(
            'id',
            'name_en',
            'name_bn',
            'url_slug_en',
            'url_slug_bn',
            'page_header',
            'page_header_bn',
            'schema_markup'
        )

            ->with(['partnerOffers' => function ($q) use ($elg, $area, $searchStr) {
                $q->where('is_active', 1);
                $q->whereHas('partner', function ($q) use ($searchStr) {
                    if ($searchStr != "") {
                        $q->whereRaw("company_name_en Like '%$searchStr%'");
                        $q->whereRaw("company_name_bn Like '%$searchStr%'");
                    }
                });
                $q->select(
                    'id',
                    'partner_id',
                    'partner_category_id',
                    'loyalty_tier_id',
                    'card_img',
                    'validity_en',
                    'validity_bn',
                    'btn_text_en',
                    'btn_text_bn',
                    'url_slug',
                    'url_slug_bn',
                    'page_header',
                    'page_header_bn',
                    'schema_markup',
                    'other_attributes'
                )
                    ->with(['partner' => function ($q) use ($searchStr) {
                        $q->select('id', 'company_name_en', 'company_name_bn');
                        if ($searchStr != "") {
                            $q->whereRaw("company_name_en Like '%$searchStr%'");
                            $q->whereRaw("company_name_bn Like '%$searchStr%'");
                        }
                    }]);
                if ($elg != "") {
                    $q->where('loyalty_tier_id', $elg);
                }
                if ($area != "") {
                    $q->where('area_id', $area);
                }
            }])
            ->withCount(['partnerOffers']);
        if (
            $cat != ""
        ) {
            $offers->where('id', $cat);
        }
        $res = $offers
            ->first();

        return $res;
    }


    public function findCategoryId($cat)
    {
        $data = $this->model
            ->where('url_slug_en', 'like', $cat)
            ->orWhere('url_slug_bn', 'like', $cat)
            ->first();
        if ($data) {
            return $data->id;
        } else {
            return null;
        }
    }
}
