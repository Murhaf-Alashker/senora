<?php

namespace App\Http\Controllers;

use App\Enum\MediaType;
use App\Http\Requests\CreateProductRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Models\Media;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    private string $CACHE_VISITOR_KEY = 'products:views:inc';
    private string $CACHE_ORDER_KEY = 'products:order:inc';
    protected string $FILE_PATH = 'uploads/products/';
    public function homePage():JsonResponse
    {
        return response()->json($this->getHomeProduct());
    }

    public function index():AnonymousResourceCollection
    {
         return ProductResource::collection(Product::inRandomOrder()->paginate(15));
    }

    public function show(string $ulid):ProductResource
    {
        $product = Product::where('ulid', $ulid)->firstOrFail();
        if(!auth()->guard('api')->check()){
            Redis::hincrby($this->CACHE_VISITOR_KEY, (string)$product->id, 1);
        }
         return new ProductResource($product);
    }

    public function latestProduct():AnonymousResourceCollection
    {
        return ProductResource::collection(Product::latest()->paginate(15));
    }

    public function mostPopularProduct():AnonymousResourceCollection
    {
        return ProductResource::collection(Product::orderBy('visitor','desc')->paginate(15));
    }

    public function mostOrderedProduct():AnonymousResourceCollection
    {
        return ProductResource::collection(Product::orderBy('orders','desc')->paginate(15));
    }

    public function productByCategory(string $ulid):AnonymousResourceCollection
    {
        $category = Category::where('ulid',$ulid)->whereHas('products')->firstOrFail();
        return ProductResource::collection($category->products()->paginate(15));
    }

    public function categoryWithItsProduct(): AnonymousResourceCollection
    {
        $categories = Category::where('active',true)->with('products',function ($q){
            return $q->where('active',true)->latest()->take(10)->get();
        })->paginate(15);

        return CategoryResource::collection($categories);
    }
    public function store(CreateProductRequest $request): JsonResponse
    {
        $info = $request->validated();
        $product = Product::create([
            'name' => $info['name'],
            'price' => $info['price'],
            'description' => $info['description'],
            'custom_tailoring' => $info['custom_tailoring'],
            'colors' => $this->avoidDuplicate($info['colors']),
            'sizes' => $this->avoidDuplicate($info['sizes']),
        ]);

        $product->categories()->sync($info['categories']);

        if($request->hasFile('media')){

           $this->storeManyMedia($request, $product);
        }
        Cache::forget('homeProduct');
        return response()->json(['message' =>'تم إنشاء المنتج الجديد بنجاح!','product' => new ProductResource($product->refresh()->load('media'))]);
    }

    public function update(CreateProductRequest $request,string $ulid):JsonResponse
    {
        $product = Product::where('ulid',$ulid)->firstOrFail();
        $wanted_media = $request->wanted_media ?? [];
        $info = $request->validated();
        $categories = Category::whereIn('ulid',$info['categories'])->pluck('id')->toArray();
        unset($info['wanted_media']);
        $info['colors'] = $this->avoidDuplicate($info['colors']);
        $info['sizes'] = $this->avoidDuplicate($info['sizes']);
        $product->update($info);
        $product->categories()->sync($categories);
        if(!empty($wanted_media)){
            $all_media = Media::whereNotIn('id',is_array($wanted_media) ? $wanted_media : [$wanted_media])->get();
        }
        else{
            $all_media =$product->media;
        }
        foreach ($all_media as $media){
            Storage::disk('public')->delete($this->FILE_PATH.'/'.$product->id.'/'.$media->type.'/'.$media->path);
        }
        Media::whereIn('id',$all_media->pluck('id')->toArray())->delete();

        if($request->hasFile('media')){
            $this->storeManyMedia($request, $product);
        }
        Cache::forget('homeProduct');
        return response()->json(['message' =>'تم تحديث المنتج بنجاح!','product' => new ProductResource($product->load('media'))]);
    }
    private function getType(string $extension):string
    {
        if(in_array($extension,MediaType::images())){
            return 'images';
        }
        return 'videos';
    }

    private function avoidDuplicate(array $element):array
    {
        $element = array_map(
            fn ($v) => preg_replace('/\s+/u', ' ', trim($v)),
            $element
        );
        $element = array_values(array_unique($element));
        sort($element);

        return $element;
    }

    public function changeStatus(Request $request,$ulid):JsonResponse
    {
        $message = ['تم تعديل حالة المنتج بنحاج,لن يظهر المنتج المحدد الى المستخدمين بعد الآن','تم تعديل حالة المنتج بنحاج,سيعود هذا المنتج متاحا للمستخدمين'];
        $product = Product::where('ulid',$ulid)->firstOrFail();
        $product->active = abs($product->active - 1);
        $product->save();
        Cache::forget('homeProduct');
        return response()->json($message[$product->active]);
    }

    private function storeManyMedia(Request $request,Product $product):void
    {
        $all_media = [];
        foreach($request->file('media') as $image){
            $type = $this->getType($image->getClientOriginalExtension());
            $path = Str::ulid() . '.' . $image->getClientOriginalExtension();
            $image->storeAs($this->FILE_PATH.'/'.$product->id.'/'.$type, $path, 'public');
            $all_media[] = ['type'=>$type,'path'=>$path];
        }
        $product->media()->createMany($all_media);
    }


    private function getHomeProduct(): array
    {

        $key = 'homeProduct';

        // 1️⃣ إذا موجود رجّعه
        $cached = Cache::get($key);
        if ($cached !== null) {
            return $cached;
        }

        // 2️⃣ Lock لإعادة البناء + التفريغ
        $this->restoreCache($key);
        return Cache::get($key);
    }

    private function restoreAllCache():void
    {
        $this->restoreCache();
    }

    private function restoreCache($key = 'homeProduct'):void
    {
        Cache::lock('homeProduct:rebuild', 20)->block(5, function () use ($key) {

            // 3️⃣ فلّش عدادات المنتجات من Redis → DB
            $this->flushAllProductViewsToDb($this->CACHE_VISITOR_KEY);
            $this->flushAllProductViewsToDb($this->CACHE_ORDER_KEY,'orders');

            // 4️⃣ ابنِ الكاش
            Cache::forget($key);
            return Cache::remember($key, 3600, function () {
                return [
                    'latest_products' => $this->latestProduct()->response()->getData(true),
                    'most_popular_product' => $this->mostPopularProduct()->response()->getData(true),
                    'most_ordered_product' => $this->mostOrderedProduct()->response()->getData(true),
                    'category_with_its_product'=> $this->categoryWithItsProduct()->response()->getData(true),
                ];
            });
        });
    }

    private function flushAllProductViewsToDb(string $src, string $column = 'visitor'): void
    {
        $tmp = $src.':flushing';

        // إذا ما في شي، اطلع
        if (!Redis::exists($src)) return;

        // انقل كل العدادات لاسم مؤقت (عملية واحدة)
        // هيك أي زيادات جديدة رح تروح للـ src بعد ما نعمله فاضي
        Redis::rename($src, $tmp);
        $all = Redis::hgetall($tmp); // [productId => count]

        if (empty($all)) {
            return;
        }

        foreach ($all as $productId => $count) {
            $count = (int) $count;
            if ($count <= 0) continue;

            DB::table('products')
                ->where('id', (int)$productId)
                ->increment($column, $count);

            Redis::hdel('products:views:inc', (string)$productId);
        }
    }


}
