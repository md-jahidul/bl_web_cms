<?php

namespace App\Services\Banglalink;

use App\Helpers\SmsLanguageHelper;
use App\Services\ApiBaseService;

class BanglalinkOtpService extends BaseService
{

    protected $service;
    protected const SEND_OTP_ENDPOINT   = "/otp/one-time-passwords";
    protected const VERIFY_OTP_ENDPOINT = "/otp/one-time-passwords/validate";

    /**
     * BanglalinkOtpService constructor.
     */
    public function __construct()
    {
        $this->service = new ApiBaseService();
    }


    /**
     * Send OTP
     * @param $msisdn
     * @param string $tokenLength
     * @param string $tokenChar
     * @param int $validity
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendOtp(
        $msisdn,
        $tokenLength = 'SIX',
        $tokenChar = '#',
        $validity = 300,
        $message = "Your OTP is #"
    ) {

        $validity = config('apiconfig.opt_token_expiry');

        $expire = number_format(($validity / 60));

        $lang = SmsLanguageHelper::getCustomerPreferredLanguage($msisdn);
        $vars = [$tokenChar, $expire];
        $message = SmsLanguageHelper::getSmsText(config('constants.sms.features')[0], $vars, $lang);
//        $message =  "Your OTP is " . $tokenChar . ". This OTP will be expired within " . $expire . " minutes.";

        $param = [
            'message' => $message,
            'msisdn' => $msisdn,
            'tokenChar' => $tokenChar,
            'tokenLength' => $tokenLength,
            'validityInSeconds' => $validity,
        ];

        $response = $this->post(self::SEND_OTP_ENDPOINT, $param);

        return $response;
    }


    /**
     * Validate Otp
     * @param $msisdn
     * @param $otp
     * @return string
     */
    public function validateOtp($msisdn, $otp)
    {
        $param = [
            'msisdn' => $msisdn,
            'token' => $otp
        ];

        $response = $this->post(self::VERIFY_OTP_ENDPOINT, $param);

        return $response;
    }


    /**
     * @param $response
     * @return \Illuminate\Http\JsonResponse
     */
    public function formatSendOtpResponse($response)
    {
        if ($response && isset($response->error)) {
            return $this->service->sendErrorResponse('Error in Internal Api Service', [], 500);
        }

        return  $this->service->sendSuccessResponse([], 'OTP send Successfully');
    }
}
