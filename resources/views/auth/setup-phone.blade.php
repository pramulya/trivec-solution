<x-guest-layout>
    <div class="mb-6 text-center">
        <h2 class="text-3xl font-extrabold text-gray-900">
            One Last Step 📱
        </h2>
        <p class="mt-2 text-sm text-gray-600">
            Please enter your work phone number for SMS integration.
        </p>
    </div>

    @if (session('status'))
        <div class="mb-4 font-medium text-sm text-green-600">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('phone.store') }}">
        @csrf

        <!-- Phone Number -->
        <div>
            <label for="phone_number" class="block font-medium text-sm text-gray-700">
                Phone Number
            </label>
            <input id="phone_number" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            type="tel"
                            name="phone_number"
                            required
                            autofocus
                            placeholder="62812345678" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <button class="w-full inline-flex items-center justify-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                Complete Setup
            </button>
        </div>
    </form>
</x-guest-layout>
