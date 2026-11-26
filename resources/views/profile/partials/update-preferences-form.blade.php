
@php
    $selectedRoute = old(
        'preferred_route',
        $user->preferred_route?->value ?? 'fastest'
    );

    $selectedModes = old('transport_modes', $user->transport_modes ?? []);
    $selectedModes = is_array($selectedModes) ? $selectedModes : [];

    $selectedNeeds = old('accessibility_needs', $user->accessibility_needs ?? []);
    $selectedNeeds = is_array($selectedNeeds) ? $selectedNeeds : [];
@endphp

<section style="background: #fff; border-radius: 20px; padding: 40px; margin-bottom: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;">

    <h2 style="font-size: 24px; font-weight: 800; color: #0f172a; margin-bottom: 8px;">
        Preferências de Mobilidade
    </h2>

    <p style="color: #64748b; margin-bottom: 28px;">
        Personalize suas viagens de acordo com suas preferências e necessidades.
    </p>

    @if (session('status') === 'preferences-updated')
        <div style="background: #d1fae5; color: #065f46; padding: 15px; border-radius: 10px; margin-bottom: 24px; font-weight: 600;">
            Preferências atualizadas com sucesso!
        </div>
    @endif

    <form method="POST" action="{{ route('profile.update') }}">
        @csrf
        @method('PATCH')

        <input type="hidden" name="preferences_form" value="1">

        <div style="margin-bottom: 30px;">

            <label for="preferred_route" style="font-weight: 700; font-size: 16px; color: #1e293b; display: block; margin-bottom: 10px;">
                Qual tipo de rota você prefere?
            </label>

            <select
                id="preferred_route"
                name="preferred_route"
                style="width: 100%; padding: 14px 16px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 16px; background: #f8fafc; font-family: inherit;"
            >
                <option value="fastest" @selected($selectedRoute === 'fastest')>
                    Mais rápida
                </option>

                <option value="cheapest" @selected($selectedRoute === 'cheapest')>
                    Mais barata
                </option>

                <option value="transfers" @selected($selectedRoute === 'transfers')>
                    Menos trocas
                </option>

                <option value="sustainable" @selected($selectedRoute === 'sustainable')>
                    Mais sustentável
                </option>
            </select>

            @error('preferred_route')
                <span style="color: #ef4444; font-size: 14px;">
                    {{ $message }}
                </span>
            @enderror

        </div>

        <div style="margin-bottom: 30px;">

            <h3 style="font-size: 17px; font-weight: 700; color: #1e293b; margin-bottom: 8px;">
                Meios de transporte preferidos
            </h3>

            <p style="color: #64748b; font-size: 14px; margin-bottom: 16px;">
                Você pode selecionar mais de uma opção.
            </p>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px;">

                @foreach ([
                    'bus' => 'Ônibus',
                    'metro' => 'Metrô',
                    'bike' => 'Bicicleta',
                    'taxi' => 'Táxi',
                    'app' => 'Aplicativo',
                    'walk' => 'Caminhada',
                ] as $code => $label)

                    <label style="display: flex; align-items: center; gap: 10px; padding: 15px; border: 1px solid #e2e8f0; border-radius: 10px; background: #f8fafc; cursor: pointer;">

                        <input
                            type="checkbox"
                            name="transport_modes[]"
                            value="{{ $code }}"
                            @checked(in_array($code, $selectedModes, true))
                        >

                        <span style="color: #334155; font-weight: 600;">
                            {{ $label }}
                        </span>

                    </label>

                @endforeach

            </div>

            @foreach ($errors->get('transport_modes.*') as $messages)
                @foreach ($messages as $message)
                    <span style="color: #ef4444; font-size: 14px; display: block; margin-top: 5px;">
                        {{ $message }}
                    </span>
                @endforeach
            @endforeach

        </div>

        <div style="margin-bottom: 30px;">

            <h3 style="font-size: 17px; font-weight: 700; color: #1e293b; margin-bottom: 8px;">
                Necessidades de acessibilidade
            </h3>

            <p style="color: #64748b; font-size: 14px; margin-bottom: 16px;">
                Selecione os recursos importantes para suas viagens.
            </p>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px;">

                @foreach ([
                    'wheelchair' => 'Cadeira de rodas',
                    'reduced_mobility' => 'Mobilidade reduzida',
                    'visual' => 'Deficiência visual',
                    'hearing' => 'Deficiência auditiva',
                ] as $code => $label)

                    <label style="display: flex; align-items: center; gap: 10px; padding: 15px; border: 1px solid #e2e8f0; border-radius: 10px; background: #f8fafc; cursor: pointer;">

                        <input
                            type="checkbox"
                            name="accessibility_needs[]"
                            value="{{ $code }}"
                            @checked(in_array($code, $selectedNeeds, true))
                        >

                        <span style="color: #334155; font-weight: 600;">
                            {{ $label }}
                        </span>

                    </label>

                @endforeach

            </div>

            @foreach ($errors->get('accessibility_needs.*') as $messages)
                @foreach ($messages as $message)
                    <span style="color: #ef4444; font-size: 14px; display: block; margin-top: 5px;">
                        {{ $message }}
                    </span>
                @endforeach
            @endforeach

        </div>

        <button
            type="submit"
            style="background: #2563eb; color: #fff; padding: 14px 28px; border-radius: 10px; font-weight: 700; font-size: 16px; border: none; cursor: pointer; font-family: inherit;"
        >
            Salvar Preferências
        </button>

    </form>

</section>
