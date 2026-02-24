@extends('layouts.app')
@section('title', 'AI Assistant')
@section('page-title', 'AI Assistant')

@section('content')
<div class="max-w-4xl mx-auto h-full">
    <div class="card flex flex-col" style="height: calc(100vh - 180px);" x-data="aiChat()">

        {{-- Header --}}
        <div class="gradient-brand p-5 rounded-t-2xl flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-robot text-white text-lg"></i>
                </div>
                <div>
                    <h2 class="text-white font-bold">Library & Media AI Assistant</h2>
                    <p class="text-blue-200 text-xs">Powered by GPT — Ask about books, get recommendations, discuss media</p>
                </div>
            </div>

            {{-- Context Switch --}}
            <div class="flex gap-2">
                <button @click="context = 'library'" class="text-xs px-3 py-1.5 rounded-lg transition"
                        :class="context === 'library' ? 'bg-white text-blue-700 font-semibold' : 'text-white/70 hover:text-white hover:bg-white/10'">
                    📚 Library
                </button>
                <button @click="context = 'media'" class="text-xs px-3 py-1.5 rounded-lg transition"
                        :class="context === 'media' ? 'bg-white text-purple-700 font-semibold' : 'text-white/70 hover:text-white hover:bg-white/10'">
                    🎬 Media
                </button>
            </div>
        </div>

        {{-- Messages --}}
        <div class="flex-1 overflow-y-auto p-4 space-y-4" x-ref="messages">

            {{-- Welcome --}}
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900 rounded-full flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-robot text-purple-500 text-sm"></i>
                </div>
                <div class="max-w-lg bg-gray-100 dark:bg-gray-700 rounded-2xl rounded-tl-none px-4 py-3">
                    <p class="text-sm text-gray-700 dark:text-gray-200">
                        Hello! I'm your AI assistant for Library & Media management. I can help you:
                    </p>
                    <ul class="text-sm text-gray-600 dark:text-gray-300 mt-2 space-y-1">
                        <li>📚 Find books by genre, mood, or similarity</li>
                        <li>🎬 Recommend movies, music, games based on your taste</li>
                        <li>💬 Discuss plots, themes, or anything about a book/film</li>
                        <li>🤖 Generate summaries or suggest your next read/watch</li>
                    </ul>
                    <p class="text-sm text-gray-600 dark:text-gray-300 mt-2">What would you like to explore today?</p>
                </div>
            </div>

            {{-- Quick suggestions --}}
            <div class="flex flex-wrap gap-2 ml-11">
                <template x-for="suggestion in suggestions">
                    <button @click="sendSuggestion(suggestion)"
                            class="text-xs px-3 py-1.5 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-full hover:bg-blue-100 transition border border-blue-100 dark:border-blue-800">
                        <span x-text="suggestion"></span>
                    </button>
                </template>
            </div>

            {{-- Chat messages --}}
            <template x-for="msg in messages" :key="msg.id">
                <div class="flex items-start gap-3" :class="msg.role === 'user' ? 'flex-row-reverse' : ''">
                    {{-- Avatar --}}
                    <div class="w-8 h-8 rounded-full flex-shrink-0 flex items-center justify-center"
                         :class="msg.role === 'user' ? 'bg-blue-600' : 'bg-purple-100 dark:bg-purple-900'">
                        <i class="text-sm"
                           :class="msg.role === 'user' ? 'fas fa-user text-white' : 'fas fa-robot text-purple-500'"></i>
                    </div>
                    {{-- Bubble --}}
                    <div class="max-w-lg rounded-2xl px-4 py-3 text-sm"
                         :class="msg.role === 'user'
                             ? 'bg-blue-600 text-white rounded-tr-none'
                             : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-tl-none'">
                        <p x-html="formatMessage(msg.content)" class="whitespace-pre-wrap leading-relaxed"></p>
                        <p class="text-xs mt-1.5 opacity-60" x-text="msg.time"></p>
                    </div>
                </div>
            </template>

            {{-- Typing indicator --}}
            <div x-show="loading" x-cloak class="flex items-start gap-3">
                <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900 rounded-full flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-robot text-purple-500 text-sm"></i>
                </div>
                <div class="bg-gray-100 dark:bg-gray-700 rounded-2xl rounded-tl-none px-4 py-3">
                    <div class="flex gap-1">
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0ms"></div>
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 150ms"></div>
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 300ms"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Input --}}
        <div class="p-4 border-t border-gray-100 dark:border-gray-700">
            <div class="flex gap-3">
                <input x-model="input" type="text"
                       placeholder="Ask about books, get recommendations, discuss media..."
                       class="form-input flex-1"
                       @keydown.enter.prevent="send()"
                       :disabled="loading">
                <button @click="send()" :disabled="loading || !input.trim()"
                        class="btn-primary px-5 disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="fas fa-paper-plane" :class="{ 'fa-spin': loading }"></i>
                </button>
                <button @click="clearChat()" class="btn-secondary px-3 text-sm" title="Clear chat">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </div>
            <p class="text-xs text-gray-400 mt-2 text-center">
                AI responses are generated by GPT and may not always be accurate. Use as a guide only.
            </p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function aiChat() {
    return {
        messages: [],
        input: '',
        loading: false,
        context: 'library',
        messageId: 0,

        suggestions: [
            'Recommend mystery novels',
            'Books similar to Harry Potter',
            'Best sci-fi movies 2023',
            'Classic literature for beginners',
        ],

        async send() {
            if (!this.input.trim() || this.loading) return;

            const text = this.input.trim();
            this.input = '';

            this.messages.push({
                id: ++this.messageId,
                role: 'user',
                content: text,
                time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
            });

            this.$nextTick(() => this.scrollToBottom());
            this.loading = true;

            try {
                const history = this.messages.map(m => ({ role: m.role, content: m.content }));
                const res = await fetch('{{ route("ai.chat.post") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ messages: history, context: this.context })
                });

                const data = await res.json();

                if (data.error) {
                    this.messages.push({ id: ++this.messageId, role: 'assistant', content: '⚠️ ' + data.error, time: '' });
                } else {
                    this.messages.push({
                        id: ++this.messageId,
                        role: 'assistant',
                        content: data.message,
                        time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
                    });
                }
            } catch(e) {
                this.messages.push({ id: ++this.messageId, role: 'assistant', content: '⚠️ Connection error. Please try again.', time: '' });
            }

            this.loading = false;
            this.$nextTick(() => this.scrollToBottom());
        },

        sendSuggestion(text) {
            this.input = text;
            this.send();
        },

        clearChat() {
            this.messages = [];
        },

        scrollToBottom() {
            const el = this.$refs.messages;
            el.scrollTop = el.scrollHeight;
        },

        formatMessage(text) {
            // Basic markdown-like formatting
            return text
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/\*(.*?)\*/g, '<em>$1</em>')
                .replace(/\n/g, '<br>');
        }
    }
}
</script>
@endpush
