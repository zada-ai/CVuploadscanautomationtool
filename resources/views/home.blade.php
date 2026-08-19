<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>CV Database | Upload Your CV</title>
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

    function showSelectedFile(input) {
        const fileName = document.getElementById('selected-file');

        if (input.files && input.files.length > 0) {
            fileName.textContent = 'Selected: ' + input.files[0].name;
            fileName.classList.remove('hidden');
        } else {
            fileName.textContent = '';
            fileName.classList.add('hidden');
        }
    }

  </script>
</head>
<body class="bg-slate-50 text-slate-800 font-sans min-h-screen flex flex-col justify-between antialiased">
  
  <!-- Topbar Header -->
  <header class="bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between shadow-sm sticky top-0 z-50">
    <div class="flex items-center gap-3">
      <div class="bg-[#6da551] text-white font-bold text-sm w-9 h-9 flex items-center justify-center rounded-lg shadow-inner">
        CV
      </div>
      <div>
        <span class="font-bold text-slate-900 tracking-tight text-lg">CV Database</span>
      </div>
    </div>
    
    <nav class="flex items-center gap-1 sm:gap-2">
      {{-- <a href="index.html" class="px-3 py-2 text-sm font-medium text-indigo-600 rounded-md bg-indigo-50">Admin Pannel</a> --}}
      {{-- <a href="#about" class="px-3 py-2 text-sm font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-md transition-colors">About</a> --}}
      <a href="{{ route('admin.dashboard') }}" class="px-3 py-2 text-sm font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-md transition-colors">Admin Pannel</a>
      @auth
          @if (auth()->user()->isAdmin())
              <a href="{{ route('admin.dashboard') }}" class="ml-2 px-4 py-2 text-sm font-medium text-white bg-slate-900 hover:bg-slate-800 rounded-md shadow-sm transition-colors">Admin</a>
          @endif
      @endauth
    </nav>
  </header>

  <!-- Main Container -->
  <main class="max-w-3xl mx-auto px-4 py-12 w-full flex-grow flex flex-col justify-center items-center">
    
       <!-- Hero Section -->
       <!-- Upload Card -->

      @if(session('success'))
        <div class="w-full mb-6">
          <div class="max-w-3xl mx-auto px-4">
            <div class="w-full bg-green-50 border border-green-200 rounded-2xl p-6 text-center">
              <div class="flex items-center justify-center mb-3">
                <div class="bg-green-100 text-green-600 rounded-full p-2 w-12 h-12 flex items-center justify-center">
                  <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  </svg>
                </div>
              </div>

              <h3 class="text-lg font-semibold text-green-800">CV Uploaded Successfully!</h3>
              <p class="text-sm text-green-700 mt-2">Thank you so much — our team will contact you.</p>
            </div>
          </div>
        </div>
      @endif

      @unless(session('success'))
      <form
        action="{{ route('cv.upload') }}"
        method="POST"
        enctype="multipart/form-data"
        class="w-full"
      >
        @csrf

        <section
          class="w-full bg-white border-2 border-dashed border-slate-300 hover:border-[#6da651] rounded-2xl p-8 sm:p-12 text-center transition-all shadow-sm hover:shadow-md flex flex-col items-center group"
        >

          <!-- Icon Wrapper -->
          <div
            class="w-16 h-16 bg-[#6da651] text-white rounded-full flex items-center justify-center mb-4 group-hover:scale-110 transition-transform"
            aria-hidden="true"
          >
            <svg
              class="w-8 h-8"
              viewBox="0 0 24 24"
              fill="none"
              xmlns="http://www.w3.org/2000/svg"
            >
              <path
                d="M7 16L12 11L17 16"
                stroke="currentColor"
                stroke-width="1.8"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
              <path
                d="M12 11V21"
                stroke="currentColor"
                stroke-width="1.8"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
              <path
                d="M5 11H19"
                stroke="currentColor"
                stroke-width="1.8"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
              <path
                d="M5 9C5 6.23858 7.23858 4 10 4H14C16.7614 4 19 6.23858 19 9V16C19 18.2091 17.2091 20 15 20H9C6.79086 20 5 18.2091 5 16V9Z"
                stroke="currentColor"
                stroke-width="1.8"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
            </svg>
          </div>

          <h2 class="text-xl font-bold text-[#6da551] mb-1">
            Drag &amp; Drop your CV here
          </h2>

          <p class="text-sm text-slate-400 mb-4">
            or
          </p>

          <!-- File Input -->
          <label
            class="inline-flex items-center justify-center px-5 py-2.5 bg-[#6da551] hover:bg-[#5a8a44] text-white text-sm font-semibold rounded-lg shadow-sm cursor-pointer transition-colors focus-within:ring-2 focus-within:ring-indigo-500 focus-within:ring-offset-2"
          >
            <input
              type="file"
              name="cv"
              accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.webp"
              class="sr-only"
              required
              onchange="showSelectedFile(this)"
            />

            Choose File
          </label>

          <!-- Selected File Name -->
          <p
            id="selected-file"
            class="text-sm text-indigo-600 font-medium mt-3 hidden"
          ></p>

          <p class="text-xs text-slate-400 mt-4">
            PDF, DOC, DOCX, JPG, JPEG • Max file size: 10MB
          </p>

          <!-- Upload Button -->
          <button
            type="submit"
            class="mt-5 inline-flex items-center justify-center px-6 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors"
          >
            Upload CV
          </button>

        </section>
      </form>
      @endunless



    <!-- Footer Note -->
    <p class="text-xs text-slate-500 text-center mt-4">Your CV information will be processed automatically.</p>
  </main>

  <!-- Footer -->
  {{-- <footer class="border-t border-slate-200 py-6 text-center text-xs text-slate-500 bg-white" id="contact">
    <p>CV Database &mdash; Demo recruitment frontend</p>
  </footer> --}}

</body>

</html>