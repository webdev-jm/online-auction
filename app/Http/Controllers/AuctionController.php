<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Auction;
use App\Models\Item;
use App\Models\Company;

use App\Http\Requests\AuctionAddRequest;
use App\Http\Requests\AuctionEditRequest;

use App\Services\AuctionLifecycle;
use App\Services\AuctionSettings;

use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AuctionExport;

use Barryvdh\DomPDF\Facade\Pdf as PDF;

class AuctionController extends Controller
{
    public function __construct(private AuctionSettings $settings)
    {
    }

    public function index(Request $request, AuctionLifecycle $lifecycle) {
        $lifecycle->syncIfDue();

        $search = trim((string) $request->get('search'));
        $status = $request->get('status');

        $auctions = Auction::with(['item', 'auction_winner'])
            ->withCount('biddings')
            ->orderBy('created_at', 'DESC')
            ->when(!empty($search), function($query) use($search) {
                $query->where(function($query) use($search) {
                    $query->where('auction_code', 'like', '%'.$search.'%')
                        ->orWhere('start', 'like', '%'.$search.'%')
                        ->orWhere('end', 'like', '%'.$search.'%')
                        ->orWhereHas('item', function($qry) use($search) {
                            $qry->where('item_number', 'like', '%'.$search.'%')
                                ->orWhere('name', 'like', '%'.$search.'%')
                                ->orWhere('brand', 'like', '%'.$search.'%')
                                ->orWhereHas('specifications', function($spec) use($search) {
                                    $spec->where('value', 'like', '%'.$search.'%')
                                        ->orWhere('specification', 'like', '%'.$search.'%');
                                });
                        });
                });
            })
            ->when(in_array($status, [Auction::STATUS_UPCOMING, Auction::STATUS_OPEN, Auction::STATUS_ENDED], true), function($query) use($status) {
                $query->where('status', $status);
            })
            ->paginate(10)->appends(request()->query());

        return view('pages.auctions.index')->with([
            'search' => $search,
            'status' => $status,
            'auctions' => $auctions
        ]);
    }

    public function create() {
        return view('pages.auctions.create')->with([
            'auction_code' => $this->generateAuctionCode(),
            'items' => $this->itemOptions(),
            'companies' => $this->companyOptions(),
            'defaults' => $this->formDefaults(),
        ]);
    }

    public function store(AuctionAddRequest $request) {

        $auction = new Auction([
            'item_id' => decrypt($request->item_id),
            'company_id' => decrypt($request->company_id),
            'auction_code' => $this->generateAuctionCode(),
            ...$this->auctionAttributes($request),
        ]);
        $auction->save();

        // logs
        activity('created')
            ->performedOn($auction)
            ->log(':causer.name has created auction :subject.auction_code');

        return redirect()->route('auction.index')->with([
            'message_success' => 'Auction '.$auction->auction_code.' has been created successfully.'
        ]);

    }

    public function show($id) {
        $auction = Auction::with(['item.pictures', 'company', 'winners.bidding.user', 'winners.handler'])
            ->findOrFail(decrypt($id));

        $biddings = $auction->biddings()
            ->with('user')
            ->orderBy('bid_amount', 'DESC')
            ->orderBy('created_at', 'ASC')
            ->get();

        return view('pages.auctions.show')->with([
            'auction' => $auction,
            'biddings' => $biddings,
        ]);
    }

    public function edit($id) {
        $auction = Auction::findOrFail(decrypt($id));

        $items = $this->itemOptions($auction->item_id);
        $companies = $this->companyOptions();

        return view('pages.auctions.edit')->with([
            'auction' => $auction,
            'items' => $items,
            'selected_item' => array_search($auction->item_id, $this->decryptedKeys($items)),
            'companies' => $companies,
            'selected_company' => array_search($auction->company_id, $this->decryptedKeys($companies)),
            'defaults' => $this->formDefaults(),
        ]);
    }

    public function update(AuctionEditRequest $request, $id) {
        $auction = Auction::findOrFail(decrypt($id));

        $changes_arr['old'] = $auction->getOriginal();

        $auction->update([
            'item_id' => decrypt($request->item_id),
            'company_id' => decrypt($request->company_id),
            ...$this->auctionAttributes($request),
        ]);

        $changes_arr['changes'] = $auction->getChanges();

        // logs
        activity('updated')
            ->performedOn($auction)
            ->withProperties($changes_arr)
            ->log(':causer.name updated auction :subject.auction_code');

        return back()->with([
            'message_success' => 'Auction '.$auction->auction_code.' has been updated successfully.'
        ]);
    }

    public function export($id) {
        $auction = Auction::findOrFail(decrypt($id));

        return Excel::download(new AuctionExport($auction), 'auction-'.$auction->auction_code.'.xlsx');
    }

    public function printPdf($id) {
        $auction = Auction::findOrFail(decrypt($id));

        $pdf = PDF::loadView('pages.auctions.pdf', [
            'auction' => $auction,
        ]);

        return $pdf->stream('auction-'.$auction->auction_code.'-'.time().'.pdf');
    }

    public function sendWinnerNotification($id, AuctionLifecycle $lifecycle) {
        $auction = Auction::findOrFail(decrypt($id));
        $auction_winner = $auction->auction_winner;

        if (!empty($auction_winner) && $lifecycle->notifyWinner($auction_winner)) {
            return back()->with([
                'message_success' => 'Winner notification sent to '.$auction_winner->bidding->user->name.'.',
            ]);
        }

        return back()->with([
            'message_error' => 'The winner notification could not be sent. Check that the auction has a winner and that mail is configured.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function auctionAttributes(Request $request): array
    {
        return [
            'start' => $request->start,
            'start_time' => $request->start_time,
            'end' => $request->end,
            'end_time' => $request->end_time,
            'min_bid' => $request->min_bid,
            'bid_limit' => $request->user_bidding_limit ? $request->bid_limit : NULL,
            'bidding_mode' => $request->bidding_mode ?: NULL,
            'min_increment' => $request->filled('min_increment') ? $request->min_increment : NULL,
            'max_bid' => $request->filled('max_bid') ? $request->max_bid : NULL,
            'show_bidders' => $request->boolean('show_bidders'),
            'show_leading_bidder' => $request->boolean('show_leading_bidder'),
            'show_last_place_bidder' => $request->boolean('show_last_place_bidder'),
            'restrict_to_company_only' => $request->boolean('restrict_to_company_only'),
        ];
    }

    /**
     * Values that pre-fill a new auction, taken from Auction Settings.
     *
     * @return array<string, mixed>
     */
    private function formDefaults(): array
    {
        return [
            'bid_limit' => $this->settings->get('max_bids_per_user'),
            'bidding_mode' => $this->settings->biddingMode(),
            'min_increment' => $this->settings->get('min_increment'),
            'max_bid' => $this->settings->get('max_bid'),
            'show_bidders' => $this->settings->get('default_show_bidders'),
            'show_leading_bidder' => $this->settings->get('default_show_leading_bidder'),
            'show_last_place_bidder' => $this->settings->get('default_show_last_place_bidder'),
            'restrict_to_company_only' => $this->settings->get('default_restrict_to_company_only'),
        ];
    }

    /**
     * Items not yet sold, keyed by encrypted id. The auction's own item stays in the list when editing.
     *
     * @return array<string, string>
     */
    private function itemOptions(?int $includeItemId = null): array {
        return Item::query()
            ->where(function($query) use($includeItemId) {
                $query->whereDoesntHave('auctions', function ($query) {
                    $query->whereHas('auction_winner');
                })
                ->when($includeItemId, fn($query) => $query->orWhere('id', $includeItemId));
            })
            ->orderBy('item_number', 'ASC')
            ->get()
            ->mapWithKeys(fn(Item $item) => [encrypt($item->id) => '['.$item->item_number.'] '.$item->name])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function companyOptions(): array {
        return Company::orderBy('name', 'ASC')
            ->get()
            ->mapWithKeys(fn(Company $company) => [encrypt($company->id) => $company->name])
            ->all();
    }

    /**
     * @param  array<string, string>  $options
     * @return array<string, int>
     */
    private function decryptedKeys(array $options): array {
        $keys = [];
        foreach (array_keys($options) as $encrypted) {
            $keys[$encrypted] = decrypt($encrypted);
        }

        return $keys;
    }

    private function generateAuctionCode(): string {
        $prefix = (string) $this->settings->get('auction_code_prefix');
        $digits = max(1, (int) $this->settings->get('auction_code_digits'));

        $lastNumber = Auction::withTrashed()
            ->where('auction_code', 'LIKE', $prefix.'%')
            ->pluck('auction_code')
            ->map(fn(string $code) => preg_match('/^'.preg_quote($prefix, '/').'(\d+)$/', $code, $matches) ? (int) $matches[1] : 0)
            ->max() ?? 0;

        do {
            $lastNumber++;
            $code = $prefix.str_pad((string) $lastNumber, $digits, '0', STR_PAD_LEFT);
        } while (Auction::withTrashed()->where('auction_code', $code)->exists());

        return $code;
    }
}
