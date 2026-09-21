<?php

declare(strict_types=1);

namespace App\Jobs\Notifications;

use App\Services\Notifications\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendWhatsAppTemplateNotification implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    /**
     * @param  list<string>  $parameters  Values that replace {{1}}, {{2}}, ... in the template body.
     */
    public function __construct(
        public readonly string $templateName,
        public readonly array $parameters = [],
        public readonly ?string $recipient = null,
        public readonly ?string $language = null,
    ) {}

    public function handle(WhatsAppService $service): void
    {
        if (! $service->isEnabled()) {
            return;
        }

        $service->sendTemplate(
            $this->templateName,
            $this->parameters,
            $this->recipient,
            $this->language,
        );
    }
}
