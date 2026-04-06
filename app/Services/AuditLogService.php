<?php
namespace App\Services;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
class AuditLogService {
    public function log(string $action, string $modelType, ?int $modelId, array $old=[], array $new=[]): void {
        AuditLog::create(['user_id'=>Auth::id(),'action'=>$action,'model_type'=>$modelType,'model_id'=>$modelId,'old_values'=>$old?:null,'new_values'=>$new?:null,'ip_address'=>request()->ip()]);
    }
}
