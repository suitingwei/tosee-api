<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    public function showLoginPage()
    {
        return view('auth.login');
    }

    /**
     * @param Request $request
     */
    public function login(Request $request)
    {
        $this->validate($request, [
            'phone'    => 'required',
            'password' => 'required'
        ]);

        $user = User::where('mobile', $request->input('phone'))->first();

//        if ($user && bcrypt($request->input('password') == $user->password)) {
        if ($user) {
            \Auth::login($user);
            return redirect()->to('/admin/groupshoots/templates');
        }

        return redirect()->back()->with(['login_error' => 'password error']);
    }
}
