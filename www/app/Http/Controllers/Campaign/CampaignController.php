<?php

declare(strict_types=1);

namespace App\Http\Controllers\Campaign;

use App\Enums\Currency;
use App\Http\Controllers\Controller;
use App\Models\Campaign\Campaign;
use App\Models\Campaign\Category;
use App\Models\Campaign\Tag;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        // TODO: Implement index method
        abort(404);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $categories = Category::where('is_active', true)
            ->orderBy('name')
            ->get();

        $tags = Tag::orderBy('name')->get();

        $currencies = Currency::cases();

        return view('campaigns.create', [
            'categories' => $categories,
            'tags' => $tags,
            'currencies' => $currencies,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): never
    {
        // TODO: Implement store method
        abort(404);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): never
    {
        // TODO: Implement show method
        abort(404);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id): never
    {
        // TODO: Implement edit method
        abort(404);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): never
    {
        // TODO: Implement update method
        abort(404);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): never
    {
        // TODO: Implement destroy method
        abort(404);
    }
}
