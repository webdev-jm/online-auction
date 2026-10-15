<?php

namespace App\Http\Requests;

use App\Models\Auction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AuctionSettingsRequest extends FormRequest
{
    /**
     * Settings stored as on/off switches. An unticked box is not submitted, so it means "off".
     *
     * @var list<string>
     */
    public const SWITCHES = [
        'anti_sniping_enabled',
        'default_show_bidders',
        'default_show_leading_bidder',
        'default_show_last_place_bidder',
        'default_restrict_to_company_only',
        'auto_forfeit',
        'notify_bid_placed',
        'notify_outbid',
        'notify_ending_soon',
        'notify_winner_automatically',
        'notify_payment_reminder',
    ];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('settings manage');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'bidding_mode' => ['required', Rule::in([Auction::MODE_SEALED, Auction::MODE_OPEN])],
            'max_bids_per_user' => ['nullable', 'integer', 'min:1', 'max:100'],
            'min_increment' => ['required', 'numeric', 'min:0'],
            'max_bid' => ['nullable', 'numeric', 'min:0'],
            'anti_sniping_minutes' => ['required', 'integer', 'min:1', 'max:120'],
            'auction_code_prefix' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9_-]+$/'],
            'auction_code_digits' => ['required', 'integer', 'min:1', 'max:8'],
            'payment_window_days' => ['required', 'integer', 'min:0', 'max:60'],
            'payment_methods' => ['required', 'string', 'max:255'],
            'pickup_location' => ['required', 'string', 'max:255'],
            'ending_soon_hours' => ['required', 'integer', 'min:1', 'max:168'],
            'terms_content' => ['required', 'string', 'max:20000'],
            'publish_new_terms' => ['nullable', 'boolean'],
            ...array_fill_keys(self::SWITCHES, ['nullable', 'boolean']),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'bidding_mode.in' => 'Choose sealed or open bidding.',
            'max_bids_per_user.min' => 'Allow at least one bid per person, or leave it empty for no limit.',
            'auction_code_prefix.regex' => 'Use only letters, numbers, dashes and underscores in the code prefix.',
            'auction_code_digits.max' => 'Use at most 8 digits in the auction code.',
            'payment_window_days.max' => 'The payment window can be at most 60 days.',
            'anti_sniping_minutes.max' => 'The extension can be at most 120 minutes.',
            'ending_soon_hours.max' => 'Send the ending-soon notice at most 168 hours (7 days) before closing.',
            'terms_content.required' => 'The terms and conditions cannot be empty.',
        ];
    }

    /**
     * Validated values in the shape AuctionSettings::save() expects.
     *
     * @return array<string, mixed>
     */
    public function settings(): array
    {
        $values = collect($this->validated())->except(['publish_new_terms', ...self::SWITCHES])->all();

        foreach (self::SWITCHES as $switch) {
            $values[$switch] = $this->boolean($switch);
        }

        return $values;
    }
}
