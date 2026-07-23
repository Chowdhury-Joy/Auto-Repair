<x-layouts.public title="Contact Us · TrueWrench Auto Repair">
    <div class="max-w-4xl mx-auto space-y-10">
        <div class="text-center">
            <h1 class="text-4xl font-bold text-brand-800">Contact Us</h1>
            <p class="text-slate-600 mt-2">Questions or custom requests? We're here to help.</p>
        </div>

        <div class="grid md:grid-cols-2 gap-8">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-8 space-y-4">
                <h2 class="text-xl font-bold text-brand-700">Shop Location & Hours</h2>
                <div class="text-sm text-slate-700 space-y-2">
                    <p class="font-semibold text-slate-900">TrueWrench Auto Repair</p>
                    <p>1234 Industrial Way</p>
                    <p>Portland, OR 97201</p>
                    <p class="mt-4 font-semibold text-slate-900">Phone</p>
                    <p>(503) 555-0100</p>
                    <p class="mt-4 font-semibold text-slate-900">Hours</p>
                    <p>Monday – Friday: 8:00 AM – 6:00 PM</p>
                    <p>Saturday: 9:00 AM – 2:00 PM</p>
                    <p>Sunday: Closed</p>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-8 space-y-4">
                <h2 class="text-xl font-bold text-brand-700">Send a Message</h2>
                <form class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Your Name</label>
                        <input type="text" class="w-full border-slate-300 rounded-md mt-1 shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Email Address</label>
                        <input type="email" class="w-full border-slate-300 rounded-md mt-1 shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Message</label>
                        <textarea rows="4" class="w-full border-slate-300 rounded-md mt-1 shadow-sm"></textarea>
                    </div>
                    <button type="button" class="bg-brand-700 hover:bg-brand-800 text-white font-bold px-6 py-2 rounded-md shadow">
                        Send Message
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-layouts.public>
