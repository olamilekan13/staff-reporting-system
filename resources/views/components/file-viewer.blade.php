@props([
    'fileName' => null,
    'fileType' => null,
    'fileSize' => null,
    'downloadUrl' => null,
    'previewUrl' => null,
    'mimeType' => null,
])

@php
    $extension = strtolower($fileType ?? '');

    $isPdf = $extension === 'pdf';
    $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
    $isVideo = in_array($extension, ['mp4', 'mov', 'avi', 'webm']);
    $isExcel = in_array($extension, ['xls', 'xlsx']);
    $isWord = in_array($extension, ['doc', 'docx']);
    $isPowerPoint = in_array($extension, ['ppt', 'pptx']);
    $isOffice = $isExcel || $isWord || $isPowerPoint;

    $iconColorClass = match(true) {
        $isPdf => 'text-red-500',
        $isWord => 'text-blue-500',
        $isExcel => 'text-green-500',
        $isPowerPoint => 'text-orange-500',
        $isImage => 'text-purple-500',
        $isVideo => 'text-pink-500',
        default => 'text-gray-400',
    };

    $effectiveMimeType = $mimeType;
    if ($isVideo && !$effectiveMimeType) {
        $effectiveMimeType = match($extension) {
            'mp4' => 'video/mp4',
            'mov' => 'video/quicktime',
            'avi' => 'video/x-msvideo',
            'webm' => 'video/webm',
            default => 'video/mp4',
        };
    }

    $effectivePreviewUrl = $previewUrl ?? $downloadUrl;
    $pdfViewerUrl = $effectivePreviewUrl ? $effectivePreviewUrl . '#view=FitH&toolbar=1' : null;
    $canPreview = $effectivePreviewUrl && ($isPdf || $isImage || $isVideo || $isExcel || ($isOffice && $previewUrl));

    $uniqueId = uniqid('file-viewer-');
@endphp

<div x-data="{ lightboxOpen: false }" {{ $attributes }}>
    {{-- File info bar --}}
    <div class="flex items-center justify-between bg-gray-50 rounded-lg px-4 py-3 @if($canPreview) mb-4 @endif">
        <div class="flex items-center gap-3 min-w-0">
            <svg class="w-6 h-6 shrink-0 {{ $iconColorClass }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
            </svg>
            <div class="min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate">{{ $fileName }}</p>
                @if($fileSize)
                    <p class="text-xs text-gray-500">{{ $fileSize }} &middot; {{ strtoupper($extension) }}</p>
                @else
                    <p class="text-xs text-gray-500">{{ strtoupper($extension) }}</p>
                @endif
            </div>
        </div>
        @if($downloadUrl)
            <a href="{{ $downloadUrl }}" download class="text-sm text-primary-600 hover:text-primary-700 font-medium shrink-0 ml-4">Download</a>
        @endif
    </div>

    {{-- Inline preview --}}
    @if($canPreview)
        @if($isExcel && $downloadUrl)
            {{-- Excel preview with SheetJS --}}
            <div x-data="{ viewMode: 'native', sheetLoaded: false, sheetError: false, sheets: [], activeSheet: 0 }" x-init="
                fetch('{{ $downloadUrl }}')
                    .then(response => response.arrayBuffer())
                    .then(data => {
                        const workbook = XLSX.read(data, { type: 'array' });
                        sheets = workbook.SheetNames.map((name, index) => ({
                            name: name,
                            index: index,
                            html: XLSX.utils.sheet_to_html(workbook.Sheets[name], { editable: false })
                        }));
                        sheetLoaded = true;
                    })
                    .catch(error => {
                        console.error('SheetJS failed:', error);
                        sheetError = true;
                    });
            ">
                {{-- View mode toggle --}}
                <div class="flex items-center gap-2 mb-3" x-show="sheetLoaded || sheetError">
                    <button
                        @click="viewMode = 'native'"
                        :class="viewMode === 'native' ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                        class="px-3 py-1.5 text-sm font-medium rounded transition-colors"
                        x-show="sheetLoaded"
                    >
                        Native Excel
                    </button>
                    <button
                        @click="viewMode = 'pdf'"
                        :class="viewMode === 'pdf' ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                        class="px-3 py-1.5 text-sm font-medium rounded transition-colors"
                        x-show="{{ $previewUrl ? 'true' : 'false' }}"
                    >
                        PDF Preview
                    </button>
                </div>

                {{-- Sheet tabs (if multiple sheets) --}}
                <div class="flex gap-2 mb-3 overflow-x-auto" x-show="sheetLoaded && sheets.length > 1 && viewMode === 'native'">
                    <template x-for="(sheet, index) in sheets" :key="index">
                        <button
                            @click="activeSheet = index"
                            :class="activeSheet === index ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                            class="px-3 py-1.5 text-sm font-medium rounded whitespace-nowrap transition-colors"
                            x-text="sheet.name"
                        ></button>
                    </template>
                </div>

                {{-- Native Excel view --}}
                <div x-show="viewMode === 'native'">
                    {{-- Loading state --}}
                    <div x-show="!sheetLoaded && !sheetError" class="border rounded-lg p-8 text-center bg-gray-50">
                        <svg class="animate-spin h-8 w-8 text-primary-500 mx-auto mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="text-sm text-gray-600">Loading Excel preview...</p>
                    </div>

                    {{-- Excel content --}}
                    <div x-show="sheetLoaded" class="border rounded-lg overflow-auto" style="max-height: calc(100vh - 250px);">
                        <template x-for="(sheet, index) in sheets" :key="index">
                            <div x-show="activeSheet === index" x-html="sheet.html" class="excel-preview p-4"></div>
                        </template>
                    </div>

                    {{-- Error state --}}
                    <div x-show="sheetError" class="border rounded-lg p-6 text-center text-gray-500 bg-gray-50">
                        <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                        </svg>
                        <p class="text-sm">Failed to load Excel preview.</p>
                        @if($previewUrl)
                            <p class="text-xs mt-1">Switch to PDF Preview or download the file.</p>
                        @else
                            <p class="text-xs mt-1">Download the file to view it.</p>
                        @endif
                    </div>
                </div>

                {{-- PDF fallback view --}}
                @if($previewUrl)
                    <div x-show="viewMode === 'pdf'" class="border rounded-lg overflow-hidden relative" style="height: calc(100vh - 200px); min-height: 500px;" x-data="{ iframeLoaded: false }">
                        <div x-show="!iframeLoaded" class="absolute inset-0 flex items-center justify-center bg-gray-50">
                            <svg class="animate-spin h-8 w-8 text-primary-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                        <iframe :src="'{{ $pdfViewerUrl }}'" class="w-full h-full" frameborder="0" loading="lazy" @load="iframeLoaded = true"></iframe>
                    </div>
                @endif
            </div>
        @elseif($isPdf || ($isOffice && $previewUrl))
            {{-- PDF / converted Office document --}}
            <div class="border rounded-lg overflow-hidden relative" style="height: calc(100vh - 200px); min-height: 500px;" x-data="{ iframeLoaded: false }">
                <div x-show="!iframeLoaded" class="absolute inset-0 flex items-center justify-center bg-gray-50">
                    <svg class="animate-spin h-8 w-8 text-primary-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
                <iframe src="{{ $pdfViewerUrl }}" class="w-full h-full" frameborder="0" loading="lazy" @load="iframeLoaded = true"></iframe>
            </div>
        @elseif($isImage)
            {{-- Image preview with click to enlarge --}}
            <div class="border rounded-lg overflow-hidden text-center p-4 bg-gray-50" x-data="{ imageError: false }">
                <img
                    x-show="!imageError"
                    src="{{ $effectivePreviewUrl }}"
                    alt="{{ $fileName }}"
                    class="max-w-full max-h-96 mx-auto rounded cursor-pointer hover:opacity-90 transition-opacity"
                    loading="lazy"
                    @click="lightboxOpen = true"
                    @@error="imageError = true"
                >
                <p x-show="imageError" x-cloak class="text-sm text-gray-500 py-4">Failed to load image preview.</p>
            </div>
        @elseif($isVideo)
            {{-- Video player --}}
            <div class="border rounded-lg overflow-hidden">
                <video controls class="w-full max-h-96" preload="metadata">
                    <source src="{{ $effectivePreviewUrl }}" type="{{ $effectiveMimeType }}">
                    Your browser does not support the video tag.
                </video>
            </div>
        @endif
    @elseif($isOffice && !$previewUrl)
        {{-- Office doc without PDF conversion — no inline preview possible --}}
        <div class="border rounded-lg p-6 text-center text-gray-500 bg-gray-50 mt-4">
            <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
            </svg>
            <p class="text-sm">Preview not available for this file type.</p>
            <p class="text-xs mt-1">Download the file to view it.</p>
        </div>
    @endif

    {{-- Image lightbox --}}
    @if($isImage && $effectivePreviewUrl)
        <div
            x-show="lightboxOpen"
            x-cloak
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-[70] flex items-center justify-center bg-black/80 p-4"
            @click.self="lightboxOpen = false"
            @keydown.escape.window="lightboxOpen = false"
            style="display: none;"
        >
            <button @click="lightboxOpen = false" class="absolute top-4 right-4 text-white/80 hover:text-white">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
            <img src="{{ $effectivePreviewUrl }}" alt="{{ $fileName }}" class="max-w-full max-h-full object-contain rounded-lg shadow-2xl">
        </div>
    @endif
</div>

@if($isExcel && $downloadUrl)
    @once
        {{-- SheetJS Library --}}
        <script src="https://cdn.sheetjs.com/xlsx-0.20.2/package/dist/xlsx.full.min.js"></script>

        {{-- Excel table styling --}}
        <style>
            .excel-preview table {
                border-collapse: collapse;
                width: 100%;
                font-size: 0.875rem;
                background: white;
            }
            .excel-preview th,
            .excel-preview td {
                border: 1px solid #e5e7eb;
                padding: 0.5rem 0.75rem;
                text-align: left;
            }
            .excel-preview th {
                background-color: #f9fafb;
                font-weight: 600;
                color: #374151;
            }
            .excel-preview tr:hover {
                background-color: #f9fafb;
            }
            .excel-preview td {
                color: #1f2937;
            }
        </style>
    @endonce
@endif
