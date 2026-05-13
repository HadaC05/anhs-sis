<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agusan National High School Enrollment</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        html {
            scroll-behavior: smooth;
        }

        /* Add scroll offset for fixed header */
        section[id] {
            scroll-margin-top: 100px;
        }
    </style>
</head>

<body class="min-h-screen flex flex-col bg-gray-100"
    x-data="{ showStatusModal: {{ session('application_status_message') || $errors->statusCheck->has('status_lookup') || $errors->statusCheck->has('status_lrn') || $errors->statusCheck->has('status_birthdate') ? 'true' : 'false' }} }">

    <!-- Header with Logo and Navigation - Fixed at top -->
    <header class="fixed top-0 left-0 right-0 w-full bg-white/95 backdrop-blur-sm shadow-sm border-b border-gray-200 z-50">
        <div class="container mx-auto px-4 py-4 flex flex-col sm:flex-row items-center justify-between gap-4">
            <!-- Logo on the left -->
            <div class="flex items-center pl-6">
                <img src="{{ asset('images/school-logo-light.png') }}" alt="School Logo" class="h-12 w-auto">
            </div>

            <!-- Navigation links on the right -->
            <nav class="flex items-center flex-wrap justify-center gap-10 sm:gap-16 pr-10">
                <a href="{{ route('home') }}" class="text-[#0C2C55] hover:text-[#0C2C55]/80 font-medium transition duration-200 text-sm sm:text-base uppercase tracking-wide">Home</a>
                <a href="#about-us" class="text-[#0C2C55] hover:text-[#0C2C55]/80 font-medium transition duration-200 text-sm sm:text-base uppercase tracking-wide">About</a>
                <a href="#faq" class="text-[#0C2C55] hover:text-[#0C2C55]/80 font-medium transition duration-200 text-sm sm:text-base uppercase tracking-wide">FAQ</a>
                <a href="#contact-us" class="text-[#0C2C55] hover:text-[#0C2C55]/80 font-medium transition duration-200 text-sm sm:text-base uppercase tracking-wide">Contact Us</a>
                <a href="{{ route('login') }}" class="rounded-full bg-[#0C2C55] px-5 py-2 text-sm font-semibold uppercase tracking-wide text-white transition hover:bg-[#143d73]">Login</a>
            </nav>
        </div>
    </header>

    <!-- Landing Hero -->
    <div class="min-h-screen flex flex-col relative pt-20" style="background-image: url('{{ asset('images/high-school-image.jpg') }}'); background-size: cover; background-position: center; background-repeat: no-repeat; background-attachment: fixed;">
        <div class="absolute inset-0 bg-gradient-to-r from-[#0C2C55]/90 via-[#0C2C55]/75 to-[#0C2C55]/30"></div>

        <div class="flex-1 flex items-center relative z-10">
            <div class="container mx-auto px-8 md:px-16 lg:px-24 py-16">
                <div class="max-w-3xl text-white">
                    <p class="mb-4 text-sm font-semibold uppercase tracking-[0.35em] text-white/80">Welcome</p>
                    <h1 class="mb-6 text-4xl font-bold leading-tight sm:text-5xl lg:text-6xl">
                        Welcome to the Agusan National High School Student Information System
                    </h1>
                    {{-- <p class="mb-8 text-lg leading-relaxed text-white/85 sm:text-xl">
                        Start your application, review enrollment information, and check your approval status from one place.
                    </p> --}}
                </div>
            </div>
        </div>
        <button type="button"
            @click="showStatusModal = true"
            class="fixed bottom-6 left-6 z-50 bg-blue-700 hover:bg-blue-800 text-white font-semibold px-5 py-3 rounded-full shadow-lg focus:outline-none focus:ring-4 focus:ring-blue-300">
            Check Application Status
        </button>
    </div>

    <div x-show="showStatusModal"
        x-transition
        class="fixed inset-0 z-[60] bg-black/50 flex items-center justify-center p-4"
        @click.self="showStatusModal = false"
        x-cloak>
        <div class="w-full max-w-md bg-white rounded-lg shadow-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-[#0C2C55]">Application Status</h3>
                <button type="button" @click="showStatusModal = false" class="text-gray-500 hover:text-gray-700 text-xl leading-none">&times;</button>
            </div>

            @if (session('application_status_message'))
            <div class="mb-4 p-3 rounded bg-blue-50 border-l-4 border-blue-500 text-blue-700 text-sm">
                {{ session('application_status_message') }}
            </div>
            @endif

            @if (session('application_status_enrollment_year'))
            <div class="mb-4 rounded border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900 space-y-3">
                <div>
                    <p class="font-semibold">Login Instructions</p>
                    <p>Use these instructions to sign in, then change your password immediately after your first login.</p>
                </div>
                <div class="text-xs leading-relaxed">
                    Username: use your LRN.
                </div>
                <div class="text-xs leading-relaxed">
                    Password: use the first 2 letters of your first name + the first 2 letters of your last name + {{ session('application_status_enrollment_year') }} + anhs.
                    Example format only: `jado{{ session('application_status_enrollment_year') }}anhs`
                </div>
            </div>
            @endif

            @if ($errors->statusCheck->has('status_lookup') || $errors->statusCheck->has('status_lrn') || $errors->statusCheck->has('status_birthdate'))
            <div class="mb-4 p-3 rounded bg-red-50 border-l-4 border-red-500 text-red-700 text-sm">
                {{ $errors->statusCheck->first('status_lookup') ?: $errors->statusCheck->first('status_lrn') ?: $errors->statusCheck->first('status_birthdate') }}
            </div>
            @endif

            <form action="{{ route('applications.status') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="status_lrn">LRN</label>
                    <input id="status_lrn" name="status_lrn" type="text" minlength="12" maxlength="12" inputmode="numeric" pattern="\d{12}" required
                        value="{{ old('status_lrn') }}"
                        class="shadow-sm appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="status_birthdate">Birthdate</label>
                    <input id="status_birthdate" name="status_birthdate" type="date" required
                        value="{{ old('status_birthdate') }}"
                        class="shadow-sm appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200">
                </div>
                <button type="submit"
                    class="w-full bg-blue-700 hover:bg-blue-800 text-white font-bold py-3 px-4 rounded focus:outline-none focus:ring-4 focus:ring-blue-300 transition duration-300">
                    Check Status
                </button>
            </form>
        </div>
    </div>

    <!-- About Us Section -->
    <section id="about-us" class="py-16 px-4 bg-white/95 backdrop-blur-sm">
        <div class="container mx-auto max-w-6xl">
            <h2 class="text-4xl font-bold text-[#0C2C55] mb-8 text-center">About Us</h2>
            <div class="grid md:grid-cols-2 gap-8">
                <div>
                    <h3 class="text-2xl font-semibold text-[#0C2C55] mb-4">Our Mission</h3>
                    <p class="text-gray-700 leading-relaxed mb-6">
                        Our mission is to provide quality education and empower students to achieve their full potential.
                        We are committed to fostering academic excellence, character development, and preparing students
                        for success in their chosen fields.
                    </p>
                    <p class="text-gray-700 leading-relaxed">
                        Through innovative teaching methods and a supportive learning environment, we strive to create
                        opportunities for every student to excel and make a positive impact in their communities.
                    </p>
                </div>
                <div>
                    <h3 class="text-2xl font-semibold text-[#0C2C55] mb-4">Our Vision</h3>
                    <p class="text-gray-700 leading-relaxed mb-6">
                        To be a leading educational institution recognized for academic excellence, innovation, and
                        commitment to student success. We envision a future where every graduate is equipped with the
                        knowledge, skills, and values needed to thrive in a rapidly changing world.
                    </p>
                    <p class="text-gray-700 leading-relaxed">
                        Our vision extends beyond the classroom, aiming to develop well-rounded individuals who
                        contribute meaningfully to society and lead with integrity and compassion.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section id="faq" class="py-16 px-4 bg-gray-50/95 backdrop-blur-sm" x-data="{ open: 0 }">
        <div class="container mx-auto max-w-4xl">
            <h2 class="text-4xl font-bold text-[#0C2C55] mb-12 text-center">Frequently Asked Questions</h2>

            <div class="space-y-4">
                <!-- FAQ Item 1 -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <button class="faq-question w-full px-6 py-4 text-left flex justify-between items-center hover:bg-gray-50 transition duration-200" @click="open = open === 1 ? 0 : 1">
                        <span class="font-semibold text-[#0C2C55]">How do I register as a new student?</span>
                        <svg class="faq-icon w-5 h-5 text-[#0C2C55] transform transition-transform duration-300" :class="{ 'rotate-180': open === 1 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div class="faq-answer px-6 pb-4" x-show="open === 1" x-transition>
                        <p class="text-gray-700 leading-relaxed">
                            To register as a new student, click on the "Register as a Student" link on the login page.
                            You will need to provide your personal information, contact details, and academic records.
                            Once your registration is approved, you can check your status using your LRN and birthdate to view your login instructions.
                        </p>
                    </div>
                </div>

                <!-- FAQ Item 2 -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <button class="faq-question w-full px-6 py-4 text-left flex justify-between items-center hover:bg-gray-50 transition duration-200" @click="open = open === 2 ? 0 : 2">
                        <span class="font-semibold text-[#0C2C55]">What documents do I need for enrollment?</span>
                        <svg class="faq-icon w-5 h-5 text-[#0C2C55] transform transition-transform duration-300" :class="{ 'rotate-180': open === 2 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div class="faq-answer px-6 pb-4" x-show="open === 2" x-transition>
                        <p class="text-gray-700 leading-relaxed">
                            For enrollment, you will need the following documents: birth certificate, high school diploma or transcript of records,
                            recent 2x2 ID photos, medical certificate, and valid ID. Additional documents may be required depending on your
                            chosen program. Please check the documents section in your student portal for a complete list.
                        </p>
                    </div>
                </div>

                <!-- FAQ Item 3 -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <button class="faq-question w-full px-6 py-4 text-left flex justify-between items-center hover:bg-gray-50 transition duration-200" @click="open = open === 3 ? 0 : 3">
                        <span class="font-semibold text-[#0C2C55]">How can I reset my password?</span>
                        <svg class="faq-icon w-5 h-5 text-[#0C2C55] transform transition-transform duration-300" :class="{ 'rotate-180': open === 3 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div class="faq-answer px-6 pb-4" x-show="open === 3" x-transition>
                        <p class="text-gray-700 leading-relaxed">
                            If you forgot your password, click on the "Forgot Password" link on the login page. Enter your registered
                            Username, and you will receive instructions on how to reset your password. If you continue to have issues,
                            please contact the IT support team or visit the registrar's office.
                        </p>
                    </div>
                </div>

                <!-- FAQ Item 4 -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <button class="faq-question w-full px-6 py-4 text-left flex justify-between items-center hover:bg-gray-50 transition duration-200" @click="open = open === 4 ? 0 : 4">
                        <span class="font-semibold text-[#0C2C55]">When is the enrollment period?</span>
                        <svg class="faq-icon w-5 h-5 text-[#0C2C55] transform transition-transform duration-300" :class="{ 'rotate-180': open === 4 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div class="faq-answer px-6 pb-4" x-show="open === 4" x-transition>
                        <p class="text-gray-700 leading-relaxed">
                            The enrollment period typically begins one month before the start of each semester. For the first semester,
                            enrollment usually starts in May and ends in July. For the second semester, enrollment starts in November
                            and ends in January. Please check the academic calendar for specific dates.
                        </p>
                    </div>
                </div>

                <!-- FAQ Item 5 -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <button class="faq-question w-full px-6 py-4 text-left flex justify-between items-center hover:bg-gray-50 transition duration-200" @click="open = open === 5 ? 0 : 5">
                        <span class="font-semibold text-[#0C2C55]">How do I access my student portal?</span>
                        <svg class="faq-icon w-5 h-5 text-[#0C2C55] transform transition-transform duration-300" :class="{ 'rotate-180': open === 5 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div class="faq-answer px-6 pb-4" x-show="open === 5" x-transition>
                        <p class="text-gray-700 leading-relaxed">
                            After your application is approved, verify your status using your LRN and birthdate. Your username will be
                            your LRN, and your default password will follow the posted password format. Change your password after your first login.
                        </p>
                    </div>
                </div>

                <!-- FAQ Item 6 -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <button class="faq-question w-full px-6 py-4 text-left flex justify-between items-center hover:bg-gray-50 transition duration-200" @click="open = open === 6 ? 0 : 6">
                        <span class="font-semibold text-[#0C2C55]">Who can I contact for technical support?</span>
                        <svg class="faq-icon w-5 h-5 text-[#0C2C55] transform transition-transform duration-300" :class="{ 'rotate-180': open === 6 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div class="faq-answer px-6 pb-4" x-show="open === 6" x-transition>
                        <p class="text-gray-700 leading-relaxed">
                            For technical support, you can contact the IT Department through email at it-support@school.edu or call
                            (123) 456-7890 during office hours (Monday to Friday, 8:00 AM to 5:00 PM). You can also visit the IT
                            office located on the ground floor of the main building for in-person assistance.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Us Section -->
    <section id="contact-us" class="py-16 px-4 bg-[#0C2C55] text-white">
        <div class="container mx-auto max-w-6xl">
            <h2 class="text-4xl font-bold mb-12 text-center">Contact Us</h2>
            <div class="grid md:grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="bg-white/10 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Phone</h3>
                    <p class="text-white/80">(123) 456-7890</p>
                    <p class="text-white/80">(123) 456-7891</p>
                </div>

                <div class="text-center">
                    <div class="bg-white/10 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Email</h3>
                    <p class="text-white/80">info@school.edu</p>
                    <p class="text-white/80">admissions@school.edu</p>
                </div>

                <div class="text-center">
                    <div class="bg-white/10 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Address</h3>
                    <p class="text-white/80">123 Education Street</p>
                    <p class="text-white/80">City, Province 1234</p>
                </div>
            </div>

            <div class="mt-12 text-center">
                <h3 class="text-2xl font-semibold mb-4">Office Hours</h3>
                <p class="text-white/80">Monday to Friday: 8:00 AM - 5:00 PM</p>
                <p class="text-white/80">Saturday: 9:00 AM - 12:00 PM</p>
                <p class="text-white/80">Sunday: Closed</p>
            </div>
        </div>
    </section>

</body>

</html>

