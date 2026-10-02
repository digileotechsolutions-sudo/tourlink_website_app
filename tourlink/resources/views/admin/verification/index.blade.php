@extends('layouts.admin')

@section('title', 'Verification requests | Havenedge Tourlink')

@section('content')
    <div class="mx-auto max-w-7xl px-5 py-10 sm:px-8">
        <header class="border-b border-slate-200 pb-6">
            <p class="text-xs font-bold uppercase tracking-wider text-emerald-800">Administration</p>
            <h1 class="mt-2 text-3xl font-black text-slate-950">Verification requests</h1>
            <p class="mt-2 text-sm text-slate-600">Review submitted documents and update the member’s verification level.</p>
        </header>

        @if (session('status'))
            <p role="status" class="mt-5 border-l-4 border-emerald-700 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-950">{{ session('status') }}</p>
        @endif
        @if ($errors->any())
            <div role="alert" class="mt-5 border-l-4 border-red-700 bg-red-50 px-4 py-3 text-sm text-red-950"><p class="font-bold">The review could not be saved.</p>@foreach ($errors->all() as $error)<p class="mt-1">{{ $error }}</p>@endforeach</div>
        @endif

        <form method="GET" class="mt-6 grid gap-3 border-b border-slate-200 pb-6 sm:grid-cols-[minmax(240px,1fr)_200px_auto_auto]">
            <label class="sr-only" for="verification-search">Search applicants</label>
            <input id="verification-search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Applicant name or email" class="min-h-11 rounded border border-slate-300 px-3 text-sm">
            <label class="sr-only" for="verification-status">Request status</label>
            <select id="verification-status" name="status" class="min-h-11 rounded border border-slate-300 bg-white px-3 text-sm">
                <option value="">Pending, under review, and documents required</option>
                @foreach (\App\VerificationStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
            <button class="min-h-11 rounded border border-slate-300 px-4 text-sm font-bold text-slate-800 hover:bg-slate-50">Filter</button>
            <a href="{{ route('admin.verification.index') }}" class="grid min-h-11 place-items-center text-sm font-semibold text-slate-600 hover:text-slate-950">Clear</a>
        </form>

        @forelse ($requests as $verificationRequest)
            <article class="grid gap-5 border-b border-slate-200 py-6 lg:grid-cols-[minmax(0,1fr)_minmax(300px,0.8fr)]">
                <div>
                    <div class="flex flex-wrap items-baseline justify-between gap-3">
                        <h2 class="text-lg font-extrabold text-slate-950">{{ $verificationRequest->user?->name ?? 'Unknown applicant' }}</h2>
                        <span class="text-xs font-bold text-slate-700">{{ $verificationRequest->status->label() }}</span>
                    </div>
                    <p class="mt-1 text-sm text-slate-600">{{ $verificationRequest->user?->email }} · {{ str($verificationRequest->user?->role?->value ?? 'UNKNOWN')->replace('_', ' ')->title() }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ str($verificationRequest->type)->replace('_', ' ')->title() }} · Submitted {{ $verificationRequest->created_at?->format('Y-m-d H:i') }}</p>
                    @if ($verificationRequest->vehicle)
                        <dl class="mt-3 grid gap-x-4 gap-y-1 rounded border border-slate-200 bg-slate-50 p-3 text-xs sm:grid-cols-2">
                            <div><dt class="inline font-bold text-slate-700">Registration:</dt> <dd class="inline text-slate-600">{{ $verificationRequest->vehicle->registration_number }}</dd></div>
                            <div><dt class="inline font-bold text-slate-700">Vehicle:</dt> <dd class="inline text-slate-600">{{ $verificationRequest->vehicle->year }} {{ $verificationRequest->vehicle->make }} {{ $verificationRequest->vehicle->model }}</dd></div>
                            <div><dt class="inline font-bold text-slate-700">Type:</dt> <dd class="inline text-slate-600">{{ $verificationRequest->vehicle->body_type }}</dd></div>
                            <div><dt class="inline font-bold text-slate-700">Seats:</dt> <dd class="inline text-slate-600">{{ $verificationRequest->vehicle->seating_capacity }}</dd></div>
                        </dl>
                    @endif
                    @if ($verificationRequest->type === 'OPERATOR' && $verificationRequest->user?->operatorProfile)
                        <dl class="mt-3 grid gap-2 rounded border border-slate-200 bg-slate-50 p-3 text-sm sm:grid-cols-2">
                            <div><dt class="font-bold text-slate-700">Business</dt><dd class="text-slate-600">{{ $verificationRequest->user->operatorProfile->company_name }}</dd></div>
                            <div><dt class="font-bold text-slate-700">Years operating</dt><dd class="text-slate-600">{{ $verificationRequest->user->operatorProfile->years_active }}</dd></div>
                            <div class="sm:col-span-2"><dt class="font-bold text-slate-700">Company/business profile</dt><dd class="whitespace-pre-wrap text-slate-600">{{ $verificationRequest->user->operatorProfile->description }}</dd></div>
                        </dl>
                    @endif
                    @if ($verificationRequest->documents)
                        <div class="mt-3">
                            <p class="text-sm font-bold text-slate-800">Submitted documents</p>
                            <ul class="mt-2 grid gap-2">
                                @foreach ($verificationRequest->documents as $document)
                                    @php($label = $document['label'] ?? str($document['key'] ?? 'Document')->headline())
                                    <li class="flex flex-wrap items-center justify-between gap-2 rounded border border-slate-200 bg-slate-50 px-3 py-2">
                                        <span class="text-sm font-semibold text-slate-700">{{ $label }}@if (! empty($document['size'])) <span class="font-normal text-slate-500">({{ number_format($document['size'] / 1024, 0) }} KB)</span>@endif</span>
                                        @if (Route::has('admin.verification.document'))
                                            <a href="{{ route('admin.verification.document', [$verificationRequest, $document['key'] ?? '']) }}" target="_blank" rel="noopener" class="rounded border border-slate-300 bg-white px-3 py-1.5 text-xs font-bold text-slate-800 hover:bg-slate-100">Open</a>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @else
                        <p class="mt-3 text-xs text-slate-500">No documents attached.</p>
                    @endif
                    @if ($verificationRequest->notes)<p class="mt-3 whitespace-pre-wrap text-sm text-slate-700">{{ $verificationRequest->notes }}</p>@endif
                </div>
                <form method="POST" action="{{ route('admin.verification.update', $verificationRequest) }}" class="grid content-start gap-3">
                    @csrf @method('PATCH')
                    <label class="grid gap-1 text-sm font-semibold text-slate-700">Decision
                        <select name="status" required class="min-h-11 rounded border border-slate-300 bg-white px-3 text-sm font-normal">
                            <option value="APPROVED">Approve</option>
                            <option value="REJECTED">Reject</option>
                            <option value="UNDER_REVIEW">Mark under review</option>
                            <option value="MORE_INFO">Request documents</option>
                        </select>
                    </label>
                    <label class="grid gap-1 text-sm font-semibold text-slate-700">Reviewer note<textarea name="notes" maxlength="2000" rows="3" class="rounded border border-slate-300 px-3 py-2 text-sm font-normal" placeholder="Include any action the applicant needs to take."></textarea></label>
                    <button class="justify-self-start rounded bg-emerald-800 px-5 py-2.5 text-sm font-bold text-white hover:bg-emerald-900">Save review</button>
                </form>
            </article>
        @empty
            <p class="py-10 text-sm text-slate-600">No verification requests match these filters.</p>
        @endforelse
        <div class="mt-6">{{ $requests->links() }}</div>
    </div>
@endsection
