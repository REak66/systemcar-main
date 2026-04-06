<?php
namespace App\Http\Controllers;
use App\Models\Invoice; use App\Services\DocumentNumberService; use App\Services\AuditLogService; use App\Services\TelegramService;
use Illuminate\Http\Request; use Illuminate\Support\Facades\Auth; use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel; use App\Exports\InvoicesExport; use Barryvdh\DomPDF\Facade\Pdf;
class InvoiceController extends Controller {
    public function __construct(private DocumentNumberService $doc, private AuditLogService $audit, private TelegramService $telegram) {}
    public function index(Request $request) {
        $query = Invoice::with('creator');
        if ($request->from_date) $query->whereDate('date','>=',$request->from_date);
        if ($request->to_date) $query->whereDate('date','<=',$request->to_date);
        if ($request->type) $query->where('invoice_type',$request->type);
        if ($request->search) $query->where(fn($q)=>$q->where('customer_name','like',"%{$request->search}%")->orWhere('invoice_number','like',"%{$request->search}%")->orWhere('car_model','like',"%{$request->search}%"));
        return Inertia::render('Invoices/Index',['invoices'=>$query->orderBy('created_at','desc')->paginate(20)->withQueryString(),'filters'=>$request->only(['from_date','to_date','search','type'])]);
    }
    public function create(Request $request) {
        $type=$request->type??'car_sale';
        return Inertia::render('Invoices/Create',['invoiceNumber'=>$this->doc->generateInvoiceNumber($type),'type'=>$type]);
    }
    public function store(Request $request) {
        $data = $request->validate(['invoice_number'=>'required|string|unique:invoices','invoice_type'=>'required|in:car_sale,service','date'=>'required|date','customer_name'=>'required|string|max:255','customer_address'=>'nullable|string|max:500','customer_phone'=>'nullable|string|max:50','car_model'=>'required|string|max:255','unit_price'=>'required|numeric|min:0','currency'=>'required|in:USD,KHR','quantity'=>'required|integer|min:1','sub_total'=>'required|numeric|min:0','with_vat'=>'boolean','vat_rate'=>'required|numeric|min:0','vat_amount'=>'required|numeric|min:0','grand_total'=>'required|numeric|min:0','payment_category'=>'required|in:booking,full_payment,down_payment,installment,service_payment,other','bank_reference'=>'required|string|max:500','notes'=>'nullable|string']);
        $data['created_by']=Auth::id();
        $invoice=Invoice::create($data);
        $this->audit->log('create','Invoice',$invoice->id,[],$invoice->toArray());
        $this->telegram->sendInvoiceCreatedAlert($invoice);
        return redirect()->route('invoices.index')->with('success','Invoice created successfully.');
    }
    public function show(Invoice $invoice) { $invoice->load('creator'); return Inertia::render('Invoices/Show',compact('invoice')); }
    public function edit(Invoice $invoice) { return Inertia::render('Invoices/Edit',compact('invoice')); }
    public function update(Request $request, Invoice $invoice) {
        $data = $request->validate(['date'=>'required|date','customer_name'=>'required|string|max:255','customer_address'=>'nullable|string|max:500','customer_phone'=>'nullable|string|max:50','car_model'=>'required|string|max:255','unit_price'=>'required|numeric|min:0','currency'=>'required|in:USD,KHR','quantity'=>'required|integer|min:1','sub_total'=>'required|numeric|min:0','with_vat'=>'boolean','vat_rate'=>'required|numeric|min:0','vat_amount'=>'required|numeric|min:0','grand_total'=>'required|numeric|min:0','payment_category'=>'required|in:booking,full_payment,down_payment,installment,service_payment,other','bank_reference'=>'required|string|max:500','notes'=>'nullable|string']);
        $old=$invoice->toArray(); $invoice->update($data);
        $this->audit->log('edit','Invoice',$invoice->id,$old,$invoice->fresh()->toArray());
        $this->telegram->sendInvoiceUpdatedAlert($invoice->fresh());
        return redirect()->route('invoices.index')->with('success','Invoice updated.');
    }
    public function destroy(Invoice $invoice) {
        $old=$invoice->toArray(); $invoice->delete();
        $this->audit->log('delete','Invoice',$invoice->id,$old,[]);
        $this->telegram->sendInvoiceDeletedAlert($old['invoice_number'], $old['customer_name'], $old['car_model']);
        return redirect()->route('invoices.index')->with('success','Invoice deleted.');
    }
    public function printAlert(Invoice $invoice) {
        $this->telegram->sendPrintAlert('Invoice', $invoice->invoice_number, $invoice->customer_name, Auth::user()?->name ?? 'Unknown');
        return response()->json(['ok' => true]);
    }
    public function downloadPdf(Invoice $invoice) {
        $invoice->load('creator');
        $this->telegram->sendInvoiceDownloadAlert($invoice);
        $filename = $invoice->invoice_number . '.pdf';
        return Pdf::loadView('pdf.invoice_single', compact('invoice'))->download($filename);
    }
    public function exportExcel(Request $request) {
        $this->telegram->sendReportDownloadAlert('Invoice', Auth::user()?->name ?? 'Unknown', 'Excel');
        return Excel::download(new InvoicesExport($request->from_date,$request->to_date,$request->type),'invoices.xlsx');
    }
    public function exportPdf(Request $request) {
        $query=Invoice::query();
        if ($request->from_date) $query->whereDate('date','>=',$request->from_date);
        if ($request->to_date) $query->whereDate('date','<=',$request->to_date);
        if ($request->type) $query->where('invoice_type',$request->type);
        $invoices=$query->orderBy('date','desc')->get();
        $this->telegram->sendReportDownloadAlert('Invoice', Auth::user()?->name ?? 'Unknown', 'PDF');
        return Pdf::loadView('pdf.invoices',compact('invoices'))->download('invoices.pdf');
    }
}
