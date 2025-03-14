<?php

namespace App\Services\Banglalink;

use App\Enums\HttpStatusCode;
use App\Repositories\CustomerRepository;
use App\Services\ApiBaseService;
use App\Services\CustomerService;
use App\Services\IdpIntegrationService;
use App\Services\NumberValidationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BalanceService extends BaseService
{
    /**
     * @var ApiBaseService
     */
    public $responseFormatter;
    /**
     * [$numberValidationService for customer info]
     */
    public $numberValidationService;

    protected const BALANCE_API_ENDPOINT = "/customer-information/customer-information";
    protected const MINIMUM_BALANCE_FOR_LOAN = 20;
    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     * @var CustomerService
     */
    protected $customerService;

    /**
     * BalanceService constructor.
     * @param CustomerService $customerService
     * @param NumberValidationService $numberValidationService
     */
    public function __construct(CustomerService $customerService, NumberValidationService $numberValidationService)
    {
        $this->responseFormatter = new ApiBaseService();
        $this->customerRepository = new CustomerRepository();
        $this->customerService = $customerService;
        $this->numberValidationService = $numberValidationService;
    }

    private function getBalanceUrl($customer_id)
    {
        return self::BALANCE_API_ENDPOINT . '/' . $customer_id . '/prepaid-balances' . '?sortType=SERVICE_TYPE';
    }

    private function getBalanceUrlPostpaid($customer_id)
    {
        return self::BALANCE_API_ENDPOINT . '/' . $customer_id . '/postpaid-info';
    }

    private function getAuthenticateUser($request)
    {
        $bearerToken = ['token' => $request->header('authorization')];


        $response = IdpIntegrationService::tokenValidationRequest($bearerToken);

        $data = json_decode($response, true);


        if ($data['token_status'] != 'Valid') {
            return $this->responseFormatter->sendErrorResponse("Token is Invalid", [], HttpStatusCode::UNAUTHORIZED);
        }

        $user = $this->customerRepository->getCustomerInfoByPhone($data['user']['mobile']);

        return $user;
    }

    private function isEligibleToLoan($balance)
    {
        return random_int(0, 1) && $balance < self::MINIMUM_BALANCE_FOR_LOAN ? true : false;
    }

    /**
     * @param $response
     * @param $customer_id
     * @return mixed
     */
    private function prepareBalanceSummary($response, $customer_id)
    {
        $balance_data = collect($response->money);

        $main_balance = $balance_data->first(function ($item) {
            return $item->type == 'MAIN';
        });

        $is_eligible_to_loan =  $this->isEligibleToLoan($customer_id);
        $data['balance'] = [
            'amount' => isset($main_balance->amount) ? $main_balance->amount : 0 ,
            'unit' => isset($main_balance->unit) ? $main_balance->unit : 'Tk.',
            'expires_in' => isset($main_balance->expiryDateTime) ?
                Carbon::parse($main_balance->expiryDateTime)->setTimezone('UTC')->toDateTimeString() : null,
            'loan' => [
                'is_eligible' => $is_eligible_to_loan,
                'amount'      => ($is_eligible_to_loan) ? 30 : 0
            ]
        ];


        $talk_time = collect($response->voice);

        if ($talk_time) {
            $total_remaining_talk_time = $talk_time->sum('amount');
            $total_talk_time = $talk_time->sum('totalAmount');
            $data['minutes'] = [
                'total' => $total_talk_time,
                'remaining' => $total_remaining_talk_time,
                'unit' => 'MIN'
            ];
        }

        $sms = collect($response->sms);

        if ($sms) {
            $total_remaining_sms = $sms->sum('amount');
            $total_sms = $sms->sum('totalAmount');
            $data['sms'] = [
                'total' => $total_sms,
                'remaining' => $total_remaining_sms,
                'unit' => 'SMS'
            ];
        }


        $internet = collect($response->data);

        if ($internet) {
            $total_remaining_internet = $internet->sum('amount');
            $total_internet = $internet->sum('totalAmount');
            $data['internet'] = [
                'total' => $total_internet,
                'remaining' => $total_remaining_internet,
                'unit' => 'MB'
            ];
        }

        return $data;
    }



    /**
     * Get Balance Summary
     *
     * @param $mobile
     * @return array|JsonResponse
     */
    public function getBalanceSummary($mobile)
    {

        $validationResponse = $this->numberValidationService->validateNumberWithResponse($mobile);
        if ($validationResponse->getData()->status == 'FAIL') {
            return $validationResponse;
        }

        $customerInfo = $validationResponse->getData()->data;
        $customer_id = $customerInfo->package->customerId;

        # Postpaid balance summery
        if( $customerInfo->connectionType == 'POSTPAID' ){

            $response = $this->get($this->getBalanceUrlPostpaid($customer_id));
            $response = json_decode($response['response']);
            if (isset($response->error)) {
                return ['status' => 'FAIL', 'data' => $response->message, 'status_code' => $response->status];
            }

           // $balanceSummary = $this->prepareBalanceSummaryPostpaid($response, $customer_id);
           // $balanceSummary = $this->preparePostpaidSummary($response);
            $balanceSummary = $this->preparePostpaidBalanceSummary($response);

        }
        # Prepaid balance summery
        else{
            $response = $this->get($this->getBalanceUrl($customer_id));
            $response = json_decode($response['response']);
            if (isset($response->error)) {
                return ['status' => 'FAIL', 'data' => $response->message, 'status_code' => $response->status];
            }
            $balanceSummary = $this->prepareBalanceSummary($response, $customer_id);
        }

        $balanceSummary['connection_type'] = isset($customerInfo->connectionType) ? $customerInfo->connectionType : null;

        return ['status' => 'SUCCESS', 'data' => $balanceSummary];
    }

    /**
     * @param $response
     * @return JsonResponse|mixed
     */
    private function getInternetBalance($response)
    {
        $internet_data = collect($response->data);

        $internet = $internet_data->filter(function ($item) {
            return $item->serviceType == 'DATA';
        });

        $data = [];
        foreach ($internet as $item) {
            $data [] = [
                'package_name' => isset($item->product->name) ? $item->product->name : null,
                'total' => $item->totalAmount,
                'remaining' => $item->amount,
                'unit' => $item->unit,
                'expires_in' => Carbon::parse($item->expiryDateTime)->setTimezone('UTC')->toDateTimeString(),
                'auto_renew' => false
            ];
        }

        return $this->responseFormatter->sendSuccessResponse($data, 'Internet  Balance Details');
    }

    /**
     * @param $response
     * @return JsonResponse|mixed
     */
    private function getSmsBalance($response)
    {
        $sms = collect($response->sms);
        $data = [];
        foreach ($sms as $item) {
            $data [] = [
                'package_name' => isset($item->product->name) ? $item->product->name : null,
                'total' => $item->totalAmount,
                'remaining' => $item->amount,
                'unit' => $item->unit,
                'expires_in' => Carbon::parse($item->expiryDateTime)->setTimezone('UTC')->toDateTimeString(),
                'auto_renew' => false
            ];
        }

        return $this->responseFormatter->sendSuccessResponse($data, 'SMS  Balance Details');
    }


    /**
     * @param $response
     * @return JsonResponse|mixed
     */
    private function getTalkTimeBalance($response)
    {
        $talk_time = collect($response->voice);

        $data = [];
        foreach ($talk_time as $item) {
            $data [] = [
                'package_name' => isset($item->product->name) ? $item->product->name : null,
                'total' => $item->totalAmount,
                'remaining' => $item->amount,
                'unit' => $item->unit,
                'expires_in' => Carbon::parse($item->expiryDateTime)->setTimezone('UTC')->toDateTimeString(),
                'auto_renew' => false
            ];
        }

        return $this->responseFormatter->sendSuccessResponse($data, 'Talk Time  Balance Details');
    }

    /**
     * @param $response
     * @return JsonResponse|mixed
     */
    private function getMainBalance($response)
    {
        $balance_data = collect($response->money);

        $main_balance = $balance_data->first(function ($item) {
            return $item->type == 'MAIN';
        });

        $data = [
            'remaining_balance' => [
                'amount' => isset($main_balance->amount) ? $main_balance->amount : 0,
                'currency' => 'Tk.',
                'expires_in' => isset($main_balance->expiryDateTime) ?
                    Carbon::parse($main_balance->expiryDateTime)->setTimezone('UTC')->toDateTimeString() : null
            ],
            'roaming_balance' => [
                'amount' => 0,
                'currency' => 'USD',
                'expires_in' => null
            ]
        ];


        return $this->responseFormatter->sendSuccessResponse($data, 'Main Balance Details');
    }


    /**
     * @param $type
     * @param Request $request
     * @return JsonResponse|mixed
     */
    public function getBalanceDetails($type, Request $request)
    {
        $user = $this->getAuthenticateUser($request);

        if (!$user) {
            return $this->responseFormatter->sendErrorResponse("User not found", [], HttpStatusCode::UNAUTHORIZED);
        }

        $customer_id = ($user->customer_account_id) ? $user->customer_account_id : 8494;
        $response = $this->get($this->getBalanceUrl($customer_id));
        $response = json_decode($response['response']);

        if (isset($response->error)) {
            return $this->responseFormatter->sendErrorResponse(
                $response->message,
                [],
                $response->status
            );
        }

        if ($type == 'internet') {
            return $this->getInternetBalance($response);
        } elseif ($type == 'sms') {
            return $this->getSmsBalance($response);
        } elseif ($type == 'minutes') {
            return $this->getTalkTimeBalance($response);
        } elseif ($type == 'balance') {
            return $this->getMainBalance($response);
        } else {
            return $this->responseFormatter->sendErrorResponse(
                "Type Not Supported",
                [],
                404
            );
        }
    }


    /**
     * @param $response
     * @return JsonResponse|mixed
     */
    private function preparePostpaidSummary($response)
    {
        $local_balance = collect($response)->where('billingAccountType', '=', 'LOCAL')->first();
        $balance = [
            'total_outstanding' => $local_balance->totalOutstanding,
            'credit_limit' => $local_balance->creditLimit,
            'payment_date' => isset($local_balance->nextPaymentDate) ?
                Carbon::parse($local_balance->nextPaymentDate)->setTimezone('UTC')->toDateTimeString() : null,
        ];

        $usage = collect($local_balance->productUsage)->where('code', '<>', '');

        $minutes = [];
        $sms = [];
        $internet = [];

        foreach ($usage as $product) {
            foreach ($product->usages as $item) {
                $type = $item->serviceType;
                switch ($type) {
                    case "DATA":
                        $internet ['total'][] = $item->total;
                        $internet ['remaining'][] = $item->left;
                        break;
                    case "VOICE":
                        $minutes ['total'][] = $item->total;
                        $minutes ['remaining'][] = $item->left;
                        break;
                    case "SMS":
                        $sms ['total'][] = $item->total;
                        $sms ['remaining'][] = $item->left;
                        break;
                }
            }
        }

        $data ['connection_type'] = 'POSTPAID';
        $data ['balance'] = $balance;
        $data ['minutes'] = [
            'total' => isset($minutes['total']) ? array_sum($minutes['total']) : 0,
            'remaining' => isset($minutes['remaining']) ? array_sum($minutes['remaining']) : 0,
            'unit' => 'MIN'
        ];
        $data ['internet'] = [
            'total' => isset($internet['total']) ? array_sum($internet['total']) : 0,
            'remaining' => isset($internet['remaining']) ? array_sum($internet['remaining']) : 0,
            'unit' => 'MB'
        ];
        $data ['sms'] = [
            'total' => isset($sms['total']) ? array_sum($sms['total']) : 0,
            'remaining' => isset($sms['remaining']) ? array_sum($sms['remaining']) : 0,
            'unit' => 'SMS'
        ];

        return $data;

        //return $this->responseFormatter->sendSuccessResponse($data, 'User Balance Summary');
    }


    /**
     * @param $response
     * @return array
     */
    private function preparePostpaidBalanceSummary($response)
    {
        $balance_data = collect($response);

        $data = [];
        $balance_data_roaming = null;
        $balance_data_local = null;
        foreach ($balance_data as $item) {

            if( $item->billingAccountType == 'ROAMING' ){

                $balance_data_roaming = $item;
            }
            elseif( $item->billingAccountType == 'LOCAL' ){

                $balance_data_local = $item;
            }
        }

        $data['balance'] = [
            'amount' => isset($balance_data_local->totalOutstanding) ? $balance_data_local->totalOutstanding : 0 ,
            'unit' => isset($balance_data_local->unit) ? $balance_data_local->unit : 'BDT',
            'expires_in' => isset($balance_data_local->nextPaymentDate) ?
                Carbon::parse($balance_data_local->nextPaymentDate)->setTimezone('UTC')->toDateTimeString() : null,
        ];

        $data['local'] = [
            'billingAccountType' => isset($balance_data_local->billingAccountType) ? $balance_data_local->billingAccountType : null,
            'totalOutstanding' => isset($balance_data_local->totalOutstanding) ? $balance_data_local->totalOutstanding : 0,
            'creditLimit' => isset($balance_data_local->creditLimit) ? $balance_data_local->creditLimit : 0,
            'overPayment' => isset($balance_data_local->overPayment) ? $balance_data_local->overPayment : 0,
            'nextPaymentDate' => isset($balance_data_local->nextPaymentDate) ? Carbon::parse($balance_data_local->nextPaymentDate)->setTimezone('UTC')->toDateTimeString() : null,
        ];

        $usage = collect($balance_data_local->productUsage)->where('code', '<>', '');

        $minutes = [];
        $sms = [];
        $internet = [];
        $local_product_usage = [];
        foreach ($usage as $product) {
            foreach ($product->usages as $item) {
                $type = $item->serviceType;
                switch ($type) {
                    case "DATA":
                        $internet ['total'][] = $item->total;
                        $internet ['remaining'][] = $item->left;
                        break;
                    case "VOICE":
                        $minutes ['total'][] = $item->total;
                        $minutes ['remaining'][] = $item->left;
                        break;
                    case "SMS":
                        $sms ['total'][] = $item->total;
                        $sms ['remaining'][] = $item->left;
                        break;
                }
            }
        }

        $data ['connection_type'] = 'POSTPAID';
       // $data ['balance'] = $balance;
        $local_product_usage ['minutes'] = [
            'total' => isset($minutes['total']) ? array_sum($minutes['total']) : 0,
            'remaining' => isset($minutes['remaining']) ? array_sum($minutes['remaining']) : 0,
            'unit' => 'MIN'
        ];
        $local_product_usage ['internet'] = [
            'total' => isset($internet['total']) ? array_sum($internet['total']) : 0,
            'remaining' => isset($internet['remaining']) ? array_sum($internet['remaining']) : 0,
            'unit' => 'MB'
        ];
        $local_product_usage ['sms'] = [
            'total' => isset($sms['total']) ? array_sum($sms['total']) : 0,
            'remaining' => isset($sms['remaining']) ? array_sum($sms['remaining']) : 0,
            'unit' => 'SMS'
        ];


        $data['local']['product_usages'] = $local_product_usage;

        $data['roaming'] = [
            'billingAccountType' => isset($balance_data_roaming->billingAccountType) ? $balance_data_roaming->billingAccountType : null,
            'totalOutstanding' => isset($balance_data_roaming->totalOutstanding) ? $balance_data_roaming->totalOutstanding : 0,
            'creditLimit' => isset($balance_data_roaming->creditLimit) ? $balance_data_roaming->creditLimit : 0,
            'overPayment' => isset($balance_data_roaming->overPayment) ? $balance_data_roaming->overPayment : 0,
            'nextPaymentDate' => isset($balance_data_roaming->overPayment) ? Carbon::parse($balance_data_roaming->nextPaymentDate)->setTimezone('UTC')->toDateTimeString() : null,
        ];

        $roming_product_usage = [];
        if( !empty($balance_data_roaming) && !empty($balance_data_roaming->productUsage) ){
            foreach ($balance_data_roaming->productUsage as $roaming_product ) {

                if( !empty($roaming_product->code) && !empty($roaming_product->commercialName && !empty($roaming_product->usages) )  ){

                    foreach ($roaming_product->usages as $usages) {

                        if( $usages->serviceType == 'VOICE' ){

                            $roming_product_usage['minutes'] = [
                                'total' => !empty($usages->total) ?  $usages->total : 0,
                                'remaining' => !empty($usages->left) ?  $usages->left : 0,
                                'unit' => 'MIN'
                            ];

                        }
                        elseif( $usages->serviceType == 'SMS' ){

                            $roming_product_usage['sms'] = [
                                'total' => !empty($usages->total) ?  $usages->total : 0,
                                'remaining' => !empty($usages->left) ?  $usages->left : 0,
                                'unit' => 'SMS'
                            ];

                        }
                        elseif( $usages->serviceType == 'DATA' ){
                            $roming_product_usage['internet'] = [
                                'total' => !empty($usages->total) ?  $usages->total : 0,
                                'remaining' => !empty($usages->left) ?  $usages->left : 0,
                                'unit' => 'MB'
                            ];

                        }

                    }
                }

            }
        }

        $data['roaming']['product_usages'] = $roming_product_usage;

        # Default local data sending for postpaid
        $default_minutes = !empty($local_product_usage['minutes']) ? $local_product_usage['minutes'] : ( !empty($roming_product_usage['minutes']) ?  $roming_product_usage['minutes'] : 0 );

        $default_sms = !empty($local_product_usage['sms']) ? $local_product_usage['sms'] : ( !empty($roming_product_usage['sms']) ?  $roming_product_usage['sms'] : 0 );

        $default_internet = !empty($local_product_usage['internet']) ? $local_product_usage['internet'] : ( !empty($roming_product_usage['internet']) ?  $roming_product_usage['internet'] : 0 );

        $data['minutes'] = $default_minutes;
        $data['sms'] = $default_sms;
        $data['internet'] = $default_internet;

        return $data;
    }

    /**
     * [prepareBalanceSummaryPostpaid Balance summery for postpaid]
     * @param  [mixed] $response    [description]
     * @param  [int] $customer_id [description]
     * @return [mixed]              [description]
     */
    private function prepareBalanceSummaryPostpaid($response, $customer_id)
    {
        $balance_data = collect($response);

        $data = [];
        $balance_data_roaming = null;
        $balance_data_local = null;
        foreach ($balance_data as $item) {

            if( $item->billingAccountType == 'ROAMING' ){

                $balance_data_roaming = $item;
            }
            elseif( $item->billingAccountType == 'LOCAL' ){

                $balance_data_local = $item;
            }
        }

        $data['balance'] = [
            'amount' => isset($balance_data_local->totalOutstanding) ? $balance_data_local->totalOutstanding : 0 ,
            'unit' => isset($balance_data_local->unit) ? $balance_data_local->unit : 'BDT',
            'expires_in' => isset($balance_data_local->nextPaymentDate) ?
                Carbon::parse($balance_data_local->nextPaymentDate)->setTimezone('UTC')->toDateTimeString() : null,
            // 'loan' => [
            //     'is_eligible' => $is_eligible_to_loan,
            //     'amount'      => ($is_eligible_to_loan) ? 30 : 0
            // ]
        ];

        $data['local'] = [
            'billingAccountType' => isset($balance_data_local->billingAccountType) ? $balance_data_local->billingAccountType : null,
            'totalOutstanding' => isset($balance_data_local->totalOutstanding) ? $balance_data_local->totalOutstanding : 0,
            'creditLimit' => isset($balance_data_local->creditLimit) ? $balance_data_local->creditLimit : 0,
            'overPayment' => isset($balance_data_local->overPayment) ? $balance_data_local->overPayment : 0,
            'nextPaymentDate' => isset($balance_data_local->nextPaymentDate) ? Carbon::parse($balance_data_local->nextPaymentDate)->setTimezone('UTC')->toDateTimeString() : null,
        ];

        $local_product_usage = [];
        if( !empty($balance_data_local) && !empty($balance_data_local->productUsage) ){
            foreach ($balance_data_local->productUsage as $local_product ) {

                if( !empty($local_product->code) && !empty($local_product->commercialName && !empty($local_product->usages) )  ){

                    foreach ($local_product->usages as $usages) {

                        if( $usages->serviceType == 'VOICE' ){

                            $local_product_usage['minutes'] = [
                                'total' => !empty($usages->total) ?  $usages->total : 0,
                                'remaining' => !empty($usages->left) ?  $usages->left : 0,
                                'unit' => 'MIN'
                            ];

                        }
                        elseif( $usages->serviceType == 'SMS' ){

                            $local_product_usage['sms'] = [
                                'total' => !empty($usages->total) ?  $usages->total : 0,
                                'remaining' => !empty($usages->left) ?  $usages->left : 0,
                                'unit' => 'SMS'
                            ];

                        }
                        elseif( $usages->serviceType == 'DATA' ){
                            $local_product_usage['internet'] = [
                                'total' => !empty($usages->total) ?  $usages->total : 0,
                                'remaining' => !empty($usages->left) ?  $usages->left : 0,
                                'unit' => 'MB'
                            ];

                        }

                    }
                }

            }
        }

        $data['local']['product_usages'] = $local_product_usage;

        $data['roaming'] = [
            'billingAccountType' => isset($balance_data_roaming->billingAccountType) ? $balance_data_roaming->billingAccountType : null,
            'totalOutstanding' => isset($balance_data_roaming->totalOutstanding) ? $balance_data_roaming->totalOutstanding : 0,
            'creditLimit' => isset($balance_data_roaming->creditLimit) ? $balance_data_roaming->creditLimit : 0,
            'overPayment' => isset($balance_data_roaming->overPayment) ? $balance_data_roaming->overPayment : 0,
            'nextPaymentDate' => isset($balance_data_roaming->overPayment) ? Carbon::parse($balance_data_roaming->nextPaymentDate)->setTimezone('UTC')->toDateTimeString() : null,
        ];

        $roming_product_usage = [];
        if( !empty($balance_data_roaming) && !empty($balance_data_roaming->productUsage) ){
            foreach ($balance_data_roaming->productUsage as $roaming_product ) {

                if( !empty($roaming_product->code) && !empty($roaming_product->commercialName && !empty($roaming_product->usages) )  ){

                    foreach ($roaming_product->usages as $usages) {

                        if( $usages->serviceType == 'VOICE' ){

                            $roming_product_usage['minutes'] = [
                                'total' => !empty($usages->total) ?  $usages->total : 0,
                                'remaining' => !empty($usages->left) ?  $usages->left : 0,
                                'unit' => 'MIN'
                            ];

                        }
                        elseif( $usages->serviceType == 'SMS' ){

                            $roming_product_usage['sms'] = [
                                'total' => !empty($usages->total) ?  $usages->total : 0,
                                'remaining' => !empty($usages->left) ?  $usages->left : 0,
                                'unit' => 'SMS'
                            ];

                        }
                        elseif( $usages->serviceType == 'DATA' ){
                            $roming_product_usage['internet'] = [
                                'total' => !empty($usages->total) ?  $usages->total : 0,
                                'remaining' => !empty($usages->left) ?  $usages->left : 0,
                                'unit' => 'MB'
                            ];

                        }

                    }
                }

            }
        }

        $data['roaming']['product_usages'] = $roming_product_usage;

        # Default local data sending for postpaid
        $default_minutes = !empty($local_product_usage['minutes']) ? $local_product_usage['minutes'] : ( !empty($roming_product_usage['minutes']) ?  $roming_product_usage['minutes'] : 0 );

        $default_sms = !empty($local_product_usage['sms']) ? $local_product_usage['sms'] : ( !empty($roming_product_usage['sms']) ?  $roming_product_usage['sms'] : 0 );

        $default_internet = !empty($local_product_usage['internet']) ? $local_product_usage['internet'] : ( !empty($roming_product_usage['internet']) ?  $roming_product_usage['internet'] : 0 );

        $data['minutes'] = $default_minutes;
        $data['sms'] = $default_sms;
        $data['internet'] = $default_internet;

        return $data;
    }

    private function getPrepaidBalanceUrl($customer_id)
    {
        return self::BALANCE_API_ENDPOINT . '/' . $customer_id . '/prepaid-balances' . '?sortType=SERVICE_TYPE';
    }

    public function getPrepaidBalance($customer_id)
    {
        $response = $this->get($this->getPrepaidBalanceUrl($customer_id));
        $response = json_decode($response['response']);

        if (isset($response->error)) {
            return $this->responseFormatter->sendErrorResponse(
                'Currently Service Unavailable. Please,try again later',
                [
                    'message' => 'Currently Service Unavailable. Please,try again later',
                ],
                500
            );
        }

        $balance_data = collect($response->money);

        $main_balance = $balance_data->first(function ($item) {
            return $item->type == 'MAIN';
        });

        return isset($main_balance->amount) ? $main_balance->amount : 0;
    }

    private function getPostpaidBalanceUrl($customer_id)
    {
        return self::BALANCE_API_ENDPOINT . '/' . $customer_id . '/postpaid-info';
    }
    
    public function getPostpaidBalance($customer_id)
    {
        $response = $this->get($this->getPostpaidBalanceUrl($customer_id));
        $response = json_decode($response['response']);

        if (isset($response->error)) {
            return $this->responseFormatter->sendErrorResponse(
                'Currently Service Unavailable. Please,try again later',
                [
                    'message' => 'Currently Service Unavailable. Please,try again later',
                ],
                500
            );
        }

        $local_balance = collect($response)->where('billingAccountType', '=', 'LOCAL')->first();

        return ($local_balance->creditLimit - $local_balance->totalOutstanding);
    }
}
