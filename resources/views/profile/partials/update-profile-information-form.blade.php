<section style="background: #fff; border-radius: 20px; padding: 40px; margin-bottom: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;">
    <header style="margin-bottom: 24px;">
        <h2 style="font-size: 24px; font-weight: 800; color: #0f172a; margin: 0 0 8px 0;">
            Informações Pessoais
        </h2>
        <p style="color: #64748b; margin: 0; font-size: 15px;">
            Atualize as informações da sua conta e o seu endereço de e-mail.
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}">
        @csrf
        @method('patch')

        <!-- Nome -->
        <div style="margin-bottom: 20px;">
            <label for="name" style="font-weight: 700; font-size: 15px; color: #1e293b; display: block; margin-bottom: 8px;">Nome</label>
            <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name" style="width: 100%; padding: 14px 16px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 16px; outline: none; background: #f8fafc; font-family: inherit; transition: all 0.2s;">
            @error('name') <span style="color:#ef4444; font-size:14px; display: block; margin-top: 5px;">{{ $message }}</span> @enderror
        </div>

        <!-- E-mail -->
        <div style="margin-bottom: 20px;">
            <label for="email" style="font-weight: 700; font-size: 15px; color: #1e293b; display: block; margin-bottom: 8px;">E-mail</label>
            <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required autocomplete="username" style="width: 100%; padding: 14px 16px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 16px; outline: none; background: #f8fafc; font-family: inherit; transition: all 0.2s;">
            @error('email') <span style="color:#ef4444; font-size:14px; display: block; margin-top: 5px;">{{ $message }}</span> @enderror

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div style="margin-top: 15px; padding: 15px; background: #fffbeb; border: 1px solid #fde68a; border-radius: 10px;">
                    <p style="color: #92400e; font-size: 14px; margin: 0;">
                        O seu endereço de e-mail não está verificado.
                        <button form="send-verification" style="background: none; border: none; color: #2563eb; text-decoration: underline; cursor: pointer; font-weight: bold; font-family: inherit; font-size: 14px;">
                            Clique aqui para reenviar o e-mail de verificação.
                        </button>
                    </p>
                    @if (session('status') === 'verification-link-sent')
                        <p style="color: #059669; font-size: 14px; margin-top: 8px; font-weight: bold;">
                            Um novo link de verificação foi enviado para o seu e-mail.
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <!-- Botão Guardar -->
        <div style="display: flex; align-items: center; gap: 15px; margin-top: 30px;">
            <button type="submit" style="background: #2563eb; color: #fff; padding: 14px 28px; border-radius: 10px; font-weight: 700; font-size: 16px; border: none; cursor: pointer; font-family: inherit; transition: background 0.2s;">
                Guardar Alterações
            </button>

            @if (session('status') === 'profile-updated')
                <span style="color: #10b981; font-weight: bold; font-size: 15px;">Guardado!</span>
            @endif
        </div>
    </form>
</section>