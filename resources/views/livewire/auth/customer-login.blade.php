<div class="max-w-md mx-auto mt-16 bg-white p-8 rounded-lg shadow-sm border border-slate-200">
    <h2 class="text-2xl font-bold text-brand-900 mb-6 text-center">Customer Portal Login</h2>

    <form wire:submit.prevent="login" class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
            <input type="email" wire:model="email" class="w-full border-slate-300 rounded-md shadow-sm focus:border-brand-500 focus:ring-brand-500" required autofocus>
            @error('email') <span class="text-sm text-red-600 mt-1 block">{{ $message }}</span> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
            <input type="password" wire:model="password" class="w-full border-slate-300 rounded-md shadow-sm focus:border-brand-500 focus:ring-brand-500" required>
            @error('password') <span class="text-sm text-red-600 mt-1 block">{{ $message }}</span> @enderror
        </div>

        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <input type="checkbox" wire:model="remember" id="remember" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                <label for="remember" class="ml-2 block text-sm text-slate-700">Remember me</label>
            </div>
            <div class="text-sm">
                <a href="{{ route('password.request') }}" class="font-medium text-brand-600 hover:text-brand-500">
                    Forgot your password?
                </a>
            </div>
        </div>

        <div class="pt-2">
            <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500">
                Sign in
            </button>
        </div>
    </form>
    
    <div class="mt-6 text-center text-sm text-slate-500">
        <p>Don't have an account? Book an appointment to create one.</p>
    </div>
</div>
