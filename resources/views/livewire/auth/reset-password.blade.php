<div class="min-h-[70vh] flex items-center justify-center bg-slate-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 bg-white p-10 rounded-xl shadow-sm border border-slate-200">
        <div>
            <h2 class="text-center text-3xl font-extrabold text-brand-800">
                Set new password
            </h2>
        </div>

        @if (session('error'))
            <div class="bg-red-50 text-red-700 p-4 rounded-md border border-red-200 text-sm">
                {{ session('error') }}
            </div>
        @endif

        <form wire:submit.prevent="resetPassword" class="mt-8 space-y-6">
            <div>
                <label for="email" class="block text-sm font-medium text-slate-700">Email address</label>
                <div class="mt-1">
                    <input id="email" wire:model="email" type="email" autocomplete="email" required readonly
                           class="appearance-none block w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm bg-slate-100 text-slate-500 sm:text-sm">
                </div>
                @error('email') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-slate-700">New Password</label>
                <div class="mt-1">
                    <input id="password" wire:model="password" type="password" required
                           class="appearance-none block w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm placeholder-slate-400 focus:outline-none focus:ring-brand-500 focus:border-brand-500 sm:text-sm">
                </div>
                @error('password') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>
            
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Confirm Password</label>
                <div class="mt-1">
                    <input id="password_confirmation" wire:model="password_confirmation" type="password" required
                           class="appearance-none block w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm placeholder-slate-400 focus:outline-none focus:ring-brand-500 focus:border-brand-500 sm:text-sm">
                </div>
            </div>

            <div>
                <button type="submit"
                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-brand-700 hover:bg-brand-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500">
                    Reset password
                </button>
            </div>
        </form>
    </div>
</div>
