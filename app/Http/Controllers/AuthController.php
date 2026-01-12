<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function login(Request $request):JsonResponse
    {
        try{
            $user = User::where('email',$request->email)->first();
            if(!$user || !Hash::check( $request->password , $user->password )){
                return response()->json(['message' => __('message.wrong_email_or_password')],400);
            }

            $token = $user->createToken('user_token',['api-user'])->plainTextToken;

            return response()->json(['message' => __('message.login_successfully'), 'token' => $token]);
        }catch (\Exception $e){
            throw new \Exception(__('message.something_wrong'),500);
        }

    }

    public function resetPasswordUsingOldPassword(Request $request):JsonResponse
    {
        try{
            $user = Auth::user();
            if(!$user || !Hash::check($request->old_password,$user->password)){
                return response()->json(['message' => __('message.wrong_password')], 400);
            }
            $user->password = Hash::make($request->new_password);
            $user->save();
            return response()->json(['message' => __('message.reset_password_successfully')], 200);
        }
        catch(\Exception $e){
            throw new \Exception(__('message.something_wrong'),500);
        }
    }
    public function loginUsingGoogle(Request $request):JsonResponse
    {
        try {
            $googleToken = $request->input('token');

            $googleUser = Socialite::driver('google')->stateless()->userFromToken($googleToken);

            return $this->authUsingGoogle($googleUser);

        }
        catch (\Exception $e) {
            throw new \Exception(__('message.something_wrong'),500);
        }
    }

    private function authUsingGoogle($googleUser):JsonResponse
    {
        try{
            $user = User::where('email', $googleUser->email)->first();

            if (!$user)
            {
                return response()->json(['message' => __('message.user_not_found')], 404);
            }

            else if (!$user->google_id)
            {
                $user->google_id = $googleUser->id;
                $user->save();
            }

            else if ($user->google_id != $googleUser->id)
            {
                return response()->json(['message' => __('message.something_wrong')], 400);
            }

            $token = $user->createToken('user_token', ['api-user'])->plainTextToken;

            return response()->json(['message' => __('message.login_successfully'), 'token' => $token],200);
        }
        catch (\Exception $e){
            throw new \Exception(__('message.something_wrong'),500);
        }
    }

    public function resetPasswordUsingCode(resetPasswordRequest $request)
    {
        $data = $request->validated();
        $info = DB::table('password_reset_tokens')->where('email',$data['email'])->first();
        if(!$info){
            return response()->json(['message' => __('message.invalid_email')], 400);
        }
        if($info->code != $data['code']){
            return response()->json(['message' => __('message.invalid_code')], 400);
        }
        if (Carbon::parse($info->expired_at)->lessThan(Carbon::now())) {
            return response()->json(['message' => __('message.expired_code')], 410);
        }
        $user = User::where('email',$data['email'])->first();
        $user->password = Hash::make($data['password']);
        $user->save();
        DB::table('password_reset_tokens')->where('email',$data['email'])->delete();
        return response()->json(['message' => __('message.reset_password_successfully')], 200);
    }

    public function requestResetPasswordCode(Request $request)
    {
        if($request->email)
        {
            $validated = $request->validate([
                'email' => ['required','string','max:30','min:15','email','exists:users,email'],
            ]);
            $email = $validated['email'];
            $user = User::where('email' , $email)->first();
        }
        else
        {
            $user=Auth::user();
            $email = $user->email;
        }
        try{
            $exist = DB::table('password_reset_tokens')->where('email' , $email)->first();
            $code = (string) rand(100000, 999999);

            DB::transaction(function () use ($email,$code,$exist){
                if($exist){
                    DB::table('password_reset_tokens')
                        ->where('email' , $email)
                        ->update([
                            'code' => $code,
                            'expired_at' => Carbon::now()->addMinutes(5)->format('Y-m-d H:i:s'),
                        ]);
                }
                else{
                    DB::table('password_reset_tokens')->insert([
                        'email' => $email,
                        'code' => $code,
                        'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                        'expired_at' => Carbon::now()->addMinutes(5)->format('Y-m-d H:i:s'),
                    ]);}
            });

            $this->sendVerificationCodeMail($code, $user->name, $user->email);

            return response()->json(['message' => __('message.send_verify_code')], 200);
        }
        catch (\Exception $e) {
            throw new \Exception(__('message.something_wrong'),500);
        }

    }

    private function sendVerificationCodeMail(string $verifyCode, string $name, string $email):void
    {
        try {
            Mail::to($email)->queue(new VerificationCodeMail($verifyCode, $name));
        }

        catch (\Exception $e) {
            throw new \Exception(__('message.something_wrong'),500);
        }

    }

}
