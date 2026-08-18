<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Login - CV Database</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-100 flex items-center justify-center px-4">

    <div class="w-full max-w-md">

        <!-- Logo / Brand -->
        <div class="text-center mb-8">

            <div class="inline-flex items-center justify-center mb-4">
                <div class="bg-white rounded-xl shadow-md px-4 py-2 flex items-center justify-center">
                    <img src="{{ asset('images/abzuamanpower.png') }}" alt="CV Database Logo"
                         class="h-14 sm:h-20 md:h-24 object-contain" />
                </div>
            </div>

            {{-- <h1 class="text-2xl font-bold text-[#6da651] ">
                CV Database
            </h1> --}}

            {{-- <p class="text-sm text-slate-500 mt-1">
                Super Admin Login
            </p> --}}

        </div>


        <!-- Login Card -->
        <div class="bg-white border border-slate-200
                    rounded-2xl shadow-sm p-6 sm:p-8">

            <div class="mb-6">

                <h2 class="text-xl font-bold text-[#6da651]">
                    Welcome back
                </h2>

                <p class="text-sm text-slate-500 mt-1">
                    Sign in to access the admin dashboard.
                </p>

            </div>


            <!-- Validation Errors -->
            @if ($errors->any())

                <div class="mb-5 rounded-lg bg-red-50
                            border border-red-200 p-3">

                    <div class="text-sm text-red-700">

                        @foreach ($errors->all() as $error)

                            <p>{{ $error }}</p>

                        @endforeach

                    </div>

                </div>

            @endif


            <!-- Login Form -->
            <form method="POST" action="{{ route('login') }}">

                @csrf


                <!-- Email -->
                <div class="mb-5">

                    <label
                        for="email"
                        class="block text-sm font-medium
                               text-slate-700 mb-2"
                    >
                        Email Address
                    </label>

                    <input
                        id="email"
                        type="text"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="email"
                        placeholder="Enter your email"
                        class="w-full px-4 py-3
                               border border-slate-300
                               rounded-lg
                               text-sm
                               focus:outline-none
                               focus:ring-2
                               focus:ring-[#6da651]
                               focus:border-[indigo-500]"
                    >

                </div>


                <!-- Password -->
                <div class="mb-5">

                    <label
                        for="password"
                        class="block text-sm font-medium
                               text-slate-700 mb-2"
                    >
                        Password
                    </label>

                    <input
                        id="password"
                        type="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        placeholder="Enter your password"
                        class="w-full px-4 py-3
                               border border-slate-300
                               rounded-lg
                               text-sm
                               focus:outline-none
                               focus:ring-2
                               focus:ring-[#6da651]
                               focus:border-[#6da651]"
                    >

                </div>


                <!-- Remember Me -->
                <div class="flex items-center justify-between mb-6">

                    <label class="flex items-center gap-2 cursor-pointer">

                        <input
                            type="checkbox"
                            name="remember"
                            class="w-4 h-4
                                   text-[#6da651]
                                   border-slate-300
                                   rounded
                                   focus:ring-[#6da651]"
                        >

                        <span class="text-sm text-slate-600">
                            Remember me
                        </span>

                    </label>


                    {{-- @if (Route::has('password.request'))

                        <a
                            href="{{ route('password.request') }}"
                            class="text-sm font-medium
                                   text-[#6da651]
                                   hover:text-[#5a8a44]"
                        >
                            Forgot password?
                        </a>

                    @endif --}}

                </div>


                <!-- Login Button -->
                <button
                    type="submit"
                    class="w-full py-3 px-4
                           bg-[#6da651]
                           hover:bg-[#5a8a44]
                           text-white
                           font-semibold
                           rounded-lg
                           shadow-sm
                           transition"
                >
                    Sign In
                </button>

            </form>

        </div>


        <!-- Footer -->
        {{-- <p class="text-center text-xs text-slate-400 mt-6">
            © {{ date('Y') }} CV Database. All rights reserved.
        </p> --}}

    </div>

</body>
</html>