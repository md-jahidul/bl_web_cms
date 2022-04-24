<?php

namespace App\Http\Controllers\API\V1\UpsellFacebook;

use App\Exceptions\IdpAuthException;
use App\Http\Controllers\Controller;
use App\Services\AboutUsService;
use App\Services\Banglalink\BalanceService;
use App\Services\CustomerService;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


class UpsellController extends Controller
{
    private $customerService, $productService, $balanceService;
    public function __construct(CustomerService $customerService, ProductService $productService, BalanceService $balanceService){

        $this->customerService = $customerService;
        $this->productService = $productService;
        $this->balanceService = $balanceService;
    }
    public function phaseOne(Request $request)
    {
        // OTP

        /**
         * OTP JOURNEY
         * 
         * 1. Find Customer By Phone No
         * 2. Check if customer is eligible for the product ** ERROR
         * 3. get product cost
         * 4. check customer balance
         * 5. 4 > 3 Return OTP page & Send OTP to Customer Phone
         * 6. 4 < 3 Return Primary Error
         */
        $customer = $this->customerService->getCustomerInfoByPhone($request->msisdn);
        $product = $this->productService->getProductByCode( $request->product_code);
        $result = $this->productService->eligible($request->msisdn, $request->product_code);
        $customerStatus =  $result->getData();
        $customerType = $customer->number_type;
        if($customerStatus->status_code!=200){
            dd("error");
        }

        $productPrice = $product->productCore->price;
        if($customerType == 'prepaid'){
            $customerBalance = $this->balanceService->getPrepaidBalance($customer->id);

            if ($productPrice > $customerBalance) {
                dd("You don't have enough balance to purchase this package.");
            }
        }
        
        if($customerType == 'postpaid'){
            $customerBalance = $this->balanceService->getPostpaidBalance($customer->id);

            if ($productPrice > $customerBalance) {
                dd("You don't have enough balance to purchase this package.");
            }
        }
        
         /**
         * CURRENCY JOURNEY
         * 
         * 1. Find Customer By Phone No
         * 2. Check if customer is eligible for the product ** ERROR
         * 3. get product cost
         * 4. Return Payment Page
         */
         
        

    }

    public function phaseTwo()
    {
        
    }
}
