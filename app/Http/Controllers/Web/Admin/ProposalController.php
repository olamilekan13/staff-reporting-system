<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Proposal;
use App\Services\ProposalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class ProposalController extends Controller
{
    public function __construct(
        protected ProposalService $proposalService
    ) {}

    public function index(Request $request)
    {
        $user = Auth::user();

        $proposals = $this->proposalService->getProposalsForUser($user, [
            'status' => $request->query('status'),
            'search' => $request->query('search'),
        ]);

        return view('admin.proposals.index', compact('proposals'));
    }

    public function show(Proposal $proposal)
    {
        Gate::authorize('view', $proposal);

        $proposal->load(['user', 'reviewer', 'comments' => function ($q) {
            $q->parentOnly()->with(['user', 'allReplies'])->latest();
        }]);

        return view('admin.proposals.show', compact('proposal'));
    }

    public function review(Request $request, Proposal $proposal)
    {
        Gate::authorize('review', $proposal);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:under_review,approved,rejected',
            'admin_notes' => 'nullable|string|max:5000',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $this->proposalService->reviewProposal(
            $proposal,
            Auth::user(),
            $request->input('status'),
            $request->input('admin_notes')
        );

        $action = match ($request->input('status')) {
            'approved' => 'approved',
            'rejected' => 'rejected',
            'under_review' => 'marked as under review',
        };

        return redirect()->route('admin.proposals.show', $proposal)
            ->with('success', "Proposal {$action} successfully.");
    }
}
