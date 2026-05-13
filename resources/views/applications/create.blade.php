<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application | Agusan National High School Enrollment</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        html {
            scroll-behavior: smooth;
        }

        section[id] {
            scroll-margin-top: 100px;
        }
    </style>
</head>

<body class="min-h-screen flex flex-col bg-gray-100">

    <header class="fixed top-0 left-0 right-0 w-full bg-white/95 backdrop-blur-sm shadow-sm border-b border-gray-200 z-50">
        <div class="container mx-auto px-4 py-4 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center pl-6">
                <img src="{{ asset('images/school-logo-light.png') }}" alt="School Logo" class="h-12 w-auto">
            </div>

            <nav class="flex items-center flex-wrap justify-center gap-10 sm:gap-16 pr-10">
                <a href="{{ route('home') }}" class="text-[#0C2C55] hover:text-[#0C2C55]/80 font-medium transition duration-200 text-sm sm:text-base uppercase tracking-wide">Home</a>
                <a href="#faq" class="text-[#0C2C55] hover:text-[#0C2C55]/80 font-medium transition duration-200 text-sm sm:text-base uppercase tracking-wide">FAQ</a>
                <a href="#contact-us" class="text-[#0C2C55] hover:text-[#0C2C55]/80 font-medium transition duration-200 text-sm sm:text-base uppercase tracking-wide">Contact Us</a>
            </nav>
        </div>
    </header>

    <div class="min-h-screen flex flex-col relative pt-20" style="background-image: url('{{ asset('images/background-blue.jpg') }}'); background-size: cover; background-position: center; background-repeat: no-repeat; background-attachment: fixed;">
<div class="flex-1 flex items-center justify-center py-8 px-4 relative z-10">
            <div class="w-full max-w-xl">
                <div class="bg-white shadow-xl rounded-lg px-8 pt-8 pb-10">

                    @if ($errors->any())
                        <div class="mb-4 p-3 rounded bg-red-50 border-l-4 border-red-500 text-red-700 text-sm">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <h1 class="text-3xl font-bold text-blue-800 tracking-tight mb-2">APPLICATION</h1>
                    <p class="text-sm text-gray-500 mb-6">Submit your details for admin verification before account credentials are issued.</p>

                    <form action="{{ route('register.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @csrf

                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="lrn">LRN</label>
                            <input id="lrn" name="lrn" value="{{ old('lrn') }}" placeholder="e.g. 198765150721" minlength="12" maxlength="12" inputmode="numeric" pattern="\d{12}" required
                                class="shadow-sm appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200">
                        </div>

                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="birthdate">Birthdate</label>
                            <input id="birthdate" type="date" name="birthdate" value="{{ old('birthdate') }}" required
                                class="shadow-sm appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200">
                        </div>

                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="first_name">First Name</label>
                            <input id="first_name" name="first_name" value="{{ old('first_name') }}" required
                                class="shadow-sm appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200">
                        </div>

                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="middle_name">Middle Name</label>
                            <input id="middle_name" name="middle_name" value="{{ old('middle_name') }}" placeholder="(optional)"
                                class="shadow-sm appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200">
                        </div>

                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="last_name">Last Name</label>
                            <input id="last_name" name="last_name" value="{{ old('last_name') }}" required
                                class="shadow-sm appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200">
                        </div>

                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="suffix">Suffix</label>
                            <input id="suffix" name="suffix" value="{{ old('suffix') }}" placeholder="(optional)"
                                class="shadow-sm appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200">
                        </div>

                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="email">Email</label>
                            <input id="email" type="email" name="email" placeholder="(optional)" value="{{ old('email') }}"
                                class="shadow-sm appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200">
                        </div>

                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="contact_no">Contact Number</label>
                            <input id="contact_no" name="contact_no" value="{{ old('contact_no') }}"
                                class="shadow-sm appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200">
                        </div>

                        <div class="md:col-span-2">
                            <button class="w-full bg-emerald-700 hover:bg-emerald-800 text-white font-bold py-3 px-4 rounded focus:outline-none focus:ring-4 focus:ring-emerald-300 transition duration-300 transform active:scale-95" type="submit">
                                Submit Application
                            </button>
                            <div class="mt-4 text-center">
                                <a href="{{ route('login') }}" class="text-xs text-[#0C2C55] font-semibold hover:underline">Back to Login</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <section id="faq" class="py-16 px-4 bg-gray-50/95 backdrop-blur-sm" x-data="{ open: 0 }">
        <div class="container mx-auto max-w-4xl">
            <h2 class="text-4xl font-bold text-[#0C2C55] mb-12 text-center">Frequently Asked Questions</h2>

            <div class="space-y-4">
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <button class="w-full px-6 py-4 text-left flex justify-between items-center hover:bg-gray-50 transition duration-200" @click="open = open === 1 ? 0 : 1">
                        <span class="font-semibold text-[#0C2C55]">How do I apply?</span>
                        <svg class="w-5 h-5 text-[#0C2C55] transform transition-transform duration-300" :class="{ 'rotate-180': open === 1 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div class="px-6 pb-4" x-show="open === 1" x-transition>
                        <p class="text-gray-700 leading-relaxed">Complete the application form with your LRN and personal details. Admin will review and verify your submission.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="contact-us" class="py-16 px-4 bg-[#0C2C55] text-white">
        <div class="container mx-auto max-w-6xl">
            <h2 class="text-4xl font-bold mb-12 text-center">Contact Us</h2>
            <div class="text-center">
                <p class="text-white/80">Please contact the registrar or IT office for application and account assistance.</p>
            </div>
        </div>
    </section>

</body>

</html>
