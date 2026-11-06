@php
    $assistantGreeting = "Hi! I'm Brew Buddy, the ".(($site['brand_name'] ?? 'Brew & Go')).' store assistant. I can help with the menu, pickup orders, payments, and store details.';
@endphp

<div class="chatbot" data-chatbot data-chatbot-endpoint="{{ route('assistant.reply') }}">
    <section class="chatbot-panel" id="brewBuddyPanel" data-chatbot-panel role="dialog" aria-label="Brew Buddy store assistant" hidden>
        <header class="chatbot-head">
            <span class="chatbot-avatar" aria-hidden="true"><i class="bi bi-cup-hot-fill"></i></span>
            <div>
                <strong class="chatbot-head-title">Brew Buddy</strong>
                <span class="chatbot-head-status"><span class="live-dot"></span>Store assistant</span>
            </div>
            <button type="button" class="chatbot-head-close" data-chatbot-close aria-label="Close Brew Buddy">
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
        </header>

        <div class="chatbot-messages" data-chatbot-messages role="log" aria-live="polite" aria-relevant="additions">
            <div class="chatbot-message chatbot-message-bot">{{ $assistantGreeting }}</div>
        </div>

        <div class="chatbot-quick-replies" data-chatbot-quick-replies>
            <button type="button" class="chatbot-quick-reply" data-chatbot-quick="What are your opening hours?">Opening hours</button>
            <button type="button" class="chatbot-quick-reply" data-chatbot-quick="What is on the menu?">Menu</button>
            <button type="button" class="chatbot-quick-reply" data-chatbot-quick="Where is my order?">My order</button>
            <button type="button" class="chatbot-quick-reply" data-chatbot-quick="How does the pickup down payment work?">Payment</button>
            <button type="button" class="chatbot-quick-reply" data-chatbot-quick="How does pickup work?">Pickup</button>
        </div>

        <form class="chatbot-form" data-chatbot-form>
            <label class="visually-hidden" for="brewBuddyInput">Ask Brew Buddy a question</label>
            <input id="brewBuddyInput" class="form-control" type="text" name="message" maxlength="300" autocomplete="off" placeholder="Ask about orders, pickup, payment…" required data-chatbot-input>
            <button class="chatbot-send" type="submit" aria-label="Send message" data-chatbot-send>
                <i class="bi bi-send-fill" aria-hidden="true"></i>
            </button>
        </form>
    </section>

    <button type="button" class="chatbot-toggle" data-chatbot-toggle aria-expanded="false" aria-controls="brewBuddyPanel" aria-label="Open Brew Buddy">
        <i class="bi bi-chat-dots-fill" aria-hidden="true"></i>
        <i class="bi bi-x-lg" aria-hidden="true"></i>
    </button>
</div>
