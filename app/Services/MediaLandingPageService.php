<?php

/**
 * Created by PhpStorm.
 * User: bs-205
 * Date: 18/08/19
 * Time: 17:23
 */

namespace App\Services;

use App\Repositories\MediaBannerImageRepository;
use App\Repositories\MediaLandingPageRepository;
use App\Repositories\MediaPressNewsEventRepository;
use App\Repositories\MediaTvcVideoRepository;

class MediaLandingPageService extends ApiBaseService
{
    protected $mediaLandingPageRepository;
    protected $mediaTvcVideoRepository;
    protected $mediaPressNewsEventRepository;
    /**
     * @var MediaBannerImageRepository
     */
    private $mediaBannerImageRepository;

    /**
     * DigitalServicesService constructor.
     * @param MediaLandingPageRepository $mediaLandingPageRepository
     * @param MediaTvcVideoRepository $mediaTvcVideoRepository
     * @param MediaPressNewsEventRepository $mediaPressNewsEventRepository
     * @param MediaBannerImageRepository $mediaBannerImageRepository
     */
    public function __construct(
        MediaLandingPageRepository $mediaLandingPageRepository,
        MediaTvcVideoRepository $mediaTvcVideoRepository,
        MediaPressNewsEventRepository $mediaPressNewsEventRepository,
        MediaBannerImageRepository $mediaBannerImageRepository
    ) {
        $this->mediaLandingPageRepository = $mediaLandingPageRepository;
        $this->mediaTvcVideoRepository = $mediaTvcVideoRepository;
        $this->mediaPressNewsEventRepository = $mediaPressNewsEventRepository;
        $this->mediaBannerImageRepository = $mediaBannerImageRepository;
    }

    public function getMediaFeatureData($componentsData, $type = null, $postRefType = null)
    {
        $data['title_en'] = $componentsData->title_en;
        $data['title_bn'] = $componentsData->title_bn;
        $data['short_desc_en'] = $componentsData->short_desc_en;
        $data['short_desc_bn'] = $componentsData->short_desc_bn;
        $data['component_type'] = $componentsData->component_type;
        $data['page_header'] = $componentsData->page_header;
        $data['page_header_bn'] = $componentsData->page_header_bn;
        $data['schema_markup'] = $componentsData->schema_markup;
        if ($type == 'press_release' || $type == 'news_events'){
            foreach ($componentsData->items as $id){
                $data['sliding_speed'] = $componentsData->sliding_speed;

                $pressNewsEvent = $this->mediaPressNewsEventRepository->getPressNewsEvent($type, $id);
                if ($pressNewsEvent) {
                    $data['data'][] = $pressNewsEvent;
                }
            }
        } elseif ($type == "blog_landing_page") {
            if (!empty($componentsData->items)) {
                $postCardItems = $this->mediaPressNewsEventRepository->landingDataByRefType($postRefType, $componentsData->items);
                $data['card_items'] = $postCardItems;
            }
            if (!empty($componentsData->slider_items)) {
                $postSlidingItems = $this->mediaPressNewsEventRepository->landingDataByRefType($postRefType, $componentsData->slider_items);
                $data['slider_items'] = $postSlidingItems;
            }
            return $data;
        } else {
            foreach ($componentsData->items as $id){
                $video = $this->mediaTvcVideoRepository->getVideoItems($id);
                if ($video){
                    $data['data'][] = $video;
                }
            }
        }
        $data['data'] = array_values(collect($data['data'])->sortByDesc('created_at')->toArray());
        return $data;
    }

    public function factoryComponent($componentsData) {
        $data = null;
        switch ($componentsData->component_type) {
            case "news_carousel_slider":
                $data = $this->getMediaFeatureData($componentsData,'news_events');
                break;
            case "press_slider":
                $data = $this->getMediaFeatureData($componentsData,'press_release');
                break;
            case "video":
                $data = $this->getMediaFeatureData($componentsData, 'video');
                break;
        }
        return $data;
    }

    public function landingData()
    {
        $orderBy = ['column' => "display_order", 'direction' => 'ASC'];
        $components = $this->mediaLandingPageRepository->findAll('', '', $orderBy);
        foreach ($components as $items){
            $allComponents[] = $this->factoryComponent($items);
        }

        $bannerData = $this->mediaBannerImageRepository->bannerImage('landing_page');
        $data = [
            'components' => isset($allComponents) ? $allComponents : [],
            'banner_image' => [
                'id' => $bannerData->id,
                'moduleType' => $bannerData->module_type,
                'banner_image_url' => $bannerData->banner_image_url,
                'banner_mobile_view' => $bannerData->banner_mobile_view,
                'alt_text_en' => $bannerData->banner_mobile_view
            ],
            'seo_data' => [
                'page_header' => $bannerData->page_header,
                'page_header_bn' => $bannerData->page_header_bn,
                'schema_markup' => $bannerData->schema_markup
            ]
        ];
        return $this->sendSuccessResponse($data, 'Media Landing Page data');
    }

    public function landingDataByReferenceType($referenceType, $postRefType)
    {
        $components = $this->mediaLandingPageRepository->getDataByRefType($referenceType);

        foreach ($components as $items){
            $allComponents[] = $this->getMediaFeatureData($items, $referenceType, $postRefType);
        }

        $data = [
            'components' => $allComponents ?? [],
            'seo_data' => [
//                'page_header' => $bannerData->page_header,
//                'page_header_bn' => $bannerData->page_header_bn,
//                'schema_markup' => $bannerData->schema_markup
            ]
        ];
        return $this->sendSuccessResponse($data, 'Media Landing Page data');
    }

}
