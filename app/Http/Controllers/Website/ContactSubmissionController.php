<?php

declare(strict_types=1);

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Http\Requests\Website\StoreContactSubmissionRequest;
use App\Jobs\Notifications\SendWhatsAppTemplateNotification;
use App\Models\ContactSubmission;
use App\Services\Notifications\WhatsAppService;
use Illuminate\Http\JsonResponse;

class ContactSubmissionController extends Controller
{
    public function __construct(
        private readonly WhatsAppService $whatsApp,
    ) {}

    public function store(StoreContactSubmissionRequest $request): JsonResponse
    {
        $submission = ContactSubmission::query()->create([
            'type' => ContactSubmission::TYPE_MESSAGE,
            'name' => (string) $request->validated('name'),
            'email' => (string) $request->validated('email'),
            'phone' => $request->filled('phone') ? (string) $request->validated('phone') : null,
            'message' => (string) $request->validated('message'),
            'locale' => $request->filled('locale') ? (string) $request->validated('locale') : null,
            'form_name' => $request->filled('form_name') ? (string) $request->validated('form_name') : null,
            'page_slug' => $request->filled('page_slug') ? (string) $request->validated('page_slug') : null,
            'page_url' => $request->filled('page_url') ? (string) $request->validated('page_url') : null,
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        SendWhatsAppTemplateNotification::dispatch(
            $this->whatsApp->templateName('contact_message'),
            [
                (string) $submission->name,
                (string) ($submission->email ?? '—'),
                (string) ($submission->phone ?? '—'),
                mb_substr((string) $submission->message, 0, 600),
                (string) ($submission->page_url ?? $submission->page_slug ?? '—'),
            ],
        );

        return response()->json([
            'success' => true,
            'message' => __('Message sent successfully.'),
        ], 201);
    }
}
