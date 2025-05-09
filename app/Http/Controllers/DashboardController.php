<?php
namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Category;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
        
        // Get all transactions for the current user
        $transactions = Transaction::where('user_id', $user->id)->get();
        
        // Debug: Check if transactions collection is empty
        if ($transactions->isEmpty()) {
            \Log::info('No transactions found for user: ' . $user->id);
        }
            
        // Calculate monthly totals
        $monthlyIncome = $transactions
            ->where('type', 'income')
            ->where('date', '>=', Carbon::now()->startOfMonth())
            ->sum('amount');

        $monthlyExpense = $transactions
            ->where('type', 'expense')
            ->where('date', '>=', Carbon::now()->startOfMonth())
            ->sum('amount');

        $netBalance = $monthlyIncome - $monthlyExpense;
        
        // Get expenses by category for the current month
        $expensesByCategory = DB::table('transactions')
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->where('transactions.user_id', $user->id)
            ->where('transactions.type', 'expense')
            ->whereMonth('transactions.date', $currentMonth)
            ->whereYear('transactions.date', $currentYear)
            ->select('categories.name', DB::raw('SUM(transactions.amount) as total'))
            ->groupBy('categories.name')
            ->get();
            
        // Debug: Log the view data
        \Log::info('Dashboard view data:', [
            'transactions_count' => $transactions->count(),
            'monthly_income' => $monthlyIncome,
            'monthly_expense' => $monthlyExpense,
            'net_balance' => $netBalance,
            'expenses_by_category_count' => $expensesByCategory->count()
        ]);

        return view('dashboard', [
            'transactions' => $transactions,
            'monthlyIncome' => $monthlyIncome,
            'monthlyExpense' => $monthlyExpense,
            'netBalance' => $netBalance,
            'expensesByCategory' => $expensesByCategory
        ]);
    }
}