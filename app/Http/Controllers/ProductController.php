<?php

namespace App\Http\Controllers;

use App\Enum\MediaType;
use App\Http\Requests\CreateProductRequest;
use App\Models\Category;
use App\Models\Media;
use App\Models\Product;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    protected string $FILE_PATH = 'uploads/products/';
    public function homePage():JsonResponse
    {
        $items = [
            'latest_products' => $this->latestProduct(),
            'most_popular_product' => $this->mostPopularProduct(),
            'most_ordered_product' => $this->mostOrderedProduct(),
            'category_with_its_product'=> $this->categoryWithItsProduct(),
        ];
        return response()->json($items);
    }

    public function index():LengthAwarePaginator
    {
         return Product::inRandomOrder()->paginate(15);
    }

    public function show($ulid)
    {
         return Product::where('ulid', $ulid)->firstOrFail();

    }

    public function latestProduct():LengthAwarePaginator
    {
        return Product::latest()->paginate(15);
    }

    public function mostPopularProduct():LengthAwarePaginator
    {
        return Product::orderBy('visitor','desc')->paginate(15);
    }

    public function mostOrderedProduct():LengthAwarePaginator
    {
        return Product::orderBy('orders','desc')->paginate(15);
    }

    public function productByCategory(Request $request ,$ulid):LengthAwarePaginator
    {
        $category = Category::where('ulid',$ulid)->whereHas('products')->firstOrFail();
        return $category->products()->paginate(15);
    }

    public function categoryWithItsProduct():LengthAwarePaginator
    {
        return Category::where('active',true)->with('products',function ($q){
            return $q->where('active',true)->latest()->take(10)->get();
        })->paginate(15);
    }
    public function store(CreateProductRequest $request):JsonResponse
    {
        $info = $request->validated();
        $product = Product::create([
            'name' => $info['name'],
            'price' => $info['price'],
            'custom_tailoring' => $info['custom_tailoring'],
            'colors' => $this->avoidDuplicate($info['colors']),
            'sizes' => $this->avoidDuplicate($info['sizes']),
            'category_id' => $info['category_id'],
        ]);

        if($request->hasFile('media')){

           $this->storeManyMedia($request, $product);
        }
        return response()->json(['message' =>'تم إنشاء المنتج الجديد بنجاح!','product' => $product->load('media')]);
    }

    public function update(CreateProductRequest $request,$ulid):JsonResponse
    {
        $product = Product::where('ulid',$ulid)->firstOrFail();
        $wanted_media = $request->wanted_media ?? [];
        $info = $request->validated();
        unset($info['wanted_media']);
        $product->update($info);
        if(!empty($wanted_media)){
            $all_media = Media::whereNotIn('id',is_array($wanted_media) ? $wanted_media : [$wanted_media])->get();
            foreach ($all_media as $media){
                Storage::disk('public')->delete($this->FILE_PATH.'/'.$product->id.'/'.$media->type.'/'.$media->path);
            }
            Media::whereIn('id',$all_media->pluck('id')->toArray())->delete();
        }
        if($request->hasFile('media')){
            $this->storeManyMedia($request, $product);
        }
        return response()->json(['message' =>'تم تحديث المنتج بنجاح!','product' => $product->load('media')]);
    }
    private function getType($extension):string
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

        return array_values(array_unique($element));
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

}
