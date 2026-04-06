<?php
namespace App\Http\Controllers;
use App\Models\AuditLog; use Illuminate\Http\Request; use Inertia\Inertia;
class AuditLogController extends Controller {
    public function index(Request $request) {
        $logs=AuditLog::with('user')
            ->when($request->action,fn($q)=>$q->where('action',$request->action))
            ->when($request->from_date,fn($q)=>$q->whereDate('created_at','>=',$request->from_date))
            ->when($request->to_date,fn($q)=>$q->whereDate('created_at','<=',$request->to_date))
            ->orderBy('created_at','desc')->paginate(50)->withQueryString();
        return Inertia::render('AuditLogs/Index',compact('logs'));
    }
}
