<div style="background: #fff; border-radius: 20px; padding: 40px; margin-bottom: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;">
    <h2 style="font-size: 24px; font-weight: 800; color: #0f172a; margin-bottom: 8px;">Atualizar Palavra-passe</h2>
    <p style="color: #64748b; margin-bottom: 24px;">Certifique-se de que a sua conta usa uma palavra-passe longa e segura.</p>

    <form method="post" action="{{ route('password.update') }}">
        @csrf
        @method('put')

        <div>
            <label for="current_password" style="font-weight: 700; font-size: 15px; color: #1e293b; display: block; margin-bottom: 8px;">Palavra-passe Atual</label>
            <input id="current_password" name="current_password" type="password" style="width: 100%; padding: 14px 16px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 16px; outline: none; background: #f8fafc; font-family: inherit;">
            @error('current_password') <span style="color:#ef4444; font-size:14px; display: block; margin-top: 5px;">{{ $message }}</span> @enderror
        </div>

        <div style="margin-top: 20px;">
            <label for="password" style="font-weight: 700; font-size: 15px; color: #1e293b; display: block; margin-bottom: 8px;">Nova Palavra-passe</label>
            <input id="password" name="password" type="password" style="width: 100%; padding: 14px 16px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 16px; outline: none; background: #f8fafc; font-family: inherit;">
            @error('password') <span style="color:#ef4444; font-size:14px; display: block; margin-top: 5px;">{{ $message }}</span> @enderror
        </div>

        <div style="margin-top: 20px;">
            <label for="password_confirmation" style="font-weight: 700; font-size: 15px; color: #1e293b; display: block; margin-bottom: 8px;">Confirmar Nova Palavra-passe</label>
            <input id="password_confirmation" name="password_confirmation" type="password" style="width: 100%; padding: 14px 16px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 16px; outline: none; background: #f8fafc; font-family: inherit;">
            @error('password_confirmation') <span style="color:#ef4444; font-size:14px; display: block; margin-top: 5px;">{{ $message }}</span> @enderror
        </div>

        <div style="display: flex; align-items: center; margin-top: 30px;">
            <button type="submit" style="background: #2563eb; color: #fff; padding: 14px 28px; border-radius: 10px; font-weight: 700; font-size: 16px; border: none; cursor: pointer; font-family: inherit;">Atualizar Palavra-passe</button>
            @if (session('status') === 'password-updated')
                <span style="color: #10b981; font-weight: bold; margin-left: 15px; font-size: 15px;">Atualizada com sucesso!</span>
            @endif
        </div>
    </form>
</div>