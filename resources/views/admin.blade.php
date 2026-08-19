<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />

    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>CV Database | Admin Dashboard</title>

    <link rel="preconnect" href="https://fonts.googleapis.com" />

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    />

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    />

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                },
            },
        };
    </script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>


<body class="bg-slate-100 text-slate-800 font-sans min-h-screen antialiased">

<div class="flex flex-col md:flex-row min-h-screen">


    <!-- Mobile Top Bar -->
    <div class="md:hidden flex items-center justify-between bg-slate-900 text-white px-4 py-3 w-full sticky top-0 z-20">

        <div class="flex items-center gap-2">

            <div class="bg-indigo-600 text-white font-bold text-xs w-8 h-8 flex items-center justify-center rounded-lg shadow-inner">
                <img src="{{ asset('images/abzuamanpower.png') }}" alt="abzuamanpower">
            </div>

            <span class="font-bold text-base tracking-tight text-white">
                CV Database
            </span>

        </div>


        <form method="POST" action="{{ route('logout') }}" class="m-0">

            @csrf

            <button
                type="submit"
                class="text-xs font-medium text-red-400 hover:text-red-300 bg-transparent border-none p-0"
            >
                Logout ›
            </button>

        </form>

    </div>



    <!-- Sidebar -->
    <aside
        id="desktop-sidebar"
        class="w-64 bg-[#000000f2] text-white flex-shrink-0 hidden md:flex flex-col justify-between p-6 transition-transform duration-300 ease-in-out relative"
    >

        <!-- Collapse Button -->
        <button
            type="button"
            id="sidebar-toggle"
            class="absolute -right-3 top-6 w-7 h-7 bg-[#6da651] text-white rounded-full flex items-center justify-center shadow-lg hover:bg-[#5a8a41] transition-colors z-30"
            title="Collapse Sidebar"
        >
            ‹
        </button>


        <div>

            <div class="flex items-center gap-3 mb-8">

                <div
                    class=" text-white font-bold text-sm w-15 h-9 flex items-center justify-center rounded-lg shadow-inner"
                >
                   <img src="{{ asset('images/abzuamanpower.png') }}" alt="abzuamanpower">
                </div>

                {{-- <span class="font-bold text-lg tracking-tight text-white">
                    CV Database
                </span> --}}

            </div>


            <nav class="space-y-1">

                <a
                    href="{{ route('admin.dashboard') }}"
                    class="flex items-center justify-between px-4 py-2.5 text-sm font-medium bg-[#6da651] text-white rounded-lg shadow-sm"
                >
                    Dashboard
                    <span>›</span>
                </a>

                <div class="mt-4">
                    <a href="{{ route('admin.dashboard') }}" class="block px-4 py-2.5 text-sm font-medium hover:bg-white/5 rounded-md {{ request()->input('filter') !== 'today' ? 'bg-white/5' : '' }}">All Candidates</a>

                    <a href="{{ route('admin.dashboard', ['filter' => 'today']) }}" class="mt-2 block px-4 py-2.5 text-sm font-medium hover:bg-white/5 rounded-md {{ request()->input('filter') === 'today' ? 'bg-[#6da651] text-white' : '' }}">Today Uploaded ({{ $todayCount ?? 0 }})</a>
                </div>

            </nav>

        </div>


        <!-- Logout -->

        <form method="POST" action="{{ route('logout') }}" class="w-full">

            @csrf

            <button
                type="submit"
                class="flex items-center justify-between w-full px-4 py-2.5 text-sm font-medium text-red-400 hover:bg-slate-800 rounded-lg transition-colors"
            >
                Logout
                <span>›</span>
            </button>

        </form>

    </aside>



    <!-- Dashboard Content Area -->
    <div id="main-content" class="flex-grow p-4 sm:p-6 md:p-10 overflow-x-auto w-full"> 


        <!-- Dashboard Header -->
        <header class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 mb-8">

            <div>

                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">
                    Recruitment Dashboard
                </p>

                <h1 class="text-2xl sm:text-3xl font-extrabold text-[#6da651] tracking-tight">
                    All Candidates
                </h1>

                {{-- <p class="text-slate-500 text-sm mt-1">
                    Browse and search all uploaded CVs in a polished and responsive admin interface.
                </p> --}}

            </div>


            <!-- Search Form -->
            <div class="w-full lg:w-auto">

                <form
                    id="search-form"
                    class="w-full"
                    action="#"
                    method="GET"
                >

                    <div class="relative max-w-md mx-auto">
                        <svg
                            class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 1010.5 18.5a7.5 7.5 0 006.15-3.85z"
                            />
                        </svg>

                        <input
                            id="search-input"
                            name="search"
                            class="w-full px-10 py-2 text-sm bg-white border border-slate-300 rounded-full focus:ring-2 focus:ring-[#6da651] focus:outline-none shadow-sm"
                            type="search"
                            placeholder="Search candidates..."
                            autocomplete="off"
                        />
                    </div>

                </form>


                <div
                    id="search-status"
                    class="text-xs text-slate-500 mt-2"
                >
                    Showing all candidates ({{ $candidates->total() }})
                </div>

            </div>

        </header>



        <!-- Selection + Bulk Actions -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">


            <!-- Selection Status -->
            <div
                id="selection-status"
                class="text-sm text-slate-500"
            >
                0 CVs selected
            </div>



            <!-- Bulk Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">


                <!-- DOWNLOAD FORM -->
                <form
                    id="bulk-download-form"
                    method="POST"
                    action="{{ route('admin.candidates.download.zip') }}"
                    class="w-full sm:w-auto"
                >

                    @csrf

                    <div id="selected-candidates"></div>


                    <button
                        type="submit"
                        id="bulk-download-btn"
                        disabled
                        class="w-full sm:w-auto px-4 py-2 text-sm font-semibold bg-[#6da651] text-white rounded-lg hover:bg-[#5a8a41] disabled:bg-[#a0bda4] disabled:cursor-not-allowed transition-colors"
                    >
                        Download Selected CVs
                    </button>

                </form>



                <!-- DELETE FORM -->
                <form
                    id="bulk-delete-form"
                    method="POST"
                    action="{{ route('admin.candidates.delete') }}"
                    class="w-full sm:w-auto"
                    onsubmit="return confirmDeleteSelected()"
                >

                    @csrf

                    <div id="selected-candidates-delete"></div>


                    <button
                        type="submit"
                        id="bulk-delete-btn"
                        disabled
                        class="w-full sm:w-auto px-4 py-2 text-sm font-semibold bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:bg-[#a0bda4] disabled:cursor-not-allowed transition-colors"
                    >
                        Delete Selected CVs
                    </button>

                </form>

                <!-- EXPORT SELECTED FORM -->
                <form
                    id="bulk-export-form"
                    method="POST"
                    action="{{ route('admin.candidates.export.selected') }}"
                    class="w-full sm:w-auto"
                >

                    @csrf

                    <div id="selected-candidates-export"></div>

                    <button
                        type="submit"
                        id="bulk-export-btn"
                        disabled
                        class="w-full sm:w-auto px-4 py-2 text-sm font-semibold bg-[#2563eb] text-white rounded-lg hover:bg-[#1e4fd1] disabled:bg-[#9bb0ef] disabled:cursor-not-allowed transition-colors"
                    >
                        Export Selected
                    </button>

                </form>

                <!-- EXPORT ALL -->
                <div class="w-full sm:w-auto">
                    <a
                        href="{{ route('admin.candidates.export.all') }}"
                        id="export-all-btn"
                        class="inline-flex items-center justify-center px-4 py-2 text-sm font-semibold bg-[#0ea5a4] text-white rounded-lg hover:bg-[#08908f] transition-colors"
                    >
                        Export All
                    </a>
                </div>

            </div>

        </div>



        <!-- Success Message -->
        @if(session('success'))

            <div
                class="mb-4 px-4 py-3 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm"
            >
                {{ session('success') }}
            </div>

        @endif



        <!-- Error Message -->
        @if(session('error'))

            <div
                class="mb-4 px-4 py-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm"
            >
                {{ session('error') }}
            </div>

        @endif



        <!-- Table Card -->
        <section
            class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden"
            id="all"
        >

            <div class="overflow-x-auto">

                <table class="w-full table-auto text-left border-collapse">

                    <thead>

                        <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">


                            <!-- Select All -->
                            <th class="py-2 px-3 w-10 text-center">

                                <input
                                    type="checkbox"
                                    id="select-all"
                                    class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500 cursor-pointer"
                                />

                            </th>


                            <th class="py-2 px-3">
                                Candidate
                            </th>

                            <th class="py-2 px-3">
                                Contact
                            </th>

                            <th class="py-2 px-3">
                                Profession
                            </th>

                            <th class="py-2 px-3">
                                Experience
                            </th>

                            <th class="py-2 px-3">
                                Skills
                            </th>

                            <th class="py-2 px-3">
                                Education
                            </th>

                            <th class="py-2 px-3">
                                Relevant Jobs
                            </th>

                            <th class="py-2 px-3">
                                Uploaded
                            </th>

                            <th class="py-2 px-3 text-center">
                                Actions
                            </th>

                        </tr>

                    </thead>


                    <tbody
                        id="candidates-table-body"
                        class="divide-y divide-slate-200 text-sm"
                    >

                    @forelse($candidates as $candidate)

                        <tr class="hover:bg-slate-50 transition-colors">


                            <!-- Checkbox -->
                            <td class="py-3 px-3 text-center">

                                <input
                                    type="checkbox"
                                    name="candidate_ids[]"
                                    value="{{ $candidate->id }}"
                                    class="row-checkbox w-4 h-4 text-indigo-600 rounded border-slate-300 cursor-pointer"
                                />

                            </td>



                            <!-- Candidate -->
                            <td class="py-3 px-3">

                                <div class="flex items-center gap-3">

                                    <div class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-700 font-bold text-sm flex items-center justify-center flex-shrink-0">

                                        {{ strtoupper(substr($candidate->full_name, 0, 2)) }}

                                    </div>


                                    <div>

                                        <p class="font-semibold text-slate-900">
                                            {{ $candidate->full_name }}
                                        </p>

                                        <p class="text-xs text-slate-500">
                                            {{ $candidate->profession ?: 'Not specified' }}
                                        </p>

                                    </div>

                                </div>

                            </td>



                            <!-- Contact -->
                            <td class="py-3 px-3 text-xs text-slate-600">

                                <div>
                                    {{ $candidate->email ?: 'No email' }}
                                </div>

                                <div class="text-slate-400">
                                    {{ $candidate->phone ?: 'No phone' }}
                                </div>

                            </td>



                            <!-- Profession -->
                            <td class="py-3 px-3 font-medium text-slate-700">

                                {{ $candidate->profession ?: 'Not specified' }}

                            </td>



                            <!-- Experience -->
                           <!-- Experience -->
                        <td class="py-3 px-3 text-slate-600 min-w-[280px]">

                            @php
                                $exps = $candidate->experiences ?? [];
                                if ($exps instanceof \Illuminate\Support\Collection) {
                                    $exps = $exps->all();
                                }
                                $expCount = is_countable($exps) ? count($exps) : 0;
                            @endphp

                            @if($expCount === 0)

                                <span class="text-xs text-slate-400">
                                    No experience found
                                </span>

                            @elseif($expCount === 1)

                                @php
                                    $experience = $exps[0];
                                @endphp

                                <div class="mb-4 last:mb-0">

                                    <div class="font-semibold text-slate-800">
                                        {{ $experience->job_title ?: 'Not specified' }}
                                    </div>

                                    @if($experience->company)
                                        <div class="text-sm font-medium text-slate-700">
                                            {{ $experience->company }}
                                        </div>
                                    @endif

                                    @if($experience->duration)
                                        <div class="text-xs text-slate-500 mt-0.5">
                                            {{ $experience->duration }}
                                        </div>
                                    @endif

                                    @if($experience->description)
                                        <div class="text-xs text-slate-600 mt-1 whitespace-pre-line leading-5">
                                            {{ $experience->description }}
                                        </div>
                                    @endif

                                </div>

                            @else

                            @php
                                $first = $exps[0];
                            @endphp

                                <div class="mb-4 last:mb-0">

                                    <div class="font-semibold text-slate-800">
                                        {{ $first->job_title ?: 'Not specified' }}
                                    </div>

                                    @if($first->company)
                                        <div class="text-sm font-medium text-slate-700">
                                            {{ $first->company }}
                                        </div>
                                    @endif

                                    @if($first->duration)
                                        <div class="text-xs text-slate-500 mt-0.5">
                                            {{ $first->duration }}
                                        </div>
                                    @endif

                                    @if($first->description)
                                        <div class="text-xs text-slate-600 mt-1 whitespace-pre-line leading-5">
                                            {{ $first->description }}
                                        </div>
                                    @endif

                                </div>

                                <div id="exp-more-{{ $candidate->id }}" class="hidden">

                                    @foreach(array_slice($exps, 1) as $experience)

                                        <div class="mb-4 last:mb-0">

                                            <div class="font-semibold text-slate-800">
                                                {{ $experience->job_title ?: 'Not specified' }}
                                            </div>

                                            @if($experience->company)
                                                <div class="text-sm font-medium text-slate-700">
                                                    {{ $experience->company }}
                                                </div>
                                            @endif

                                            @if($experience->duration)
                                                <div class="text-xs text-slate-500 mt-0.5">
                                                    {{ $experience->duration }}
                                                </div>
                                            @endif

                                            @if($experience->description)
                                                <div class="text-xs text-slate-600 mt-1 whitespace-pre-line leading-5">
                                                    {{ $experience->description }}
                                                </div>
                                            @endif

                                        </div>

                                    @endforeach

                                </div>

                                <button type="button" class="exp-toggle mt-2 text-xs text-indigo-600 hover:underline" data-id="{{ $candidate->id }}">Read more</button>

                            @endif

                        </td>



                            <!-- Skills -->
                            <td class="py-3 px-3">

                                <div class="flex flex-wrap gap-1">

                                    @php
                                        $skills = $candidate->skills ?? [];
                                        if ($skills instanceof \Illuminate\Support\Collection) {
                                            $skills = $skills->all();
                                        }
                                        $skillCount = is_countable($skills) ? count($skills) : 0;
                                    @endphp

                                    @if($skillCount === 0)

                                        <span class="text-xs text-slate-400">
                                            No skills found
                                        </span>

                                    @elseif($skillCount <= 2)

                                        @foreach($skills as $skill)

                                            <span class="px-2 py-0.5 text-xs bg-slate-100 text-slate-700 rounded border border-slate-200">
                                                {{ $skill->skill }}
                                            </span>

                                        @endforeach

                                    @else

                                        @foreach(array_slice($skills, 0, 2) as $skill)

                                            <span class="px-2 py-0.5 text-xs bg-slate-100 text-slate-700 rounded border border-slate-200">
                                                {{ $skill->skill }}
                                            </span>

                                        @endforeach

                                        <span id="skills-more-{{ $candidate->id }}" class="hidden">

                                            @foreach(array_slice($skills, 2) as $skill)

                                                <span class="px-2 py-0.5 text-xs bg-slate-100 text-slate-700 rounded border border-slate-200">
                                                    {{ $skill->skill }}
                                                </span>

                                            @endforeach

                                        </span>

                                        <button type="button" class="skills-toggle mt-1 text-xs text-indigo-600 hover:underline" data-id="{{ $candidate->id }}">+{{ $skillCount - 2 }} more</button>

                                    @endif

                                </div>

                            </td>



                            <!-- Education -->
                            <td class="py-3 px-3 text-slate-600">

                                        @php
                                            $eduRaw = $candidate->education;
                                            $eduList = [];

                                            if (is_array($eduRaw)) {
                                                $eduList = $eduRaw;
                                            } elseif (is_string($eduRaw) && $eduRaw !== '') {
                                                $decoded = json_decode($eduRaw, true);
                                                if (is_array($decoded)) {
                                                    $eduList = $decoded;
                                                } else {
                                                    $eduList = [$eduRaw];
                                                        // try splitting common delimiters (newline, semicolon, comma)
                                                        if (preg_match('/[\r\n;,|]/', $eduRaw)) {
                                                            $parts = preg_split('/[\r\n;,|]+/', $eduRaw);
                                                            $eduList = array_map('trim', $parts);
                                                        } else {
                                                            $eduList = [$eduRaw];
                                                        }
                                                }
                                            }

                                            // dedupe and normalize
                                            $eduList = array_values(array_unique(array_filter(array_map(function ($e) {
                                                return is_string($e) ? trim($e) : null;
                                            }, $eduList))));

                                            $eduCount = count($eduList);
                                        @endphp

                                        @if($eduCount === 0)

                                            {{ 'Not specified' }}

                                        @elseif($eduCount <= 2)

                                            @foreach($eduList as $edu)
                                                <span class="px-2 py-0.5 text-xs bg-slate-100 text-slate-700 rounded border border-slate-200">{{ $edu }}</span>
                                            @endforeach

                                        @else

                                            @foreach(array_slice($eduList, 0, 2) as $edu)
                                                <span class="px-2 py-0.5 text-xs bg-slate-100 text-slate-700 rounded border border-slate-200">{{ $edu }}</span>
                                            @endforeach

                                            <span id="education-more-{{ $candidate->id }}" class="hidden">

                                                @foreach(array_slice($eduList, 2) as $edu)

                                                    <span class="px-2 py-0.5 text-xs bg-slate-100 text-slate-700 rounded border border-slate-200">{{ $edu }}</span>

                                                @endforeach

                                            </span>

                                            <button type="button" class="education-toggle mt-1 text-xs text-indigo-600 hover:underline" data-id="{{ $candidate->id }}">+{{ $eduCount - 2 }} more</button>

                                        @endif

                            </td>


                            <!-- Relevant Jobs -->
                            <td class="py-3 px-3">

                                <div class="flex flex-wrap gap-1">

                                    @php
                                        $rjobs = $candidate->relevantJobs ?? [];
                                        if ($rjobs instanceof \Illuminate\Support\Collection) {
                                            $rjobs = $rjobs->all();
                                        }

                                        // extract titles, dedupe
                                        $rjobTitles = array_values(array_unique(array_filter(array_map(function ($j) {
                                            return is_object($j) && isset($j->title) ? trim($j->title) : null;
                                        }, $rjobs))));

                                        $rjobCount = count($rjobTitles);
                                    @endphp

                                    @if($rjobCount === 0)

                                        <span class="text-xs text-slate-400">
                                            No relevant jobs
                                        </span>

                                    @elseif($rjobCount <= 2)

                                        @foreach($rjobTitles as $title)

                                            <span class="px-2 py-0.5 text-xs bg-slate-100 text-slate-700 rounded border border-slate-200">
                                                {{ $title }}
                                            </span>

                                        @endforeach

                                    @else

                                        @foreach(array_slice($rjobTitles, 0, 2) as $title)

                                            <span class="px-2 py-0.5 text-xs bg-slate-100 text-slate-700 rounded border border-slate-200">
                                                {{ $title }}
                                            </span>

                                        @endforeach

                                        <span id="relevant-more-{{ $candidate->id }}" class="hidden">

                                            @foreach(array_slice($rjobTitles, 2) as $title)

                                                <span class="px-2 py-0.5 text-xs bg-slate-100 text-slate-700 rounded border border-slate-200">
                                                    {{ $title }}
                                                </span>

                                            @endforeach

                                        </span>

                                        <button type="button" class="relevant-toggle mt-1 text-xs text-indigo-600 hover:underline" data-id="{{ $candidate->id }}">+{{ $rjobCount - 2 }} more</button>

                                    @endif

                                </div>

                            </td>



                            <!-- Uploaded -->
                            <td class="py-3 px-3 text-xs text-slate-500 whitespace-nowrap">

                                {{ $candidate->created_at->format('d M Y') }}

                            </td>



                            <!-- Actions -->
                            <td class="py-3 px-3 text-center">

                                <div class="flex items-center justify-center gap-2">

                                    <a href="#" data-id="{{ $candidate->id }}" class="px-3 py-1.5 text-xs font-semibold bg-yellow-50 text-yellow-700 hover:bg-yellow-100 rounded-md edit-btn">Edit</a>

                                    <a
                                        href="{{ asset('storage/' . $candidate->cv_file) }}"
                                        target="_blank"
                                        class="px-3 py-1.5 text-xs font-semibold bg-indigo-50 text-indigo-600 hover:bg-indigo-100 rounded-md"
                                    >
                                        View
                                    </a>


                                    <a
                                        href="{{ asset('storage/' . $candidate->cv_file) }}"
                                        download="{{ $candidate->cv_original_name }}"
                                        class="px-3 py-1.5 text-xs font-semibold bg-emerald-50 text-emerald-600 hover:bg-emerald-100 rounded-md"
                                    >
                                        Download
                                    </a>

                                </div>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="10"
                                class="py-10 text-center text-slate-500"
                            >
                                No CVs uploaded yet.
                            </td>

                        </tr>

                    @endforelse

                    </tbody>

                </table>

                <div id="server-pagination" class="p-4">
                    @if($candidates->lastPage() > 1)
                        {{ $candidates->appends(request()->query())->links() }}
                    @endif
                </div>

                <div id="ajax-pagination" class="p-4"></div>
            </div>

        </section>

    </div>

</div>



<script>

document.addEventListener('DOMContentLoaded', function () {


    /* ============================================================
       ELEMENTS
    ============================================================ */

    const searchForm =
        document.getElementById('search-form');

    const searchInput =
        document.getElementById('search-input');

    const searchStatus =
        document.getElementById('search-status');

    const tableBody =
        document.getElementById('candidates-table-body');

    const selectAllCheckbox =
        document.getElementById('select-all');

    const selectionStatus =
        document.getElementById('selection-status');

    const downloadButton =
        document.getElementById('bulk-download-btn');

    const deleteButton =
        document.getElementById('bulk-delete-btn');

    const selectedCandidates =
        document.getElementById('selected-candidates');

    const selectedCandidatesDelete =
        document.getElementById('selected-candidates-delete');

    const exportButton =
        document.getElementById('bulk-export-btn');

    const selectedCandidatesExport =
        document.getElementById('selected-candidates-export');



    /* ============================================================
       SELECT ALL
    ============================================================ */

    if (selectAllCheckbox) {

        selectAllCheckbox.addEventListener(
            'change',
            function () {

                toggleAllCheckboxes(this);

            }
        );

    }








    /* ============================================================
       ENTER TO SEARCH
    ============================================================ */

    searchForm.addEventListener(
        'submit',
        function (event) {

            event.preventDefault();

            performSearch();

        }
    );



    /* ============================================================
       SEARCH WHILE TYPING
    ============================================================ */

    let searchTimer;


    searchInput.addEventListener(
        'input',
        function () {

            clearTimeout(searchTimer);


            searchTimer = setTimeout(
                function () {

                    performSearch();

                },
                300
            );

        }
    );



    /* ============================================================
       SEARCH FUNCTION
    ============================================================ */

    function performSearch(page = 1) {

        const searchValue =
            searchInput.value.trim();


        const url = new URL(
            "{{ route('admin.search') }}",
            window.location.origin
        );


        url.searchParams.set(
            'search',
            searchValue
        );

        // include page and filter params
        url.searchParams.set('page', page);

        const filter = new URL(window.location).searchParams.get('filter');
        if (filter) {
            url.searchParams.set('filter', filter);
        }


        searchStatus.textContent =
            'Searching candidates...';


        fetch(
            url.toString(),
            {
                method: 'GET',

                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }
        )

        .then(
            function (response) {

                if (!response.ok) {

                    throw new Error(
                        'Search request failed.'
                    );

                }

                return response.json();

            }
        )

        .then(
            function (data) {


                if (searchValue === '') {

                    searchStatus.textContent =
                        `Showing all candidates (${data.count})`;

                } else {

                    searchStatus.textContent =
                        `Showing ${data.count} result(s) for "${searchValue}"`;

                }


                renderCandidates(
                    data.candidates || []
                );

                // pagination HTML (AJAX)
                const serverPagination = document.getElementById('server-pagination');
                const ajaxPagination = document.getElementById('ajax-pagination');

                if (data.pagination && ajaxPagination) {
                    ajaxPagination.innerHTML = data.pagination;
                    if (serverPagination) serverPagination.style.display = 'none';
                } else {
                    if (ajaxPagination) ajaxPagination.innerHTML = '';
                    if (serverPagination) serverPagination.style.display = '';
                }

            }
        )

        .catch(
            function (error) {

                console.error(
                    'Search Error:',
                    error
                );

                searchStatus.textContent =
                    'Something went wrong while searching.';

            }
        )

        .finally(
            function () {
                // finished loading
            }
        );

    }


    // Intercept clicks on AJAX pagination
    document.addEventListener('click', function (e) {
        // AJAX-style pagination box
        const pagAjax = e.target.closest('#ajax-pagination a');
        if (pagAjax) {
            e.preventDefault();
            try {
                const href = pagAjax.getAttribute('href');
                const page = new URL(href, window.location.origin).searchParams.get('page') || 1;
                performSearch(page);
            } catch (err) {
                performSearch(1);
            }
            return;
        }

        // Server-rendered pagination (use AJAX instead of full reload)
        const pagServer = e.target.closest('#server-pagination a');
        if (pagServer) {
            e.preventDefault();
            try {
                const href = pagServer.getAttribute('href');
                const page = new URL(href, window.location.origin).searchParams.get('page') || 1;
                performSearch(page);
            } catch (err) {
                performSearch(1);
            }
            return;
        }
    });

    // Delegated click for Edit buttons (server and ajax rows)
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.edit-btn');
        if (btn) {
            e.preventDefault();
            const id = btn.dataset.id;
            openEditModal(id);
        }
    });



    /* ============================================================
       RENDER CANDIDATES
    ============================================================ */

    function renderCandidates(candidates) {



        if (candidates.length === 0) {

            tableBody.innerHTML = `

                <tr>

                    <td
                        colspan="10"
                        class="py-12 px-4 text-center"
                    >

                        <div class="text-slate-500">

                            <div class="font-semibold text-slate-700 text-lg">
                                No candidates found
                            </div>

                            <div class="text-sm mt-1">
                                Try another profession or skill.
                            </div>

                        </div>

                    </td>

                </tr>

            `;


            resetSelection();

            return;

        }



        tableBody.innerHTML =
            candidates.map(
                function (candidate) {


                    const fullName =
                        candidate.full_name ||
                        'Not specified';


                    const initials =
                        fullName
                            .substring(0, 2)
                            .toUpperCase();



                    /* ====================================================
                       SKILLS (show first 2, toggle remaining)
                    ==================================================== */

                    let skillsHtml = '';

                    if (candidate.skills && candidate.skills.length > 0) {

                        if (candidate.skills.length <= 2) {

                            skillsHtml = candidate.skills.map(function (skill) {
                                return `
                                    <span class="px-2 py-0.5 text-xs bg-slate-100 text-slate-700 rounded border border-slate-200">
                                        ${escapeHtml(skill)}
                                    </span>
                                `;
                            }).join('');

                        } else {

                            const firstSkills = candidate.skills.slice(0, 2).map(function (skill) {
                                return `
                                    <span class="px-2 py-0.5 text-xs bg-slate-100 text-slate-700 rounded border border-slate-200">
                                        ${escapeHtml(skill)}
                                    </span>
                                `;
                            }).join('');

                            const moreSkillsHtml = candidate.skills.slice(2).map(function (skill) {
                                return `
                                    <span class="px-2 py-0.5 text-xs bg-slate-100 text-slate-700 rounded border border-slate-200">
                                        ${escapeHtml(skill)}
                                    </span>
                                `;
                            }).join('');

                            skillsHtml = `${firstSkills}<span id="skills-more-${candidate.id}" class="hidden">${moreSkillsHtml}</span><button type="button" class="skills-toggle mt-1 text-xs text-indigo-600 hover:underline" data-id="${candidate.id}">+${candidate.skills.length - 2} more</button>`;

                        }

                    } else {

                        skillsHtml = `
                            <span class="text-xs text-slate-400">
                                No skills found
                            </span>
                        `;

                    }



                    /* ====================================================
                       CV URL
                    ==================================================== */

                    let cvUrl = '#';


                    if (candidate.cv_file) {

                        cvUrl =
                            "{{ asset('storage') }}/" +
                            candidate.cv_file;

                    }

                    /* ====================================================
                       EDUCATION + RELEVANT JOBS PREP
                    ==================================================== */

                    // Education: handle string, JSON string, or array and render as badges (latest 2 + toggle)
                    let educationInner = '';
                    (function () {
                        let list = [];

                        if (Array.isArray(candidate.education)) {
                            list = candidate.education.slice();
                        } else if (candidate.education && typeof candidate.education === 'string') {
                            try {
                                const parsed = JSON.parse(candidate.education);
                                if (Array.isArray(parsed)) list = parsed.slice();
                                else list = [candidate.education];
                            } catch (e) {
                                // try splitting on common delimiters when not JSON
                                const raw = candidate.education;
                                if (/[\r\n;,|]/.test(raw)) {
                                    list = raw.split(/(?:\r\n|\n|[;,|])+/).map(s => s.trim()).filter(Boolean);
                                } else {
                                    list = [candidate.education];
                                }
                            }
                        }

                        // normalize & dedupe
                        list = list.map(function (s) {
                            return typeof s === 'string' ? s.trim() : '';
                        }).filter(function (s) { return s.length > 0; });

                        list = Array.from(new Set(list));

                        if (list.length === 0) {
                            educationInner = escapeHtml('Not specified');
                        } else if (list.length <= 2) {
                            educationInner = list.map(function (e) {
                                return `<span class="px-2 py-0.5 text-xs bg-slate-100 text-slate-700 rounded border border-slate-200">${escapeHtml(e)}</span>`;
                            }).join('');
                        } else {
                            const first = list.slice(0,2).map(function (e) {
                                return `<span class="px-2 py-0.5 text-xs bg-slate-100 text-slate-700 rounded border border-slate-200">${escapeHtml(e)}</span>`;
                            }).join('');

                            const more = list.slice(2).map(function (e) {
                                return `<span class="px-2 py-0.5 text-xs bg-slate-100 text-slate-700 rounded border border-slate-200">${escapeHtml(e)}</span>`;
                            }).join('');

                            educationInner = `${first}<span id="education-more-${candidate.id}" class="hidden">${more}</span><button type="button" class="education-toggle mt-1 text-xs text-indigo-600 hover:underline" data-id="${candidate.id}">+${list.length - 2} more</button>`;
                        }
                    })();

                    // Relevant jobs: show first 2, toggle remaining
                    let relevantJobsHtml = '';

                    if (candidate.relevant_jobs && candidate.relevant_jobs.length > 0) {

                        // dedupe by title
                        const titles = Array.from(new Set(candidate.relevant_jobs.map(function (j) { return (j && j.title) ? String(j.title).trim() : ''; }).filter(Boolean)));

                        if (titles.length <= 2) {

                            relevantJobsHtml = titles.map(function (t) {
                                return `
                                    <span class="px-2 py-0.5 text-xs bg-slate-100 text-slate-700 rounded border border-slate-200">${escapeHtml(t)}</span>
                                `;
                            }).join('');

                        } else {

                            const first = titles.slice(0, 2).map(function (t) {
                                return `
                                    <span class="px-2 py-0.5 text-xs bg-slate-100 text-slate-700 rounded border border-slate-200">${escapeHtml(t)}</span>
                                `;
                            }).join('');

                            const more = titles.slice(2).map(function (t) {
                                return `
                                    <span class="px-2 py-0.5 text-xs bg-slate-100 text-slate-700 rounded border border-slate-200">${escapeHtml(t)}</span>
                                `;
                            }).join('');

                            relevantJobsHtml = `${first}<span id="relevant-more-${candidate.id}" class="hidden">${more}</span><button type="button" class="relevant-toggle mt-1 text-xs text-indigo-600 hover:underline" data-id="${candidate.id}">+${titles.length - 2} more</button>`;

                        }

                    } else {

                        relevantJobsHtml = `
                            <span class="text-xs text-slate-400">No relevant jobs</span>
                        `;

                    }



                    /* ====================================================
                       ROW
                    ==================================================== */

                    return `

                        <tr class="border-t border-slate-100">


                            <!-- Checkbox -->

                            <td class="py-3 px-3 text-center">

                                <input
                                    type="checkbox"
                                    name="candidate_ids[]"
                                    value="${candidate.id}"
                                    class="row-checkbox w-4 h-4 text-indigo-600 rounded border-slate-300 cursor-pointer"
                                >

                            </td>



                            <!-- Candidate -->

                            <td class="py-4 px-4">

                                <div class="flex items-center gap-3">

                                    <div
                                        class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-700 font-bold text-sm flex items-center justify-center flex-shrink-0"
                                    >
                                        ${escapeHtml(initials)}
                                    </div>


                                    <div>

                                        <p class="font-semibold text-slate-900">
                                            ${escapeHtml(fullName)}
                                        </p>

                                        <p class="text-xs text-slate-500">
                                            ${escapeHtml(
                                                candidate.profession ||
                                                'Not specified'
                                            )}
                                        </p>

                                    </div>

                                </div>

                            </td>



                            <!-- Contact -->

                            <td class="py-4 px-4 text-xs text-slate-600">

                                <div>
                                    ${escapeHtml(
                                        candidate.email ||
                                        'No email'
                                    )}
                                </div>

                                <div class="text-slate-400">

                                    ${escapeHtml(
                                        candidate.phone ||
                                        'No phone'
                                    )}

                                </div>

                            </td>



                            <!-- Profession -->

                            <td class="py-4 px-4 font-medium text-slate-700">

                                ${escapeHtml(
                                    candidate.profession ||
                                    'Not specified'
                                )}

                            </td>



                            <!-- Experience -->

                          <!-- Experience -->

<td class="py-4 px-4 text-slate-600 min-w-[320px]">

    ${ (candidate.experiences && candidate.experiences.length > 0) ? (function () {

        if (candidate.experiences.length === 1) {

            const e = candidate.experiences[0];
            return `
                <div class="mb-4 last:mb-0">
                    <div class="font-semibold text-slate-800">${escapeHtml(e.job_title || 'Not specified')}</div>
                    ${ e.company ? `<div class="text-sm font-medium text-slate-700">${escapeHtml(e.company)}</div>` : '' }
                    ${ e.duration ? `<div class="text-xs text-slate-500 mt-0.5">${escapeHtml(e.duration)}</div>` : '' }
                    ${ e.description ? `<div class="text-xs text-slate-600 mt-1 whitespace-pre-line leading-5">${escapeHtml(e.description)}</div>` : '' }
                </div>
            `;

        }

        // multiple experiences: show first, hide rest
        const first = candidate.experiences[0];
        const rest = candidate.experiences.slice(1).map(function (experience) {
            return `
                <div class="mb-4 last:mb-0">
                    <div class="font-semibold text-slate-800">${escapeHtml(experience.job_title || 'Not specified')}</div>
                    ${ experience.company ? `<div class="text-sm font-medium text-slate-700">${escapeHtml(experience.company)}</div>` : '' }
                    ${ experience.duration ? `<div class="text-xs text-slate-500 mt-0.5">${escapeHtml(experience.duration)}</div>` : '' }
                    ${ experience.description ? `<div class="text-xs text-slate-600 mt-1 whitespace-pre-line leading-5">${escapeHtml(experience.description)}</div>` : '' }
                </div>
            `;
        }).join('');

        return `
            <div class="mb-4 last:mb-0">
                <div class="font-semibold text-slate-800">${escapeHtml(first.job_title || 'Not specified')}</div>
                ${ first.company ? `<div class="text-sm font-medium text-slate-700">${escapeHtml(first.company)}</div>` : '' }
                ${ first.duration ? `<div class="text-xs text-slate-500 mt-0.5">${escapeHtml(first.duration)}</div>` : '' }
                ${ first.description ? `<div class="text-xs text-slate-600 mt-1 whitespace-pre-line leading-5">${escapeHtml(first.description)}</div>` : '' }
            </div>
            <div id="exp-more-${candidate.id}" class="hidden">${rest}</div>
            <button type="button" class="exp-toggle mt-2 text-xs text-indigo-600 hover:underline" data-id="${candidate.id}">Read more</button>
        `;

    })() : `
        <span class="text-xs text-slate-400">No experience found</span>
    ` }

</td>



                            <!-- Skills -->

                            <td class="py-4 px-4">

                                <div class="flex flex-wrap gap-1">

                                    ${skillsHtml}

                                </div>

                            </td>



                            <!-- Education -->

                            <td class="py-4 px-4 text-slate-600">

                                ${educationInner}

                            </td>



                            <!-- Relevant Jobs -->
                            <td class="py-4 px-4">

                                <div class="flex flex-wrap gap-1">

                                    ${relevantJobsHtml}

                                </div>

                            </td>



                            <!-- Uploaded -->

                            <td class="py-4 px-4 text-xs text-slate-500 whitespace-nowrap">

                                ${escapeHtml(
                                    candidate.created_at ||
                                    ''
                                )}

                            </td>



                            <!-- Actions -->

                            <td class="py-4 px-4 text-center">

                                <div class="flex items-center justify-center gap-2">


                                    <a
                                        href="${cvUrl}"
                                        target="_blank"
                                        class="px-3 py-1.5 text-xs font-semibold bg-indigo-50 text-indigo-600 hover:bg-indigo-100 rounded-md"
                                    >
                                        View
                                    </a>

                                    <a href="#" data-id="${candidate.id}" class="px-3 py-1.5 text-xs font-semibold bg-yellow-50 text-yellow-700 hover:bg-yellow-100 rounded-md edit-btn">Edit</a>


                                    <a
                                        href="${cvUrl}"
                                        download="${escapeHtml(
                                            candidate.cv_original_name ||
                                            'cv'
                                        )}"
                                        class="px-3 py-1.5 text-xs font-semibold bg-emerald-50 text-emerald-600 hover:bg-emerald-100 rounded-md"
                                    >
                                        Download
                                    </a>

                                </div>

                            </td>

                        </tr>

                    `;

                }
            )
            .join('');


        initializeCheckboxes();

    }



    /* ============================================================
       CHECKBOX INITIALIZATION + GLOBAL SELECTION PERSISTENCE
    ============================================================ */

    // Global selected IDs set persisted in sessionStorage
    const STORAGE_KEY = 'selectedCandidateIds';
    const selectedCandidateIds = new Set(JSON.parse(sessionStorage.getItem(STORAGE_KEY) || '[]'));

    function saveSelectedIdsToStorage() {
        try {
            sessionStorage.setItem(STORAGE_KEY, JSON.stringify([...selectedCandidateIds]));
        } catch (e) {
            // sessionStorage might be unavailable; ignore
        }
    }

    function initializeCheckboxes() {

        const checkboxes = document.querySelectorAll('.row-checkbox');

        checkboxes.forEach(function (checkbox) {

            const id = checkbox.value;

            // Set checkbox state based on global set
            checkbox.checked = selectedCandidateIds.has(id);

            // Ensure change handler only once
            checkbox.removeEventListener('change', onRowCheckboxChange);
            checkbox.addEventListener('change', onRowCheckboxChange);

        });

        // Update select-all and hidden inputs
        updateSelection();

    }

    function onRowCheckboxChange(event) {
        const checkbox = event.currentTarget;
        const id = checkbox.value;

        if (checkbox.checked) {
            selectedCandidateIds.add(id);
        } else {
            selectedCandidateIds.delete(id);
        }

        saveSelectedIdsToStorage();

        updateSelection();
    }



    /* ============================================================
       UPDATE SELECTION
    ============================================================ */

    function updateSelection() {

        // Visible checkboxes on current page
        const visibleCheckboxes = Array.from(document.querySelectorAll('.row-checkbox'));

        const visibleTotal = visibleCheckboxes.length;
        const visibleSelectedCount = visibleCheckboxes.filter(cb => selectedCandidateIds.has(cb.value)).length;

        // Update selectAll checkbox state based on visible page
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = visibleSelectedCount > 0 && visibleSelectedCount === visibleTotal && visibleTotal > 0;
            selectAllCheckbox.indeterminate = visibleSelectedCount > 0 && visibleSelectedCount < visibleTotal;
        }

        // Total selected count across pages
        const totalSelected = selectedCandidateIds.size;

        selectionStatus.textContent = `${totalSelected} CVs selected`;

        // Enable/disable buttons based on global selection
        const anySelected = totalSelected > 0;
        downloadButton.disabled = !anySelected;
        deleteButton.disabled = !anySelected;
        if (exportButton) exportButton.disabled = !anySelected;

        // Rebuild hidden inputs for all selected IDs (global)
        selectedCandidates.innerHTML = '';
        selectedCandidatesDelete.innerHTML = '';
        if (selectedCandidatesExport) selectedCandidatesExport.innerHTML = '';

        [...selectedCandidateIds].forEach(function (id) {
            // Download
            const downloadInput = document.createElement('input');
            downloadInput.type = 'hidden';
            downloadInput.name = 'candidate_ids[]';
            downloadInput.value = id;
            selectedCandidates.appendChild(downloadInput);

            // Delete
            const deleteInput = document.createElement('input');
            deleteInput.type = 'hidden';
            deleteInput.name = 'candidate_ids[]';
            deleteInput.value = id;
            selectedCandidatesDelete.appendChild(deleteInput);

            // Export
            if (selectedCandidatesExport) {
                const exportInput = document.createElement('input');
                exportInput.type = 'hidden';
                exportInput.name = 'candidate_ids[]';
                exportInput.value = id;
                selectedCandidatesExport.appendChild(exportInput);
            }
        });

    }



    /* ============================================================
       RESET SELECTION
    ============================================================ */

    function resetSelection() {

        selectedCandidateIds.clear();
        saveSelectedIdsToStorage();

        selectedCandidates.innerHTML = '';
        selectedCandidatesDelete.innerHTML = '';
        if (selectedCandidatesExport) selectedCandidatesExport.innerHTML = '';

        selectionStatus.textContent = '0 CVs selected';

        downloadButton.disabled = true;
        deleteButton.disabled = true;
        if (exportButton) exportButton.disabled = true;

        if (selectAllCheckbox) {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;
        }

        // Uncheck visible boxes
        document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = false);

    }



    /* ============================================================
       SELECT ALL CHECKBOXES
    ============================================================ */

    function toggleAllCheckboxes(source) {

        const checkboxes = Array.from(document.querySelectorAll('.row-checkbox'));

        if (source.checked) {
            // add visible IDs to global set
            checkboxes.forEach(cb => selectedCandidateIds.add(cb.value));
        } else {
            // remove visible IDs from global set
            checkboxes.forEach(cb => selectedCandidateIds.delete(cb.value));
        }

        // reflect on page
        checkboxes.forEach(cb => cb.checked = source.checked);

        saveSelectedIdsToStorage();
        updateSelection();

    }



    /* ============================================================
       HTML ESCAPE
    ============================================================ */

    function escapeHtml(value) {

        if (
            value === null ||
            value === undefined
        ) {

            return '';

        }


        const div =
            document.createElement(
                'div'
            );


        div.textContent =
            String(value);


        return div.innerHTML;

    }


    /* ============================================================
       EDIT MODAL
    ============================================================ */

    function createEditModal() {
        if (document.getElementById('edit-modal')) return;

        const modal = document.createElement('div');
        modal.id = 'edit-modal';
        modal.className = 'fixed inset-0 z-50 flex items-center justify-center bg-black/40 hidden';
        modal.innerHTML = `
            <div class="bg-white rounded-lg shadow-lg w-full max-w-lg mx-4">
                <div class="p-4 border-b flex items-center justify-between">
                    <h3 class="font-semibold">Edit Candidate</h3>
                    <button id="edit-modal-close" class="text-slate-500 hover:text-slate-700">✕</button>
                </div>
                <div class="p-4 overflow-y-auto max-h-[70vh]">
                <form id="edit-form" class="space-y-3">
                    <input type="hidden" name="id" id="edit-id">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs text-slate-600">Full name</label>
                            <input id="edit-full_name" name="full_name" class="w-full px-3 py-2 border rounded" />
                        </div>
                        <div>
                            <label class="text-xs text-slate-600">Profession</label>
                            <input id="edit-profession" name="profession" class="w-full px-3 py-2 border rounded" />
                        </div>
                        <div>
                            <label class="text-xs text-slate-600">Email</label>
                            <input id="edit-email" name="email" class="w-full px-3 py-2 border rounded" />
                        </div>
                        <div>
                            <label class="text-xs text-slate-600">Phone</label>
                            <input id="edit-phone" name="phone" class="w-full px-3 py-2 border rounded" />
                        </div>
                    </div>
                    <div>
                        <label class="text-xs text-slate-600">Education</label>
                        <textarea id="edit-education" name="education" rows="3" class="w-full px-3 py-2 border rounded"></textarea>
                    </div>
                    <div>
                        <label class="text-xs text-slate-600">Skills (comma separated)</label>
                        <input id="edit-skills" name="skills" class="w-full px-3 py-2 border rounded" placeholder="e.g. PHP, Laravel, MySQL" />
                    </div>

                    <div>
                        <label class="text-xs text-slate-600">Relevant Jobs (comma separated)</label>
                        <input id="edit-relevant_jobs" name="relevant_jobs" class="w-full px-3 py-2 border rounded" placeholder="e.g. Frontend Developer, Backend Developer" />
                    </div>
                    <div>
                        <label class="text-xs text-slate-600">Remarks (private)</label>
                        <textarea id="edit-remarks" name="remarks" rows="2" class="w-full px-3 py-2 border rounded" placeholder="Internal remark for this candidate (not displayed publicly)"></textarea>
                    </div>
                    <div>
                        <label class="text-xs text-slate-600">Experiences</label>
                        <div id="experiences-list" class="space-y-3">
                            <!-- experience rows inserted here -->
                        </div>
                        <button type="button" id="add-experience" class="mt-2 px-3 py-1 text-sm bg-slate-100 rounded">Add Experience</button>
                    </div>
                    <div class="flex items-center justify-end gap-2">
                        <button type="button" id="edit-cancel" class="px-4 py-2 bg-slate-100 rounded">Cancel</button>
                        <button type="submit" id="edit-save" class="px-4 py-2 bg-indigo-600 text-white rounded">Save</button>
                    </div>
                </form>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        // Close handlers
        document.getElementById('edit-modal-close').addEventListener('click', closeEditModal);
        document.getElementById('edit-cancel').addEventListener('click', closeEditModal);

        document.getElementById('edit-form').addEventListener('submit', function (e) {
            e.preventDefault();
            submitEditForm();
        });

        // Add experience row handler
        document.getElementById('add-experience').addEventListener('click', function () {
            addExperienceRow();
        });

        // Delegated remove for experience rows
        document.getElementById('experiences-list').addEventListener('click', function (e) {
            const btn = e.target.closest('.remove-experience');
            if (btn) {
                const row = btn.closest('.experience-row');
                if (row) row.remove();
            }
        });
    }

    function openEditModal(id) {
        createEditModal();
        const modal = document.getElementById('edit-modal');
        modal.classList.remove('hidden');

        // fetch candidate data
        fetch(`/admin/candidates/${id}`, { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(data => {
                document.getElementById('edit-id').value = data.id || '';
                document.getElementById('edit-full_name').value = data.full_name || '';
                document.getElementById('edit-email').value = data.email || '';
                document.getElementById('edit-phone').value = data.phone || '';
                document.getElementById('edit-profession').value = data.profession || '';
                document.getElementById('edit-education').value = data.education || '';
                document.getElementById('edit-remarks').value = data.remarks || '';
                // populate skills and relevant jobs as comma-separated values
            // Skills can be array of objects or array of strings
            if (data.skills && data.skills.length) {
                if (typeof data.skills[0] === 'string') {
                    document.getElementById('edit-skills').value = data.skills.join(', ');
                } else {
                    document.getElementById('edit-skills').value = data.skills.map(s => s.skill || s.name || '').filter(Boolean).join(', ');
                }
            } else {
                document.getElementById('edit-skills').value = '';
            }

            if (data.relevantJobs && data.relevantJobs.length) {
                if (typeof data.relevantJobs[0] === 'string') {
                    document.getElementById('edit-relevant_jobs').value = data.relevantJobs.join(', ');
                } else {
                    document.getElementById('edit-relevant_jobs').value = data.relevantJobs.map(j => j.title || j.name || '').filter(Boolean).join(', ');
                }
            } else {
                document.getElementById('edit-relevant_jobs').value = '';
            }

            // populate experiences into editable rows
            populateExperiences(data.experiences || []);
            })
            .catch(err => {
                alert('Unable to load candidate data.');
                closeEditModal();
            });
    }

    function addExperienceRow(data = {}) {
        const list = document.getElementById('experiences-list');
        const idx = Date.now();
        const row = document.createElement('div');
        row.className = 'experience-row p-2 border rounded space-y-2';
        row.innerHTML = `
            <div class="flex items-center justify-between">
                <strong class="text-sm">Experience</strong>
                <button type="button" class="remove-experience text-red-500 text-sm">Remove</button>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <input type="text" name="job_title" placeholder="Job title" class="exp-job w-full px-2 py-1 border rounded" value="${escapeHtml(data.job_title || '')}" />
                <input type="text" name="company" placeholder="Company" class="exp-company w-full px-2 py-1 border rounded" value="${escapeHtml(data.company || '')}" />
            </div>
            <div class="grid grid-cols-2 gap-2">
                <input type="text" name="duration" placeholder="Duration" class="exp-duration w-full px-2 py-1 border rounded" value="${escapeHtml(data.duration || '')}" />
            </div>
            <div>
                <textarea name="description" placeholder="Description" class="exp-description w-full px-2 py-1 border rounded" rows="3">${escapeHtml(data.description || '')}</textarea>
            </div>
        `;
        list.appendChild(row);
        return row;
    }

    function populateExperiences(arr) {
        const list = document.getElementById('experiences-list');
        list.innerHTML = '';
        if (!Array.isArray(arr) || arr.length === 0) return;
        arr.forEach(function (e) {
            // e might be string or object
            if (typeof e === 'string') {
                addExperienceRow({ description: e });
            } else {
                addExperienceRow({ job_title: e.job_title || '', company: e.company || '', duration: e.duration || '', description: e.description || '' });
            }
        });
    }

    function closeEditModal() {
        const modal = document.getElementById('edit-modal');
        if (modal) modal.classList.add('hidden');
    }

    function submitEditForm() {
        const id = document.getElementById('edit-id').value;
        const payload = {
            full_name: document.getElementById('edit-full_name').value,
            email: document.getElementById('edit-email').value,
            phone: document.getElementById('edit-phone').value,
            profession: document.getElementById('edit-profession').value,
            education: document.getElementById('edit-education').value,
            remarks: document.getElementById('edit-remarks').value,
            skills: document.getElementById('edit-skills').value,
            relevant_jobs: document.getElementById('edit-relevant_jobs').value,
        };

        // collect experiences from rows
        const experiences = [];
        document.querySelectorAll('.experience-row').forEach(function (row) {
            experiences.push({
                job_title: row.querySelector('.exp-job') ? row.querySelector('.exp-job').value : '',
                company: row.querySelector('.exp-company') ? row.querySelector('.exp-company').value : '',
                duration: row.querySelector('.exp-duration') ? row.querySelector('.exp-duration').value : '',
                description: row.querySelector('.exp-description') ? row.querySelector('.exp-description').value : '',
            });
        });

        payload.experiences = experiences;

        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        fetch(`/admin/candidates/${id}/update`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token
            },
            body: JSON.stringify(payload)
        })
        .then(r => r.json().then(j => ({ ok: r.ok, status: r.status, body: j })))
        .then(result => {
            if (!result.ok) {
                if (result.status === 422 && result.body.errors) {
                    alert('Validation error: ' + JSON.stringify(result.body.errors));
                } else {
                    alert('Update failed');
                }
                return;
            }

            // success — refresh page to reflect changes
            closeEditModal();
            location.reload();
        })
        .catch(err => {
            console.error(err);
            alert('Update failed');
        });
    }



    /* ============================================================
       INITIAL SETUP
    ============================================================ */

    initializeCheckboxes();

});



/* ================================================================
   DELETE CONFIRMATION
================================================================ */

function confirmDeleteSelected() {

    const selectedCheckboxes =
        document.querySelectorAll(
            '.row-checkbox:checked'
        );


    const count =
        selectedCheckboxes.length;


    if (count === 0) {

        alert(
            'Please select at least one CV.'
        );

        return false;

    }


    return confirm(

        'Are you sure you want to permanently delete ' +
        count +
        ' selected CV(s)?\n\n' +

        'This will delete the CV files and all related candidate data. ' +

        'This action cannot be undone.'

    );

}


        // Delegated toggles for both server-rendered and dynamically inserted rows
        document.addEventListener('click', function (event) {
                        // (edit handler moved into DOMContentLoaded scope where openEditModal is defined)

            const expBtn = event.target.closest('.exp-toggle');
            if (expBtn) {
                const id = expBtn.dataset.id;
                const more = document.getElementById(`exp-more-${id}`);
                if (more) {
                    more.classList.toggle('hidden');
                    expBtn.textContent = more.classList.contains('hidden') ? 'Read more' : 'Read less';
                }
                return;
            }

            const skillsBtn = event.target.closest('.skills-toggle');
            if (skillsBtn) {
                const id = skillsBtn.dataset.id;
                const more = document.getElementById(`skills-more-${id}`);
                if (more) {
                    more.classList.toggle('hidden');
                    skillsBtn.textContent = more.classList.contains('hidden') ? `+${more.querySelectorAll('span').length} more` : 'Read less';
                }
                return;
            }

            const relBtn = event.target.closest('.relevant-toggle');
            if (relBtn) {
                const id = relBtn.dataset.id;
                const more = document.getElementById(`relevant-more-${id}`);
                if (more) {
                    more.classList.toggle('hidden');
                    relBtn.textContent = more.classList.contains('hidden') ? `+${more.querySelectorAll('span').length} more` : 'Read less';
                }
                return;
            }

            const eduBtn = event.target.closest('.education-toggle');
            if (eduBtn) {
                const id = eduBtn.dataset.id;
                const more = document.getElementById(`education-more-${id}`);
                if (more) {
                    more.classList.toggle('hidden');
                    eduBtn.textContent = more.classList.contains('hidden') ? `+${more.querySelectorAll('span').length} more` : 'Read less';
                }
                return;
            }

        });


/* ================================================================
   SIDEBAR
================================================================ */

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const sidebar =
            document.getElementById(
                'desktop-sidebar'
            );


        const toggleButton =
            document.getElementById(
                'sidebar-toggle'
            );


        if (
            !sidebar ||
            !toggleButton
        ) {

            return;

        }


        const mainContent = document.getElementById('main-content');

        toggleButton.addEventListener(
            'click',
            function () {

                sidebar.classList.toggle(
                    '-translate-x-full'
                );

                // when collapsed, hide horizontal overflow and remove left offset
                if (sidebar.classList.contains('-translate-x-full')) {
                    toggleButton.innerHTML = '›';
                    toggleButton.title = 'Open Sidebar';

                    // Make sidebar fixed and off-screen so it does not reserve layout width
                    sidebar.style.position = 'fixed';
                    sidebar.style.top = '0';
                    sidebar.style.left = '0';
                    sidebar.style.height = '100%';
                    sidebar.style.zIndex = '40';
                    sidebar.style.transform = 'translateX(-110%)';

                    // Prevent x-axis scrollbar and remove left offset
                    document.documentElement.style.overflowX = 'hidden';
                    if (mainContent) mainContent.style.marginLeft = '0';

                } else {

                    toggleButton.innerHTML = '‹';
                    toggleButton.title = 'Collapse Sidebar';

                    // Restore sidebar positioning
                    sidebar.style.position = '';
                    sidebar.style.top = '';
                    sidebar.style.left = '';
                    sidebar.style.height = '';
                    sidebar.style.zIndex = '';
                    sidebar.style.transform = '';

                    document.documentElement.style.overflowX = '';
                    if (mainContent) mainContent.style.marginLeft = '';
                }

            }
        );

    }
);

</script>

</body>
</html>