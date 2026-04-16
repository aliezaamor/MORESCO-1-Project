<?php

namespace App\Http\Controllers;

use App\Services\MorescoDbService;
use Illuminate\Http\Request;

class InquiryController extends Controller
{
    protected $morescoService;

    public function __construct(MorescoDbService $morescoService)
    {
        $this->morescoService = $morescoService;
    }

    /**
     * Display a listing of the inquiries from MORESCO database.
     */
    public function index(Request $request)
    {
        $page = (int)$request->get('page', 1);
        $search = $request->get('search');
        $status = $request->get('status', 'new');
        $perPage = 50;
        $offset = ($page - 1) * $perPage;

        $inquiries = $this->morescoService->getInquiries($perPage, $offset, $search, $status);
        $total = $this->morescoService->countInquiries($search, $status);

        return view('inquiries.index', [
            'inquiries' => $inquiries,
            'total'     => $total,
            'page'      => $page,
            'perPage'   => $perPage,
            'lastPage'  => ceil($total / $perPage),
            'search'    => $search,
            'status'    => $status
        ]);
    }

    /**
     * Optional: Fetch list of inquiry types for reference.
     */
    public function types()
    {
        $types = $this->morescoService->getInquiryTypes();
        return response()->json($types);
    }

    /**
     * Mark an inquiry as processed.
     */
    public function process(Request $request, $id)
    {
        $userName = auth()->check() ? auth()->user()->name : 'System Admin';
        $success = $this->morescoService->markInquiryProcessed($id, $userName);

        if ($request->wantsJson()) {
            return response()->json(['success' => $success]);
        }

        if ($success) {
            return redirect()->back()->with('success', 'Inquiry marked as processed.');
        } else {
            return redirect()->back()->with('error', 'Failed to update inquiry status.');
        }
    }

    /**
     * Fetch threaded history for an account.
     */
    public function history(Request $request)
    {
        $account = $request->get('account');
        $phone = $request->get('phone');
        $exclude = (int) $request->get('exclude', 0);

        if (!$account && !$phone) {
            return response()->json([]);
        }

        $history = $this->morescoService->getInquiryHistory($account, $phone, $exclude);
        return response()->json($history);
    }
}
