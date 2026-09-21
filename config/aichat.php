<?php

return [

    /*
    |--------------------------------------------------------------------------
    | პროვაიდერები
    |--------------------------------------------------------------------------
    | free_chain — უფასო პროვაიდერების რიგი (პირველი მუშაობს default-ად,
    | ჩავარდნისას გადადის შემდეგზე). paid — ფასიანი escalation მოდელი.
    */

    'free_chain' => ['gemini', 'openrouter'],
    'paid' => 'openrouter_paid',

    'providers' => [

        'groq' => [
            'api_key' => env('GROQ_API_KEY'),
            'model' => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
            'url' => 'https://api.groq.com/openai/v1/chat/completions',
        ],

        'gemini' => [
            'api_key' => env('GEMINI_API_KEY'),
            'model' => env('GEMINI_MODEL', 'gemini-2.0-flash'),
            'url' => 'https://generativelanguage.googleapis.com/v1beta/models',
        ],

        // OpenRouter — უფასო რგოლი free chain-ში (:free მოდელი)
        'openrouter' => [
            'api_key' => env('OPENROUTER_API_KEY'),
            'model' => env('AICHAT_OPENROUTER_MODEL', 'meta-llama/llama-3.3-70b-instruct:free'),
            'url' => 'https://openrouter.ai/api/v1/chat/completions',
        ],

        // OpenRouter — ფასიანი escalation/fallback (Claude Haiku OpenRouter-ით,
        // ცალკე ANTHROPIC_API_KEY აღარ არის საჭირო)
        'openrouter_paid' => [
            'api_key' => env('OPENROUTER_API_KEY'),
            'model' => env('AICHAT_OPENROUTER_PAID_MODEL', 'anthropic/claude-haiku-4.5'),
            'url' => 'https://openrouter.ai/api/v1/chat/completions',
        ],

        // პირდაპირი Anthropic API — ალტერნატივა: 'paid' => 'claude'
        'claude' => [
            'api_key' => env('ANTHROPIC_API_KEY'),
            'model' => env('CLAUDE_MODEL', 'claude-haiku-4-5'),
            'url' => 'https://api.anthropic.com/v1/messages',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Embeddings (Gemini — უფასო, მრავალენოვანი)
    |--------------------------------------------------------------------------
    */

    'embeddings' => [
        'model' => env('AICHAT_EMBED_MODEL', 'gemini-embedding-001'),
        'dimensions' => 768,
    ],

    /*
    |--------------------------------------------------------------------------
    | ჰიბრიდული routing-ის წესები
    |--------------------------------------------------------------------------
    | როდის გადავიდეთ პირდაპირ ფასიან Claude-ზე:
    |  - escalate_message_length: კითხვა ამ სიგრძეზე გრძელია (სიმბოლო)
    |  - escalate_history_turns:  საუბარი ამდენ სვლაზე ღრმაა
    |  - escalate_keywords:       კითხვა შეიცავს "რთულ" საკვანძო სიტყვებს
    |  - user-მა დააჭირა "უკეთესი პასუხი" ღილაკს (force_escalate)
    |  - უფასო ჯაჭვი ჩავარდა (429/5xx) — ავტომატური fallback
    */

    'routing' => [
        'escalate_message_length' => 350,
        'escalate_history_turns' => 6,
        'escalate_keywords' => [
            'შეადარე', 'შედარება', 'რომელი ჯობია', 'ურჩიე', 'რჩევა',
            'დამიკონფიგურირე', 'გამოთვალე', 'დეტალურად', 'ნაბიჯ-ნაბიჯ',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Retrieval
    |--------------------------------------------------------------------------
    */

    'retrieval' => [
        'top_k' => 6,     // რამდენი chunk მიეწოდოს მოდელს
        'min_score' => 0.45,  // cosine similarity-ის მინიმალური ზღვარი
        'chunk_size' => 900,   // სიმბოლო ერთ chunk-ში
        'chunk_overlap' => 150,
    ],

    /*
    |--------------------------------------------------------------------------
    | ინდექსაციის წყაროები
    |--------------------------------------------------------------------------
    | ამ CMS-ში ტექსტი per-locale translation ცხრილებშია, ამიტომ წყაროებად
    | translation მოდელები გამოიყენება (თითო ლოკალი = თითო დოკუმენტი).
    | fields / meta / title / url მხარს უჭერს dot-notation-ს მშობელ
    | relation-ზე გადასასვლელად (მაგ. product.price). where_has ფილტრავს
    | მშობლის ველებით (published/is_active). ბლოკური კონტენტი (data JSON)
    | ავტომატურად იშლება ტექსტად ინდექსაციისას.
    | url — closure ვერ ჩაიწერება config-ში, ამიტომ pattern-ია: {id}, {slug}.
    */

    'sources' => [

        'product' => [
            'model' => \App\Models\ProductTranslation::class,
            'scope' => null,
            'where_has' => ['product' => ['published' => true, 'is_active' => true]],
            'title' => 'title',
            'fields' => ['title', 'excerpt', 'content'],
            'meta' => ['product.category', 'product.price', 'product.on_sale', 'product.sale_price', 'product.brand', 'slug'],
            'url' => '/product/{slug}',
            'label' => 'პროდუქტი',
        ],

        'product_block' => [
            'model' => \App\Models\ProductContentBlock::class,
            'scope' => null,
            'where_has' => ['translation.product' => ['published' => true, 'is_active' => true]],
            'title' => 'translation.title',
            'fields' => ['data'],
            'meta' => ['translation.slug'],
            'url' => '/product/{translation.slug}',
            'label' => 'პროდუქტის აღწერა',
        ],

        'page' => [
            'model' => \App\Models\PageTranslation::class,
            'scope' => null,
            'where_has' => ['page' => ['published' => true]],
            'title' => 'title',
            'fields' => ['title', 'subtitle', 'excerpt', 'content', 'description'],
            'meta' => ['slug'],
            'url' => '/{slug}',
            'label' => 'გვერდი',
        ],

        'page_block' => [
            'model' => \App\Models\PageContentBlock::class,
            'scope' => null,
            'where_has' => ['translation.page' => ['published' => true]],
            'title' => 'translation.title',
            'fields' => ['data'],
            'meta' => ['translation.slug'],
            'url' => '/{translation.slug}',
            'label' => 'გვერდის კონტენტი',
        ],

        'post' => [
            'model' => \App\Models\PostTranslation::class,
            'scope' => null,
            'where_has' => ['post' => ['published' => true]],
            'title' => 'title',
            'fields' => ['title', 'excerpt', 'content'],
            'meta' => ['post.category', 'slug'],
            'url' => '/blog/{slug}',
            'label' => 'სტატია',
        ],

        'post_block' => [
            'model' => \App\Models\PostContentBlock::class,
            'scope' => null,
            'where_has' => ['translation.post' => ['published' => true]],
            'title' => 'translation.title',
            'fields' => ['data'],
            'meta' => ['translation.slug'],
            'url' => '/blog/{translation.slug}',
            'label' => 'სტატიის კონტენტი',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | სისტემური პრომპტი
    |--------------------------------------------------------------------------
    */

    'system_prompt' => env('AICHAT_SYSTEM_PROMPT',
        'შენ ხარ ამ ვებსაიტის დამხმარე ასისტენტი. უპასუხე მხოლოდ ქართულად, '.
        'მოკლედ და ზუსტად. გამოიყენე მხოლოდ მოწოდებული კონტექსტი — პროდუქტების '.
        'ფასები, აღწერები და საიტის ინფორმაცია. თუ პასუხი კონტექსტში არ არის, '.
        'გულახდილად თქვი, რომ ეს ინფორმაცია არ გაქვს და შესთავაზე დაკავშირება. '.
        'არასოდეს გამოიგონო ფასი ან მახასიათებელი. როცა პროდუქტს ახსენებ, '.
        'მიუთითე მისი ფასი თუ ცნობილია.'
    ),

    'voice' => [
        // Groq Whisper — უფასო tier-ით
        'stt_model' => env('AICHAT_STT_MODEL', 'whisper-large-v3'),
        'stt_url' => 'https://api.groq.com/openai/v1/audio/transcriptions',
        'language' => 'ka',
    ],
];
