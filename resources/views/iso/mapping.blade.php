<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Correspondance ISO 27001:2013 → ISO 27001:2022
        </h2>
    </x-slot>

    <div class="py-6">

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white shadow-sm sm:rounded-lg p-6">

                <p class="text-sm text-gray-600 mb-2">
                    Wazuh étiquette ses règles avec la numérotation ISO 27001:2013 (114 contrôles).
                    L'application évalue la conformité avec ISO 27001:2022 (93 contrôles).
                    Cette table, issue de l'annexe B de l'ISO/IEC 27002:2022, sert à proposer le contrôle
                    dans la fiche incident ; le choix final reste celui de l'analyste.
                </p>

                <p class="text-sm text-gray-600 mb-4">
                    <span class="px-2 py-0.5 text-xs rounded bg-green-100 text-green-800">dans la liste</span>
                    contrôle disponible dans le formulaire ·
                    <span class="px-2 py-0.5 text-xs rounded bg-indigo-50 text-indigo-700">Wazuh × n</span>
                    référence présente dans n incidents importés
                </p>

                <div class="overflow-x-auto">

                    <table class="min-w-full divide-y divide-gray-200 text-sm">

                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">ISO 27001:2013 (Wazuh)</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">ISO 27001:2022</th>
                            </tr>
                        </thead>

                        <tbody class="bg-white divide-y divide-gray-200">

                            @foreach($mapping as $code2013 => $codes2022)
                                <tr class="{{ isset($seenInWazuh[$code2013]) ? 'bg-indigo-50' : '' }}">
                                    <td class="px-4 py-2 whitespace-nowrap">
                                        {{ $code2013 }}
                                        @isset($seenInWazuh[$code2013])
                                            <span class="ml-2 px-2 py-0.5 text-xs rounded bg-indigo-50 text-indigo-700">Wazuh × {{ $seenInWazuh[$code2013] }}</span>
                                        @endisset
                                    </td>
                                    <td class="px-4 py-2">
                                        @foreach($codes2022 as $code)
                                            <div>
                                                <strong>{{ $code }}</strong> {{ $controls[$code] ?? '' }}
                                                @if(in_array($code, $inApp))
                                                    <span class="ml-2 px-2 py-0.5 text-xs rounded bg-green-100 text-green-800">dans la liste</span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </td>
                                </tr>
                            @endforeach

                        </tbody>

                    </table>

                </div>

                <h3 class="text-lg font-semibold text-gray-900 mt-8 mb-2">
                    Contrôles nouveaux en 2022 (sans équivalent 2013)
                </h3>

                <ul class="text-sm text-gray-700 space-y-1">
                    @foreach(array_diff(array_keys($controls), array_merge(...array_values($mapping))) as $code)
                        <li>
                            <strong>{{ $code }}</strong> {{ $controls[$code] }}
                            @if(in_array($code, $inApp))
                                <span class="ml-2 px-2 py-0.5 text-xs rounded bg-green-100 text-green-800">dans la liste</span>
                            @endif
                        </li>
                    @endforeach
                </ul>

            </div>

        </div>

    </div>

</x-app-layout>
