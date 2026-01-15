<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    protected static string $FILE_PATH = 'uploads/category/';

    public function index()
    {
        return CategoryResource::collection(Category::tap(function ($q)
        {
            return auth()->guard('api')->check()? $q : $q->whereHas('products');
        })->get());
    }

    public function store(Request $request){
        $newCategory = $this->validateCategory($request);
        $newCategory['name'] = preg_replace('/\s+/u', ' ', trim($newCategory['name'] ));
        $category = Category::create($newCategory);
        if($request->hasFile('image')){
            Storage::disk('public')->deleteDirectory(self::$FILE_PATH."$category->id/images");
            $path = $request->file('image')->storeAs(self::$FILE_PATH."$category->id/images", Str::ulid() . '.' . $request->file('image')->getClientOriginalExtension(),'public');
            $category->image = $path;
            $category->save();
        }
        $category->image = Storage::disk('public')->url($category->image);
        return response()->json(['message'=>'تم إنشاء الصنف الجديد بنجاح!','category' => new CategoryResource($category)]);

    }

    public function update(Request $request,$ulid):JsonResponse
    {
        $category = Category::where('ulid',$ulid)->firstOrFail();
        $info = $this->validateCategory($request,'update');

        if($request->hasFile('image')){
            Storage::disk('public')->deleteDirectory(self::$FILE_PATH."$category->id");
            $path = $request->file('image')->storeAs(self::$FILE_PATH."$category->id/images", Str::ulid() . '.' . $request->file('image')->getClientOriginalExtension(),'public');
            $info['image'] = $path;
        }
        $category->update($info);
        $category->image = Storage::disk('public')->url($category->image);
        return response()->json(['message' => 'تم تحديث الصنف بنجاح!','category' => new CategoryResource($category)]);
    }

    public function changeStatus(Request $request,$ulid):JsonResponse
    {
        $message = ['تم تعديل حالة الصنف بنحاج,لن يظهر الصنف المحدد الى المستخدمين بعد الآن','تم تعديل حالة الصنف بنحاج,سيعود هذا الصنف متاحا للمستخدمين'];
        $category = Category::where('ulid',$ulid)->firstOrFail();
        $category->active = abs($category->active - 1);
        $category->save();
        return response()->json($message[$category->active]);
    }

    private function validateCategory(Request $request, string $type = 'create'):array
    {
        return $request->validate(['name'=>'required|unique:categories,name|max:255|min:3',
            'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);
    }
}
