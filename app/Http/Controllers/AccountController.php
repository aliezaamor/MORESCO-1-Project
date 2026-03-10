<?php

namespace App\Http\Controllers;

use App\Services\MorescoDbService;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    /**
     * Display the Accounts Master List page.
     */
    public function index()
    {
        return view('accounts.index');
    }

    /**
     * Provide JSON data for the DataTables grid.
     */
    public function data(Request $request, MorescoDbService $service)
    {
        $search  = $request->get('search');
        $perPage = (int) $request->get('per_page', 100);
        $offset  = (int) $request->get('offset', 0);

        return response()->json([
            'data'  => $service->getAccounts($search, $perPage, $offset),
            'total' => $service->countAccounts($search),
        ]);
    }

    /**
     * Provide live billing and outage JSON data for a specific member's accounts.
     */
    public function show(string $memberId, MorescoDbService $service)
    {
        $member = $service->getMemberById($memberId);
        if (!$member) {
            return response()->json(['error' => 'Member not found'], 404);
        }

        // Find all accounts belonging to this member
        $pdo = $service->getConnection();
        $stmt = $pdo->prepare("SELECT account_no FROM dbo.account WHERE member_id = ? ORDER BY account_no ASC");
        $stmt->execute([$memberId]);
        $accountsRows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $accountsData = [];
        foreach ($accountsRows as $row) {
            $accNo = $row['account_no'];
            $accountsData[] = [
                'account_no' => $accNo,
                'billing'    => $service->getMemberBillingData($accNo),
                'outage'     => $service->getMemberOutageData($accNo, $member['sa_code'] ?? null)
            ];
        }
        
        return response()->json([
            'member'   => $member,
            'accounts' => $accountsData
        ]);
    }
}
