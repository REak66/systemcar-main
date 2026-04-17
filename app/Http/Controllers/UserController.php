<?php
namespace App\Http\Controllers;
use App\Models\User; use App\Services\AuditLogService;
use Illuminate\Http\Request; use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
class UserController extends Controller {
    public function __construct(private AuditLogService $audit) {}
    public function index(Request $request) { $perPage = in_array($request->integer('per_page', 10), [10, 25, 50]) ? $request->integer('per_page', 10) : 10; return Inertia::render('Users/Index',['users'=>User::withTrashed()->orderBy('created_at','desc')->paginate($perPage)->withQueryString()]); }
    public function create() { return Inertia::render('Users/Create'); }
    public function store(Request $request) {
        $data = $request->validate(['name'=>'required|string|max:255','username'=>'required|string|max:255|unique:users','email'=>'required|email|unique:users','password'=>'required|string|min:8|confirmed','role'=>'required|in:admin,cashier','is_active'=>'boolean']);
        $data['password'] = Hash::make($data['password']);
        $user = User::create($data);
        $this->audit->log('create','User',$user->id,[],$user->toArray());
        return redirect()->route('users.index')->with('success','User created successfully.');
    }
    public function edit(User $user) { return Inertia::render('Users/Edit',compact('user')); }
    public function update(Request $request, User $user) {
        $data = $request->validate(['name'=>'required|string|max:255','username'=>'required|string|max:255|unique:users,username,'.$user->id,'email'=>'required|email|unique:users,email,'.$user->id,'password'=>'nullable|string|min:8|confirmed','role'=>'required|in:admin,cashier','is_active'=>'boolean']);
        $old = $user->toArray();
        if (empty($data['password'])) unset($data['password']); else $data['password']=Hash::make($data['password']);
        $user->update($data);
        $this->audit->log('edit','User',$user->id,$old,$user->fresh()->toArray());
        return redirect()->route('users.index')->with('success','User updated successfully.');
    }
    public function destroy(User $user) {
        $old = $user->toArray(); $user->delete();
        $this->audit->log('delete','User',$user->id,$old,[]);
        return redirect()->route('users.index')->with('success','User deleted.');
    }
    public function toggleStatus(User $user) {
        $user->update(['is_active'=>!$user->is_active]);
        return back()->with('success','User status updated.');
    }
}
