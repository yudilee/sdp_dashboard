@extends('layouts.app')

@section('title', 'Import Data - SDP Dashboard')

@section('content')
<div x-data="{
    // Password protection
    isUnlocked: sessionStorage.getItem('import_unlocked') === 'true',
    passwordInput: '',
    passwordError: false,
    correctPassword: 'Sut@1204',
    
    checkPassword() {
        if (this.passwordInput === this.correctPassword) {
            this.isUnlocked = true;
            this.passwordError = false;
            sessionStorage.setItem('import_unlocked', 'true');
        } else {
            this.passwordError = true;
            this.passwordInput = '';
        }
    },
    
    // Original import functionality
    activeTab: 'excel',
    odooConfig: {
        url: '{{ $odooConfig['url'] ?? '' }}',
        db: '{{ $odooConfig['db'] ?? '' }}',
        user: '{{ $odooConfig['user'] ?? '' }}',
        password: '{{ $odooConfig['password'] ?? '' }}'
    },
    isSaving: false,
    isTesting: false,
    isSyncing: false,
    testResult: null,
    syncResult: null,

    async saveConfig() {
        this.isSaving = true;
        try {
            const response = await fetch('{{ route('import.odoo.config') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    odoo_url: this.odooConfig.url,
                    odoo_db: this.odooConfig.db,
                    odoo_user: this.odooConfig.user,
                    odoo_password: this.odooConfig.password
                })
            });
            const data = await response.json();
            alert(data.message);
        } catch (e) {
            alert('Error: ' + e.message);
        } finally {
            this.isSaving = false;
        }
    },

    async testConnection() {
        this.isTesting = true;
        this.testResult = null;
        try {
            const response = await fetch('{{ route('import.odoo.test') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });
            this.testResult = await response.json();
        } catch (e) {
            this.testResult = { success: false, message: 'Error: ' + e.message };
        } finally {
            this.isTesting = false;
        }
    },

    async syncData() {
        this.isSyncing = true;
        this.syncResult = null;
        try {
            const response = await fetch('{{ route('import.odoo.sync') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });
            this.syncResult = await response.json();
        } catch (e) {
            this.syncResult = { success: false, message: 'Error: ' + e.message };
        } finally {
            this.isSyncing = false;
        }
    }
}">
    <!-- Password Modal -->
    <div x-show="!isUnlocked" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/80 backdrop-blur-sm">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl p-8 w-full max-w-md mx-4 border border-slate-200 dark:border-slate-700">
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-indigo-100 dark:bg-indigo-900/50 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-slate-800 dark:text-slate-100">Access Protected</h2>
                <p class="text-slate-500 dark:text-slate-400 mt-2">Enter password to access Import Menu</p>
            </div>
            
            <form @submit.prevent="checkPassword()">
                <div class="mb-4">
                    <input type="password" 
                           x-model="passwordInput"
                           placeholder="Enter password"
                           :class="passwordError ? 'border-red-500 ring-2 ring-red-200 dark:ring-red-900' : 'border-slate-200 dark:border-slate-700'"
                           class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all text-center text-lg tracking-widest"
                           autofocus>
                    <p x-show="passwordError" class="text-red-500 text-sm mt-2 text-center">Incorrect password. Please try again.</p>
                </div>
                
                <button type="submit"
                        class="w-full py-3 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-xl transition-colors flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"></path>
                    </svg>
                    Unlock
                </button>
            </form>
            
            <div class="mt-6 text-center">
                <a href="{{ route('dashboard') }}" class="text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 text-sm transition-colors">
                    ← Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content (hidden until unlocked) -->
    <div x-show="isUnlocked" x-transition>
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-slate-800 dark:text-slate-100">Import Data</h1>
        <p class="text-slate-500 dark:text-slate-400 mt-2">Import inventory data from Excel or sync directly from Odoo</p>
    </div>

    <!-- Tabs -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800 overflow-hidden">
        <div class="flex border-b border-slate-200 dark:border-slate-700">
            <button @click="activeTab = 'excel'" 
                    :class="activeTab === 'excel' ? 'border-b-2 border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300'"
                    class="flex items-center gap-2 px-6 py-4 font-medium transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Excel Import
            </button>
            <button @click="activeTab = 'odoo'"
                    :class="activeTab === 'odoo' ? 'border-b-2 border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300'"
                    class="flex items-center gap-2 px-6 py-4 font-medium transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                Odoo API
            </button>
        </div>

        <div class="p-6">
            <!-- Excel Tab -->
            <div x-show="activeTab === 'excel'" x-transition>
                <div class="max-w-xl">
                    <h3 class="text-lg font-semibold text-slate-800 dark:text-slate-100 mb-4">Upload Excel File</h3>
                    <p class="text-slate-500 dark:text-slate-400 mb-6">Upload your inventory Excel file (.xlsx, .xls, .csv) to import data into the dashboard.</p>
                    
                    <form action="{{ route('import.excel') }}" method="POST" enctype="multipart/form-data" 
                          x-data="{ isUploading: false, fileName: '' }"
                          @submit="isUploading = true">
                        @csrf
                        
                        <div class="border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-xl p-8 text-center hover:border-indigo-300 dark:hover:border-indigo-700 transition-colors">
                            <svg class="w-12 h-12 mx-auto text-slate-400 dark:text-slate-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                            
                            <label class="cursor-pointer">
                                <span class="text-indigo-600 dark:text-indigo-400 font-medium hover:text-indigo-700 dark:hover:text-indigo-300">Choose file</span>
                                <span class="text-slate-500 dark:text-slate-400"> or drag and drop</span>
                                <input type="file" name="file" required 
                                       class="hidden" 
                                       accept=".xlsx,.xls,.csv"
                                       @change="fileName = $event.target.files[0]?.name || ''">
                            </label>
                            
                            <p class="text-xs text-slate-400 dark:text-slate-500 mt-2">XLSX, XLS, or CSV up to 10MB</p>
                            
                            <p x-show="fileName" x-text="fileName" class="mt-3 text-sm text-indigo-600 dark:text-indigo-400 font-medium"></p>
                        </div>
                        
                        <button type="submit" 
                                class="mt-6 w-full py-3 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-xl transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                                :disabled="isUploading">
                            <svg x-show="isUploading" class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-text="isUploading ? 'Processing...' : 'Upload & Import'"></span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Odoo Tab -->
            <div x-show="activeTab === 'odoo'" x-transition>
                <div class="max-w-xl">
                    <h3 class="text-lg font-semibold text-slate-800 dark:text-slate-100 mb-4">Odoo Configuration</h3>
                    <p class="text-slate-500 dark:text-slate-400 mb-6">Configure your Odoo connection to sync inventory data directly.</p>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Odoo URL</label>
                            <input type="url" x-model="odooConfig.url" 
                                   placeholder="https://your-odoo-instance.com"
                                   class="w-full px-4 py-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Database</label>
                            <input type="text" x-model="odooConfig.db" 
                                   placeholder="your-database-name"
                                   class="w-full px-4 py-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">User Email</label>
                            <input type="text" x-model="odooConfig.user" 
                                   placeholder="admin@example.com"
                                   class="w-full px-4 py-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">API Key / Password</label>
                            <input type="password" x-model="odooConfig.password" 
                                   placeholder="Your API key or password"
                                   class="w-full px-4 py-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="mt-6 flex flex-wrap gap-3">
                        <button @click="saveConfig()" 
                                :disabled="isSaving"
                                class="px-5 py-2.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-medium rounded-lg transition-colors disabled:opacity-50 flex items-center gap-2">
                            <svg x-show="isSaving" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span x-text="isSaving ? 'Saving...' : 'Save Config'"></span>
                        </button>
                        
                        <button @click="testConnection()" 
                                :disabled="isTesting"
                                class="px-5 py-2.5 bg-amber-100 dark:bg-amber-900/30 hover:bg-amber-200 dark:hover:bg-amber-900/50 text-amber-700 dark:text-amber-400 font-medium rounded-lg transition-colors disabled:opacity-50 flex items-center gap-2">
                            <svg x-show="isTesting" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span x-text="isTesting ? 'Testing...' : 'Test Connection'"></span>
                        </button>
                        
                        <button @click="syncData()" 
                                :disabled="isSyncing"
                                class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50 flex items-center gap-2">
                            <svg x-show="isSyncing" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span x-text="isSyncing ? 'Syncing...' : 'Sync Now'"></span>
                        </button>
                    </div>
                    
                    <!-- Test Result -->
                    <div x-show="testResult" x-transition class="mt-4">
                        <div :class="testResult?.success ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800 text-green-700 dark:text-green-400' : 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800 text-red-700 dark:text-red-400'"
                             class="p-4 rounded-lg border flex items-start gap-3">
                            <svg x-show="testResult?.success" class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <svg x-show="!testResult?.success" class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span x-text="testResult?.message"></span>
                        </div>
                    </div>
                    
                    <!-- Sync Result -->
                    <div x-show="syncResult" x-transition class="mt-4">
                        <div :class="syncResult?.success ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800 text-green-700 dark:text-green-400' : 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800 text-red-700 dark:text-red-400'"
                             class="p-4 rounded-lg border flex items-start gap-3">
                            <svg x-show="syncResult?.success" class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <svg x-show="!syncResult?.success" class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span x-text="syncResult?.message"></span>
                        </div>
                    </div>

                    <!-- Info Box -->
                    <div class="mt-8 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800 rounded-lg">
                        <h4 class="font-medium text-blue-700 dark:text-blue-400 mb-2 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Note
                        </h4>
                        <p class="text-sm text-blue-600 dark:text-blue-300">
                            The Odoo sync requires PHP's <code class="px-1 py-0.5 bg-blue-100 dark:bg-blue-800 rounded">xmlrpc</code> extension. 
                            Data mapping may need configuration based on your Odoo model structure.
                        </p>
                </div>
            </div>
        </div>
    </div>
    </div> <!-- Close isUnlocked wrapper -->
</div>
@endsection
