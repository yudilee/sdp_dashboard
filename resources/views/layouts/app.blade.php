<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'SDP Dashboard')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; }
        [x-cloak] { display: none !important; }
        .glass {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }
        .dark .glass {
            background: rgba(15, 23, 42, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        .sidebar-link.active {
            background: linear-gradient(90deg, rgba(99, 102, 241, 0.1) 0%, rgba(99, 102, 241, 0.05) 100%);
            color: #4f46e5;
            border-right: 3px solid #4f46e5;
        }
        .dark .sidebar-link.active {
            background: linear-gradient(90deg, rgba(99, 102, 241, 0.2) 0%, rgba(99, 102, 241, 0.05) 100%);
            color: #818cf8;
            border-right: 3px solid #818cf8;
        }

        /* Dark Mode Transitions */
        .theme-transition {
            transition: background-color 0.3s ease, border-color 0.3s ease, color 0.3s ease;
        }
    </style>
    <script>
        // On page load or when changing themes, best to add inline in `head` to avoid FOUC
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark')
        } else {
            document.documentElement.classList.remove('dark')
        }
    </script>
</head>
<body class="bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 antialiased theme-transition" 
      x-data="{ 
        sidebarOpen: false, 
        sidebarCollapsed: false,
        isDark: document.documentElement.classList.contains('dark'),
        toggleTheme() {
            if (this.isDark) {
                document.documentElement.classList.remove('dark');
                localStorage.theme = 'light';
                this.isDark = false;
            } else {
                document.documentElement.classList.add('dark');
                localStorage.theme = 'dark';
                this.isDark = true;
            }
        },
        init() {
            this.$watch('isDark', value => {
                // Ensure consistency
                if (value) document.documentElement.classList.add('dark');
                else document.documentElement.classList.remove('dark');
            });
        }
      }"
      x-init="init()">
    
    <!-- Mobile Header -->
    <header class="lg:hidden flex items-center justify-between p-4 glass sticky top-0 z-50">
        <div class="flex items-center gap-2 font-bold text-xl text-indigo-600 dark:text-indigo-400">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
            SDP<span class="text-slate-600 dark:text-slate-400">Stock</span>
        </div>
        <div class="flex items-center gap-2">
            <button @click="toggleTheme()" class="p-2 rounded-lg bg-white dark:bg-slate-800 shadow-sm text-slate-600 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-indigo-400 focus:outline-none transition-colors">
                <svg x-show="!isDark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                <svg x-show="isDark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 9h-1m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
            </button>
            <button @click="sidebarOpen = !sidebarOpen" class="p-2 rounded-lg bg-white dark:bg-slate-800 shadow-sm text-slate-600 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-indigo-400 focus:outline-none transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            </button>
        </div>
    </header>

    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside 
            :class="{ 'translate-x-0': sidebarOpen, '-translate-x-full lg:translate-x-0': !sidebarOpen, 'lg:w-20': sidebarCollapsed, 'lg:w-64': !sidebarCollapsed }"
            class="fixed inset-y-0 left-0 z-40 bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 transition-all duration-300 lg:static transform flex flex-col w-64 h-screen lg:h-auto overflow-hidden">
            
            <!-- Sidebar Header -->
            <div class="h-20 flex items-center justify-center border-b border-slate-100 flex-shrink-0" :class="sidebarCollapsed ? 'px-0' : 'px-6 justify-start gap-2'">
                <div class="bg-indigo-600 text-white p-1.5 rounded-lg shadow-lg shadow-indigo-200 flex-shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                </div>
                <div class="font-bold text-xl text-indigo-600 whitespace-nowrap overflow-hidden transition-all duration-300" 
                     :class="sidebarCollapsed ? 'w-0 opacity-0' : 'w-auto opacity-100'" x-show="!sidebarCollapsed">
                    SDP<span class="text-slate-600 font-light">Dashboard</span>
                </div>
            </div>

            <!-- Scrollable Nav -->
            <nav class="p-4 space-y-1 flex-1 overflow-y-auto overflow-x-hidden custom-scrollbar">
                <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2 mt-4 px-2 whitespace-nowrap overflow-hidden transition-all duration-300"
                     :class="sidebarCollapsed ? 'text-center' : 'px-4'">
                     <span x-show="!sidebarCollapsed">Overview</span>
                     <span x-show="sidebarCollapsed" class="block w-full border-b border-slate-200"></span>
                </div>
                
                <a href="{{ route('dashboard') }}" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-slate-600 hover:bg-slate-50 hover:text-indigo-600 transition-all group {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                   title="Dashboard">
                    <svg class="w-5 h-5 group-hover:scale-110 transition-transform flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                    <span class="font-medium whitespace-nowrap overflow-hidden transition-all duration-300" 
                          :class="sidebarCollapsed ? 'w-0 opacity-0' : 'w-auto opacity-100'">Dashboard</span>
                </a>
                
                <a href="{{ route('rental.pairs') }}" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-slate-600 hover:bg-slate-50 hover:text-indigo-600 transition-all group {{ request()->routeIs('rental.pairs') ? 'active' : '' }}"
                   title="Rental Pairs">
                   <svg class="w-5 h-5 group-hover:scale-110 transition-transform flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                    <span class="font-medium whitespace-nowrap overflow-hidden transition-all duration-300" 
                          :class="sidebarCollapsed ? 'w-0 opacity-0' : 'w-auto opacity-100'">Rental Pairs</span>
                </a>
                <a href="{{ route('total.stock') }}" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-slate-600 hover:bg-slate-50 hover:text-indigo-600 transition-all group {{ request()->routeIs('total.stock') ? 'active' : '' }}"
                   title="Total Stock">
                   <svg class="w-5 h-5 group-hover:scale-110 transition-transform flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    <span class="font-medium whitespace-nowrap overflow-hidden transition-all duration-300" 
                          :class="sidebarCollapsed ? 'w-0 opacity-0' : 'w-auto opacity-100'">Total Stock</span>
                </a>

                <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2 mt-6 px-2 whitespace-nowrap overflow-hidden transition-all duration-300"
                     :class="sidebarCollapsed ? 'text-center' : 'px-4'">
                     <span x-show="!sidebarCollapsed">Inventory</span>
                     <span x-show="sidebarCollapsed" class="block w-full border-b border-slate-200"></span>
                </div>
                
                <a href="{{ route('details', ['category' => 'in_stock']) }}" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-slate-600 hover:bg-slate-50 hover:text-indigo-600 transition-all group {{ request()->input('category') == 'in_stock' ? 'active' : '' }}"
                   title="In Stock">
                    <svg class="w-5 h-5 group-hover:scale-110 transition-transform flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    <span class="font-medium whitespace-nowrap overflow-hidden transition-all duration-300" 
                          :class="sidebarCollapsed ? 'w-0 opacity-0' : 'w-auto opacity-100'">In Stock</span>
                </a>
                
                <a href="{{ route('details', ['category' => 'rented']) }}" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-slate-600 hover:bg-slate-50 hover:text-indigo-600 transition-all group {{ request()->input('category') == 'rented' ? 'active' : '' }}"
                   title="Rented">
                   <svg class="w-5 h-5 group-hover:scale-110 transition-transform flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    <span class="font-medium whitespace-nowrap overflow-hidden transition-all duration-300" 
                          :class="sidebarCollapsed ? 'w-0 opacity-0' : 'w-auto opacity-100'">Rented</span>
                </a>

                <a href="{{ route('details', ['category' => 'in_service']) }}" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-indigo-600 dark:hover:text-indigo-400 transition-all group {{ request()->input('category') == 'in_service' ? 'active' : '' }}"
                   title="In Service">
                   <svg class="w-5 h-5 group-hover:scale-110 transition-transform flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    <span class="font-medium whitespace-nowrap overflow-hidden transition-all duration-300" 
                          :class="sidebarCollapsed ? 'w-0 opacity-0' : 'w-auto opacity-100'">In Service</span>
                </a>

                <div class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-2 mt-6 px-2 whitespace-nowrap overflow-hidden transition-all duration-300"
                     :class="sidebarCollapsed ? 'text-center' : 'px-4'">
                     <span x-show="!sidebarCollapsed">Utilities</span>
                     <span x-show="sidebarCollapsed" class="block w-full border-b border-slate-200 dark:border-slate-800"></span>
                </div>

                <a href="{{ route('summary') }}" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-indigo-600 dark:hover:text-indigo-400 transition-all group {{ request()->routeIs('summary') ? 'active' : '' }}"
                   title="Import Data">
                   <svg class="w-5 h-5 group-hover:scale-110 transition-transform flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                    <span class="font-medium whitespace-nowrap overflow-hidden transition-all duration-300" 
                          :class="sidebarCollapsed ? 'w-0 opacity-0' : 'w-auto opacity-100'">Import Data</span>
                </a>

                <a href="{{ route('help') }}" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-indigo-600 dark:hover:text-indigo-400 transition-all group {{ request()->routeIs('help') ? 'active' : '' }}"
                   title="Help & Guide">
                   <svg class="w-5 h-5 group-hover:scale-110 transition-transform flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span class="font-medium whitespace-nowrap overflow-hidden transition-all duration-300" 
                          :class="sidebarCollapsed ? 'w-0 opacity-0' : 'w-auto opacity-100'">Help & Guide</span>
                </a>


            </nav>

            <!-- Sidebar Footer -->
            <div class="p-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 whitespace-nowrap overflow-hidden transition-all duration-300 flex-shrink-0"
                 :class="sidebarCollapsed ? 'items-center justify-center p-2' : ''">
                <div class="flex items-center gap-3" :class="sidebarCollapsed ? 'justify-center' : ''">
                    <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center text-indigo-700 dark:text-indigo-300 font-bold text-xs flex-shrink-0">
                        U
                    </div>
                    <div :class="sidebarCollapsed ? 'hidden' : 'block'">
                        <div class="text-sm font-bold text-slate-700 dark:text-slate-200">User</div>
                        <div class="text-xs text-slate-500 dark:text-slate-400">Admin</div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Overlay -->
        <div x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 z-30 bg-black/20 backdrop-blur-sm lg:hidden" style="display: none;"></div>

        <!-- Main Content -->
        <main class="flex-1 min-w-0 overflow-hidden bg-slate-50/50 dark:bg-slate-950 flex flex-col theme-transition">
            <!-- Desktop Top Bar -->
            <div class="bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 p-4 hidden lg:flex items-center gap-4 sticky top-0 z-20">
                <button @click="sidebarCollapsed = !sidebarCollapsed" class="p-2 text-slate-500 hover:text-indigo-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                     <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path></svg>
                </button>
                <div class="flex-1 px-4">
                    <form action="{{ route('details') }}" method="GET" class="max-w-md w-full relative">
                        <input type="hidden" name="category" value="search">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                        <input type="text" name="q" class="w-full pl-10 pr-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all bg-slate-50 dark:bg-slate-800 focus:bg-white dark:focus:bg-slate-700 placeholder-slate-400 dark:placeholder-slate-500" placeholder="Global Search (Lot, Product, Location)...">
                    </form>
                </div>

                <!-- Theme Toggle -->
                <button @click="toggleTheme()" class="p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:border-indigo-100 dark:hover:border-indigo-900 transition-all shadow-sm">
                    <svg x-show="!isDark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                    <svg x-show="isDark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 9h-1m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto">
                <div class="w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    @if(session('success'))
                    <div x-data="{ show: true }" x-show="show" class="mb-6 p-4 rounded-xl bg-green-50 border border-green-200 text-green-700 flex items-center justify-between shadow-sm">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span class="font-medium">{{ session('success') }}</span>
                        </div>
                        <button @click="show = false" class="text-green-500 hover:text-green-700"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
                    </div>
                    @endif

                    @yield('content')
                </div>
            </div>
        </main>
    </div>
    
    @yield('scripts')
</body>
</html>
