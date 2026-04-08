<?php
namespace App\Http\Controllers;
use App\Models\Receipt; use App\Services\DocumentNumberService; use App\Services\AuditLogService; use App\Services\TelegramService;
use Illuminate\Http\Request; use Illuminate\Support\Facades\Auth; use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel; use App\Exports\ReceiptsExport; use App\Exports\ReceiptTemplateExport; use App\Imports\ReceiptsImport; use Spatie\Browsershot\Browsershot;
class ReceiptController extends Controller {
    public function __construct(private DocumentNumberService $doc, private AuditLogService $audit, private TelegramService $telegram) {}
    private function embedFonts(string $html): string { return preg_replace_callback("/url\\(['\"]?file:\\/\\/([^'\"\\)]+\\.ttf)['\"]?\\)/i", function ($m) { return file_exists($m[1]) ? "url('data:font/truetype;base64," . base64_encode(file_get_contents($m[1])) . "')" : $m[0]; }, $html); }
    public function index(Request $request) {
        $query = Receipt::with('creator');
        if ($request->from_date) $query->whereDate('date','>=',$request->from_date);
        if ($request->to_date) $query->whereDate('date','<=',$request->to_date);
        if ($request->search) $query->where(fn($q)=>$q->where('customer_name','like',"%{$request->search}%")->orWhere('receipt_number','like',"%{$request->search}%")->orWhere('car_model','like',"%{$request->search}%"));
        return Inertia::render('Receipts/Index',['receipts'=>$query->orderBy('created_at','desc')->paginate(20)->withQueryString(),'filters'=>$request->only(['from_date','to_date','search'])]);
    }
    public function create() { return Inertia::render('Receipts/Create',['receiptNumber'=>$this->doc->generateReceiptNumber()]); }
    public function store(Request $request) {
        $data = $request->validate(['receipt_number'=>'required|string|unique:receipts','date'=>'required|date','customer_name'=>'required|string|max:255','customer_phone'=>'nullable|string|max:50','car_model'=>'required|string|max:255','unit_price'=>'required|numeric|min:0','currency'=>'required|in:USD,KHR','quantity'=>'required|integer|min:1','total_amount'=>'required|numeric|min:0','payment_category'=>'required|in:booking,full_payment,down_payment,installment,service_payment,other','bank_reference'=>'required|string|max:500','notes'=>'nullable|string']);
        $data['created_by']=Auth::id();
        $receipt = Receipt::create($data);
        $this->audit->log('create','Receipt',$receipt->id,[],$receipt->toArray());
        $this->telegram->sendReceiptCreatedAlert($receipt);
        return redirect()->route('receipts.index')->with('success','Receipt created successfully.');
    }
    public function show(Receipt $receipt) { $receipt->load('creator'); return Inertia::render('Receipts/Show',compact('receipt')); }
    public function edit(Receipt $receipt) { return Inertia::render('Receipts/Edit',compact('receipt')); }
    public function update(Request $request, Receipt $receipt) {
        $data = $request->validate(['date'=>'required|date','customer_name'=>'required|string|max:255','customer_phone'=>'nullable|string|max:50','car_model'=>'required|string|max:255','unit_price'=>'required|numeric|min:0','currency'=>'required|in:USD,KHR','quantity'=>'required|integer|min:1','total_amount'=>'required|numeric|min:0','payment_category'=>'required|in:booking,full_payment,down_payment,installment,service_payment,other','bank_reference'=>'required|string|max:500','notes'=>'nullable|string']);
        $old=$receipt->toArray(); $receipt->update($data);
        $this->audit->log('edit','Receipt',$receipt->id,$old,$receipt->fresh()->toArray());
        $this->telegram->sendReceiptUpdatedAlert($receipt->fresh());
        return redirect()->route('receipts.index')->with('success','Receipt updated.');
    }
    public function destroy(Receipt $receipt) {
        $old=$receipt->toArray(); $receipt->delete();
        $this->audit->log('delete','Receipt',$receipt->id,$old,[]);
        $this->telegram->sendReceiptDeletedAlert($old['receipt_number'], $old['customer_name'], $old['car_model']);
        return redirect()->route('receipts.index')->with('success','Receipt deleted.');
    }
    public function printAlert(Receipt $receipt) {
        $this->telegram->sendPrintAlert('Receipt', $receipt->receipt_number, $receipt->customer_name, Auth::user()?->name ?? 'Unknown');
        return response()->json(['ok' => true]);
    }
    public function exportExcel(Request $request) {
        $this->telegram->sendReportDownloadAlert('Receipt', Auth::user()?->name ?? 'Unknown', 'Excel');
        return Excel::download(new ReceiptsExport($request->from_date,$request->to_date),'receipts.xlsx');
    }
    public function exportTemplate(Receipt $receipt) {
        $receipt->load('creator');
        $this->telegram->sendReceiptDownloadAlert($receipt);
        $filename = $receipt->receipt_number . '.xlsx';
        return Excel::download(new ReceiptTemplateExport($receipt), $filename);
    }
    public function importExcel(Request $request) {
        $request->validate(['files' => 'required|array|min:1', 'files.*' => 'file|max:10240']);
        $importer = new ReceiptsImport();
        $allResults = [];
        foreach ($request->file('files') as $file) {
            $allResults = array_merge($allResults, $importer->import($file));
        }
        $succeeded = collect($allResults)->where('success', true)->count();
        $failed    = collect($allResults)->where('success', false)->count();
        $errors    = collect($allResults)->where('success', false)->pluck('error')->values()->toArray();
        if ($succeeded > 0) {
            $this->audit->log('import', 'Receipt', null, [], ['imported' => $succeeded]);
            $this->telegram->sendImportAlert('Receipt', $succeeded, $failed, Auth::user()?->name ?? 'Unknown');
        }
        return back()->with([
            'import_success' => $succeeded,
            'import_failed'  => $failed,
            'import_errors'  => $errors,
        ]);
    }
    public function previewImport(Request $request) {
        $request->validate(['files' => 'required|array|min:1', 'files.*' => 'file|max:10240']);
        $importer = new ReceiptsImport();
        $rows = [];
        foreach ($request->file('files') as $file) {
            $rows = array_merge($rows, $importer->preview($file));
        }
        return response()->json($rows);
    }
    public function exportPdf(Request $request) {
        $query=Receipt::query();
        if ($request->from_date) $query->whereDate('date','>=',$request->from_date);
        if ($request->to_date) $query->whereDate('date','<=',$request->to_date);
        $receipts=$query->orderBy('date','desc')->get();
        $this->telegram->sendReportDownloadAlert('Receipt', Auth::user()?->name ?? 'Unknown', 'PDF');
        $html = view('pdf.receipts', compact('receipts'))->render();
        $html = $this->embedFonts($html);
        $pdf = Browsershot::html($html)->setNodeBinary(trim(shell_exec('which node')))->setNpmBinary(trim(shell_exec('which npm')))->format('A4')->margins(15,15,15,15)->showBackground()->waitUntilNetworkIdle()->pdf();
        return response($pdf, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="receipts.pdf"']);
    }
}
