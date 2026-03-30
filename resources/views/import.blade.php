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
            // Refresh schedule info after sync
            await this.loadSchedule();
        } catch (e) {
            this.syncResult = { success: false, message: 'Error: ' + e.message };
        } finally {
            this.isSyncing = false;
        }
    },

    // Schedule configuration
    schedule: {
        enabled: false,
        interval: 'daily',
        lastSync: null
    },
    isSavingSchedule: false,
    scheduleResult: null,

    // Import History
    historyData: [],
    isLoadingHistory: false,
    expandedLogId: null,

    async init() {
        await this.loadSchedule();
        await this.loadHistory();
    },

    async loadSchedule() {
        try {
            const response = await fetch('{{ route('import.odoo.schedule.get') }}');
            const data = await response.json();
            this.schedule.enabled = data.enabled;
            this.schedule.interval = data.interval;
            this.schedule.lastSync = data.last_sync;
        } catch (e) {
            console.error('Failed to load schedule:', e);
        }
    },

    async saveSchedule() {
        this.isSavingSchedule = true;
        this.scheduleResult = null;
        try {
            const response = await fetch('{{ route('import.odoo.schedule.save') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    enabled: this.schedule.enabled,
                    interval: this.schedule.interval
                })
            });
            this.scheduleResult = await response.json();
        } catch (e) {
            this.scheduleResult = { success: false, message: 'Error: ' + e.message };
        } finally {
            this.isSavingSchedule = false;
        }
    },

    formatInterval(interval) {
        const map = {
            'hourly': 'Every hour',
            'every_2_hours': 'Every 2 hours',
            'every_4_hours': 'Every 4 hours',
            'every_6_hours': 'Every 6 hours',
            'every_12_hours': 'Every 12 hours',
            'daily': 'Once daily'
        };
        return map[interval] || interval;
    },

    formatDate(dateStr) {
        if (!dateStr) return 'Never';
        const bulan = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        const hh = String(d.getHours()).padStart(2, '0');
        const mm = String(d.getMinutes()).padStart(2, '0');
        return d.getDate() + ' ' + bulan[d.getMonth()] + ' ' + d.getFullYear() + ' ' + hh + ':' + mm;
    },

    async loadHistory() {
        this.isLoadingHistory = true;
        try {
            const response = await fetch('{{ route('import.history') }}');
            this.historyData = await response.json();
        } catch (e) {
            console.error('Failed to load history:', e);
        } finally {
            this.isLoadingHistory = false;
        }
    },

    toggleExpand(logId) {
        this.expandedLogId = this.expandedLogId === logId ? null : logId;
    },

    formatNumber(num) {
        return new Intl.NumberFormat().format(num || 0);
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
            <button @click="activeTab = 'history'; loadHistory()"
                    :class="activeTab === 'history' ? 'border-b-2 border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300'"
                    class="flex items-center gap-2 px-6 py-4 font-medium transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                History
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

                    <!-- Schedule Configuration -->
                    <div class="mt-8 border-t border-slate-200 dark:border-slate-700 pt-8">
                        <h3 class="text-lg font-semibold text-slate-800 dark:text-slate-100 mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Auto-Sync Schedule
                        </h3>
                        
                        <div class="bg-slate-50 dark:bg-slate-800/50 rounded-xl p-5 space-y-4">
                            <!-- Enable Toggle -->
                            <div class="flex items-center justify-between">
                                <div>
                                    <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Enable Auto-Sync</label>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Automatically sync data from Odoo</p>
                                </div>
                                <button @click="schedule.enabled = !schedule.enabled"
                                        :class="schedule.enabled ? 'bg-indigo-600' : 'bg-slate-300 dark:bg-slate-600'"
                                        class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors">
                                    <span :class="schedule.enabled ? 'translate-x-6' : 'translate-x-1'"
                                          class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform shadow"></span>
                                </button>
                            </div>

                            <!-- Interval Dropdown -->
                            <div x-show="schedule.enabled" x-transition>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Sync Interval</label>
                                <select x-model="schedule.interval"
                                        class="w-full px-4 py-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                                    <option value="hourly">Every hour</option>
                                    <option value="every_2_hours">Every 2 hours</option>
                                    <option value="every_4_hours">Every 4 hours</option>
                                    <option value="every_6_hours">Every 6 hours</option>
                                    <option value="every_12_hours">Every 12 hours</option>
                                    <option value="daily">Once daily (midnight)</option>
                                </select>
                            </div>

                            <!-- Last Sync Info -->
                            <div class="flex items-center justify-between text-sm pt-2 border-t border-slate-200 dark:border-slate-700">
                                <span class="text-slate-500 dark:text-slate-400">Last Sync:</span>
                                <span class="text-slate-700 dark:text-slate-300 font-medium" x-text="formatDate(schedule.lastSync)"></span>
                            </div>

                            <!-- Save Button -->
                            <button @click="saveSchedule()"
                                    :disabled="isSavingSchedule"
                                    class="w-full mt-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50 flex items-center justify-center gap-2">
                                <svg x-show="isSavingSchedule" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <span x-text="isSavingSchedule ? 'Saving...' : 'Save Schedule'"></span>
                            </button>

                            <!-- Schedule Result -->
                            <div x-show="scheduleResult" x-transition class="mt-2">
                                <div :class="scheduleResult?.success ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800 text-green-700 dark:text-green-400' : 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800 text-red-700 dark:text-red-400'"
                                     class="p-3 rounded-lg border text-sm">
                                    <span x-text="scheduleResult?.message"></span>
                                </div>
                            </div>
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

            <!-- History Tab -->
            <div x-show="activeTab === 'history'" x-transition>
                <div class="max-w-4xl">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-800 dark:text-slate-100">Import History</h3>
                            <p class="text-slate-500 dark:text-slate-400">View past data imports and their summaries</p>
                        </div>
                        <button @click="loadHistory()" 
                                :disabled="isLoadingHistory"
                                class="px-4 py-2 text-sm bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg transition-colors disabled:opacity-50 flex items-center gap-2">
                            <svg :class="isLoadingHistory ? 'animate-spin' : ''" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Refresh
                        </button>
                    </div>

                    <!-- Loading State -->
                    <div x-show="isLoadingHistory" class="text-center py-12">
                        <svg class="animate-spin h-8 w-8 mx-auto text-indigo-500" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <p class="mt-2 text-slate-500 dark:text-slate-400">Loading history...</p>
                    </div>

                    <!-- Empty State -->
                    <div x-show="!isLoadingHistory && historyData.length === 0" class="text-center py-12 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                        <svg class="w-12 h-12 mx-auto text-slate-400 dark:text-slate-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <p class="text-slate-500 dark:text-slate-400">No import history yet</p>
                        <p class="text-sm text-slate-400 dark:text-slate-500 mt-1">Import data via Excel or Odoo to see history here</p>
                    </div>

                    <!-- History Table -->
                    <div x-show="!isLoadingHistory && historyData.length > 0" class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-700">
                        <table class="w-full">
                            <thead class="bg-slate-50 dark:bg-slate-800">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Date & Time</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Source</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">File</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Items</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Details</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
                                <template x-for="log in historyData" :key="log.id">
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                        <td class="px-4 py-3">
                                            <span class="text-sm text-slate-700 dark:text-slate-300" x-text="formatDate(log.imported_at)"></span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium"
                                                  :class="{
                                                      'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400': log.source === 'excel',
                                                      'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400': log.source === 'odoo_manual',
                                                      'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400': log.source === 'odoo_scheduled'
                                                  }">
                                                <svg x-show="log.source === 'excel'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                                <svg x-show="log.source.startsWith('odoo')" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                                <span x-text="log.source_label"></span>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="text-sm text-slate-600 dark:text-slate-400 truncate max-w-[200px] block" x-text="log.filename || '—'"></span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300" x-text="formatNumber(log.items_count)"></span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium"
                                                  :class="log.status === 'success' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400'">
                                                <svg x-show="log.status === 'success'" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                <svg x-show="log.status !== 'success'" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                                <span x-text="log.status === 'success' ? 'Success' : 'Failed'"></span>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <button @click="toggleExpand(log.id)" 
                                                    class="p-1.5 text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded transition-colors">
                                                <svg class="w-4 h-4 transition-transform" :class="expandedLogId === log.id ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                        
                        <!-- Expanded Summary Panel -->
                        <template x-for="log in historyData" :key="'summary-' + log.id">
                            <div x-show="expandedLogId === log.id" 
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0"
                                 x-transition:enter-end="opacity-100"
                                 class="border-t border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 p-4">
                                <template x-if="log.error_message">
                                    <div class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-sm text-red-700 dark:text-red-400">
                                        <strong>Error:</strong> <span x-text="log.error_message"></span>
                                    </div>
                                </template>
                                <template x-if="log.summary">
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                        <div class="bg-white dark:bg-slate-900 rounded-lg p-3 border border-slate-200 dark:border-slate-700">
                                            <p class="text-xs text-slate-500 dark:text-slate-400 uppercase">SDP Stock</p>
                                            <p class="text-lg font-semibold text-slate-800 dark:text-slate-200" x-text="formatNumber(log.summary.sdp_stock)"></p>
                                        </div>
                                        <div class="bg-white dark:bg-slate-900 rounded-lg p-3 border border-slate-200 dark:border-slate-700">
                                            <p class="text-xs text-slate-500 dark:text-slate-400 uppercase">In Stock</p>
                                            <p class="text-lg font-semibold text-slate-800 dark:text-slate-200" x-text="formatNumber(log.summary.in_stock?.total)"></p>
                                        </div>
                                        <div class="bg-white dark:bg-slate-900 rounded-lg p-3 border border-slate-200 dark:border-slate-700">
                                            <p class="text-xs text-slate-500 dark:text-slate-400 uppercase">Rented</p>
                                            <p class="text-lg font-semibold text-slate-800 dark:text-slate-200" x-text="formatNumber(log.summary.rented_in_customer?.total)"></p>
                                        </div>
                                        <div class="bg-white dark:bg-slate-900 rounded-lg p-3 border border-slate-200 dark:border-slate-700">
                                            <p class="text-xs text-slate-500 dark:text-slate-400 uppercase">Vendor Rent</p>
                                            <p class="text-lg font-semibold text-slate-800 dark:text-slate-200" x-text="formatNumber(log.summary.vendor_rent)"></p>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div> <!-- Close isUnlocked wrapper -->
</div>
@endsection
