<?php
namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
class LoginController extends Controller {
    public function showLogin() { return Inertia::render('Auth/Login'); }
    public function login(Request $request) {
        $data = $request->validate(['username'=>'required|string','password'=>'required|string']);
        $field = filter_var($data['username'],FILTER_VALIDATE_EMAIL)?'email':'username';
        if (!Auth::attempt([$field=>$data['username'],'password'=>$data['password']],$request->boolean('remember')))
            return back()->withErrors(['username'=>__('auth.failed')])->onlyInput('username');
        $user = Auth::user();
        if (!$user->is_active) { Auth::logout(); return back()->withErrors(['username'=>'Your account is deactivated.'])->onlyInput('username'); }
        $request->session()->regenerate();
        return redirect()->intended(route('dashboard'));
    }
    public function logout(Request $request) {
        Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
