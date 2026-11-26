
<x-app-layout>
    <div style="max-width: 800px; margin: 0 auto; padding-bottom: 40px;">

        @if (session('status') === 'profile-updated')
            <div style="background: #d1fae5; color: #065f46; padding: 15px; border-radius: 10px; margin-bottom: 20px; font-weight: 600;">
                Perfil atualizado com sucesso!
            </div>
        @endif

        {{-- Informações pessoais --}}
        @include('profile.partials.update-profile-information-form')

        {{-- Preferências de mobilidade --}}
        @include('profile.partials.update-preferences-form')

        {{-- Atualização de senha --}}
        @include('profile.partials.update-password-form')

        {{-- Exclusão da conta --}}
        @include('profile.partials.delete-user-form')

    </div>
</x-app-layout>
