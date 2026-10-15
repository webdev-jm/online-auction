<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuctionSettingsRequest;
use App\Services\AuctionSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class AuctionSettingController extends Controller
{
    public function __construct(private AuctionSettings $settings)
    {
    }

    public function edit(): View
    {
        return view('pages.settings.auction')->with([
            'settings' => $this->settings->all(),
        ]);
    }

    public function update(AuctionSettingsRequest $request): RedirectResponse
    {
        $old = $this->settings->all();
        $values = $request->settings();

        if ($request->boolean('publish_new_terms')) {
            $values['terms_version'] = (int) $old['terms_version'] + 1;
        }

        $this->settings->save($values);

        activity('updated')
            ->withProperties([
                'old' => array_intersect_key($old, $values),
                'changes' => array_diff_assoc(array_map('strval', $values), array_map('strval', array_intersect_key($old, $values))),
            ])
            ->log(':causer.name updated the auction settings');

        $message = $request->boolean('publish_new_terms')
            ? 'Auction settings saved. Version '.$values['terms_version'].' of the terms is live, and bidders will be asked to accept it.'
            : 'Auction settings saved.';

        return redirect()->route('settings.auction')->with('message_success', $message);
    }
}
