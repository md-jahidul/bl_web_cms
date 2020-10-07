<?php

namespace App\Http\Controllers\API\V1;

use App\Services\CorpCaseStudyComponentService;
use App\Services\CorpCrStrategyComponentService;
use App\Services\CorporateInitiativeTabService;
use App\Services\CorporateRespSectionService;
use App\Services\MediaLandingPageService;
use App\Http\Controllers\Controller;
use App\Services\MediaPressNewsEventService;
use App\Services\MediaTvcVideoService;
use Illuminate\Http\JsonResponse;

class CorporateResponsibilityController extends Controller
{
    /**
     * @var CorporateRespSectionService
     */
    private $corporateRespSectionService;
    /**
     * @var CorpCrStrategyComponentService
     */
    private $corpCrStrategyComponentService;
    /**
     * @var CorpCaseStudyComponentService
     */
    private $corpCaseStudyComponentService;
    /**
     * @var CorporateInitiativeTabService
     */
    private $corporateInitiativeTabService;

    /**
     * CorporateRespSectionController constructor.
     * @param CorporateRespSectionService $corporateRespSectionService
     * @param CorpCrStrategyComponentService $corpCrStrategyComponentService
     * @param CorpCaseStudyComponentService $corpCaseStudyComponentService
     * @param CorporateInitiativeTabService $corporateInitiativeTabService
     */
    public function __construct(
        CorporateRespSectionService $corporateRespSectionService,
        CorpCrStrategyComponentService $corpCrStrategyComponentService,
        CorpCaseStudyComponentService $corpCaseStudyComponentService,
        CorporateInitiativeTabService $corporateInitiativeTabService
    ) {
        $this->corporateRespSectionService = $corporateRespSectionService;
        $this->corpCrStrategyComponentService = $corpCrStrategyComponentService;
        $this->corpCaseStudyComponentService = $corpCaseStudyComponentService;
        $this->corporateInitiativeTabService = $corporateInitiativeTabService;
    }

    /**
     * CorporateRespSection
     * @return JsonResponse|mixed
     */
    public function getSection()
    {
        return $this->corporateRespSectionService->sections();
    }

    /**
     * CrStrategySection
     * @return JsonResponse|mixed
     */
    public function getCrStrategySection()
    {
        return $this->corpCrStrategyComponentService->crStrategySection();
    }

    /**
     * CrStrategyDetailsComponents
     * @param $urlSlug
     * @return JsonResponse|mixed
     */
    public function getCrStrategyDetailsComponents($urlSlug)
    {
        return $this->corpCrStrategyComponentService->getComponentWithDetails($urlSlug);
    }

    /**
     * CaseStudySection
     * @return JsonResponse|mixed
     */
    public function getCaseStudySection()
    {
        return $this->corpCaseStudyComponentService->caseStudySectionWithComponent();
    }

    /**
     * CaseStudyDetailsComponents
     * @param $urlSlug
     * @return JsonResponse|mixed
     */
    public function getCaseStudyDetailsComponents($urlSlug)
    {
        return $this->corpCaseStudyComponentService->getComponentWithDetails($urlSlug);
    }

//    /**
//     * Initiative Tab
//     */
    /**
     * @return JsonResponse|mixed
     */
    public function getInitiativeTabs()
    {
        return $this->corporateInitiativeTabService->getTabs();
    }
}
