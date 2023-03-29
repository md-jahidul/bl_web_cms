<?php

namespace App\Repositories;

use App\Models\FrontEndDynamicRoute;
use App\Models\OtherDynamicPage;

class DynamicPageRepository extends BaseRepository {

    public $modelName = OtherDynamicPage::class;

    public function page($slug)
    {

        return $this->model->where('url_slug', $slug)
            ->orWhere('url_slug_bn', $slug)
            ->select(
                'id', 'page_header', 'page_header_bn', 'schema_markup',
                'banner_image_url', 'banner_mobile_view',
                'alt_text', 'page_name_en', 'page_name_bn',
                'page_content_en', 'page_content_bn',
                'url_slug', 'url_slug_bn'
            )
            ->with(['components' => function($q){
                $q->orderBy('component_order', 'ASC')
                    ->with(['componentMultiData' => function($q){
                        $q->select('component_id', 'title_en', 'title_bn', 'alt_text_en', 'alt_text_bn',
                            'base_image', 'img_name_en', 'img_name_bn'
                        );
                    }])
                    ->where('page_type', 'other_dynamic_page')
                    ->select(
                        'id', 'section_details_id', 'page_type',
                        'component_type', 'title_en', 'title_bn',
                        'editor_en', 'editor_bn', 'description_en', 'description_bn', 'extra_title_bn',
                        'extra_title_en', 'multiple_attributes',
                        'video', 'image', 'alt_text', 'other_attributes'
                    )
                    ->where('status', 1);
            }])
            ->first();
    }
}
