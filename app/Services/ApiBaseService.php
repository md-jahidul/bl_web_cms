<?php

namespace App\Services;

use App\Enums\ApiCustomStatusCode;
use App\Enums\HttpStatusCode;
use Illuminate\Http\JsonResponse;
use App\Contracts\ApiBaseServiceInterface;

/**
 * Class ApiBaseService
 * @package App\Services
 */
class ApiBaseService implements ApiBaseServiceInterface
{

    /**
     * @param $result
     * @param null $baseURL
     * @param $message
     * @param array $pagination
     * @param int $http_status
     * @param int $status_code
     * @return JsonResponse|mixed
     */
    public function sendSuccessResponse(
        $result,
        $message,
        $baseUrl = [],
        $pagination = [],
        $http_status = HttpStatusCode::SUCCESS,
        $status_code = ApiCustomStatusCode::SUCCESS
    ) {
        $response = [
            'status' => 'SUCCESS',
            'status_code' => $status_code,
            'message' => $message,
            'data' => $result,
            'base_url' => $baseUrl
        ];

        if (!empty($pagination)) {
            $response ['pagination'] = $pagination;
        }

        return response()->json($response, $http_status);
    }


    /**
     * Return error response.
     *
     * @param $message
     * @param array $errorMessages
     * @param int $status_code
     * @return JsonResponse
     */
    public function sendErrorResponse($message, $errorMessages = [], $status_code = HttpStatusCode::VALIDATION_ERROR)
    {
        $response = [
            'status' => 'FAIL',
            'status_code' => $status_code,
            'message' => $message,
        ];

        if (!empty($errorMessages)) {
            $response['errors'] = $errorMessages;
        }

        return response()->json($response, $status_code);
    }


    /**
     * Return Response with pagination
     *
     * @param $items
     * @return array
     */
    public function paginationResponse($items)
    {
        return array(
            'total_items' => $items->total(),
            'current_items' => $items->count(),
            'first_item' => $items->firstItem(),
            'last_item' => $items->lastItem(),
            'current_page' => $items->currentPage(),
            'last_page' => $items->lastPage(),
            'has_more_pages' => $items->hasMorePages(),
        );
    }
}
