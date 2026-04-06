<?php
namespace App\Http\Controllers;
use App\Models\Receipt; use App\Models\Invoice;
use Inertia\Inertia; use Carbon\Carbon;
class DashboardController extends Controller {
    public function index() {
        $today = Carbon::today();
        $stats = [
            'total_receipts'=>Receipt::count(),'total_invoices'=>Invoice::count(),
            'today_receipts'=>Receipt::whereDate('date',$today)->count(),
            'today_invoices'=>Invoice::whereDate('date',$today)->count(),
            'total_amount_today'=>Receipt::whereDate('date',$today)->sum('total_amount')+Invoice::whereDate('date',$today)->sum('grand_total'),
            'monthly_receipts'=>Receipt::whereMonth('date',$today->month)->whereYear('date',$today->year)->count(),
            'monthly_invoices'=>Invoice::whereMonth('date',$today->month)->whereYear('date',$today->year)->count(),
        ];
        return Inertia::render('Dashboard',compact('stats'));
    }
}
