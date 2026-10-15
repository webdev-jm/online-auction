<?php

/*
|--------------------------------------------------------------------------
| Auction settings
|--------------------------------------------------------------------------
|
| Every option on Settings > Auction Settings, with its type and the value
| used until an administrator saves a different one. Saved values live in
| the settings table and are read through App\Support\AuctionSettings.
|
*/

return [

    'settings' => [

        // Bidding
        'bidding_mode' => ['type' => 'string', 'default' => 'sealed'],
        'max_bids_per_user' => ['type' => 'int', 'default' => 1],
        'min_increment' => ['type' => 'decimal', 'default' => 100],
        'max_bid' => ['type' => 'decimal', 'default' => null],
        'anti_sniping_enabled' => ['type' => 'bool', 'default' => false],
        'anti_sniping_minutes' => ['type' => 'int', 'default' => 5],

        // New auction defaults
        'default_show_bidders' => ['type' => 'bool', 'default' => false],
        'default_show_leading_bidder' => ['type' => 'bool', 'default' => false],
        'default_show_last_place_bidder' => ['type' => 'bool', 'default' => false],
        'default_restrict_to_company_only' => ['type' => 'bool', 'default' => false],
        'auction_code_prefix' => ['type' => 'string', 'default' => 'AUCT-'],
        'auction_code_digits' => ['type' => 'int', 'default' => 3],

        // After the auction
        'payment_window_days' => ['type' => 'int', 'default' => 2],
        'payment_methods' => ['type' => 'string', 'default' => 'Cash'],
        'pickup_location' => ['type' => 'string', 'default' => 'IT Department'],
        'auto_forfeit' => ['type' => 'bool', 'default' => true],

        // Notifications
        'notify_bid_placed' => ['type' => 'bool', 'default' => true],
        'notify_outbid' => ['type' => 'bool', 'default' => true],
        'notify_ending_soon' => ['type' => 'bool', 'default' => false],
        'ending_soon_hours' => ['type' => 'int', 'default' => 24],
        'notify_winner_automatically' => ['type' => 'bool', 'default' => false],
        'notify_payment_reminder' => ['type' => 'bool', 'default' => true],

        // Terms and conditions
        'terms_version' => ['type' => 'int', 'default' => 1],
        'terms_content' => ['type' => 'text', 'default' => <<<'MD'
## Bidding process

- Each auction item has a minimum bid. Your bid must be equal to or greater than it.
- You may place up to the number of bids shown on each auction.
- Each auction is open for the period shown on its card.
- Items without a bidder will be auctioned again at a later date.

## Bidding rules

- Once a bid is placed, it cannot be cancelled or withdrawn.
- The highest bid at closing time wins the auction.
- If several people place the same highest bid, the first one to bid wins.

## Payment and confirmation

- Accepted payment: {payment_methods}.
- Payment must be completed within {payment_window_days} day(s) of the auction closing.
- If payment is not made in time, the item may be awarded to the next highest bidder.

## Item pickup

- After payment, the item can be picked up at {pickup_location}.
- You may check and test the item there to confirm it matches the auction details.
MD],

    ],

];
