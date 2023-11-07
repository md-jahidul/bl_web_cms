<?php

namespace App\Http\Requests;

use App\Enums\HttpStatusCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;

class UsageHistoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'from' => 'required|date|date_format:Y-m-d|before_or_equal:today',
            'to' => 'required|date_format:Y-m-d|after_or_equal:from'
        ];
    }

}
