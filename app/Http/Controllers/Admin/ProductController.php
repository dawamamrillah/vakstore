<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Game;
use App\Models\Product;
use App\Models\ProductSyncChange;
use App\Models\Provider;
use App\Services\Audit\AuditService;
use App\Services\Provider\DigiflazzSyncService;
use App\Support\ErrorSanitizer;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $activeTab = $request->get('tab', 'mobile-legends');
        $search = $request->get('search');

        // Fetch active games and providers
        $games = Game::with('category')->where('status', 'active')->orderBy('category_id')->orderBy('id')->get();
        $ppobCategory = Category::where('type', 'ppob')->first();
        $providers = Provider::all();

        $query = Product::with(['category', 'game', 'provider'])->where('status', 'active');

        $activeGame = null;
        if ($activeTab === 'ppob') {
            if ($ppobCategory) {
                $query->where('category_id', $ppobCategory->id);
            }
        } elseif ($activeTab === 'all') {
            // No game filter
        } else {
            $activeGame = Game::where('slug', $activeTab)->where('status', 'active')->first();
            if ($activeGame) {
                $query->where('game_id', $activeGame->id);
            } elseif ($games->isNotEmpty()) {
                $activeGame = $games->first();
                $activeTab = $activeGame->slug;
                $query->where('game_id', $activeGame->id);
            }
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('provider_sku', 'like', "%{$search}%")
                    ->orWhere('sub_category', 'like', "%{$search}%");
            });
        }

        $products = $query->orderBy('sub_category')->orderBy('cost_price')->paginate(50)->withQueryString();

        // Preset Subcategories mapping per game / service for modal
        $presetSubCategories = [
            'mobile-legends' => ['Weekly Diamond Pass', 'Top Up Diamond', 'Special Bundle', 'Twilight Pass'],
            'free-fire' => ['Top Up Diamond', 'Membership & Pass'],
            'free-fire-max' => ['Top Up Diamond', 'Membership & Pass'],
            'pubg-mobile' => ['UC Top Up', 'Membership & Pass'],
            'call-of-duty-mobile' => ['CP (Call of Duty Points)', 'Battle Pass & Bundle'],
            'honor-of-kings' => ['Tokens (Top Up)', 'Weekly Card & Pass'],
            'magic-chess-go-go' => ['Diamonds (Top Up)', 'Commander Pass'],
            'valorant' => ['Points (VP)'],
            'pln' => ['Prabayar (Token)'],
            'telkomsel' => ['Pulsa Reguler', 'Paket Data & Kuota'],
            'indosat' => ['Pulsa Reguler', 'Paket Data & Kuota'],
            'xl' => ['Pulsa Reguler', 'Paket Data & Kuota'],
            'axis' => ['Pulsa Reguler', 'Paket Data & Kuota'],
            'tri' => ['Pulsa Reguler', 'Paket Data & Kuota'],
            'smartfren' => ['Pulsa Reguler', 'Paket Data & Kuota'],
            'byu' => ['Pulsa Reguler', 'Paket Data & Kuota'],
            'pln-pascabayar' => ['Tagihan Bulanan'],
            'pdam-nusantara' => ['PDAM Jawa Timur', 'PDAM DKI Jakarta & Banten', 'PDAM Jawa Barat', 'PDAM Bali', 'PDAM Jawa Tengah & DIY'],
            'telkom-indihome' => ['Telkom & IndiHome', 'Fiber Internet & TV Kabel'],
        ];

        // Stats for current tab
        $tabTotalItems = $products->total();
        $tabActiveItems = (clone $query)->where('status', 'active')->count();
        $tabAvgProfit = (clone $query)->where('status', 'active')->avg('profit') ?? 0;
        $lastSyncedAt = Product::whereNotNull('last_synced_at')->max('last_synced_at');

        // Unreviewed sync changes for active tab
        $changesQuery = ProductSyncChange::with(['product', 'game', 'category'])->unreviewed();
        if ($activeTab === 'ppob') {
            if ($ppobCategory) {
                $changesQuery->where('category_id', $ppobCategory->id);
            }
        } elseif ($activeTab === 'all') {
            // all
        } elseif ($activeGame) {
            $changesQuery->where('game_id', $activeGame->id);
        }
        $unreviewedChanges = $changesQuery->latest()->get();

        // Counters per game and total
        $unreviewedCountsByGame = ProductSyncChange::unreviewed()
            ->whereNotNull('game_id')
            ->selectRaw('game_id, count(*) as total')
            ->groupBy('game_id')
            ->pluck('total', 'game_id')
            ->toArray();
        $totalUnreviewedCount = ProductSyncChange::unreviewed()->count();

        return view('admin.products.index', compact(
            'products',
            'games',
            'ppobCategory',
            'providers',
            'activeTab',
            'activeGame',
            'presetSubCategories',
            'tabTotalItems',
            'tabActiveItems',
            'tabAvgProfit',
            'lastSyncedAt',
            'unreviewedChanges',
            'unreviewedCountsByGame',
            'totalUnreviewedCount'
        ));
    }

    /**
     * Trigger Real-Time Sync from Provider
     */
    public function syncDigiflazz(DigiflazzSyncService $syncService)
    {
        try {
            $result = $syncService->sync();

            return back()->with('success', $result['message']);
        } catch (\Exception $e) {
            $cleanError = ErrorSanitizer::sanitize($e->getMessage());

            return back()->with('error', 'Gagal menyinkronkan data provider: '.$cleanError);
        }
    }

    /**
     * Review a single sync change and optionally adjust selling price
     */
    public function reviewChange(Request $request, $id)
    {
        $change = ProductSyncChange::with('product')->findOrFail($id);

        if ($request->filled('selling_price') && $change->product) {
            $newSell = (float) $request->selling_price;
            $cost = (float) $change->product->cost_price;
            $change->product->update([
                'selling_price' => $newSell,
                'profit' => $newSell - $cost,
            ]);
        }

        $change->update([
            'is_reviewed' => true,
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Perubahan produk berhasil ditinjau dan harga telah diperbarui!');
    }

    /**
     * Mark all unreviewed changes in current category/game as reviewed
     */
    public function markAllReviewed(Request $request)
    {
        $gameId = $request->get('game_id');
        $categoryId = $request->get('category_id');
        $tab = $request->get('tab');

        $query = ProductSyncChange::unreviewed();
        if ($gameId) {
            $query->where('game_id', $gameId);
        } elseif ($categoryId) {
            $query->where('category_id', $categoryId);
        } elseif ($tab && $tab !== 'all') {
            if ($tab === 'ppob') {
                $cat = Category::where('type', 'ppob')->first();
                if ($cat) {
                    $query->where('category_id', $cat->id);
                }
            } else {
                $game = Game::where('slug', $tab)->first();
                if ($game) {
                    $query->where('game_id', $game->id);
                }
            }
        }

        $count = $query->count();
        $query->update([
            'is_reviewed' => true,
            'reviewed_at' => now(),
        ]);

        return back()->with('success', "Berhasil menandai {$count} perubahan produk sebagai selesai ditinjau.");
    }

    /**
     * Bulk adjust margin across selected game or all products
     */
    public function bulkMargin(Request $request)
    {
        $request->validate([
            'game_id' => 'nullable|exists:games,id',
            'mode' => 'required|in:percent,fixed',
            'value' => 'required|numeric|min:0',
        ]);

        $query = Product::where('status', 'active');
        if ($request->filled('game_id')) {
            $query->where('game_id', $request->game_id);
        }

        $products = $query->get();
        $count = 0;

        foreach ($products as $p) {
            $cost = (float) $p->cost_price;
            if ($cost <= 0) {
                continue;
            }

            if ($request->mode === 'percent') {
                $margin = ($cost * ((float) $request->value / 100));
            } else {
                $margin = (float) $request->value;
            }

            $sell = ceil(($cost + $margin) / 50) * 50;
            $profit = $sell - $cost;

            $p->update([
                'selling_price' => $sell,
                'profit' => $profit,
            ]);

            $count++;
        }

        return back()->with('success', "Berhasil memperbarui margin & harga jual untuk {$count} produk!");
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'game_id' => 'nullable|exists:games,id',
            'category_id' => 'nullable|exists:categories,id',
            'provider_id' => 'nullable|exists:providers,id',
            'sub_category' => 'required|string|max:100',
            'sku' => 'required|string|max:100|unique:products,sku',
            'provider_sku' => 'nullable|string|max:100',
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'badge' => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer',
            'status' => 'required|in:active,inactive',
        ]);

        $cost = (float) $request->cost_price;
        $sell = (float) $request->selling_price;
        $profit = $sell - $cost;

        $categoryId = $request->category_id;
        if (! $categoryId && $request->filled('game_id')) {
            $game = Game::find($request->game_id);
            $categoryId = $game?->category_id;
        }

        $providerId = $request->provider_id;
        if (! $providerId) {
            $provider = Provider::first();
            $providerId = $provider?->id;
        }

        $providerSku = $request->provider_sku ?: 'PRV-'.$request->sku;

        $product = Product::create([
            'category_id' => $categoryId ?? 1,
            'game_id' => $request->game_id,
            'provider_id' => $providerId,
            'name' => $request->name,
            'sku' => strtoupper(trim($request->sku)),
            'provider_sku' => $providerSku,
            'description' => $request->name.' resmi instan.',
            'cost_price' => $cost,
            'selling_price' => $sell,
            'profit' => $profit,
            'badge' => $request->badge,
            'sub_category' => $request->sub_category,
            'sort_order' => (int) ($request->sort_order ?? 0),
            'status' => $request->status,
        ]);

        AuditService::log(
            'create_product',
            Product::class,
            $product->id,
            [],
            $product->toArray(),
            auth()->user()
        );

        return back()->with('success', 'Produk '.$product->name.' ('.$product->sub_category.') berhasil ditambahkan!');
    }

    public function update(Request $request, string $id)
    {
        $product = Product::findOrFail($id);

        $request->validate([
            'name' => 'nullable|string|max:255',
            'sub_category' => 'nullable|string|max:100',
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'profit_percent' => 'nullable|numeric',
            'pricing_mode' => 'nullable|in:fixed,percent',
            'badge' => 'nullable|string|max:50',
            'status' => 'required|in:active,inactive',
            'sort_order' => 'nullable|integer',
        ]);

        $cost = (float) $request->cost_price;

        if ($request->input('pricing_mode') === 'percent' && $request->filled('profit_percent')) {
            $percent = (float) $request->profit_percent;
            $calculatedSell = $cost + ($cost * ($percent / 100));
            $sell = ceil($calculatedSell / 50) * 50;
            $profit = $sell - $cost;
        } else {
            $sell = (float) ($request->selling_price ?? $product->selling_price);
            $profit = $sell - $cost;
        }

        $oldData = $product->toArray();

        $product->update([
            'name' => $request->name ?? $product->name,
            'sub_category' => $request->sub_category ?? $product->sub_category,
            'cost_price' => $cost,
            'selling_price' => $sell,
            'profit' => $profit,
            'badge' => $request->badge,
            'status' => $request->status,
            'sort_order' => (int) ($request->sort_order ?? $product->sort_order),
        ]);

        AuditService::log(
            'update_product',
            Product::class,
            $product->id,
            $oldData,
            $product->toArray(),
            auth()->user()
        );

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Produk '.$product->name.' berhasil diperbarui!',
                'product' => $product,
            ]);
        }

        return back()->with('success', 'Produk '.$product->name.' berhasil diperbarui!');
    }

    /**
     * Batch / Bulk update multiple products at once
     */
    public function batchUpdate(Request $request)
    {
        $request->validate([
            'products' => 'required|array',
            'products.*.id' => 'required|exists:products,id',
            'products.*.name' => 'nullable|string|max:255',
            'products.*.sub_category' => 'nullable|string|max:100',
            'products.*.cost_price' => 'required|numeric|min:0',
            'products.*.selling_price' => 'required|numeric|min:0',
            'products.*.badge' => 'nullable|string|max:50',
            'products.*.status' => 'required|in:active,inactive',
        ]);

        $updatedIds = [];
        $user = auth()->user();

        foreach ($request->input('products') as $item) {
            $product = Product::find($item['id']);
            if (! $product) {
                continue;
            }

            $cost = (float) $item['cost_price'];
            $sell = (float) $item['selling_price'];
            $profit = $sell - $cost;

            $oldData = $product->toArray();

            $product->update([
                'name' => $item['name'] ?? $product->name,
                'sub_category' => $item['sub_category'] ?? $product->sub_category,
                'cost_price' => $cost,
                'selling_price' => $sell,
                'profit' => $profit,
                'badge' => $item['badge'] ?? null,
                'status' => $item['status'] ?? 'active',
            ]);

            AuditService::log(
                'bulk_update_product',
                Product::class,
                $product->id,
                $oldData,
                $product->toArray(),
                $user
            );

            $updatedIds[] = $product->id;
        }

        $count = count($updatedIds);
        $message = "Berhasil menyimpan perubahan untuk {$count} produk secara massal!";

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => $message,
                'updated_count' => $count,
                'updated_ids' => $updatedIds,
            ]);
        }

        return back()->with('success', $message);
    }

    public function destroy(string $id)
    {
        $product = Product::findOrFail($id);
        $name = $product->name;

        $oldData = $product->toArray();
        $product->delete();

        AuditService::log(
            'delete_product',
            Product::class,
            $id,
            $oldData,
            [],
            auth()->user()
        );

        return back()->with('success', 'Produk '.$name.' berhasil dihapus dari katalog!');
    }
}
