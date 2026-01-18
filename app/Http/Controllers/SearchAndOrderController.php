<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\Scopes\ActiveScope;
use Illuminate\Foundation\Mix;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class SearchAndOrderController extends Controller
{
    public function search(Request $request){
        $search = $request->validate([
            'search' => ['sometimes','string','min:1','max:200'],
            'category' => ['sometimes','string','exists:categories,ulid'],
        ]);
        $category = $search['category'] ?? null;
        $search = array_key_exists('search',$search) ? preg_replace('/\s+/u', ' ', trim($search['search'])) : null;
        $products = Product::tap(function ($q) use ($category){
            return $category ? $q->whereHas('categories', function ($q1) use ($category){
                return $q1->where('ulid', $category);
            }) : $q;
        })->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        })
            ->paginate(15);
        return response()->json(['products' => ProductResource::collection($products)->response()->getData()], 200);
    }
    public function order(OrderRequest $request)
    {
        $old_info = $request->validated();
        $products = $this->prepareForOrder($old_info['data']);



        $info = collect($old_info['data'])->keyBy('product_id');
        $unset = [];
        $isset = [];
        $inc = []; // تجميع ids لزيادة العداد بعد التحقق
        foreach ($products as $product){
            if(!$product->active){
                $unset[] = $info[$product->ulid];
                continue;
            }
            $isset[] = $info[$product->ulid];
            $inc[] = (string)$product->id;
        }
        if(!empty($unset)){
            $key = Str::uuid()->toString();
            Redis::setex($key, 300, json_encode($isset, JSON_UNESCAPED_UNICODE));
            $productsName = $this->prepareForOrder($unset);
            return response()->json(['message' => 'خطأ في الطلب, المنتجات المذكورة غير متاحة حاليا هل تود الطلب بدونها','products' =>implode(',',$productsName->pluck('name')->toArray()),'key' => $key],400);
        }
        foreach ($inc as $id) {
            Redis::hincrby('products:order:inc', $id, 1);
        }
        return response()->json('تم التاكد من صحة طلبك ,يتم ارسال الطلب عبر واتساب');

    }

    public function confirmConflictedOrder(Request $request):JsonResponse
    {
        $key = ($request->validate([
            'key' => ['required','string'],
        ]))['key'];

        if(!Redis::exists($key)){
            return response()->json('الطلب خاطئ, يرجى اعادة الطلب وتاكيده خلال مدة اقصاها 5 دقائق');
        }
        $info = Redis::get($key);
        $info = json_decode($info,true);
        Redis::del($key);
        $products = $this->prepareForOrder($info);

        foreach ($products as $product){
            Redis::hincrby('products:order:inc', (string)$product->id, 1);
        }

        return response()->json('تم التاكد من صحة طلبك ,يتم ارسال الطلب عبر واتساب');

    }

    private function prepareForOrder(array $info)
    {
        $productIds = collect($info)
            ->pluck('product_id')
            ->unique()
            ->toArray();
        return Product::withoutGlobalScope(ActiveScope::class)->whereIn('ulid', $productIds)->get();
    }


}
