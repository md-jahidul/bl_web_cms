<?php

namespace App\Repositories;

use App\Models\Otp;
use Carbon\Carbon;

/**
 * Class OtpRepository
 * @package App\Repositories
 */
class OtpRepository
{
    /**
     * @var Otp
     */
    protected $model;


    /**
     * OtpRepository constructor.
     * @param Otp $model
     */
    public function __construct(Otp $model)
    {
        $this->model = $model;
    }




    /**
     * Update Otp
     *
     * @param $phone
     * @param $otp_token
     * @param $encrypted_token
     * @return mixed
     */
    public function createOtp($phone, $otp_token, $encrypted_token)
    {
        $otp = $this->model->where('phone', $phone)->first();
        # OTP expiry in sec
        $otp_expiry = config('apiconfig.opt_token_expiry');

        if ($otp) {
            $otp->otp = $otp_token;

            $otp->token = $encrypted_token;

            $otp->starts_at = Carbon::now();

            // $otp->expires_at = Carbon::now()->addMinutes(5);
            $otp->expires_at = Carbon::now()->addSeconds($otp_expiry);  // otp expire changed

            $otp->save();

            return $otp;
        }

        $this->model->phone = $phone;

        $this->model->otp = $otp_token;

        $this->model->token = $encrypted_token;

        $this->model->starts_at = Carbon::now();

        // $this->model->expires_at = Carbon::now()->addMinutes(5);
        $this->model->expires_at = Carbon::now()->addSeconds($otp_expiry); // otp expire changed

        return $this->model->save();
    }

    /**
     * Update Otp
     *
     * @param $phone
     * @param $otp_token
     * @param $encrypted_token
     * @return mixed
     */
    public function updateOtpInfo($phone, $otp_token, $encrypted_token)
    {
        $otp_expiry = config('apiconfig.opt_token_expiry');

        $otp = $this->model->where('phone', $phone)->first();

        $otp->otp = $otp_token;

        $otp->token = $encrypted_token;

        $otp->starts_at = Carbon::now();

        $otp->expires_at = Carbon::now()->addSeconds($otp_expiry);

        $otp->save();

        return $otp;
    }

    /**
     * Retrieve otp info
     *
     * @param $phone
     * @return mixed
     */
    public function getOtpInfo($phone)
    {
        return $this->model->where('phone', $phone)->first();
    }

    /**
     * Validate otp token with phone number
     * @param  [number] $phone     [phone]
     * @param  [string] $otp_token [otp_session key]
     * @return [mixed]            [description]
     */
    public function validateOtpToken($phone, $otp_token){
        return $this->model->where('phone', $phone)
                ->where('token', $otp_token)
                ->where('expires_at', '>', Carbon::now())
                ->first();
    }
}
