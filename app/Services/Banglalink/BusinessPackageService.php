<?php

/**
 * User: Bulbul Mahmud Nito
 * Date: 13/02/2020
 */

namespace App\Services\Banglalink;
use App\Services\ApiBaseService;

use App\Repositories\BusinessPackageRepository;
use App\Repositories\BusinessFeaturesRepository;
use App\Repositories\BusinessAssignedFeaturesRepository;
use Illuminate\Http\Response;

class BusinessPackageService {


    /**
     * @var $packageRepo
     */
    protected $packageRepo;
    protected $featureRepo;
    protected $asgnFeatureRepo;
    public $responseFormatter;

    /**
     * BusinessPackageService constructor.
     * @param BusinessPackageRepository $packageRepo
     * @param BusinessFeaturesRepository $featureRepo
     * @param BusinessAssignedFeaturesRepository $asgnFeatureRepo
     */
    public function __construct(ApiBaseService $responseFormatter, BusinessPackageRepository $packageRepo, BusinessFeaturesRepository $featureRepo, BusinessAssignedFeaturesRepository $asgnFeatureRepo) {
        $this->packageRepo = $packageRepo;
        $this->featureRepo = $featureRepo;
        $this->asgnFeatureRepo = $asgnFeatureRepo;
         $this->responseFormatter = $responseFormatter;
    }

    /**
     * Get business product categories
     * @return Response
     */
    public function getPackages() {
        $response = $this->packageRepo->getPackageList();
        return $this->responseFormatter->sendSuccessResponse($response, 'Business Package List');
    }


    /**
     * Get business package by id
     * @return Response
     */
    public function getPackageById($packageId) {
        $data['packageDetails'] = $this->packageRepo->getPackageById($packageId);
        $data['packageFeatures'] = $this->_getFeaturesByPackage($packageId);
        return $this->responseFormatter->sendSuccessResponse($data, 'Business Package Details');
    }

    /**
     * Get business package by id
     * @return Response
     */
    private function _getFeaturesByPackage($packageId) {
        $parentType = 1; //parent type 1 for package
        $response = $this->asgnFeatureRepo->getAssignedFeatures($packageId, $parentType);
        return $response;
    }

    

}
