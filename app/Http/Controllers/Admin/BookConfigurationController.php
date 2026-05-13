<?php

namespace App\Http\Controllers\Admin;

use App\Models\BookInventory;
use App\Models\RefBook;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class BookConfigurationController extends Controller
{
    public function index(Request $request): View
    {
        $bookPerPage = (int) $request->integer('books_per_page', 10);
        if (! in_array($bookPerPage, [5, 10, 15, 25, 50], true)) {
            $bookPerPage = 10;
        }

        $inventoryPerPage = (int) $request->integer('inventory_per_page', 10);
        if (! in_array($inventoryPerPage, [5, 10, 15, 25, 50], true)) {
            $inventoryPerPage = 10;
        }

        $bookSearch = trim($request->string('books_search')->toString());
        $bookStatus = $request->string('books_status')->toString();
        $bookGradeLevel = $request->string('books_grade_level')->toString();
        $bookSubjectId = $request->integer('books_subject_ID');

        $inventorySearch = trim($request->string('inventory_search')->toString());
        $inventoryRecordStatus = $request->string('inventory_record_status')->toString();
        $inventoryAvailability = $request->string('inventory_availability')->toString();
        $inventoryCondition = $request->string('inventory_condition')->toString();
        $inventoryBookId = $request->integer('inventory_book_ID');

        $books = RefBook::query()
            ->with('subject')
            ->when($bookSearch !== '', function ($query) use ($bookSearch): void {
                $query->where(function ($inner) use ($bookSearch): void {
                    $inner->where('book_code', 'like', "%{$bookSearch}%")
                        ->orWhere('title', 'like', "%{$bookSearch}%")
                        ->orWhere('author', 'like', "%{$bookSearch}%");
                });
            })
            ->when(in_array($bookStatus, ['active', 'archived'], true), function ($query) use ($bookStatus): void {
                $query->where('status', $bookStatus);
            })
            ->when(in_array($bookGradeLevel, ['grade_7', 'grade_8', 'grade_9', 'grade_10', 'grade_11', 'grade_12'], true), function ($query) use ($bookGradeLevel): void {
                $query->where('grade_level', $bookGradeLevel);
            })
            ->when($bookSubjectId > 0, function ($query) use ($bookSubjectId): void {
                $query->where('subject_ID', $bookSubjectId);
            })
            ->orderBy('status')
            ->orderByDesc('book_ID')
            ->paginate($bookPerPage, ['*'], 'books_page')
            ->withQueryString();

        $inventories = BookInventory::query()
            ->with('book')
            ->when($inventorySearch !== '', function ($query) use ($inventorySearch): void {
                $query->where('property_no', 'like', "%{$inventorySearch}%");
            })
            ->when(in_array($inventoryRecordStatus, ['active', 'archived'], true), function ($query) use ($inventoryRecordStatus): void {
                $query->where('record_status', $inventoryRecordStatus);
            })
            ->when(in_array($inventoryAvailability, ['available', 'issued', 'lost'], true), function ($query) use ($inventoryAvailability): void {
                $query->where('status', $inventoryAvailability);
            })
            ->when(in_array($inventoryCondition, ['good', 'fair', 'damaged'], true), function ($query) use ($inventoryCondition): void {
                $query->where('condition', $inventoryCondition);
            })
            ->when($inventoryBookId > 0, function ($query) use ($inventoryBookId): void {
                $query->where('book_ID', $inventoryBookId);
            })
            ->orderBy('record_status')
            ->orderByDesc('inventory_ID')
            ->paginate($inventoryPerPage, ['*'], 'inventory_page')
            ->withQueryString();

        $subjects = Subject::query()
            ->where('status', 'active')
            ->orderBy('title')
            ->get(['subject_ID', 'code', 'title']);

        $activeBooks = RefBook::query()
            ->where('status', 'active')
            ->orderBy('title')
            ->get(['book_ID', 'book_code', 'title']);

        return view('users.admin.book-config', [
            'books' => $books,
            'inventories' => $inventories,
            'subjects' => $subjects,
            'activeBooks' => $activeBooks,
            'bookPerPage' => $bookPerPage,
            'inventoryPerPage' => $inventoryPerPage,
        ]);
    }

    public function storeBook(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subject_ID' => ['required', 'integer', Rule::exists('subjects', 'subject_ID')->where('status', 'active')],
            'book_code' => ['required', 'string', 'max:255', 'unique:ref_books,book_code'],
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'grade_level' => ['required', Rule::in(['grade_7', 'grade_8', 'grade_9', 'grade_10', 'grade_11', 'grade_12'])],
        ]);

        RefBook::query()->create($validated);

        return back()->with('success', 'Reference book created successfully.');
    }

    public function updateBook(Request $request, RefBook $book): RedirectResponse
    {
        $validated = $request->validate([
            'subject_ID' => ['required', 'integer', Rule::exists('subjects', 'subject_ID')->where('status', 'active')],
            'book_code' => ['required', 'string', 'max:255', Rule::unique('ref_books', 'book_code')->ignore($book->book_ID, 'book_ID')],
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'grade_level' => ['required', Rule::in(['grade_7', 'grade_8', 'grade_9', 'grade_10', 'grade_11', 'grade_12'])],
        ]);

        $book->update($validated);

        return back()->with('success', 'Reference book updated successfully.');
    }

    public function destroyBook(RefBook $book): RedirectResponse
    {
        $nextStatus = $book->status === 'archived' ? 'active' : 'archived';
        $book->update(['status' => $nextStatus]);

        return back()->with('success', $nextStatus === 'archived'
            ? 'Reference book archived successfully.'
            : 'Reference book restored successfully.');
    }

    public function storeInventory(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'book_ID' => ['required', 'integer', Rule::exists('ref_books', 'book_ID')->where('status', 'active')],
            'property_no' => ['required', 'string', 'max:255', 'unique:book_inventory,property_no'],
            'condition' => ['required', Rule::in(['good', 'fair', 'damaged'])],
            'status' => ['required', Rule::in(['available', 'issued', 'lost'])],
        ]);

        BookInventory::query()->create($validated);

        return back()->with('success', 'Book inventory item created successfully.');
    }

    public function updateInventory(Request $request, BookInventory $inventory): RedirectResponse
    {
        $validated = $request->validate([
            'book_ID' => ['required', 'integer', Rule::exists('ref_books', 'book_ID')->where('status', 'active')],
            'property_no' => ['required', 'string', 'max:255', Rule::unique('book_inventory', 'property_no')->ignore($inventory->inventory_ID, 'inventory_ID')],
            'condition' => ['required', Rule::in(['good', 'fair', 'damaged'])],
            'status' => ['required', Rule::in(['available', 'issued', 'lost'])],
        ]);

        $inventory->update($validated);

        return back()->with('success', 'Book inventory item updated successfully.');
    }

    public function destroyInventory(BookInventory $inventory): RedirectResponse
    {
        $nextStatus = $inventory->record_status === 'archived' ? 'active' : 'archived';
        $inventory->update(['record_status' => $nextStatus]);

        return back()->with('success', $nextStatus === 'archived'
            ? 'Book inventory item archived successfully.'
            : 'Book inventory item restored successfully.');
    }
}
