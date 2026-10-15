<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Auction;
use App\Models\Item;
use App\Models\Company;

class AuctionAddRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()->can('auction create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'item_id' => [
                'required',
                function ($attribute, $value, $fail) {
                    try {
                        $decryptedId = decrypt($value);

                        if (!Item::where('id', $decryptedId)->exists()) {
                            $fail('The selected item is invalid.');
                        }
                    } catch (\Exception $e) {
                        $fail('Invalid item ID.');
                    }
                }
            ],
            'company_id'=> [
                'required',
                function ($attribute, $value, $fail) {
                    try {
                        $decryptedId = decrypt($value);

                        if (!Company::where('id', $decryptedId)->exists()) {
                            $fail('The selected company is invalid.');
                        }
                    } catch (\Exception $e) {
                        $fail('Invalid company ID.');
                    }
                }
            ],
            'start' => [
                'required',
                'date',
                'after_or_equal:today'
            ],
            'start_time' => [
                'required',
            ],
            'end' => [
                'required',
                'date',
                'after_or_equal:start'
            ],
            'end_time' => [
                'required',
            ],
            'min_bid' => [
                'required',
                'numeric',
                'min:0',
            ],
            'bid_limit' => [
                'nullable',
                'required_if:user_bidding_limit,1',
                'integer',
                'min:1',
            ],
            'bidding_mode' => [
                'nullable',
                Rule::in([Auction::MODE_SEALED, Auction::MODE_OPEN]),
            ],
            'min_increment' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'max_bid' => [
                'nullable',
                'numeric',
                'gte:min_bid',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'start.after_or_equal' => 'The start date cannot be in the past.',
            'end.after_or_equal' => 'The end date must be on or after the start date.',
            'min_bid.min' => 'The minimum bid cannot be negative.',
            'bid_limit.required_if' => 'Enter how many bids each person may place, or turn off the bid limit.',
            'bid_limit.min' => 'Allow at least one bid per person.',
            'bidding_mode.in' => 'Choose sealed or open bidding.',
            'max_bid.gte' => 'The maximum bid must be at least the minimum bid.',
        ];
    }
}
