<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactSubmission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactSubmissionController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));
        $type = trim((string) $request->query('type', ContactSubmission::TYPE_MESSAGE));

        if (! in_array($type, [ContactSubmission::TYPE_MESSAGE, ContactSubmission::TYPE_CALL_REQUEST], true)) {
            $type = ContactSubmission::TYPE_MESSAGE;
        }

        $query = ContactSubmission::query()
            ->where('type', $type)
            ->when($search !== '', function ($builder) use ($search): void {
                $builder->where(function ($inner) use ($search): void {
                    $inner
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.$search.'%')
                        ->orWhere('message', 'like', '%'.$search.'%')
                        ->orWhere('page_slug', 'like', '%'.$search.'%')
                        ->orWhere('page_url', 'like', '%'.$search.'%');
                });
            })
            ->when($status === 'read', fn ($builder) => $builder->where('is_read', true))
            ->when($status === 'unread', fn ($builder) => $builder->where('is_read', false));

        $submissions = $query
            ->orderBy('is_read')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $counts = [
            ContactSubmission::TYPE_MESSAGE => [
                'total' => ContactSubmission::query()->where('type', ContactSubmission::TYPE_MESSAGE)->count(),
                'unread' => ContactSubmission::query()->where('type', ContactSubmission::TYPE_MESSAGE)->where('is_read', false)->count(),
            ],
            ContactSubmission::TYPE_CALL_REQUEST => [
                'total' => ContactSubmission::query()->where('type', ContactSubmission::TYPE_CALL_REQUEST)->count(),
                'unread' => ContactSubmission::query()->where('type', ContactSubmission::TYPE_CALL_REQUEST)->where('is_read', false)->count(),
            ],
        ];

        return view('admin.contact-submissions.index', [
            'submissions' => $submissions,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'type' => $type,
            ],
            'activeType' => $type,
            'statusOptions' => ['unread', 'read'],
            'typeCounts' => $counts,
            'unreadCount' => $counts[$type]['unread'],
            'totalCount' => $counts[$type]['total'],
        ]);
    }

    public function show(ContactSubmission $contactSubmission): View
    {
        if (! $contactSubmission->is_read) {
            $contactSubmission->forceFill([
                'is_read' => true,
                'read_at' => now(),
            ])->save();
        }

        return view('admin.contact-submissions.show', [
            'submission' => $contactSubmission->fresh(),
        ]);
    }

    public function markRead(ContactSubmission $contactSubmission): RedirectResponse
    {
        $contactSubmission->forceFill([
            'is_read' => true,
            'read_at' => now(),
        ])->save();

        return back()->with('success', __('Message marked as read.'));
    }

    public function markUnread(ContactSubmission $contactSubmission): RedirectResponse
    {
        $contactSubmission->forceFill([
            'is_read' => false,
            'read_at' => null,
        ])->save();

        return back()->with('success', __('Message marked as unread.'));
    }

    public function destroy(ContactSubmission $contactSubmission): RedirectResponse
    {
        $type = $contactSubmission->type;
        $contactSubmission->delete();

        return redirect()
            ->route('admin.contact-submissions.index', ['type' => $type])
            ->with('success', __('Submission deleted.'));
    }
}
