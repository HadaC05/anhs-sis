@extends('users.student.layout')

@section('title', 'Documents')

@section('content')
@php
    $studentName = optional($application)->last_name ? optional($application)->last_name . ', ' . optional($application)->first_name : Auth::user()->name;
    $documentTypes = [
        'birth_certificate' => ['title' => 'Birth Certificate', 'desc' => 'PSA or local civil registrar copy', 'required' => true],
        'form_137' => ['title' => 'Form 137 / SF9', 'desc' => 'Latest report card or permanent record', 'required' => true],
        'good_moral' => ['title' => 'Good Moral Certificate', 'desc' => 'Issued by previous school', 'required' => false],
        'id_photo' => ['title' => '2x2 ID Photo', 'desc' => 'Recent photo with white background', 'required' => false],
    ];
    
    $uploadedDocuments = $documents->keyBy('doc_type');
    $requiredCount = 2; // birth_certificate, form_137
    $uploadedRequiredCount = $documents->whereIn('doc_type', ['birth_certificate', 'form_137'])->count();
    $progressPercentage = $requiredCount > 0 ? ($uploadedRequiredCount / $requiredCount) * 100 : 0;
@endphp
<div class="space-y-6">
    <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-100 p-6">
        <h1 class="text-2xl md:text-3xl font-bold text-gray-700 mb-2 tracking-tight">Document Requirements</h1>
        <p class="text-gray-600 text-sm md:text-base">Upload your required documents for enrollment verification.</p>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div>
                    <p class="text-sm font-medium text-red-800">Error:</p>
                    <ul class="text-xs text-red-700 mt-1 list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <div class="bg-white/95 backdrop-blur-sm shadow-xl rounded-lg p-6 border border-white/20">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-[#296374]">Upload Progress</h3>
            <span class="text-sm font-medium text-gray-600">{{ $uploadedRequiredCount }}/{{ $requiredCount }} Required Documents</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2.5">
            <div class="h-2.5 rounded-full bg-[#296374]" style="width: {{ $progressPercentage }}%"></div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @foreach($documentTypes as $docType => $docInfo)
            @php
                $uploadedDoc = $uploadedDocuments->get($docType);
            @endphp
            <div class="bg-white/95 backdrop-blur-sm shadow-xl rounded-lg p-6 border border-white/20 hover:shadow-2xl transition-all duration-300">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex-1">
                        <h3 class="font-bold text-[#296374] text-lg mb-1">{{ $docInfo['title'] }}</h3>
                        <p class="text-xs text-gray-600">{{ $docInfo['desc'] }}</p>
                    </div>
                    @if($docInfo['required'])
                        <span class="text-[10px] font-bold text-red-500 uppercase tracking-tighter bg-red-50 px-2 py-1 rounded ml-2">Required</span>
                    @else
                        <span class="text-[10px] font-bold text-gray-500 uppercase tracking-tighter bg-gray-50 px-2 py-1 rounded ml-2">Optional</span>
                    @endif
                </div>

                @if($uploadedDoc)
                    <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-green-800">Document Uploaded</p>
                                    <p class="text-xs text-green-600">{{ $uploadedDoc->date_uploaded->format('M d, Y h:i A') }}</p>
                                </div>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $uploadedDoc->status === 'verified' ? 'bg-green-100 text-green-800' : 
                                   ($uploadedDoc->status === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                                {{ ucfirst($uploadedDoc->status) }}
                            </span>
                        </div>
                        @if($uploadedDoc->file_path)
                            <div class="mt-2 flex items-center gap-2">
                                <a href="{{ route('student.documents.view', $uploadedDoc) }}" target="_blank" 
                                   class="text-xs text-blue-600 hover:text-blue-800 underline">View Document</a>
                                <form action="{{ route('student.documents.delete', $uploadedDoc) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs text-red-600 hover:text-red-800 underline" 
                                            onclick="return confirm('Are you sure you want to delete this document?')">Delete</button>
                                </form>
                            </div>
                        @endif
                    </div>
                @endif

                <form action="{{ route('student.documents.upload') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <input type="hidden" name="doc_type" value="{{ $docType }}">
                    
                    <label class="flex flex-col items-center justify-center w-full h-36 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer bg-white/50 hover:bg-white/80 transition-all group">
                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                            <svg class="w-8 h-8 mb-2 text-gray-400 group-hover:text-[#296374] transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                            <p class="text-sm text-gray-600 font-medium">Click to upload or drag & drop</p>
                            <p class="text-[10px] text-gray-500 mt-1 uppercase">PDF, JPG, PNG (Max 5MB)</p>
                        </div>
                        <input type="file" name="document" class="hidden" accept=".pdf,.jpg,.jpeg,.png">
                    </label>
                    
                    <button type="submit" class="w-full px-4 py-2 bg-[#296374] text-white rounded-lg hover:bg-opacity-90 transition-colors font-medium text-sm">
                        {{ $uploadedDoc ? 'Replace Document' : 'Upload Document' }}
                    </button>
                </form>
            </div>
        @endforeach
    </div>

    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div>
                <p class="text-sm font-medium text-blue-800">Important Notes:</p>
                <ul class="text-xs text-blue-700 mt-1 list-disc list-inside space-y-1">
                    <li>Uploaded documents will be reviewed by the guidance office.</li>
                    <li>Make sure all documents are clear and readable.</li>
                <li>Birth Certificate and Form 137 / SF9 are the only required documents before enrollment is finalized.</li>
                    <li>Documents can be replaced by uploading a new file of the same type.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('input[type="file"]').forEach(input => {
    input.addEventListener('change', function(e) {
        const fileName = e.target.files[0]?.name;
        if (fileName) {
            const label = e.target.closest('label');
            const p = label.querySelector('p.text-sm');
            p.textContent = `Selected: ${fileName}`;
        }
    });
});
</script>
@endsection


