<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDocumentReturnReasonRequest;
use App\Http\Requests\Admin\UpdateDocumentReturnReasonRequest;
use App\Models\DocumentReturnReason;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DocumentReturnReasonConfigurationController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = (int) $request->integer('per_page', 10);
        if (! in_array($perPage, [5, 10, 15, 25, 50], true)) {
            $perPage = 10;
        }

        $search = trim($request->string('search')->toString());

        $returnReasons = DocumentReturnReason::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return view('users.admin.document-return-reason-config', [
            'returnReasons' => $returnReasons,
            'perPage' => $perPage,
        ]);
    }

    public function store(StoreDocumentReturnReasonRequest $request): RedirectResponse
    {
        DocumentReturnReason::query()->create($request->validated());

        return back()->with('success', 'Document return reason created successfully.');
    }

    public function update(UpdateDocumentReturnReasonRequest $request, DocumentReturnReason $documentReturnReason): RedirectResponse
    {
        $documentReturnReason->update($request->validated());

        return back()->with('success', 'Document return reason updated successfully.');
    }

    public function destroy(DocumentReturnReason $documentReturnReason): RedirectResponse
    {
        if ($documentReturnReason->studentDocuments()->exists()) {
            return back()->withErrors([
                'error' => 'This return reason is already used on student documents and cannot be deleted.',
            ]);
        }

        $documentReturnReason->delete();

        return back()->with('success', 'Document return reason deleted successfully.');
    }
}
