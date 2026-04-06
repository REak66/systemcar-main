<?php
namespace App\Http\Controllers;
use App\Models\Receipt; use App\Models\Invoice; use App\Services\TelegramService; use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request; use Inertia\Inertia; use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel; use App\Exports\ReportExport; use Barryvdh\DomPDF\Facade\Pdf;
class ReportController extends Controller {
    public function __construct(private TelegramService $telegram) {}
    public function index(Request $request) {
        $type=$request->type??'daily';
        [$start,$end]=$this->range($type,$request);
        $receipts=Receipt::whereBetween('date',[$start,$end])->orderBy('date','desc')->paginate(20,['*'],'receipt_page')->withQueryString();
        $invoices=Invoice::whereBetween('date',[$start,$end])->orderBy('date','desc')->paginate(20,['*'],'invoice_page')->withQueryString();
        $receiptAmount=Receipt::whereBetween('date',[$start,$end])->sum('total_amount');
        $invoiceAmount=Invoice::whereBetween('date',[$start,$end])->sum('grand_total');
        $summary=['total_receipts'=>Receipt::whereBetween('date',[$start,$end])->count(),'total_invoices'=>Invoice::whereBetween('date',[$start,$end])->count(),'receipt_amount'=>$receiptAmount,'invoice_amount'=>$invoiceAmount,'total_amount'=>$receiptAmount+$invoiceAmount,'vat_total'=>Invoice::whereBetween('date',[$start,$end])->sum('vat_amount')];
        return Inertia::render('Reports/Index',compact('receipts','invoices','summary','type','start','end'));
    }
    private function range(string $type, Request $r): array {
        return match($type) {
            'daily'=>[$r->date??Carbon::today()->toDateString(),$r->date??Carbon::today()->toDateString()],
            'weekly'=>[Carbon::now()->startOfWeek()->toDateString(),Carbon::now()->endOfWeek()->toDateString()],
            'monthly'=>[Carbon::now()->startOfMonth()->toDateString(),Carbon::now()->endOfMonth()->toDateString()],
            default=>[$r->from_date??Carbon::today()->toDateString(),$r->to_date??Carbon::today()->toDateString()],
        };
    }
    public function exportExcel(Request $request) {
        $this->telegram->sendReportDownloadAlert('General', Auth::user()?->name ?? 'Unknown', 'Excel');
        return Excel::download(new ReportExport($request->from_date,$request->to_date),'report.xlsx');
    }
    public function exportPdf(Request $request) {
        $start=$request->from_date; $end=$request->to_date;
        $receipts=Receipt::whereBetween('date',[$start,$end])->get();
        $invoices=Invoice::whereBetween('date',[$start,$end])->get();
        $this->telegram->sendReportDownloadAlert('General', Auth::user()?->name ?? 'Unknown', 'PDF');
        return Pdf::loadView('pdf.report',compact('receipts','invoices','start','end'))->download('report.pdf');
    }
}
