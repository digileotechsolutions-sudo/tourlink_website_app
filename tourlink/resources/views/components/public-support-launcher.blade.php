<details class="public-support-launcher" data-support-widget
    @auth
        @if (auth()->user()->role !== \App\Role::Admin)
            data-start-url="{{ route('support.conversations.store') }}"
            data-messages-url="{{ route('support.messages.index', ['supportConversation' => '__CONVERSATION__']) }}"
            data-send-url="{{ route('support.messages.store', ['supportConversation' => '__CONVERSATION__']) }}"
            data-csrf-token="{{ csrf_token() }}"
        @endif
    @endauth>
    <summary class="public-support-launcher__button" aria-label="Open support chat">
        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 11.5a8.4 8.4 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.4 8.4 0 0 1-3.8-.9L3 21l1.9-5.7a8.4 8.4 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.4 8.4 0 0 1 3.8-.9h.5a8.5 8.5 0 0 1 8 8z"/>
        </svg>
        <span>@auth{{ auth()->user()->role === \App\Role::Admin ? 'Support inbox' : 'Chat with support' }}@else Chat with support @endauth</span>
    </summary>
    <section class="public-support-launcher__panel" aria-label="Message Havenedge Tourlink support">
        <header class="public-support-launcher__header">
            <span class="support-online-dot" aria-hidden="true"></span>
            <div>
                <h2>Havenedge Tourlink Support</h2>
                <p>Message our team directly</p>
            </div>
        </header>
        @auth
            @if (auth()->user()->role === \App\Role::Admin)
                <p class="public-support-launcher__message">View and reply to customer conversations in the admin support inbox.</p>
                <a class="public-support-launcher__action" href="{{ route('admin.support.index') }}">Open support inbox</a>
            @else
                <p class="support-widget__notice" data-support-notice role="status">Open a private chat with our team. Replies will appear here.</p>
                <div class="support-widget__messages" data-widget-messages role="log" aria-live="polite" aria-relevant="additions text" aria-label="Support messages"></div>
                <form class="support-widget__form" data-support-form data-no-loading>
                    <label class="sr-only" for="support-widget-message">Your message</label>
                    <textarea id="support-widget-message" name="message" rows="2" maxlength="4000" placeholder="Write a message..." required data-support-input></textarea>
                    <button type="submit" aria-label="Send message" data-support-send>
                        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m22 2-7 20-4-9-9-4 20-7ZM22 2 11 13"/></svg>
                    </button>
                </form>
                <p class="support-widget__error" data-widget-error role="alert" hidden></p>
                <button class="support-widget__new-chat" type="button" data-support-new-chat hidden>Start a new chat</button>
                <a class="public-support-launcher__action" href="{{ route('support.index') }}">Open full support chat</a>
            @endif
        @else
            <p class="public-support-launcher__message">Sign in to send a private message to our support team. No phone call or email is needed.</p>
            <a class="public-support-launcher__action" href="{{ route('login', ['continue' => 'support']) }}">Sign in to chat</a>
            <a class="public-support-launcher__register" href="{{ route('register', ['continue' => 'support']) }}">Create an account</a>
        @endauth
    </section>
</details>
