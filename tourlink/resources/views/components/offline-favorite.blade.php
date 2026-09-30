@if(auth()->check() && auth()->user()->role === \App\Role::Traveler)
    <form class="mb-5" method="POST" action="{{ route('traveler.favorites.save', $trip) }}" data-offline-favorite data-user-id="{{ auth()->id() }}" data-trip-id="{{ $trip->id }}">
        @csrf
        <input type="hidden" name="trip_id" value="{{ $trip->id }}">
        <input type="hidden" name="expected_user_id" value="{{ auth()->id() }}">
        <button class="min-h-11 rounded-xl border border-emerald-800 px-4 py-3 text-sm font-bold text-emerald-800" type="submit">Save trip</button>
    </form>
@endif