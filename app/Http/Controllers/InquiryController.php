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
        $perPage = 50;
        $offset = ($page - 1) * $perPage;

        $inquiries = $this->morescoService->getInquiries($perPage, $offset);
        $total = $this->morescoService->countInquiries();

        return view('inquiries.index', [
            'inquiries' => $inquiries,
            'total'     => $total,
            'page'      => $page,
            'perPage'   => $perPage,
            'lastPage'  => ceil($total / $perPage)
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
}
