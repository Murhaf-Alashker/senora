<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index(){
        return response()->json(Setting::first());
    }

    public function update(Request $request){
        $setting = $request->validate([
            'instagram' => 'required|string|max:255',
            'facebook' => 'required|string|max:255',
            'contact_us_email' => 'required|string|max:255|email',
            'whatsapp' => 'required|string|size:13|starts_with:+963|regex:/^\+?[0-9]+$/',
            'wholesale_at' => 'required|integer|max:1000|min:1'
        ]);
        Setting::update($setting);
        return response()->json(['message' => 'Settings updated successfully'],201);
    }
}
