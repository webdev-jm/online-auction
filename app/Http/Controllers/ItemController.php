<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    public function index(Request $request) {
        $search = trim((string) $request->get('search'));

        $items = Item::with(['pictures', 'auctions.auction_winner'])
            ->orderBy('item_number', 'DESC')
            ->when(!empty($search), function($query) use($search) {
                $query->where(function($query) use($search) {
                    $query->where('item_number', 'like', '%'.$search.'%')
                        ->orWhere('name', 'like', '%'.$search.'%')
                        ->orWhere('brand', 'like', '%'.$search.'%')
                        ->orWhereHas('specifications', function($qry) use($search) {
                            $qry->where('value', 'like', '%'.$search.'%')
                                ->orWhere('specification', 'like', '%'.$search.'%');
                        });
                });
            })
            ->paginate(10)->appends(request()->query());

        return view('pages.items.index')->with([
            'search' => $search,
            'items' => $items
        ]);
    }

    public function create() {
        return view('pages.items.create');
    }

    public function edit($id) {
        $item = Item::findOrFail(decrypt($id));

        return view('pages.items.edit')->with([
            'item' => $item
        ]);
    }

    public function show($id) {
        $item = Item::findOrFail(decrypt($id));

        return view('pages.items.show')->with([
            'item' => $item
        ]);
    }

    public function upload() {
        return view('pages.items.upload');
    }
}
