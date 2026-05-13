@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
<div x-data="dashboard()" x-cloak>

    {{-- =====================================================================
         Header
    ====================================================================== --}}
    <div class="mb-6 flex items-center justify-between flex-wrap gap-3">
        <h2 class="text-xl font-bold text-gray-800">Database Connections</h2>
        <button
            @click="openModal()"
            class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2
                   text-sm font-semibold text-white hover:bg-blue-700 transition-colors
                   focus:outline-none focus:ring-2 focus:ring-blue-500">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Add Connection
        </button>
    </div>

    {{-- =====================================================================
         Connection cards
    ====================================================================== --}}
    @if($connections->isEmpty())
    <div class="rounded-2xl border-2 border-dashed border-gray-200 bg-white px-6 py-16
                text-center text-gray-400">
        <p class="text-lg font-medium">No connections yet.</p>
        <p class="mt-1 text-sm">Click <strong>Add Connection</strong> to get started.</p>
    </div>
    @else
    <div class="grid gap-4 sm:grid-cols-1 md:grid-cols-2">
        @foreach($connections as $connection)
        <div
            x-data="connectionCard({{ $connection->id }}, '{{ $connection->status }}')"
            class="rounded-2xl bg-white shadow-sm border border-gray-200 p-5 flex flex-col gap-4">

            {{-- Card header --}}
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    {{-- Status dot (reactive) --}}
                    <span
                        class="mt-0.5 h-3 w-3 flex-shrink-0 rounded-full transition-colors duration-300"
                        :class="status === 'active' ? 'bg-green-500' : 'bg-red-500'"
                        :title="status === 'active' ? 'Active' : 'Paused'">
                    </span>
                    <h3 class="text-base font-semibold text-gray-900 truncate">
                        {{ $connection->alias }}
                    </h3>
                </div>

                {{-- Toggle pause/resume --}}
                <button
                    @click="toggle()"
                    :disabled="toggling"
                    class="flex-shrink-0 inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5
                           text-xs font-semibold transition-colors focus:outline-none
                           focus:ring-2 focus:ring-offset-1 disabled:opacity-50"
                    :class="status === 'active'
                        ? 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200 focus:ring-yellow-400'
                        : 'bg-green-100 text-green-800 hover:bg-green-200 focus:ring-green-400'">
                    <template x-if="toggling">
                        <svg class="h-3 w-3 animate-spin" xmlns="http://www.w3.org/2000/svg"
                             fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10"
                                    stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor"
                                  d="M4 12a8 8 0 018-8v8z"></path>
                        </svg>
                    </template>
                    <span x-text="status === 'active' ? 'Pause' : 'Resume'"></span>
                </button>
            </div>

            {{-- Schedules --}}
            <div class="divide-y divide-gray-100 rounded-lg border border-gray-100 bg-gray-50">
                @foreach($connection->schedules as $schedule)
                <div class="flex items-center justify-between gap-2 px-3 py-2 text-sm">
                    <div class="text-gray-600 leading-tight">
                        <span class="font-medium text-gray-800">{{ $schedule->frequencyLabel() }}</span>
                        &nbsp;·&nbsp;keep {{ $schedule->retentionLabel() }}
                    </div>
                    <div class="text-right text-xs text-gray-500 whitespace-nowrap">
                        @if($schedule->last_backup_at)
                            Next in {{ $schedule->nextBackupAt()->diffForHumans(null, true) }}
                        @else
                            <span class="text-amber-600 font-medium">No backup yet</span>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Actions --}}
            <div class="flex items-center gap-2 flex-wrap">
                <button
                    @click="backupNow()"
                    :disabled="backingUp || status !== 'active'"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-1.5
                           text-xs font-semibold text-white hover:bg-blue-700 transition-colors
                           focus:outline-none focus:ring-2 focus:ring-blue-500
                           disabled:opacity-50 disabled:cursor-not-allowed">
                    <template x-if="backingUp">
                        <svg class="h-3 w-3 animate-spin" xmlns="http://www.w3.org/2000/svg"
                             fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10"
                                    stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor"
                                  d="M4 12a8 8 0 018-8v8z"></path>
                        </svg>
                    </template>
                    <span x-text="backingUp ? 'Running…' : 'Backup Now'"></span>
                </button>

                <button
                    type="button"
                    @click="openSchedulesModalFromButton($el)"
                    data-connection-id="{{ $connection->id }}"
                    data-connection-alias="{{ e($connection->alias) }}"
                    data-connection-schedules="{{ base64_encode(json_encode($connection->schedules->map(fn ($s) => [
                        'id' => $s->id,
                        'frequency_hours' => (string) $s->frequency_hours,
                        'retention_amount' => (int) $s->retention_amount,
                        'retention_unit' => $s->retention_unit,
                    ])->values()->all())) }}"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-gray-100 px-3 py-1.5
                           text-xs font-semibold text-gray-700 hover:bg-gray-200 transition-colors
                           focus:outline-none focus:ring-2 focus:ring-gray-400">
                    Edit Schedules
                </button>

                {{-- Inline feedback --}}
                <span
                    x-show="backupMessage"
                    x-transition
                    class="text-xs font-medium"
                    :class="backupSuccess ? 'text-green-600' : 'text-red-600'"
                    x-text="backupMessage">
                </span>

                {{-- Delete connection --}}
                <form action="{{ route('connections.destroy', $connection) }}" method="POST"
                      class="ml-auto"
                      onsubmit="return confirm('Delete connection \'{{ $connection->alias }}\'? All backup history will be removed.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        class="inline-flex items-center rounded-lg px-2.5 py-1.5 text-xs
                               font-medium text-red-600 hover:bg-red-50 transition-colors
                               focus:outline-none focus:ring-2 focus:ring-red-400">
                        Delete
                    </button>
                </form>
            </div>

        </div>
        @endforeach
    </div>
    @endif

    {{-- =====================================================================
         Add Connection Modal
    ====================================================================== --}}
    <div
        x-show="showModal"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-40 flex items-start justify-center bg-black/60 backdrop-blur-sm
               overflow-y-auto px-4 py-8"
        @click.self="closeModal()">

        <div
            x-show="showModal"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="relative w-full max-w-lg rounded-2xl bg-white shadow-2xl">

            {{-- Modal header --}}
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                <h3 class="text-base font-semibold text-gray-900">Add PostgreSQL Connection</h3>
                <button @click="closeModal()"
                    class="rounded-lg p-1.5 text-gray-400 hover:text-gray-700 hover:bg-gray-100
                           transition-colors focus:outline-none">
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Modal body --}}
            <form action="{{ route('connections.store') }}" method="POST">
                @csrf
                <div class="px-6 py-5 space-y-5">

                    {{-- Alias --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Alias</label>
                        <input type="text" name="alias" required maxlength="100"
                               placeholder="e.g. production-db"
                               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm
                                      focus:outline-none focus:ring-2 focus:ring-blue-500"
                               @error('alias') value="{{ old('alias') }}" @enderror>
                        @error('alias')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Host + Port --}}
                    <div class="grid grid-cols-3 gap-3">
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Host</label>
                            <input type="text" name="host" required placeholder="127.0.0.1"
                                   class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm
                                          focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('host')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Port</label>
                            <input type="number" name="port" value="5432" required min="1" max="65535"
                                   class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm
                                          focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('port')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Database Name --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Database Name</label>
                        <input type="text" name="database_name" required
                               placeholder="myapp_production"
                               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm
                                      focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @error('database_name')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Username + Password --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                            <input type="text" name="username" required
                                   placeholder="postgres"
                                   class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm
                                          focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('username')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                            <input type="password" name="password" required
                                   class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm
                                          focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('password')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Backup Schedules --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-sm font-medium text-gray-700">Backup Schedules</label>
                            <button type="button" @click="addScheduleRow()"
                                class="text-xs font-medium text-blue-600 hover:text-blue-800
                                       focus:outline-none transition-colors">
                                + Add schedule
                            </button>
                        </div>

                        <div class="space-y-2">
                            <template x-for="(row, index) in scheduleRows" :key="index">
                                <div class="flex items-start gap-2 rounded-lg border border-gray-200
                                            bg-gray-50 p-3">

                                    {{-- Frequency --}}
                                    <div class="flex-1 min-w-0">
                                        <label class="block text-xs text-gray-500 mb-1">Frequency</label>
                                        <select
                                            :name="`schedules[${index}][frequency_hours]`"
                                            x-model="row.frequency_hours"
                                            class="w-full rounded border border-gray-300 px-2 py-1.5
                                                   text-sm focus:outline-none focus:ring-2
                                                   focus:ring-blue-500 bg-white">
                                            <option value="1">Every Hour</option>
                                            <option value="4">Every 4 hours</option>
                                            <option value="12">Every 12 hours</option>
                                            <option value="24">Every Day</option>
                                            <option value="48">Every 2 Days</option>
                                            <option value="168">Every Week</option>
                                            <option value="200">Every Month</option>
                                        </select>
                                    </div>

                                    {{-- Retention Amount --}}
                                    <div class="w-20 flex-shrink-0">
                                        <label class="block text-xs text-gray-500 mb-1">Keep for</label>
                                        <input
                                            type="number"
                                            :name="`schedules[${index}][retention_amount]`"
                                            x-model="row.retention_amount"
                                            min="1"
                                            required
                                            class="w-full rounded border border-gray-300 px-2 py-1.5
                                                   text-sm focus:outline-none focus:ring-2
                                                   focus:ring-blue-500">
                                    </div>

                                    {{-- Retention Unit --}}
                                    <div class="w-28 flex-shrink-0">
                                        <label class="block text-xs text-gray-500 mb-1">&nbsp;</label>
                                        <select
                                            :name="`schedules[${index}][retention_unit]`"
                                            x-model="row.retention_unit"
                                            class="w-full rounded border border-gray-300 px-2 py-1.5
                                                   text-sm focus:outline-none focus:ring-2
                                                   focus:ring-blue-500 bg-white">
                                            <option value="hours">Hours</option>
                                            <option value="days">Days</option>
                                            <option value="weeks">Weeks</option>
                                            <option value="months">Months</option>
                                            <option value="years">Years</option>
                                        </select>
                                    </div>

                                    {{-- Remove row --}}
                                    <div class="flex-shrink-0 pt-5">
                                        <button type="button"
                                            @click="removeScheduleRow(index)"
                                            :disabled="scheduleRows.length === 1"
                                            class="rounded p-1 text-gray-400 hover:text-red-500
                                                   disabled:opacity-30 transition-colors
                                                   focus:outline-none">
                                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                                 fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                 stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                      d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>

                                </div>
                            </template>
                        </div>

                        @error('schedules')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                </div>

                {{-- Modal footer --}}
                <div class="flex items-center justify-end gap-3 border-t border-gray-100
                            px-6 py-4">
                    <button type="button" @click="closeModal()"
                        class="rounded-lg px-4 py-2 text-sm font-medium text-gray-700
                               hover:bg-gray-100 transition-colors focus:outline-none">
                        Cancel
                    </button>
                    <button type="submit"
                        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white
                               hover:bg-blue-700 transition-colors focus:outline-none
                               focus:ring-2 focus:ring-blue-500">
                        Add Connection
                    </button>
                </div>

            </form>
        </div>
    </div>

    {{-- =====================================================================
         Edit Schedules Modal
    ====================================================================== --}}
    <div
        x-show="showSchedulesModal"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-start justify-center bg-black/60 backdrop-blur-sm
               overflow-y-auto px-4 py-8"
        @click.self="closeSchedulesModal()">

        <div
            x-show="showSchedulesModal"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="relative w-full max-w-lg rounded-2xl bg-white shadow-2xl">

            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                <h3 class="text-base font-semibold text-gray-900">
                    Edit Backup Schedules
                    <span class="text-gray-500 font-medium" x-text="editingConnectionAlias ? `- ${editingConnectionAlias}` : ''"></span>
                </h3>
                <button @click="closeSchedulesModal()"
                    class="rounded-lg p-1.5 text-gray-400 hover:text-gray-700 hover:bg-gray-100
                           transition-colors focus:outline-none">
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form method="POST" :action="`/connections/${editingConnectionId}/schedules`">
                @csrf
                @method('PUT')

                <div class="px-6 py-5 space-y-3">
                    <div class="flex items-center justify-between mb-1">
                        <label class="text-sm font-medium text-gray-700">Backup Schedules</label>
                        <button type="button" @click="addEditableSchedule()"
                            class="text-xs font-medium text-blue-600 hover:text-blue-800
                                   focus:outline-none transition-colors">
                            + Add schedule
                        </button>
                    </div>

                    <template x-for="(row, index) in editableScheduleRows" :key="`${row.id ?? 'new'}-${index}`">
                        <div class="flex items-start gap-2 rounded-lg border border-gray-200 bg-gray-50 p-3">
                            <input type="hidden" :name="`schedules[${index}][id]`" :value="row.id ?? ''">

                            <div class="flex-1 min-w-0">
                                <label class="block text-xs text-gray-500 mb-1">Frequency</label>
                                <select
                                    :name="`schedules[${index}][frequency_hours]`"
                                    x-model="row.frequency_hours"
                                    class="w-full rounded border border-gray-300 px-2 py-1.5 text-sm
                                           focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                                    <option value="1">Every Hour</option>
                                    <option value="4">Every 4 Hours</option>
                                    <option value="12">Every 12 Hours</option>
                                    <option value="24">Every Day</option>
                                    <option value="48">Every 2 Days</option>
                                    <option value="168">Every Week</option>
                                    <option value="200">Every Month</option>
                                </select>
                            </div>

                            <div class="w-20 flex-shrink-0">
                                <label class="block text-xs text-gray-500 mb-1">Keep for</label>
                                <input
                                    type="number"
                                    :name="`schedules[${index}][retention_amount]`"
                                    x-model="row.retention_amount"
                                    min="1"
                                    required
                                    class="w-full rounded border border-gray-300 px-2 py-1.5 text-sm
                                           focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>

                            <div class="w-28 flex-shrink-0">
                                <label class="block text-xs text-gray-500 mb-1">&nbsp;</label>
                                <select
                                    :name="`schedules[${index}][retention_unit]`"
                                    x-model="row.retention_unit"
                                    class="w-full rounded border border-gray-300 px-2 py-1.5 text-sm
                                           focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                                    <option value="hours">Hours</option>
                                    <option value="days">Days</option>
                                    <option value="weeks">Weeks</option>
                                    <option value="months">Months</option>
                                    <option value="years">Years</option>
                                </select>
                            </div>

                            <div class="flex-shrink-0 pt-5">
                                <button type="button"
                                    @click="removeEditableSchedule(index)"
                                    :disabled="editableScheduleRows.length === 1"
                                    class="rounded p-1 text-gray-400 hover:text-red-500 disabled:opacity-30
                                           transition-colors focus:outline-none">
                                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-gray-100 px-6 py-4">
                    <button type="button" @click="closeSchedulesModal()"
                        class="rounded-lg px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100
                               transition-colors focus:outline-none">
                        Cancel
                    </button>
                    <button type="submit"
                        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white
                               hover:bg-blue-700 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500">
                        Save Schedules
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
function dashboard() {
    return {
        showModal: {{ $errors->any() ? 'true' : 'false' }},
        showSchedulesModal: false,
        editingConnectionId: null,
        editingConnectionAlias: '',
        editableScheduleRows: [],
        scheduleRows: [
            { frequency_hours: '24', retention_amount: 7, retention_unit: 'days' }
        ],

        updateBodyOverflow() {
            document.body.style.overflow = (this.showModal || this.showSchedulesModal) ? 'hidden' : '';
        },

        openModal() {
            this.showModal = true;
            this.updateBodyOverflow();
        },

        closeModal() {
            this.showModal = false;
            this.updateBodyOverflow();
        },

        openSchedulesModal(connectionId, alias, schedules) {
            this.editingConnectionId = connectionId;
            this.editingConnectionAlias = alias;
            this.editableScheduleRows = (schedules || []).map((item) => ({
                id: item.id ?? null,
                frequency_hours: String(item.frequency_hours ?? '24'),
                retention_amount: Number(item.retention_amount ?? 7),
                retention_unit: item.retention_unit ?? 'days',
            }));

            if (this.editableScheduleRows.length === 0) {
                this.editableScheduleRows = [{ id: null, frequency_hours: '24', retention_amount: 7, retention_unit: 'days' }];
            }

            this.showSchedulesModal = true;
            this.updateBodyOverflow();
        },

        openSchedulesModalFromButton(el) {
            const id = Number(el.dataset.connectionId || 0);
            const alias = el.dataset.connectionAlias || '';

            let schedules = [];
            try {
                schedules = JSON.parse(atob(el.dataset.connectionSchedules || 'W10='));
            } catch {
                schedules = [];
            }

            this.openSchedulesModal(id, alias, schedules);
        },

        closeSchedulesModal() {
            this.showSchedulesModal = false;
            this.editingConnectionId = null;
            this.editingConnectionAlias = '';
            this.editableScheduleRows = [];
            this.updateBodyOverflow();
        },

        addEditableSchedule() {
            this.editableScheduleRows.push({ id: null, frequency_hours: '24', retention_amount: 7, retention_unit: 'days' });
        },

        removeEditableSchedule(index) {
            if (this.editableScheduleRows.length > 1) {
                this.editableScheduleRows.splice(index, 1);
            }
        },

        addScheduleRow() {
            this.scheduleRows.push({ frequency_hours: '24', retention_amount: 7, retention_unit: 'days' });
        },

        removeScheduleRow(index) {
            if (this.scheduleRows.length > 1) {
                this.scheduleRows.splice(index, 1);
            }
        },
    };
}

function connectionCard(id, initialStatus) {
    return {
        status: initialStatus,
        toggling: false,
        backingUp: false,
        backupMessage: '',
        backupSuccess: false,

        async toggle() {
            this.toggling = true;
            try {
                const resp = await fetch(`/connections/${id}/toggle`, {
                    method: 'PUT',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                if (!resp.ok) throw new Error('Toggle failed');
                const data = await resp.json();
                this.status = data.status;
            } catch {
                // Silently revert on error — page reload will show true state
            } finally {
                this.toggling = false;
            }
        },

        async backupNow() {
            this.backingUp = true;
            this.backupMessage = '';
            try {
                const resp = await fetch(`/connections/${id}/backup`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                const data = await resp.json();
                this.backupSuccess = data.success ?? resp.ok;
                this.backupMessage = this.backupSuccess ? 'Backup done!' : 'Backup failed.';
            } catch {
                this.backupSuccess = false;
                this.backupMessage = 'Request failed.';
            } finally {
                this.backingUp = false;
                setTimeout(() => { this.backupMessage = ''; }, 5000);
            }
        },
    };
}
</script>
@endsection
