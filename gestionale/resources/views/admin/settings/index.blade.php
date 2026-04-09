@extends('admin.layouts.app')

@section('title', 'Settings')

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-cog mr-2 text-emerald-600"></i> Impostazioni di Sistema
        </h1>
    </div>

    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valore</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tabella</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ordinamento</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stato</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Azioni</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($settings as $setting)
                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $setting->id_settings ?? $setting->id }}
                        </td>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-600 max-w-md break-words">
                                {{ $setting->valore ?: '-' }}
                            </div>
                        </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                {{ $setting->tabella_riferimento }}
                            </span>
                        </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $setting->ordinamento }}
                        </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                {{ $setting->valid ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $setting->valid ? 'Attivo' : 'Disattivo' }}
                            </span>
                        </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            @php
                                $settingId = $setting->id_settings ?? $setting->id;
                            @endphp
                            <a href="{{ route('admin.settings.edit', $settingId) }}" 
                               class="text-emerald-600 hover:text-emerald-900 transition-colors">
                                <i class="fas fa-edit mr-1"></i> Modifica
                            </a>
                        </div>
                    </div>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                            <i class="fas fa-cog text-4xl mb-2 block"></i>
                            Nessuna impostazione trovata
                        </div>
                    </div>
                    @endforelse
                </tbody>
             </div>
        </div>
    </div>
</div>
@endsection