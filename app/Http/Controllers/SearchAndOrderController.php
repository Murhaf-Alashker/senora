<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class SearchAndOrderController extends Controller
{
    public function search(Request $request){
        $search = $request->validate([
            'search' => ['required','string','min:1','max:200'],
            'category' => ['sometimes','string','exists:categories,ulid'],
        ]);
        $category = $search['category'] ?? null;
        $search = preg_replace('/\s+/u', ' ', trim($search['search']));
        $products = Product::tap(function ($q) use ($category){
            return $category ? $q->whereHas('category', function ($q1) use ($category){
                return $q1->where('ulid', $category);
            }) : $q;
        })->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        })
            ->paginate(5);
        return response()->json(['products' => ProductResource::collection($products)], 200);
    }

    public function order(Request $request){}
}
