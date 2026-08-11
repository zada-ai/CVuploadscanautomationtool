<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>CV Database | Admin Dashboard</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
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
    }
    
  </script>
</head>
<body class="bg-slate-100 text-slate-800 font-sans min-h-screen antialiased">

  <div class="flex flex-col md:flex-row min-h-screen">

    <!-- Mobile Top Bar (only visible below md, since sidebar is hidden there) -->
    <div class="md:hidden flex items-center justify-between bg-slate-900 text-white px-4 py-3 w-full sticky top-0 z-20">
      <div class="flex items-center gap-2">
        <div class="bg-indigo-600 text-white font-bold text-xs w-8 h-8 flex items-center justify-center rounded-lg shadow-inner">
          DB
        </div>
        <span class="font-bold text-base tracking-tight text-white">CV Database</span>
      </div>

      <form method="POST" action="{{ route('logout') }}" class="m-0">
        @csrf
        <button type="submit" class="text-xs font-medium text-red-400 hover:text-red-300 bg-transparent border-none p-0">
          Logout ›
        </button>
      </form>
    </div>

    <!-- Sidebar -->
    <aside
    id="desktop-sidebar"
    class="w-64 bg-slate-900 text-white flex-shrink-0 hidden md:flex flex-col justify-between p-6
           transition-transform duration-300 ease-in-out relative"
>

    <!-- Collapse Button -->
    <button
        type="button"
        id="sidebar-toggle"
        class="absolute -right-3 top-6 w-7 h-7 bg-indigo-600
               text-white rounded-full flex items-center justify-center
               shadow-lg hover:bg-indigo-700 transition-colors z-30"
        title="Collapse Sidebar"
    >
        ‹
    </button>


    <div>

        <div class="flex items-center gap-3 mb-8">

            <div
                class="bg-indigo-600 text-white font-bold text-sm
                       w-9 h-9 flex items-center justify-center
                       rounded-lg shadow-inner"
            >
                DB
            </div>

            <span class="font-bold text-lg tracking-tight text-white">
                CV Database
            </span>

        </div>


        <nav class="space-y-1">

            <a
                href="{{ route('admin.dashboard') }}"
                class="flex items-center justify-between px-4 py-2.5
                       text-sm font-medium bg-indigo-600 text-white
                       rounded-lg shadow-sm"
            >
                Dashboard
                <span>›</span>
            </a>

        </nav>

    </div>


    <!-- Logout -->

    <form method="POST" action="{{ route('logout') }}" class="w-full">

        @csrf

        <button
            type="submit"
            class="flex items-center justify-between w-full
                   px-4 py-2.5 text-sm font-medium text-red-400
                   hover:bg-slate-800 rounded-lg transition-colors"
        >
            Logout
            <span>›</span>
        </button>

    </form>

</aside>

    <!-- Dashboard Content Area -->
    <div class="flex-grow p-4 sm:p-6 md:p-10 overflow-x-auto w-full">
      
      <!-- Dashboard Header -->
      <header class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 mb-8">
        <div>
          <p class="text-xs font-semibold uppercase tracking-wider text-indigo-600 mb-1">Recruitment Dashboard</p>
          <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">All Candidates</h1>
          <p class="text-slate-500 text-sm mt-1">Browse and search all uploaded CVs in a polished and responsive admin interface.</p>
        </div>

        <!-- Search Form -->
        <div class="w-full lg:w-auto">
       
<!-- Search Form -->
<div class="w-full lg:w-auto">

  <form id="search-form" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 w-full" action="#" method="GET">

    <!-- Search Type -->
    <select
        id="search-by"
        name="search_by"
        class="w-full sm:w-auto px-4 py-2 text-sm bg-white border border-slate-300 rounded-lg
               focus:ring-2 focus:ring-indigo-500 focus:outline-none shadow-sm"
    >
        <option value="profession">Profession</option>
        <option value="skills">Skills</option>
    </select>

    <!-- Search Input -->
    <input
        id="search-input"
        name="search"
        class="w-full sm:w-full lg:w-64 px-4 py-2 text-sm bg-white border border-slate-300 rounded-lg
               focus:ring-2 focus:ring-indigo-500 focus:outline-none shadow-sm"
        type="search"
        placeholder="Search by profession..."
        autocomplete="off"
    />

    <!-- Search Button -->
    <button
        id="search-button"
        type="button"
        class="w-full sm:w-auto px-4 py-2 text-sm font-medium bg-indigo-600 text-white
               hover:bg-indigo-700 rounded-lg shadow-sm transition-colors"
    >
        Search
    </button>

</form>

<div id="search-status" class="text-xs text-slate-500 mt-2">
    Showing all candidates
</div>

    {{-- <div id="search-status" class="text-xs text-slate-500 mt-2">
        Showing all candidates
    </div> --}}

</div>

{{-- <div
    id="search-status"
    class="text-xs text-slate-500 mt-2"
>
    Showing all candidates
</div> --}}

          {{-- <div class="text-xs text-slate-500 mt-2">Showing results for: <strong class="text-slate-800">Civil Engineer</strong></div>
        </div> --}}
      </header>
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">

    <div id="selection-status" class="text-sm text-slate-500">
        0 CVs selected
    </div>

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
            class="w-full sm:w-auto px-4 py-2 text-sm font-semibold bg-indigo-600 text-white rounded-lg
                   hover:bg-indigo-700 disabled:bg-slate-300 disabled:cursor-not-allowed
                   transition-colors"
        >
            Download Selected CVs
        </button>
    </form>

</div>
      <!-- Table Card -->
      <section class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden" id="all">
        <div class="overflow-x-auto">
          <table class="w-full min-w-[900px] text-left border-collapse">
            <thead>
              <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                <!-- Checkbox Header -->
                <th class="py-3.5 px-4 w-10 text-center">
                  <input type="checkbox" id="select-all" class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500 cursor-pointer" />
                </th>
                <th class="py-3.5 px-4">Candidate</th>
                <th class="py-3.5 px-4">Contact</th>
                <th class="py-3.5 px-4">Profession</th>
                <th class="py-3.5 px-4">Experience</th>
                <th class="py-3.5 px-4">Skills</th>
                <th class="py-3.5 px-4">Education</th>
                <th class="py-3.5 px-4">Uploaded</th>
                <th class="py-3.5 px-4 text-center">Actions</th>
              </tr>
            </thead>
          <tbody
    id="candidates-table-body"
    class="divide-y divide-slate-200 text-sm"
>

@forelse($candidates as $candidate)

<tr class="hover:bg-slate-50 transition-colors">

    <td class="py-4 px-4 text-center">
      <input
    type="checkbox"
    name="candidate_ids[]"
    value="{{ $candidate->id }}"
    class="row-checkbox w-4 h-4 text-indigo-600 rounded border-slate-300 cursor-pointer"
/>
    </td>

    <td class="py-4 px-4">
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

    <td class="py-4 px-4 text-xs text-slate-600">
        <div>{{ $candidate->email ?: 'No email' }}</div>

        <div class="text-slate-400">
            {{ $candidate->phone ?: 'No phone' }}
        </div>
    </td>

    <td class="py-4 px-4 font-medium text-slate-700">
        {{ $candidate->profession ?: 'Not specified' }}
    </td>

    <td class="py-4 px-4 text-slate-600">
        {{ $candidate->experience ?: 'Not specified' }}
    </td>

    <td class="py-4 px-4">

        <div class="flex flex-wrap gap-1">

            @forelse($candidate->skills as $skill)

                <span class="px-2 py-0.5 text-xs bg-slate-100 text-slate-700 rounded border border-slate-200">
                    {{ $skill->skill }}
                </span>

            @empty

                <span class="text-xs text-slate-400">
                    No skills found
                </span>

            @endforelse

        </div>

    </td>

    <td class="py-4 px-4 text-slate-600">
        {{ $candidate->education ?: 'Not specified' }}
    </td>

    <td class="py-4 px-4 text-xs text-slate-500 whitespace-nowrap">
        {{ $candidate->created_at->format('d M Y') }}
    </td>

    <td class="py-4 px-4 text-center">

        <div class="flex items-center justify-center gap-2">

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
    <td colspan="9" class="py-10 text-center text-slate-500">
        No CVs uploaded yet.
    </td>
</tr>

@endforelse

</tbody>
          </table>
        </div>
      </section>

    </div>   
  </div>

  <!-- Select All JavaScript Handler -->
<script>
document.addEventListener('DOMContentLoaded', function () {

    const searchForm = document.getElementById('search-form');
    const searchBy = document.getElementById('search-by');
    const searchInput = document.getElementById('search-input');
    const searchButton = document.getElementById('search-button');
    const searchStatus = document.getElementById('search-status');

    const tableBody = document.getElementById('candidates-table-body');
    const selectAllCheckbox = document.getElementById('select-all');

    const selectionStatus = document.getElementById('selection-status');
    const downloadButton = document.getElementById('bulk-download-btn');
    const selectedCandidates = document.getElementById('selected-candidates');

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function () {
            toggleAllCheckboxes(this);
        });
    }


    /*
    |--------------------------------------------------------------------------
    | Search Dropdown Change
    |--------------------------------------------------------------------------
    */

    searchBy.addEventListener('change', function () {

        searchInput.value = '';

        if (this.value === 'skills') {

            searchInput.placeholder = 'Search by skills...';

        } else {

            searchInput.placeholder = 'Search by profession...';

        }

        // Empty search = show all candidates
        performSearch();

    });


    /*
    |--------------------------------------------------------------------------
    | Search Button
    |--------------------------------------------------------------------------
    */

    searchButton.addEventListener('click', function () {

        performSearch();

    });


    /*
    |--------------------------------------------------------------------------
    | Press Enter To Search
    |--------------------------------------------------------------------------
    */

    searchForm.addEventListener('submit', function (event) {

        event.preventDefault();

        performSearch();

    });


    /*
    |--------------------------------------------------------------------------
    | Search While Typing
    |--------------------------------------------------------------------------
    |
    | User kuch bhi type karega to results automatically update honge.
    |
    */

    let searchTimer;

    searchInput.addEventListener('input', function () {

        clearTimeout(searchTimer);

        searchTimer = setTimeout(function () {

            performSearch();

        }, 400);

    });


    /*
    |--------------------------------------------------------------------------
    | Main Search Function
    |--------------------------------------------------------------------------
    */

    function performSearch() {

        const searchType = searchBy.value;

        const searchValue = searchInput.value.trim();


        /*
        |--------------------------------------------------------------------------
        | Build URL
        |--------------------------------------------------------------------------
        */

        const url = new URL(
            "{{ route('admin.search') }}",
            window.location.origin
        );

        url.searchParams.set('search_by', searchType);
        url.searchParams.set('search', searchValue);


        /*
        |--------------------------------------------------------------------------
        | Loading
        |--------------------------------------------------------------------------
        */

        searchButton.disabled = true;
        searchButton.textContent = 'Searching...';

        searchStatus.textContent = 'Searching candidates...';


        /*
        |--------------------------------------------------------------------------
        | AJAX Request
        |--------------------------------------------------------------------------
        */

        fetch(url.toString(), {

            method: 'GET',

            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }

        })

        .then(function (response) {

            if (!response.ok) {

                throw new Error('Search request failed.');

            }

            return response.json();

        })

        .then(function (data) {

            /*
            |--------------------------------------------------------------------------
            | Empty Search
            |--------------------------------------------------------------------------
            |
            | Agar search box empty hai to ALL candidates show honge.
            |
            */

            if (searchValue === '') {

                searchStatus.textContent =
                    `Showing all candidates (${data.count})`;

            }

            /*
            |--------------------------------------------------------------------------
            | Search Result
            |--------------------------------------------------------------------------
            */

            else {

                const typeText =
                    searchType === 'skills'
                        ? 'skills'
                        : 'profession';


                searchStatus.textContent =
                    `Showing ${data.count} result(s) for ${typeText}: "${searchValue}"`;

            }


            /*
            |--------------------------------------------------------------------------
            | Show Results
            |--------------------------------------------------------------------------
            */

            renderCandidates(data.candidates || []);

        })

        .catch(function (error) {

            console.error('Search Error:', error);

            searchStatus.textContent =
                'Something went wrong while searching.';

        })

        .finally(function () {

            searchButton.disabled = false;
            searchButton.textContent = 'Search';

        });

    }


    /*
    |--------------------------------------------------------------------------
    | Render Candidates In Table
    |--------------------------------------------------------------------------
    */

    function renderCandidates(candidates) {

        /*
        |--------------------------------------------------------------------------
        | No Results
        |--------------------------------------------------------------------------
        */

        if (candidates.length === 0) {

            tableBody.innerHTML = `

                <tr>

                    <td
                        colspan="9"
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


        /*
        |--------------------------------------------------------------------------
        | Generate Candidate Rows
        |--------------------------------------------------------------------------
        */

        tableBody.innerHTML = candidates.map(function (candidate) {


            /*
            |--------------------------------------------------------------------------
            | Candidate Name
            |--------------------------------------------------------------------------
            */

            const fullName =
                candidate.full_name || 'Not specified';


            /*
            |--------------------------------------------------------------------------
            | Initials
            |--------------------------------------------------------------------------
            */

            const initials =
                fullName
                    .substring(0, 2)
                    .toUpperCase();


            /*
            |--------------------------------------------------------------------------
            | Skills
            |--------------------------------------------------------------------------
            */

            let skillsHtml = '';


            if (
                candidate.skills &&
                candidate.skills.length > 0
            ) {

                skillsHtml =
                    candidate.skills.map(function (skill) {

                        return `
                            <span
                                class="px-2 py-0.5 text-xs
                                       bg-slate-100 text-slate-700
                                       rounded border border-slate-200"
                            >
                                ${escapeHtml(skill)}
                            </span>
                        `;

                    }).join('');

            } else {

                skillsHtml = `
                    <span class="text-xs text-slate-400">
                        No skills found
                    </span>
                `;

            }


            /*
            |--------------------------------------------------------------------------
            | CV URL
            |--------------------------------------------------------------------------
            */

            let cvUrl = '#';

            if (candidate.cv_file) {

                cvUrl =
                    "{{ asset('storage') }}/" +
                    candidate.cv_file;

            }


            /*
            |--------------------------------------------------------------------------
            | Return Table Row
            |--------------------------------------------------------------------------
            */

            return `

                <tr class="border-t border-slate-100">


                    <!-- Checkbox -->

                    <td class="py-4 px-4 text-center">

                        <input
                            type="checkbox"
                            name="candidate_ids[]"
                            value="${candidate.id}"
                            class="row-checkbox w-4 h-4
                                   text-indigo-600 rounded
                                   border-slate-300 cursor-pointer"
                        >

                    </td>


                    <!-- Candidate -->

                    <td class="py-4 px-4">

                        <div class="flex items-center gap-3">

                            <div
                                class="w-10 h-10 rounded-full
                                       bg-indigo-100 text-indigo-700
                                       font-bold text-sm
                                       flex items-center justify-center
                                       flex-shrink-0"
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

                    <td class="py-4 px-4 text-slate-600">

                        ${escapeHtml(
                            candidate.experience ||
                            'Not specified'
                        )}

                    </td>


                    <!-- Skills -->

                    <td class="py-4 px-4">

                        <div class="flex flex-wrap gap-1">

                            ${skillsHtml}

                        </div>

                    </td>


                    <!-- Education -->

                    <td class="py-4 px-4 text-slate-600">

                        ${escapeHtml(
                            candidate.education ||
                            'Not specified'
                        )}

                    </td>


                    <!-- Date -->

                    <td
                        class="py-4 px-4 text-xs text-slate-500
                               whitespace-nowrap"
                    >

                        ${escapeHtml(
                            candidate.created_at || ''
                        )}

                    </td>


                    <!-- View / Download -->

                    <td class="py-4 px-4 text-center">

                        <div
                            class="flex items-center
                                   justify-center gap-2"
                        >

                            <a
                                href="${cvUrl}"
                                target="_blank"
                                class="px-3 py-1.5 text-xs
                                       font-semibold
                                       bg-indigo-50 text-indigo-600
                                       hover:bg-indigo-100
                                       rounded-md"
                            >
                                View
                            </a>


                            <a
                                href="${cvUrl}"
                                download="${escapeHtml(
                                    candidate.cv_original_name ||
                                    'cv'
                                )}"
                                class="px-3 py-1.5 text-xs
                                       font-semibold
                                       bg-emerald-50
                                       text-emerald-600
                                       hover:bg-emerald-100
                                       rounded-md"
                            >
                                Download
                            </a>

                        </div>

                    </td>

                </tr>

            `;

        }).join('');


        /*
        |--------------------------------------------------------------------------
        | Reinitialize Checkboxes
        |--------------------------------------------------------------------------
        */

        initializeCheckboxes();

    }


    /*
    |--------------------------------------------------------------------------
    | Checkbox Setup
    |--------------------------------------------------------------------------
    */

    function initializeCheckboxes() {

        const checkboxes =
            document.querySelectorAll('.row-checkbox');


        checkboxes.forEach(function (checkbox) {

            checkbox.addEventListener(
                'change',
                updateSelection
            );

        });


        updateSelection();

    }


    /*
    |--------------------------------------------------------------------------
    | Update Selected CVs
    |--------------------------------------------------------------------------
    */

    function updateSelection() {

        const checkboxes =
            document.querySelectorAll('.row-checkbox');


        const selected =
            Array.from(checkboxes)
                .filter(function (checkbox) {

                    return checkbox.checked;

                });

        if (selectAllCheckbox) {
            selectAllCheckbox.checked =
                selected.length > 0 &&
                selected.length === checkboxes.length;
        }


        /*
        |--------------------------------------------------------------------------
        | Selection Counter
        |--------------------------------------------------------------------------
        */

        selectionStatus.textContent =
            `${selected.length} CVs selected`;


        /*
        |--------------------------------------------------------------------------
        | Download Button
        |--------------------------------------------------------------------------
        */

        downloadButton.disabled =
            selected.length === 0;


        /*
        |--------------------------------------------------------------------------
        | Hidden Candidate IDs
        |--------------------------------------------------------------------------
        */

        selectedCandidates.innerHTML = '';


        selected.forEach(function (checkbox) {

            const hiddenInput =
                document.createElement('input');

            hiddenInput.type = 'hidden';

            hiddenInput.name =
                'candidate_ids[]';

            hiddenInput.value =
                checkbox.value;

            selectedCandidates.appendChild(
                hiddenInput
            );

        });

    }


    /*
    |--------------------------------------------------------------------------
    | Reset Selection
    |--------------------------------------------------------------------------
    */

    function resetSelection() {

        selectedCandidates.innerHTML = '';

        selectionStatus.textContent =
            '0 CVs selected';

        downloadButton.disabled = true;

        if (selectAllCheckbox) {
            selectAllCheckbox.checked = false;
        }

    }


    /*
    |--------------------------------------------------------------------------
    | HTML Escape
    |--------------------------------------------------------------------------
    */

    function toggleAllCheckboxes(source) {

        const checkboxes =
            document.querySelectorAll('.row-checkbox');

        checkboxes.forEach(function (checkbox) {
            checkbox.checked = source.checked;
        });

        updateSelection();

    }


    function escapeHtml(value) {

        if (
            value === null ||
            value === undefined
        ) {
            return '';
        }

        const div =
            document.createElement('div');

        div.textContent =
            String(value);

        return div.innerHTML;

    }


    /*
    |--------------------------------------------------------------------------
    | Initial Setup
    |--------------------------------------------------------------------------
    */

    initializeCheckboxes();

});

document.addEventListener('DOMContentLoaded', function () {

    const sidebar =
        document.getElementById('desktop-sidebar');

    const toggleButton =
        document.getElementById('sidebar-toggle');


    if (!sidebar || !toggleButton) {
        return;
    }


    toggleButton.addEventListener('click', function () {

        /*
        |--------------------------------------------------------------------------
        | Collapse
        |--------------------------------------------------------------------------
        */

        sidebar.classList.toggle('-translate-x-full');


        /*
        |--------------------------------------------------------------------------
        | Change Arrow
        |--------------------------------------------------------------------------
        */

        if (sidebar.classList.contains('-translate-x-full')) {

            toggleButton.innerHTML = '›';

            toggleButton.title =
                'Open Sidebar';

        } else {

            toggleButton.innerHTML = '‹';

            toggleButton.title =
                'Collapse Sidebar';

        }

    });

});

</script>


</body>
</html>