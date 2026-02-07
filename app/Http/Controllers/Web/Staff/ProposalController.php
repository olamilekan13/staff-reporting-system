<?php

namespace App\Http\Controllers\Web\Staff;

use App\Http\Controllers\Controller;
use App\Models\Proposal;
use App\Services\ProposalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Mews\Purifier\Facades\Purifier;

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

        return view('staff.proposals.index', compact('proposals'));
    }

    public function create()
    {
        Gate::authorize('create', Proposal::class);

        return view('staff.proposals.create');
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Proposal::class);

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:10000',
            'file' => 'nullable|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,gif',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $request->only(['title', 'description']);

        if (!empty($data['description'])) {
            $data['description'] = Purifier::clean($data['description']);
        }

        $proposal = $this->proposalService->createProposal(
            Auth::user(),
            $data,
            $request->file('file')
        );

        return redirect()->route('staff.proposals.show', $proposal)
            ->with('success', 'Proposal submitted successfully.');
    }

    public function show(Proposal $proposal)
    {
        Gate::authorize('view', $proposal);

        $proposal->load(['user', 'reviewer', 'comments' => function ($q) {
            $q->parentOnly()->with(['user', 'allReplies'])->latest();
        }]);

        return view('staff.proposals.show', compact('proposal'));
    }

    public function edit(Proposal $proposal)
    {
        Gate::authorize('update', $proposal);

        return view('staff.proposals.edit', compact('proposal'));
    }

    public function update(Request $request, Proposal $proposal)
    {
        Gate::authorize('update', $proposal);

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:10000',
            'file' => 'nullable|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,gif',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $request->only(['title', 'description']);

        if (!empty($data['description'])) {
            $data['description'] = Purifier::clean($data['description']);
        }

        $this->proposalService->updateProposal(
            $proposal,
            $data,
            $request->file('file')
        );

        return redirect()->route('staff.proposals.show', $proposal)
            ->with('success', 'Proposal updated successfully.');
    }

    public function destroy(Proposal $proposal)
    {
        Gate::authorize('delete', $proposal);

        $this->proposalService->deleteProposal($proposal);

        return redirect()->route('staff.proposals.index')
            ->with('success', 'Proposal deleted successfully.');
    }
}
