@extends('users.admin.layout')

@section('title', 'Book Configuration')

@section('content')
<div class="space-y-6">
    <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-100 p-6">
        <h1 class="text-2xl font-bold text-gray-800">Book Configuration</h1>
        <p class="text-sm text-gray-500 mt-1">Manage reference books and inventory records.</p>
    </div>

    @if (session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
        <div class="border-b border-gray-200 bg-gray-50/50 px-4 py-2">
            <nav class="flex gap-2">
                <button type="button" id="booksTabBtn" onclick="switchBookConfigTab('books')" class="px-4 py-2 text-sm font-semibold rounded-lg transition-colors bg-[#296374] text-white">Ref Books</button>
                <button type="button" id="inventoryTabBtn" onclick="switchBookConfigTab('inventory')" class="px-4 py-2 text-sm font-semibold rounded-lg transition-colors bg-white text-gray-600 hover:bg-gray-100">Book Inventory</button>
            </nav>
        </div>

        <div id="booksTabPanel">
            <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-[#296374]/10 to-transparent flex items-center justify-between">
                <h2 class="text-base font-bold text-[#296374] uppercase tracking-wider">Ref Books</h2>
                <button type="button" onclick="openBookModal()" class="inline-flex items-center px-3 py-2 bg-[#296374] text-white text-sm rounded-lg hover:bg-[#1e4a57] transition-colors">Add Ref Book</button>
            </div>

            <div class="p-6 border-b border-gray-200 bg-gray-50/50">
                <form method="GET" action="{{ route('admin.book-config.index') }}" class="flex flex-wrap items-end gap-4">
                    <input type="hidden" name="tab" value="books">
                    <input type="hidden" name="inventory_search" value="{{ request('inventory_search') }}">
                    <input type="hidden" name="inventory_record_status" value="{{ request('inventory_record_status') }}">
                    <input type="hidden" name="inventory_availability" value="{{ request('inventory_availability') }}">
                    <input type="hidden" name="inventory_condition" value="{{ request('inventory_condition') }}">
                    <input type="hidden" name="inventory_book_ID" value="{{ request('inventory_book_ID') }}">
                    <input type="hidden" name="inventory_per_page" value="{{ request('inventory_per_page', $inventoryPerPage ?? 10) }}">

                    <div class="min-w-[220px]">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Search</label>
                        <input type="text" name="books_search" value="{{ request('books_search') }}" placeholder="Code, title, or author" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div class="w-44">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Subject</label>
                        <select name="books_subject_ID" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="">All Subjects</option>
                            @foreach ($subjects as $subject)
                                <option value="{{ $subject->subject_ID }}" {{ (int) request('books_subject_ID') === (int) $subject->subject_ID ? 'selected' : '' }}>{{ $subject->code }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-40">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Grade Level</label>
                        <select name="books_grade_level" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="">All</option>
                            <option value="grade_7" {{ request('books_grade_level') === 'grade_7' ? 'selected' : '' }}>Grade 7</option>
                            <option value="grade_8" {{ request('books_grade_level') === 'grade_8' ? 'selected' : '' }}>Grade 8</option>
                            <option value="grade_9" {{ request('books_grade_level') === 'grade_9' ? 'selected' : '' }}>Grade 9</option>
                            <option value="grade_10" {{ request('books_grade_level') === 'grade_10' ? 'selected' : '' }}>Grade 10</option>
                            <option value="grade_11" {{ request('books_grade_level') === 'grade_11' ? 'selected' : '' }}>Grade 11</option>
                            <option value="grade_12" {{ request('books_grade_level') === 'grade_12' ? 'selected' : '' }}>Grade 12</option>
                        </select>
                    </div>
                    <div class="w-40">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Status</label>
                        <select name="books_status" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="">All</option>
                            <option value="active" {{ request('books_status') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="archived" {{ request('books_status') === 'archived' ? 'selected' : '' }}>Archived</option>
                        </select>
                    </div>
                    <div class="w-32">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Per Page</label>
                        <select name="books_per_page" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            @foreach([5, 10, 15, 25, 50] as $size)
                                <option value="{{ $size }}" {{ (int) ($bookPerPage ?? 10) === $size ? 'selected' : '' }}>{{ $size }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="px-5 py-2 bg-[#296374] text-white rounded-lg hover:bg-[#1e4a57]">Filter</button>
                    @if(request()->hasAny(['books_search', 'books_subject_ID', 'books_grade_level', 'books_status', 'books_per_page']))
                        <a href="{{ route('admin.book-config.index', ['tab' => 'books']) }}" class="px-5 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">Clear</a>
                    @endif
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            <th class="px-6 py-4">Subject</th>
                            <th class="px-6 py-4">Book Code</th>
                            <th class="px-6 py-4">Title</th>
                            <th class="px-6 py-4">Author</th>
                            <th class="px-6 py-4">Grade Level</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($books as $book)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 text-gray-600">{{ optional($book->subject)->title ?? 'N/A' }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ $book->book_code }}</td>
                                <td class="px-6 py-4 text-gray-800 font-semibold">{{ $book->title }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ $book->author }}</td>
                                <td class="px-6 py-4 text-gray-600 uppercase">{{ str_replace('_', ' ', $book->grade_level) }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $book->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">
                                        {{ ucfirst($book->status ?? 'active') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <button type="button" onclick='openBookModal(@json($book))' class="px-3 py-1 text-xs rounded bg-blue-100 text-blue-700 hover:bg-blue-200">Edit</button>
                                        <form action="{{ route('admin.book-config.books.delete', $book) }}" method="POST" class="inline" onsubmit="return confirm('{{ $book->status === 'active' ? 'Archive this reference book?' : 'Restore this reference book?' }}');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-3 py-1 text-xs rounded {{ $book->status === 'active' ? 'bg-amber-100 text-amber-700 hover:bg-amber-200' : 'bg-green-100 text-green-700 hover:bg-green-200' }}">
                                                {{ $book->status === 'active' ? 'Archive' : 'Restore' }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-gray-500">No ref books yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($books->hasPages())
                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                    {{ $books->appends(['tab' => 'books'])->withQueryString()->links() }}
                </div>
            @endif
        </div>

        <div id="inventoryTabPanel" class="hidden">
            <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-[#296374]/10 to-transparent flex items-center justify-between">
                <h2 class="text-base font-bold text-[#296374] uppercase tracking-wider">Book Inventory</h2>
                <button type="button" onclick="openInventoryModal()" class="inline-flex items-center px-3 py-2 bg-[#296374] text-white text-sm rounded-lg hover:bg-[#1e4a57] transition-colors">Add Inventory Item</button>
            </div>

            <div class="p-6 border-b border-gray-200 bg-gray-50/50">
                <form method="GET" action="{{ route('admin.book-config.index') }}" class="flex flex-wrap items-end gap-4">
                    <input type="hidden" name="tab" value="inventory">
                    <input type="hidden" name="books_search" value="{{ request('books_search') }}">
                    <input type="hidden" name="books_subject_ID" value="{{ request('books_subject_ID') }}">
                    <input type="hidden" name="books_grade_level" value="{{ request('books_grade_level') }}">
                    <input type="hidden" name="books_status" value="{{ request('books_status') }}">
                    <input type="hidden" name="books_per_page" value="{{ request('books_per_page', $bookPerPage ?? 10) }}">

                    <div class="min-w-[220px]">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Search</label>
                        <input type="text" name="inventory_search" value="{{ request('inventory_search') }}" placeholder="Property number" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div class="w-44">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Book</label>
                        <select name="inventory_book_ID" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="">All Books</option>
                            @foreach ($activeBooks as $book)
                                <option value="{{ $book->book_ID }}" {{ (int) request('inventory_book_ID') === (int) $book->book_ID ? 'selected' : '' }}>{{ $book->book_code }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-40">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Condition</label>
                        <select name="inventory_condition" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="">All</option>
                            <option value="good" {{ request('inventory_condition') === 'good' ? 'selected' : '' }}>Good</option>
                            <option value="fair" {{ request('inventory_condition') === 'fair' ? 'selected' : '' }}>Fair</option>
                            <option value="damaged" {{ request('inventory_condition') === 'damaged' ? 'selected' : '' }}>Damaged</option>
                        </select>
                    </div>
                    <div class="w-40">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Availability</label>
                        <select name="inventory_availability" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="">All</option>
                            <option value="available" {{ request('inventory_availability') === 'available' ? 'selected' : '' }}>Available</option>
                            <option value="issued" {{ request('inventory_availability') === 'issued' ? 'selected' : '' }}>Issued</option>
                            <option value="lost" {{ request('inventory_availability') === 'lost' ? 'selected' : '' }}>Lost</option>
                        </select>
                    </div>
                    <div class="w-40">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Record Status</label>
                        <select name="inventory_record_status" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="">All</option>
                            <option value="active" {{ request('inventory_record_status') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="archived" {{ request('inventory_record_status') === 'archived' ? 'selected' : '' }}>Archived</option>
                        </select>
                    </div>
                    <div class="w-32">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Per Page</label>
                        <select name="inventory_per_page" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            @foreach([5, 10, 15, 25, 50] as $size)
                                <option value="{{ $size }}" {{ (int) ($inventoryPerPage ?? 10) === $size ? 'selected' : '' }}>{{ $size }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="px-5 py-2 bg-[#296374] text-white rounded-lg hover:bg-[#1e4a57]">Filter</button>
                    @if(request()->hasAny(['inventory_search', 'inventory_book_ID', 'inventory_condition', 'inventory_availability', 'inventory_record_status', 'inventory_per_page']))
                        <a href="{{ route('admin.book-config.index', ['tab' => 'inventory']) }}" class="px-5 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">Clear</a>
                    @endif
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            <th class="px-6 py-4">Book Title</th>
                            <th class="px-6 py-4">Property No</th>
                            <th class="px-6 py-4">Condition</th>
                            <th class="px-6 py-4">Availability</th>
                            <th class="px-6 py-4">Record Status</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($inventories as $item)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 text-gray-800 font-semibold">{{ optional($item->book)->title ?? 'N/A' }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ $item->property_no }}</td>
                                <td class="px-6 py-4 uppercase text-gray-600">{{ $item->condition }}</td>
                                <td class="px-6 py-4 uppercase text-gray-600">{{ $item->status }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $item->record_status === 'active' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">
                                        {{ ucfirst($item->record_status ?? 'active') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <button type="button" onclick='openInventoryModal(@json($item))' class="px-3 py-1 text-xs rounded bg-blue-100 text-blue-700 hover:bg-blue-200">Edit</button>
                                        <form action="{{ route('admin.book-config.inventory.delete', $item) }}" method="POST" class="inline" onsubmit="return confirm('{{ $item->record_status === 'active' ? 'Archive this inventory item?' : 'Restore this inventory item?' }}');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-3 py-1 text-xs rounded {{ $item->record_status === 'active' ? 'bg-amber-100 text-amber-700 hover:bg-amber-200' : 'bg-green-100 text-green-700 hover:bg-green-200' }}">
                                                {{ $item->record_status === 'active' ? 'Archive' : 'Restore' }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">No inventory records yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($inventories->hasPages())
                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                    {{ $inventories->appends(['tab' => 'inventory'])->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<div id="bookModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden items-center justify-center">
    <div class="bg-white rounded-2xl shadow-2xl max-w-xl w-full mx-4 overflow-hidden">
        <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-[#296374]/10 to-transparent">
            <h3 id="bookModalTitle" class="text-xl font-bold text-gray-800">Add Ref Book</h3>
        </div>
        <form id="bookForm" class="p-6 space-y-4" action="{{ route('admin.book-config.books.store') }}" method="POST">
            @csrf
            <input type="hidden" id="book_method" name="_method" value="POST">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Subject</label>
                    <select id="book_subject_id" name="subject_ID" class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
                        <option value="">Select subject</option>
                        @foreach ($subjects as $subject)
                            <option value="{{ $subject->subject_ID }}">{{ $subject->code }} - {{ $subject->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Book Code</label>
                    <input id="book_code" name="book_code" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
                </div>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Title</label>
                <input id="book_title" name="title" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Author</label>
                <input id="book_author" name="author" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Grade Level</label>
                <select id="book_grade_level" name="grade_level" class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
                    <option value="">Select</option>
                    <option value="grade_7">Grade 7</option>
                    <option value="grade_8">Grade 8</option>
                    <option value="grade_9">Grade 9</option>
                    <option value="grade_10">Grade 10</option>
                    <option value="grade_11">Grade 11</option>
                    <option value="grade_12">Grade 12</option>
                </select>
            </div>
            <div class="flex gap-3 pt-4">
                <button type="button" onclick="closeBookModal()" class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">Cancel</button>
                <button type="submit" class="flex-1 px-4 py-2 bg-[#296374] text-white rounded-lg hover:bg-[#1e4a57]">Save Book</button>
            </div>
        </form>
    </div>
</div>

<div id="inventoryModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden items-center justify-center">
    <div class="bg-white rounded-2xl shadow-2xl max-w-xl w-full mx-4 overflow-hidden">
        <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-[#296374]/10 to-transparent">
            <h3 id="inventoryModalTitle" class="text-xl font-bold text-gray-800">Add Inventory Item</h3>
        </div>
        <form id="inventoryForm" class="p-6 space-y-4" action="{{ route('admin.book-config.inventory.store') }}" method="POST">
            @csrf
            <input type="hidden" id="inventory_method" name="_method" value="POST">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Book</label>
                    <select id="inventory_book_id" name="book_ID" class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
                        <option value="">Select book</option>
                        @foreach ($activeBooks as $book)
                            <option value="{{ $book->book_ID }}">{{ $book->book_code }} - {{ $book->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Property No</label>
                    <input id="inventory_property_no" name="property_no" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Condition</label>
                    <select id="inventory_condition" name="condition" class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
                        <option value="">Select</option>
                        <option value="good">Good</option>
                        <option value="fair">Fair</option>
                        <option value="damaged">Damaged</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Status</label>
                    <select id="inventory_status" name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
                        <option value="">Select</option>
                        <option value="available">Available</option>
                        <option value="issued">Issued</option>
                        <option value="lost">Lost</option>
                    </select>
                </div>
            </div>
            <div class="flex gap-3 pt-4">
                <button type="button" onclick="closeInventoryModal()" class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">Cancel</button>
                <button type="submit" class="flex-1 px-4 py-2 bg-[#296374] text-white rounded-lg hover:bg-[#1e4a57]">Save Inventory</button>
            </div>
        </form>
    </div>
</div>

<script>
    function switchBookConfigTab(tab) {
        const tabs = {
            books: { panel: document.getElementById('booksTabPanel'), button: document.getElementById('booksTabBtn') },
            inventory: { panel: document.getElementById('inventoryTabPanel'), button: document.getElementById('inventoryTabBtn') },
        };

        Object.keys(tabs).forEach(function (key) {
            const isActive = key === tab;
            tabs[key].panel.classList.toggle('hidden', !isActive);
            tabs[key].button.classList.toggle('bg-[#296374]', isActive);
            tabs[key].button.classList.toggle('text-white', isActive);
            tabs[key].button.classList.toggle('bg-white', !isActive);
            tabs[key].button.classList.toggle('text-gray-600', !isActive);
            tabs[key].button.classList.toggle('hover:bg-gray-100', !isActive);
        });
    }

    function openBookModal(book = null) {
        const form = document.getElementById('bookForm');
        const method = document.getElementById('book_method');
        const updateRouteTemplate = '{{ route('admin.book-config.books.update', ['book' => '__BOOK__']) }}';

        if (book) {
            document.getElementById('bookModalTitle').textContent = 'Edit Ref Book';
            form.action = updateRouteTemplate.replace('__BOOK__', book.book_ID);
            method.value = 'PUT';
            document.getElementById('book_subject_id').value = book.subject_ID || '';
            document.getElementById('book_code').value = book.book_code || '';
            document.getElementById('book_title').value = book.title || '';
            document.getElementById('book_author').value = book.author || '';
            document.getElementById('book_grade_level').value = book.grade_level || '';
        } else {
            document.getElementById('bookModalTitle').textContent = 'Add Ref Book';
            form.action = '{{ route('admin.book-config.books.store') }}';
            method.value = 'POST';
            form.reset();
        }

        document.getElementById('bookModal').classList.remove('hidden');
        document.getElementById('bookModal').classList.add('flex');
    }

    function closeBookModal() {
        document.getElementById('bookModal').classList.add('hidden');
        document.getElementById('bookModal').classList.remove('flex');
    }

    function openInventoryModal(item = null) {
        const form = document.getElementById('inventoryForm');
        const method = document.getElementById('inventory_method');
        const updateRouteTemplate = '{{ route('admin.book-config.inventory.update', ['inventory' => '__INV__']) }}';

        if (item) {
            document.getElementById('inventoryModalTitle').textContent = 'Edit Inventory Item';
            form.action = updateRouteTemplate.replace('__INV__', item.inventory_ID);
            method.value = 'PUT';
            document.getElementById('inventory_book_id').value = item.book_ID || '';
            document.getElementById('inventory_property_no').value = item.property_no || '';
            document.getElementById('inventory_condition').value = item.condition || '';
            document.getElementById('inventory_status').value = item.status || '';
        } else {
            document.getElementById('inventoryModalTitle').textContent = 'Add Inventory Item';
            form.action = '{{ route('admin.book-config.inventory.store') }}';
            method.value = 'POST';
            form.reset();
        }

        document.getElementById('inventoryModal').classList.remove('hidden');
        document.getElementById('inventoryModal').classList.add('flex');
    }

    function closeInventoryModal() {
        document.getElementById('inventoryModal').classList.add('hidden');
        document.getElementById('inventoryModal').classList.remove('flex');
    }

    document.getElementById('bookModal').addEventListener('click', function (e) {
        if (e.target === this) closeBookModal();
    });

    document.getElementById('inventoryModal').addEventListener('click', function (e) {
        if (e.target === this) closeInventoryModal();
    });

    const currentTab = new URLSearchParams(window.location.search).get('tab');
    if (currentTab === 'inventory') {
        switchBookConfigTab('inventory');
    } else {
        switchBookConfigTab('books');
    }
</script>
@endsection
