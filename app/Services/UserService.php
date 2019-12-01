<?php

namespace App\Services;

use App\Enums\HttpStatusCode;
use App\Repositories\OtpConfigRepository;
use App\Repositories\OtpRepository;
use App\Services\Banglalink\BanglalinkOtpService;
use Exception;
use App\Repositories\UserRepository;
use App\Http\Requests\DeviceTokenRequest;
use Illuminate\Support\Facades\Crypt;

/**
 * Class BannerService
 * @package App\Services
 */
class UserService extends ApiBaseService
{

    /**
     * @var UserRepository
     */
    protected $userRepository;
    /**
     * @var NumberValidationService
     */
    protected $numberValidationService;

    /**
     * @var OtpRepository
     */
    protected $otpRepository;

    /**
     * @var BanglalinkOtpService
     */
    protected $blOtpService;

    /**
     * @var OtpConfigRepository
     */
    protected $otpConfigRepository;


    /**
     * UserService constructor.
     *
     * @param UserRepository $userRepository
     * @param NumberValidationService $numberValidationService
     * @param OtpRepository $otpRepository
     * @param BanglalinkOtpService $blOtpService
     * @param OtpConfigRepository $otpConfigRepository
     */
    public function __construct(UserRepository $userRepository, NumberValidationService $numberValidationService,
                                OtpRepository $otpRepository, BanglalinkOtpService $blOtpService, OtpConfigRepository $otpConfigRepository)
    {
        $this->userRepository = $userRepository;
        $this->numberValidationService = $numberValidationService;
        $this->otpRepository = $otpRepository;
        $this->blOtpService = $blOtpService;
        $this->otpConfigRepository = $otpConfigRepository;
    }

    public function otpLoginRequest($request)
    {
        $mobile = $request->mobile;
        $validationResponse = $this->numberValidationService->validateNumberWithResponse($mobile);

        if ($validationResponse->getData()->status == 'FAIL') {
            return $validationResponse;
        }

        if (!$this->isUserExist($mobile)) {
            $customerInfo = $validationResponse->getData()->data;
            $registrationStatus = $this->register($customerInfo, $mobile);
            if ($registrationStatus['status'] == 'FAIL') {
                return $this->sendErrorResponse($registrationStatus['data']->message, [], $registrationStatus['status_code']);
            }
        }

        return $this->sendOTP('88'.$mobile);

    }

    public function otpLogin($request)
    {
        //Todo: Check otp session
        $data['otp'] = "1234";
        $data['grant_type'] = "otp_grant";
        $data['client_id'] = "690848d0-0f37-11ea-8ab4-8d71fb6b7fa1";
        $data['client_secret'] = "fEnetOLLSdVLT4xe1ARH95l6dKEpiPl6AnIQelkv";
        $data['username'] = $request['mobile'];

        $tokenResponse = IdpIntegrationService::otpGrantTokenRequest($data);
        $tokenResponseData = json_decode($tokenResponse['data']);
        if ($tokenResponse['http_code'] != 200) {
            return $this->sendErrorResponse('IDP error', $tokenResponseData->message, HttpStatusCode::UNAUTHORIZED);
        } else {
            $customerInfo = $this->getCustomerInfo($request['mobile']);
            $profileData = [
                'token' => $tokenResponseData,
                'customerInfo' => $customerInfo,
            ];

            return $this->sendSuccessResponse($profileData, "Data found");
        }
    }

    public function getCustomerInfo($mobile)
    {
        $customerInfo = array();
        $user = $this->userRepository->findOneBy(['phone' => $mobile]);
        $customerInfo['personal_data'] = $user;
        $customerInfo['balance_data'] = 'balance data';

        return $customerInfo;

    }


    /**
     * Send OTP
     *
     * @param $number
     * @return string
     */
    public function sendOTP($number)
    {

        $otp_config = $this->otpConfigRepository->getOtpConfig();

        $conf = $otp_config->toArray();

        if (isset($conf[0]['validation_time'])) {
            $validation_time = $conf[0]['validation_time'];
            $otp_bl = $this->blOtpService->sendOtp($number, $conf[0]['token_length_string'], "#", $validation_time);
        } else {
            $validation_time = 300;
            $otp_bl = $this->blOtpService->sendOtp($number);
        }


        $token = $this->generateOtpToken(18);

        $encrypted_token = Crypt::encryptString($token);

        $otp = $this->generateNumericOTP(6);

        $this->otpRepository->createOtp($number, $otp, $encrypted_token);

        $data = [
            'validation_time' => $validation_time,
            'otp_token' => $encrypted_token
        ];

        return $this->sendSuccessResponse($data, 'OTP Send Successfully', [], HttpStatusCode::SUCCESS);
    }

    /**
     * Generate OTP
     *
     * @param $n
     * @return string
     */
    public function generateNumericOTP($n)
    {
        $generator = "1357902468";

        $result = "";

        for ($i = 1; $i <= $n; $i++) {
            $result .= substr($generator, (rand() % (strlen($generator))), 1);
        }

        return $result;
    }


    public function generateOtpToken($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }


    public function viewProfile($request)
    {
        $bearerToken = ['token' => $request->header('authorization')];

        $response = IdpIntegrationService::tokenValidationRequest($bearerToken);

        $idpData = json_decode($response['data']);

        if ($response['http_code'] != 200 || $idpData->token_status != 'Valid') {
            return $this->sendErrorResponse("Token is Invalid", [], HttpStatusCode::UNAUTHORIZED);
        }

        $user = $this->getCustomerInfo($idpData->user->mobile);
        return $this->sendSuccessResponse($user, 'Data found', []);
    }

    private function transformUserData($user)
    {
        $data = [];
        $data['first_name'] = $user['name'];
        $data['last_name'] = $user['name'];
        $data['gender'] = 'Male';
        $data['mobile'] = $user['mobile'];
        $data['alt_mobile'] = '01722445625';
        $data['dob'] = $user['birth_date'];
        $data['profile_picture'] = $user['profile_image'];
        $data['email'] = $user['email'];
        $data['address'] = 'Mohakhali Dhaka';

        return $data;
    }

    public function isUserExist($mobile)
    {
        $user = $this->userRepository->findOneBy(['phone' => $mobile]);

        return $user ? true : false;
    }

    private function register($customerInfo, $mobile)
    {
        $data['mobile'] = $mobile;
        $data['phone'] = $mobile;

        $data['password'] = '15152515';

        $data['password_confirmation'] = '15152515'; //TODO:generate random string

        $data['username'] = $mobile;

        $customer_account_id = $customerInfo->package->customerId;

        $data['customer_account_id'] = $customer_account_id;

        $idpCus = IdpIntegrationService::getCustomerInfo($mobile);

        if ($idpCus['http_code'] != 200) {
            //If customer is not exist add data to IDP
            $response = IdpIntegrationService::registrationRequest($data);
            if ($response['http_code'] != 201) {
                $errorData = json_decode($response['data']);
                return ['status' => 'FAIL', 'data' => $errorData, 'status_code' => $response['http_code']];
            }
        }

        $user = $this->userRepository->create($data);

        return ['status' => 'SUCCESS', 'data' => $user];;

    }

    public function updateProfile($request)
    {
        $bearerToken = ['token' => $request->header('authorization')];


        $response = IdpIntegrationService::tokenValidationRequest($bearerToken);
        $idpData = json_decode($response['data']);

        if ($response['http_code'] != 200 || $idpData->token_status != 'Valid') {
            return $this->sendErrorResponse("Token is Invalid", [], HttpStatusCode::UNAUTHORIZED);
        }

        $user = $this->userRepository->findOneBy(['phone' => $idpData->user->mobile]);
        if (!$user) {
            return $this->sendErrorResponse('User not found in the system');
        }
        $data = $request->all();
        $data['msisdn'] = '+880' . $idpData->user->mobile;

        if ($request->hasFile('profile_photo')) {
            $path = $this->uploadImage($request);
            $data['profile_image'] = $path;
        }

        $user->update($data);

        return $this->sendSuccessResponse($user, 'Data updated successfully');
    }

    public function uploadProfileImage($request)
    {
        $bearerToken = ['token' => $request->header('authorization')];

        $response = IdpIntegrationService::tokenValidationRequest($bearerToken);
        $idpData = json_decode($response['data']);

        if ($response['http_code'] != 200 || $idpData->token_status != 'Valid') {
            return $this->sendErrorResponse("Token is Invalid", [], HttpStatusCode::UNAUTHORIZED);
        }

        $user = $this->userRepository->findOneBy(['phone' => $idpData->user->mobile]);

        $path = $this->uploadImage($request);

        $user->update(['profile_image' => $path]);

        return $this->sendSuccessResponse(['image_path' => $path], 'Profile picture updated successfully');
    }

    private function uploadImage($request)
    {
        try {
            $file = $request->file('profile_photo');
            $ext = $file->getClientOriginalExtension();
            $photoExt = array('jpg', 'JPG', 'JPEG', 'jpeg', 'png', 'PNG', 'gif', 'bmp');
            if (!in_array($ext, $photoExt)) {
                return $this->sendErrorResponse('Invalid image extension', [], 400);
            }
            $fileName = md5(strtotime(now())) . '.' . $file->getClientOriginalExtension();
            $file->storeAs(
                'uploads/profile-images',
                $fileName,
                'public'
            );
            return '/storage/uploads/profile-images/' . $fileName;
        } catch (\Exception $e) {
            return $this->sendErrorResponse($e->getMessage(), [], 500);
        }
    }
}
