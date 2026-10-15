<?php

namespace App\Http\Requests;

class AuctionEditRequest extends AuctionAddRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()->can('auction edit');
    }

    /**
     * Same rules as a new auction, except an existing auction may have started in the past.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['start'] = ['required', 'date'];

        return $rules;
    }
}
